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

class ImportController extends hikashopController
{
	var $type='import';
	var $helperImport;
	var $db;

	public function __construct() {
		parent::__construct();
		$this->db = JFactory::getDBO();
		$this->modify[] = 'import';
		$this->modify[] = 'previewcolumns';
		$this->modify[] = 'savemapping';
		$this->modify[] = 'deletemapping';
		$this->modify[] = 'listmappings';
		$this->modify[] = 'getmapping';
		$this->display[] = 'columnmatching';
		$this->registerDefaultTask('show');
		$this->importHelper = hikashop_get('helper.import');
	}

	public function import() {
		JSession::checkToken('request') || die('Invalid Token');

		$function = hikaInput::get()->getCmd('importfrom');
		$this->importHelper->addTemplate(hikaInput::get()->getInt('template_product',0));

		switch($function){
			case 'file':
				$this->_file();
				break;
			case 'textarea':
				$this->_textarea();
				break;
			case 'folder':
				if(hikashop_level(2)){
					$this->_folder();
				}else{
					$app = JFactory::getApplication();
					$app->enqueueMessage(JText::_('ONLY_FROM_HIKASHOP_BUSINESS'),'error');
				}
				break;
			case 'vm':
				$query = 'SHOW TABLES LIKE '.$this->db->Quote($this->db->getPrefix().substr(hikashop_table('virtuemart_products',false),3));
				$this->db->setQuery($query);
				$table = $this->db->loadResult();
				if (empty($table))
				{
					$query='SHOW TABLES LIKE '.$this->db->Quote($this->db->getPrefix().substr(hikashop_table('vm_product',false),3));
					$this->db->setQuery($query);
					$table = $this->db->loadResult();
					if (empty($table))
					{
						$app = JFactory::getApplication();
						$app->enqueueMessage('VirtueMart has not been found in the database','error');
					}
					else
					{
						$this->helperImport = hikashop_get('helper.import-vm1', $this);
						$this->_vm();
					}
				}
				else
				{
					$this->helperImport = hikashop_get('helper.import-vm2', $this);
					$this->_vm();
				}
				break;
			case 'mijo':
				$this->helperImport = hikashop_get('helper.import-mijo',$this);
				$query='SHOW TABLES LIKE '.$this->db->Quote($this->db->getPrefix().substr(hikashop_table('mijoshop_product',false),3));
				$this->db->setQuery($query);
				$table = $this->db->loadResult();
				if (empty($table))
				{
					$app = JFactory::getApplication();
					$app->enqueueMessage('Mijoshop has not been found in the database','error');
				}
				else
				{
					$this->_mijo();
				}
				break;
			case 'redshop':
				$this->helperImport = hikashop_get('helper.import-reds',$this);
				$query='SHOW TABLES LIKE '.$this->db->Quote($this->db->getPrefix().substr(hikashop_table('redshop_product',false),3));
				$this->db->setQuery($query);
				$table = $this->db->loadResult();
				if (empty($table))
				{
					$app = JFactory::getApplication();
					$app->enqueueMessage('Redshop has not been found in the database','error');
				}
				else
				{
					$this->_redshop();
				}
				break;
			case 'openc':
				$this->helperImport = hikashop_get('helper.import-openc',$this);
				$this->_opencart();
				break;
			case 'j2':
				$this->helperImport = hikashop_get('helper.import-j2',$this);
				$this->_j2store();
				break;
			case 'woo':
				$this->helperImport = hikashop_get('helper.import-woo',$this);
				if(defined('HIKASHOP_WORDPRESS') && !$this->helperImport->isWooCommerceDetected()) {
					$app = JFactory::getApplication();
					$app->enqueueMessage(JText::sprintf('HAS_NOT_BEEN_FOUND', 'WooCommerce'), 'error');
				} else {
					$this->_woocommerce();
				}
				break;
			case 'hk':
				$this->helperImport = hikashop_get('helper.import-hk',$this);
				$this->_hikashop();
				break;
			case 'ec':
				$this->helperImport = hikashop_get('helper.import-ec',$this);
				$this->_eccube();
				break;
			default:
				$plugin = hikashop_import('hikashop',$function);
				if($plugin)
					$plugin->onImportRun();
				break;
		}
		return $this->show();
	}

	function _textarea(){
		$content = hikaInput::get()->getRaw('textareaentries', '');

		$this->_applyColumnMapping('textarea');

		$this->importHelper->overwrite = hikaInput::get()->getInt('textarea_update_products');
		$this->importHelper->createCategories = hikaInput::get()->getInt('textarea_create_categories');
		$this->importHelper->force_published = hikaInput::get()->getInt('textarea_force_publish');
		$this->importHelper->update_product_quantity = hikaInput::get()->getInt('textarea_update_product_quantity');
		$this->importHelper->store_images_locally = hikaInput::get()->getInt('textarea_store_images_locally', 1);
		$this->importHelper->store_files_locally = hikaInput::get()->getInt('textarea_store_files_locally', 1);
		$this->importHelper->keep_other_variants = hikaInput::get()->getInt('keep_other_variants', 1);
		return $this->importHelper->handleContent($content);
	}

	function _folder(){
		$type = hikaInput::get()->getCmd('importfolderfrom');
		$delete = hikaInput::get()->getInt('delete_files_automatically');
		$uploadFolder = hikaInput::get()->getVar($type.'_folder','');
		return $this->importHelper->importFromFolder($type,$delete,$uploadFolder);
	}

	function _file(){
		$importFile =  hikaInput::get()->files->getVar('importfile', array(), 'array');
		if(@$importFile['error'] !== 0) {
			$app = JFactory::getApplication();
			$phpFileUploadErrors = array(
				0 => 'There is no error, the file uploaded with success',
				1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
				2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
				3 => 'The uploaded file was only partially uploaded',
				4 => 'No file was uploaded',
				6 => 'Missing a temporary folder',
				7 => 'Failed to write file to disk.',
				8 => 'A PHP extension stopped the file upload.',
			);
			if(isset($phpFileUploadErrors[$importFile['error']]))
				$app->enqueueMessage($phpFileUploadErrors[$importFile['error']], 'error');
			return false;
		}
		$this->importHelper->overwrite = hikaInput::get()->getInt('file_update_products');
		$this->importHelper->createCategories = hikaInput::get()->getInt('file_create_categories');
		$this->importHelper->force_published = hikaInput::get()->getInt('file_force_publish');
		$this->importHelper->update_product_quantity = hikaInput::get()->getInt('file_update_product_quantity');
		$this->importHelper->store_images_locally = hikaInput::get()->getInt('file_store_images_locally', 1);
		$this->importHelper->store_files_locally = hikaInput::get()->getInt('file_store_files_locally', 1);
		$this->importHelper->keep_other_variants = hikaInput::get()->getInt('keep_other_variants', 1);

		$this->_applyColumnMapping('file');

		return $this->importHelper->importFromFile($importFile);
	}


	function _vm() {
		return $this->helperImport->importFromVM();
	}

	function _mijo() {
		return $this->helperImport->importFromMijo();
	}

	function _redshop() {
		return $this->helperImport->importFromRedshop();
	}

	function _opencart() {
		return $this->helperImport->importFromOpenc();
	}

	function _j2store() {
		return $this->helperImport->importFromJ2();
	}

	function _woocommerce() {
		return $this->helperImport->importFromWoo();
	}

	function _hikashop() {
		return $this->helperImport->importFromHk();
	}

	function _eccube() {
		return $this->helperImport->importFromEc();
	}

	public function previewcolumns() {
		JSession::checkToken('request') || die('Invalid Token');

		$app = JFactory::getApplication();
		$content = '';
		$source = hikaInput::get()->getCmd('source', 'file');

		if($source === 'textarea') {
			$content = hikaInput::get()->getRaw('textareaentries', '');
		} else {
			$importFile = hikaInput::get()->files->getVar('importfile', array(), 'array');
			if(!empty($importFile['tmp_name']) && $importFile['error'] === 0) {
				$content = file_get_contents($importFile['tmp_name']);
			}
		}

		if(empty($content)) {
			echo json_encode(array('error' => JText::_('NO_DATA_TO_IMPORT')));
			$app->close();
			return;
		}

		$csvColumns = $this->importHelper->parseHeader($content);
		if($csvColumns === false) {
			echo json_encode(array('error' => JText::_('IMPORT_ERROR_FIELD')));
			$app->close();
			return;
		}

		$app->setUserState('com_hikashop.import.csv_columns', $csvColumns);

		echo json_encode(array('success' => true));
		$app->close();
	}

	public function columnmatching() {
		$this->display();
	}

	public function savemapping() {
		JSession::checkToken('request') || die('Invalid Token');

		$app = JFactory::getApplication();

		$name = hikaInput::get()->getString('mapping_name', '');
		$mapping = hikaInput::get()->getRaw('mapping', '');

		if(empty($name)) {
			echo json_encode(array('error' => JText::_('HIKA_PLEASE_FILL_FORM_PROPERLY')));
			$app->close();
			return;
		}

		$mappingData = json_decode($mapping, true);
		if(empty($mappingData) || !is_array($mappingData)) {
			echo json_encode(array('error' => JText::_('INVALID_DATA')));
			$app->close();
			return;
		}

		$key = 'import_mapping_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($name));

		$configData = array(
			'name' => $name,
			'mapping' => $mappingData,
			'created' => time()
		);

		$config = hikashop_config();
		$configClass = hikashop_get('class.config');
		$values = array($key => json_encode($configData));
		$configClass->save($values);

		echo json_encode(array('success' => true, 'key' => $key));
		$app->close();
	}

	public function deletemapping() {
		JSession::checkToken('request') || die('Invalid Token');

		$app = JFactory::getApplication();

		$key = hikaInput::get()->getString('mapping_key', '');

		if(empty($key) || strpos($key, 'import_mapping_') !== 0) {
			echo json_encode(array('error' => JText::_('INVALID_DATA')));
			$app->close();
			return;
		}

		$query = 'DELETE FROM ' . hikashop_table('config') . ' WHERE config_namekey = ' . $this->db->Quote($key);
		$this->db->setQuery($query);
		$this->db->execute();

		echo json_encode(array('success' => true));
		$app->close();
	}

	public function listmappings() {
		$app = JFactory::getApplication();

		$query = 'SELECT config_namekey, config_value FROM ' . hikashop_table('config') . 
				 ' WHERE config_namekey LIKE ' . $this->db->Quote('import_mapping_%');
		$this->db->setQuery($query);
		$results = $this->db->loadObjectList();

		$mappings = array();
		if(!empty($results)) {
			foreach($results as $row) {
				$data = json_decode($row->config_value, true);
				if(!empty($data)) {
					$mappings[] = array(
						'key' => $row->config_namekey,
						'name' => isset($data['name']) ? $data['name'] : $row->config_namekey,
						'created' => isset($data['created']) ? $data['created'] : 0
					);
				}
			}
		}

		echo json_encode(array('mappings' => $mappings));
		$app->close();
	}

	public function getmapping() {
		$app = JFactory::getApplication();

		$key = hikaInput::get()->getString('mapping_key', '');

		if(empty($key) || strpos($key, 'import_mapping_') !== 0) {
			echo json_encode(array('error' => JText::_('INVALID_DATA')));
			$app->close();
			return;
		}

		$config = hikashop_config();
		$value = $config->get($key, '');

		if(empty($value)) {
			echo json_encode(array('error' => JText::_('NOT_FOUND')));
			$app->close();
			return;
		}

		$data = json_decode($value, true);
		echo json_encode(array('mapping' => $data));
		$app->close();
	}

	protected function _applyColumnMapping($prefix) {
		$mappingOption = hikaInput::get()->getString($prefix . '_column_mapping', 'none');

		if($mappingOption === 'none') {
			return;
		}

		$mapping = array();

		if($mappingOption === 'inline') {
			$inlineMapping = hikaInput::get()->getRaw($prefix . '_mapping', '');
			if(!empty($inlineMapping)) {
				$mapping = json_decode($inlineMapping, true);
			}
		} elseif(strpos($mappingOption, 'import_mapping_') === 0) {
			$config = hikashop_config();
			$value = $config->get($mappingOption, '');
			if(!empty($value)) {
				$data = json_decode($value, true);
				if(!empty($data['mapping'])) {
					$mapping = $data['mapping'];
				}
			}
		}

		if(!empty($mapping)) {
			$this->setColumnMapping($mapping);
		}
	}

	protected function setColumnMapping($mapping) {
		if(empty($mapping) || !is_array($mapping)) {
			return;
		}

		$conversionTable = array();
		foreach($mapping as $csvCol => $hikaCol) {
			if(!empty($hikaCol) && $csvCol !== $hikaCol) {
				$conversionTable[strtolower($csvCol)] = $hikaCol;
			}
		}

		$this->importHelper->columnNamesConversionTable = $conversionTable;
	}

}
