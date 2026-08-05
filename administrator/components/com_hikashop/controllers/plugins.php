<?php
/**
 * @package	HikaShop
 * @version	6.5.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2026 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or defined('ABSPATH') or die('Restricted access');
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?><?php
class PluginsController extends hikashopController {
	var $type = 'plugins';
	var $listing = true;
	var $toggle = array(
		'payment_published' => 'payment_id',
		'shipping_published' => 'shipping_id',
		'plugin_published' => 'plugin_id',
	);

	function __construct($config = array()){
		parent::__construct($config);

		$this->display[]='selectimages';
		$this->display[]='uploadimage';
		$this->display[]='selectnew';
		$this->display[]='export';
		$this->modify_views[]='edit_translation';
		$this->modify_views[] = 'unpublish';
		$this->modify_views[] = 'publish';
		$this->modify[]='save_translation';
		$this->modify[]='saveimage';
		$this->modify[]='trigger';
		$this->modify[]='cancel';
		$this->modify[]='copy';
		$this->modify[]='generatecopies';
		$this->modify[]='importprocess';
		$this->display[]='generate';
		$this->display[]='import';
		$this->display[]='step2import';

		if(defined('HIKASHOP_WORDPRESS'))
			$this->display[] = 'install';
	}

	function _getToggle() {
		$this->type = hikaInput::get()->getCmd('plugin_type');
		if($this->type == 'plugin') {
			$this->toggle = array('plugin_published' => 'plugin_id');
		} else if($this->type == 'payment') {
			$this->toggle = array('payment_published' => 'payment_id');
		} else {
			$this->toggle = array('shipping_published' => 'shipping_id');
		}
	}

	function publish(){
		$this->_getToggle();
		return parent::publish();
	}

	function unpublish(){
		$this->_getToggle();
		return parent::unpublish();
	}

	function trigger(){
		$cid= hikaInput::get()->getInt('cid', 0);
		$function = hikaInput::get()->getString('function', '');
		if(empty($cid) || empty($function)){
			return false;
		}
		$pluginsClass = hikashop_get('class.plugins');
		$plugin = $pluginsClass->get($cid);
		if(empty($plugin)){
			return false;
		}
		$plugin = hikashop_import($plugin->folder, $plugin->element);
		if(method_exists($plugin, $function))
			return $plugin->$function();
		return false;
	}

	function copy(){
		$plugins = hikaInput::get()->get('cid', array(), 'array');
		$result = true;
		if(!empty($plugins)){
			$type = hikaInput::get()->getCMD('plugin_type');
			if(!in_array($type, array('payment','shipping'))){
				$this->listing();
				return false;
			}
			$pluginsClass = hikashop_get('class.'.$type);
			foreach($plugins as $plugin){
				$data = $pluginsClass->get($plugin);
				if($data){
					$key = $type.'_id';
					unset($data->$key);
					$name = $type .'_name';
					$data->$name = $data->$name.' ('.JText::_('HIKA_COPY').')';
					if(!$pluginsClass->save($data)){
						$result=false;
					}
				}
			}
		}
		if($result){
			$app = JFactory::getApplication();
			if(!HIKASHOP_J30)
				$app->enqueueMessage(JText::_( 'HIKASHOP_SUCC_SAVED' ), 'success');
			else
				$app->enqueueMessage(JText::_( 'HIKASHOP_SUCC_SAVED' ));
			return $this->listing();
		}
		return $this->listing();
	}

	function generatecopies() {
		$shipping_ids = hikaInput::get()->get('shipping_ids', array(), 'array');
		$shipping_id = hikaInput::get()->getInt('shipping_id');
		if(empty($shipping_ids) && $shipping_id) $shipping_ids = array($shipping_id);

		$rows = hikaInput::get()->get('rows', array(), 'array');

		if((empty($shipping_ids) || empty($rows)) && $json = json_decode(file_get_contents('php://input'), true)) {
			if(is_array($json)) {
				if(empty($shipping_ids)) {
					if(!empty($json['shipping_ids'])) $shipping_ids = $json['shipping_ids'];
					elseif(!empty($json['shipping_id'])) $shipping_ids = array($json['shipping_id']);
				}
				if(empty($rows) && !empty($json['rows'])) $rows = $json['rows'];
			}
		}

		$result = array('success' => 0, 'error' => 0);

		if (empty($shipping_ids)) {
			echo json_encode(array('status' => 'error', 'message' => JText::_('GENERATION_FAILED') . ': Missing Shipping IDs'));
			exit;
		}
		if (empty($rows)) {
			echo json_encode(array('status' => 'error', 'message' => JText::_('GENERATION_FAILED') . ': Missing Rows data ' . print_r($_POST, true)));
			exit;
		}

		$shippingClass = hikashop_get('class.shipping');

		foreach($shipping_ids as $sid) {
			$source = $shippingClass->get((int)$sid);
			if (!$source) continue;

			foreach ($rows as $row) {
				$copy = clone $source;
				unset($copy->shipping_id);

				if (isset($row['price'])) $copy->shipping_price = $row['price'];

				if (is_string($copy->shipping_params)) {
					$copy->shipping_params = unserialize($copy->shipping_params);
				}

				if (!is_object($copy->shipping_params)) {
					$obj = new stdClass();
					if(is_array($copy->shipping_params)) {
						foreach($copy->shipping_params as $k => $v) $obj->$k = $v;
					}
					$copy->shipping_params = $obj;
				}

				if (isset($row['percentage'])) $copy->shipping_params->shipping_percentage = $row['percentage'];
				if (isset($row['formula'])) $copy->shipping_params->shipping_formula = $row['formula'];

			$type = isset($row['type']) ? $row['type'] : 'zone';
			$filter = JFilterInput::getInstance();

			if ($type == 'zone') {
				if (!empty($row['zone_namekey'])) {
					$copy->shipping_zone_namekey = $filter->clean($row['zone_namekey'], 'cmd');
				}
			} elseif ($type == 'weight') {
				if (isset($row['min_weight']) && $row['min_weight'] !== '') $copy->shipping_params->shipping_min_weight = hikashop_toFloat($row['min_weight']);
				if (isset($row['max_weight']) && $row['max_weight'] !== '') $copy->shipping_params->shipping_max_weight = hikashop_toFloat($row['max_weight']);
				if (isset($row['weight_unit'])) $copy->shipping_params->shipping_weight_unit = $filter->clean($row['weight_unit'], 'cmd');
			} elseif ($type == 'volume') {
				if (isset($row['min_volume']) && $row['min_volume'] !== '') $copy->shipping_params->shipping_min_volume = hikashop_toFloat($row['min_volume']);
				if (isset($row['max_volume']) && $row['max_volume'] !== '') $copy->shipping_params->shipping_max_volume = hikashop_toFloat($row['max_volume']);
				if (isset($row['volume_unit'])) $copy->shipping_params->shipping_size_unit = $filter->clean($row['volume_unit'], 'cmd');
			} elseif ($type == 'postcoderegex') {
				if (isset($row['zip_regex']) && $row['zip_regex'] !== '') $copy->shipping_params->shipping_zip_regex = $filter->clean($row['zip_regex'], 'string');
			} elseif ($type == 'warehouse') {
				if (isset($row['warehouse_filter']) && $row['warehouse_filter'] !== '') $copy->shipping_params->shipping_warehouse_filter = $row['warehouse_filter'];
			} elseif ($type == 'quantity') {
				if (isset($row['min_quantity']) && $row['min_quantity'] !== '') $copy->shipping_params->shipping_min_quantity = (int)$row['min_quantity'];
				if (isset($row['max_quantity']) && $row['max_quantity'] !== '') $copy->shipping_params->shipping_max_quantity = (int)$row['max_quantity'];
			} elseif ($type == 'price') {
				if (isset($row['min_price']) && $row['min_price'] !== '') $copy->shipping_params->shipping_min_price = hikashop_toFloat($row['min_price']);
				if (isset($row['max_price']) && $row['max_price'] !== '') $copy->shipping_params->shipping_max_price = hikashop_toFloat($row['max_price']);
				if (isset($row['price_use_tax'])) $copy->shipping_params->shipping_price_use_tax = (int)$row['price_use_tax'];
			} elseif ($type == 'currency') {
				if (isset($row['currency'])) $copy->shipping_currency = $filter->clean($row['currency'], 'string');
			}

			$copy->shipping_params = serialize($copy->shipping_params);


			if ($shippingClass->save($copy)) {
				$result['success']++;
			} else {
				$result['error']++;
			}
		}
		} // Close outer foreach($shipping_ids)

		echo json_encode(array('status' => 'success', 'data' => $result));
		exit;
	}

	function generate() {
		$cid = hikaInput::get()->get('cid', array(), 'array');
		if(!empty($cid) && is_array($cid)) {
			$id = (int)reset($cid);
			hikaInput::get()->set('shipping_id', $id);
		}

		hikaInput::get()->set('layout', 'generate');
		parent::display();
	}

	function export() {
		hikaInput::get()->set('layout', 'export');
		parent::display();
	}

	function import() {
		hikaInput::get()->set('layout', 'import');
		$plugin_type = hikaInput::get()->getCmd('plugin_type', 'shipping');
		$view = $this->getView('plugins', 'html');
		$view->assignRef('plugin_type', $plugin_type);
		$csv_content = hikaInput::get()->get('csv_content', '', 'raw');
		if(!empty($csv_content)) {
			$view->assignRef('csv_content', $csv_content);
		}
		parent::display();
	}

	function step2import() {
		$csv_content = hikaInput::get()->get('csv_content', '', 'raw');
		if(empty($csv_content) && !empty($_FILES['csv_file']['tmp_name'])) {
			$csv_content = file_get_contents($_FILES['csv_file']['tmp_name']);
		}

		$plugin_type = hikaInput::get()->getCmd('plugin_type', 'shipping');

		if(empty($csv_content)) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('NO_CSV_CONTENT_FOUND'), 'error');
			return $this->import();
		}

		$parsed = $this->parseCSV($csv_content);

		if(empty($parsed['header'])) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('INVALID_CSV_FORMAT'), 'error');
			return $this->import();
		}

		hikaInput::get()->set('layout', 'import_step2');
		$view = $this->getView('plugins', 'html');
		$view->assignRef('csv_header', $parsed['header']);
		$view->assignRef('csv_data', $parsed['data']);
		$view->assignRef('csv_content', $csv_content);
		$view->assignRef('plugin_type', $plugin_type);

		return parent::display();
	}

	function importprocess() {
		$csv_content = hikaInput::get()->get('csv_content', '', 'raw');
		$mapping = hikaInput::get()->get('mapping', array(), 'array');
		$type = hikaInput::get()->getCmd('plugin_type', 'shipping');

		if(empty($csv_content) || empty($mapping)) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('IMPORT_FAILED_MISSING_DATA'), 'error');
			return $this->import();
		}

		$classType = 'class.'.$type;
		$class = hikashop_get($classType);
		$parsed = $this->parseCSV($csv_content);

		$imported = 0;
		$errors = array();

		foreach($parsed['data'] as $row) {
			$element = new stdClass();

			$hasData = false;
			foreach($mapping as $csvCol => $hkCol) {
				if($hkCol == 'ignore' || !isset($row[$csvCol])) continue;
				$element->$hkCol = $row[$csvCol];
				$hasData = true;
			}

			if(!$hasData) continue;

            $idCol = $type.'_id';
            if(isset($element->$idCol) && empty($element->$idCol)) {
                unset($element->$idCol);
            }

			if($class->save($element)) {
				$imported++;
			} else {
				$errors[] = JText::_('ERROR_IMPORTING_ROW');
			}
		}

		$msg = JText::sprintf('X_ITEMS_IMPORTED', $imported);
		if(!empty($errors)) {
			$msg .= ' ' . JText::sprintf('X_ERRORS_OCCURRED', count($errors));
		}

		$url = 'index.php?option=com_hikashop&ctrl=plugins&plugin_type='.$type;
		hikashop_display($msg);
		echo '<script type="text/javascript">'."\n";
		echo 'window.parent.location.href="'.$url.'";'."\n";
		echo 'window.parent.hikashop.closeBox();'."\n";
		echo '</script>'."\n";
		$app = JFactory::getApplication();
		$app->close();
	}

	public function parseCSV($content) {
		$bom = pack('H*', 'EFBBBF');
		$content = preg_replace("/^$bom/", '', $content);

		$content = str_replace(array("\r\n", "\r"), "\n", $content);
		$lines = explode("\n", $content);

		$data = array();
		$header = array();
		$separator = ',';
		$listSeparators = array(',', ';', "\t");

		foreach($lines as $line) {
			$line = trim($line);
			if(empty($line)) continue;

			if(empty($header)) {
				foreach($listSeparators as $sep) {
					if(strpos($line, $sep) !== false) {
						$separator = $sep;
						break;
					}
				}
				$header = str_getcsv($line, $separator, '"', '\\');
				foreach($header as $k => $v) {
					$header[$k] = trim($v, " \t\n\r\0\x0B\"");
				}
				continue;
			}

			$row = str_getcsv($line, $separator, '"', '\\');
			if(count($row) == count($header)) {
				foreach($row as $k => $v) {
					$row[$k] = trim($v, " \t\n\r\0\x0B\"");
				}
				$data[] = array_combine($header, $row);
			}
		}

		return array('header' => $header, 'data' => $data);
	}

	function edit_translation(){
		hikaInput::get()->set( 'layout', 'edit_translation');
		return parent::display();
	}

	function save_translation(){
		$cid= hikaInput::get()->getInt('cid');
		$type = hikaInput::get()->getString('type');
		$id_field = $type.'_id';
		$pluginClass = hikashop_get('class.'.$type);
		$element = $pluginClass->getRaw($cid);
		if(!empty($element->$id_field)){
			$type_field = $type . '_type';
			if(!empty($element->$type_field)) {
				if($type == 'plugin')
					$plugin = hikashop_import('hikashop', $element->$type_field);
				else
					$plugin = hikashop_import('hikashop' . $type, $element->$type_field);
				if(!empty($plugin->pluginConfig)) {
					$params_name = $type.'_params';
					$params = null;
					if(!empty($element->$params_name)) {
						$params = $element->$params_name;
						if(is_string($params))
							$params = hikashop_unserialize($params);
					}
					$translatableTypes = array('input', 'textarea', 'big-textarea', 'wysiwyg');
					foreach($plugin->pluginConfig as $key => $config) {
						if(empty($config['translatable']))
							continue;
						if(!isset($config[1]) || !in_array($config[1], $translatableTypes))
							continue;
						$prop = $params_name . '_' . $key;
						$element->$prop = '';
						if(!empty($params) && isset($params->$key))
							$element->$prop = $params->$key;
					}
				}
			}
			$translationHelper = hikashop_get('helper.translation');
			$translationHelper->getTranslations($element);
			$translationHelper->handleTranslations($type,$element->$id_field,$element);
		}
		$document= JFactory::getDocument();
		$document->addScriptDeclaration('window.top.hikashop.closeBox();');
	}

	function orderdown(){
		$this->setOptions();
		return parent::orderdown();
	}

	function orderup(){
		$this->setOptions();
		return parent::orderup();
	}

	function saveorder(){
		$this->setOptions();
		$this->listing = false;
		hikaInput::get()->set('subtask', '');
		return parent::saveorder();
	}

	function cancel(){
		$type = hikaInput::get()->getVar( 'plugin_type','shipping').'_edit';
		if(hikaInput::get()->getVar('subtask', '') == $type) {
			hikaInput::get()->set('subtask', '');
			return $this->edit();
		}
		return $this->listing();
	}

	function add(){
		hikaInput::get()->set('layout', 'selectnew');
		return parent::display();
	}

	function install() {
		if(!defined('HIKASHOP_WORDPRESS'))
			return false;

		if(!empty($_FILES['hikashop_plugin_zip']['name'])) {
			require_once(HIKASHOP_WP_PLUGIN_DIR . 'plugin-installer.php');
			$plugin_type = hikaInput::get()->getCmd('plugin_type', 'plugin');
			$result = hikashop_wp_process_plugin_upload($plugin_type);
			if(!empty($result) && is_array($result)) {
				$folder = $result['folder'];
				$element = $result['element'];
				$ext_type = isset($result['ext_type']) ? $result['ext_type'] : 'plugin';
				if($ext_type === 'module') {
					$this->setRedirect(hikashop_completeLink('plugins&plugin_type=plugin', false, true));
				} elseif($folder === 'hikashoppayment' || $folder === 'hikashopshipping') {
					$this->setRedirect(hikashop_completeLink('plugins&task=edit&name=' . $element . '&plugin_type=' . str_replace('hikashop', '', $folder) . '&subtask=edit', false, true));
				} else {
					$this->setRedirect(hikashop_completeLink('plugins&task=edit&name=' . $element . '&plugin_type=plugin&subtask=edit', false, true));
				}
			} else {
				$this->setRedirect(hikashop_completeLink('plugins&plugin_type=' . $plugin_type, false, true));
			}
			return;
		}

		hikaInput::get()->set('layout', 'install');
		return parent::display();
	}

	function listing(){
		hikaInput::get()->set('layout', 'listing');
		return parent::display();
	}

	function selectimages(){
		hikaInput::get()->set( 'layout', 'selectimages'  );
		return parent::display();
	}

	function uploadimage(){
		hikaInput::get()->set('layout', 'uploadimage');
		return parent::display();
	}

	function saveimage(){
		if(!JSession::checkToken('request')) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('INVALID_TOKEN'), 'error');
			return false;
		}

		$type = hikaInput::get()->getCmd('type', 'shipping');
		if(!in_array($type, array('payment', 'shipping')))
			$type = 'shipping';

		$app = JFactory::getApplication();

		if(empty($_FILES['plugin_image']) || empty($_FILES['plugin_image']['name']) || $_FILES['plugin_image']['error'] != 0) {
			$app->enqueueMessage(JText::_('ERROR_UPLOADING_FILE'), 'error');
			hikaInput::get()->set('layout', 'uploadimage');
			return parent::display();
		}

		$file = $_FILES['plugin_image'];

		$config = hikashop_config();
		$allowed = $config->get('allowedimages', 'gif,jpg,jpeg,png,svg');
		$file_path = JFile::makeSafe($file['name']);

		if(!preg_match('#\.('.str_replace(array(',','.'), array('|','\.'), $allowed).')$#Ui', $file_path, $extension) || preg_match('#\.(php.?|.?htm.?|pl|py|jsp|asp|sh|cgi)$#Ui', $file_path)) {
			$app->enqueueMessage(JText::sprintf('ACCEPTED_TYPE', substr($file_path, strrpos($file_path, '.') + 1), $allowed), 'error');
			hikaInput::get()->set('layout', 'uploadimage');
			return parent::display();
		}

		$file_path = str_replace(array('.',' '), '_', substr($file_path, 0, strpos($file_path, $extension[0]))) . $extension[0];

		$dest_dir = HIKASHOP_MEDIA . 'images' . DS . $type . DS;

		if(is_file($dest_dir . $file_path)) {
			$pos = strrpos($file_path, '.');
			$file_path = substr($file_path, 0, $pos) . '_' . rand() . '.' . substr($file_path, $pos + 1);
		}

		if(!is_uploaded_file($file['tmp_name'])) {
			$app->enqueueMessage(JText::_('ERROR_UPLOADING_FILE'), 'error');
			hikaInput::get()->set('layout', 'uploadimage');
			return parent::display();
		}

		jimport('joomla.filesystem.file');
		if(!JFile::upload($file['tmp_name'], $dest_dir . $file_path)) {
			$app->enqueueMessage(JText::_('ERROR_UPLOADING_FILE'), 'error');
			hikaInput::get()->set('layout', 'uploadimage');
			return parent::display();
		}

		$this->setRedirect(hikashop_completeLink('plugins&task=uploadimage&type='.$type.'&uploaded='.urlencode($file_path), false, true));
	}

	function setOptions(){
		$app = JFactory::getApplication();
		$this->listing = false;
		$this->groupVal = $app->getUserStateFromRequest(HIKASHOP_COMPONENT.'.'.$this->type.'_plugin_type', $this->type.'_plugin_type', 'manual');
		$this->type = $app->getUserStateFromRequest(HIKASHOP_COMPONENT.'.plugin_type', 'plugin_type', 'shipping');
		$this->pkey = $this->type.'_id';
		$this->table = $this->type;
		$this->groupMap = ''; // No more group map
		$this->orderingMap = $this->type.'_ordering';
	}

	function save() {
		$status = $this->store();
		$subtask = hikaInput::get()->getVar('subtask');
		if(!empty($subtask)){
			hikaInput::get()->set('subtask','');
		}
		return $this->listing();
	}

	function store($new = false) {
		$this->plugin = hikaInput::get()->getCmd('name', 'manual');
		$this->plugin_type = hikaInput::get()->getCmd('plugin_type', 'shipping');
		if(!in_array($this->plugin_type,array('shipping','payment','plugin'))) {
			return false;
		}
		if($this->plugin_type == 'plugin')
			$data = hikashop_import('hikashop',$this->plugin);
		else
			$data = hikashop_import('hikashop'.$this->plugin_type, $this->plugin);

		if(!$data) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::sprintf('PLUGIN_FILES_NOT_FOUND', $this->plugin), 'error');
			return false;
		}

		$element = new stdClass();
		$id = hikashop_getCID($this->plugin_type.'_id');
		$formData = hikaInput::get()->get('data', array(), 'array');

		$params_name = $this->plugin_type.'_params';
		if(!empty($formData[$this->plugin_type])) {
			$plugin_id = $this->plugin_type.'_id';
			$element->$plugin_id = $id;
			if(in_array($this->plugin_type, array('payment', 'shipping'))) {
				$plugin_images = $this->plugin_type.'_images';
				$element->$plugin_images = '';
			}
			foreach($formData[$this->plugin_type] as $column => $value){
				hikashop_secureField($column);
				if(is_array($value)){
					if($column == $params_name) {
						$element->$params_name = new stdClass();
						foreach($formData[$this->plugin_type][$column] as $key=>$val){
							hikashop_secureField($key);
							if(in_array($key,array('shipping_percentage','shipping_min_price','shipping_max_price','shipping_min_weight','shipping_max_weight','shipping_min_volume','shipping_max_volume'))){
								$val = hikashop_toFloat($val);
							}
							if(is_array($val) || $key=='information'){
								$element->$params_name->$key = $val;
							}elseif($key =='shipping_override_address_text' && $formData[$this->plugin_type][$column]['shipping_override_address'] == '4'){
								$safeHtmlFilter = JFilterInput::getInstance(array(), array(), 1, 1);
								$element->$params_name->$key = $safeHtmlFilter->clean($val, 'string');
							}else{
								$element->$params_name->$key = strip_tags($val);
							}
						}
					} elseif(in_array($column, array('payment_shipping_methods', 'payment_currency', 'shipping_currency'))) {
						$element->$column = array();
						foreach($formData[$this->plugin_type][$column] as $key=>$val){
							$element->{$column}[(int)$key] = strip_tags($val);
						}
					} elseif(in_array($column, array('payment_images','shipping_images'))) {
						$element->$column = array();
						foreach($formData[$this->plugin_type][$column] as $key=>$val){
							$element->{$column}[(int)$key] = strip_tags($val);
						}
						$element->$column = implode(',', $element->$column);
					}
				}else{
					$element->$column = strip_tags($value);
				}
			}
			if($this->plugin_type=='payment') {
				if(!isset($element->payment_shipping_methods)) $element->payment_shipping_methods = array();
				if(!isset($element->payment_currency)) $element->payment_currency = array();
			}elseif($this->plugin_type=='shipping'){
				if(!isset($element->shipping_currency)) $element->shipping_currency = array();
			}

			$plugin_description = $this->plugin_type.'_description';
			$plugin_description_data = hikaInput::get()->getRaw($plugin_description, '');
			$element->$plugin_description = $plugin_description_data;
			$translationHelper = hikashop_get('helper.translation');
			$translationHelper->getTranslations($element);
		}
		$function = 'on'.ucfirst($this->plugin_type).'ConfigurationSave';
		if(method_exists($data,$function)){
			$data->$function($element);
		}

		if(!empty($element)) {
			$pluginClass = hikashop_get('class.'.$this->plugin_type);
			$status = $pluginClass->save($element);

			if(!$status) {
				hikaInput::get()->set('fail', $element);
			} else {
				if(!empty($data->pluginConfig)) {
					$params_name = $this->plugin_type.'_params';
					$params = null;
					if(!empty($element->$params_name)) {
						$params = $element->$params_name;
						if(is_string($params))
							$params = hikashop_unserialize($params);
					}
					$translatableTypes = array('input', 'textarea', 'big-textarea', 'wysiwyg');
					foreach($data->pluginConfig as $key => $config) {
						if(empty($config['translatable']))
							continue;
						if(!isset($config[1]) || !in_array($config[1], $translatableTypes))
							continue;
						$prop = $params_name . '_' . $key;
						$element->$prop = '';
						if(!empty($params) && isset($params->$key))
							$element->$prop = $params->$key;
					}
				}
				$translationHelper->handleTranslations($this->plugin_type, $status, $element);
				$app = JFactory::getApplication();
				if(!HIKASHOP_J30)
					$app->enqueueMessage(JText::_( 'HIKASHOP_SUCC_SAVED' ), 'success');
				else
					$app->enqueueMessage(JText::_( 'HIKASHOP_SUCC_SAVED' ));
				if(empty($id)) {
					hikaInput::get()->set($this->plugin_type.'_id',$status);
				}
			}
		}
	}

	function edit(){
		if(hikaInput::get()->getInt('fromjoomla')){
			$app = JFactory::getApplication();
			$context = 'com_plugins.edit.plugin';
			$id = hikashop_getCID('id');
			if($id){
				$values = (array) $app->getUserState($context . '.id');
				$index = array_search((int) $id, $values, true);
				if (is_int($index)){
					unset($values[$index]);
					$app->setUserState($context . '.id', $values);
				}
			}
		}
		return parent::edit();
	}
}
