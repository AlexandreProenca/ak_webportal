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
class DiscountController extends hikashopController{
	var $toggle = array('discount_published'=>'discount_id');
	var $type='discount';

	function __construct($config = array()) {
		parent::__construct($config);
		$this->modify_views[]='select_coupon';
		$this->modify_views[]='add_coupon';
		$this->modify[]='copy';
		$this->modify_views[]='form';
		$this->display[]='selection';
		$this->display[]='export';
		$this->modify[]='useselection';
		$this->display[]='findList';
		$this->modify_views[]='import';
		$this->modify_views[]='step2import';
		$this->modify[]='importprocess';
	}
	function form(){
		return $this->edit();
	}

	function copy(){
		$discounts = hikaInput::get()->get('cid', array(), 'array');
		$result = true;
		if(!empty($discounts)){
			$discountClass = hikashop_get('class.discount');
			foreach($discounts as $discount){
				$data = $discountClass->get($discount);
				if($data){
					unset($data->discount_id);
					$data->discount_code = $data->discount_code.'_copy'.rand();
					if(!$discountClass->save($data)){
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
		return $this->form();
	}

	function export(){
		hikaInput::get()->set( 'layout', 'export'  );
		return parent::display();
	}

	function import() {
		hikaInput::get()->set('layout', 'import');
		$import_content = hikaInput::get()->get('import_content', '', 'raw');

		$importType = hikaInput::get()->getCmd('import_type', '');
		if(!in_array($importType, array('coupon', 'discount'), true)) {
			$importType = hikaInput::get()->getCmd('filter_type', '');
		}
		if(!in_array($importType, array('coupon', 'discount'), true)) {
			$app = JFactory::getApplication();
			$importType = (string)$app->getUserState('com_hikashop.discount.filter_type', '');
		}
		if(!in_array($importType, array('coupon', 'discount'), true)) {
			$importType = 'coupon';
		}

		$view = $this->getView('discount', 'html');
		if(!empty($import_content)) {
			$view->assignRef('import_content', $import_content);
		}
		$view->assignRef('import_type', $importType);
		return parent::display();
	}

	function step2import() {
		$import_content = hikaInput::get()->get('import_content', '', 'raw');
		$filename = '';
		if(empty($import_content) && !empty($_FILES['import_file']['tmp_name'])) {
			$import_content = file_get_contents($_FILES['import_file']['tmp_name']);
			$filename = $_FILES['import_file']['name'];
		}

		if(empty($import_content)) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('DISCOUNT_IMPORT_NO_DATA'), 'error');
			return $this->import();
		}

		hikashop_get('helper.discount_import');
		$importHelper = new hikashopDiscountImportHelper();

		$format = $importHelper->detectFormat($import_content, $filename);
		if($format == 'xml') {
			$parsed = $importHelper->parseXML($import_content);
		} else {
			$parsed = $importHelper->parseCSV($import_content);
		}

		if(empty($parsed['header'])) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('DISCOUNT_IMPORT_INVALID_FORMAT'), 'error');
			return $this->import();
		}

		$importType = hikaInput::get()->getCmd('import_type', 'coupon');
		if(!in_array($importType, array('coupon', 'discount'), true))
			$importType = 'coupon';

		hikaInput::get()->set('layout', 'import_step2');
		$view = $this->getView('discount', 'html');
		$view->assignRef('import_header', $parsed['header']);
		$view->assignRef('import_data', $parsed['data']);
		$view->assignRef('import_content', $import_content);
		$view->assignRef('import_format', $format);
		$view->assignRef('import_type', $importType);

		return parent::display();
	}

	function importprocess() {
		$import_content = hikaInput::get()->get('import_content', '', 'raw');
		$mapping = hikaInput::get()->get('mapping', array(), 'array');
		$import_format = hikaInput::get()->getCmd('import_format', 'csv');
		$import_type = hikaInput::get()->getCmd('import_type', 'coupon');
		if(!in_array($import_type, array('coupon', 'discount'), true))
			$import_type = 'coupon';

		if(empty($import_content) || empty($mapping)) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('DISCOUNT_IMPORT_NO_DATA'), 'error');
			return $this->import();
		}

		hikashop_get('helper.discount_import');
		$importHelper = new hikashopDiscountImportHelper();

		if($import_format == 'xml') {
			$parsed = $importHelper->parseXML($import_content);
		} else {
			$parsed = $importHelper->parseCSV($import_content);
		}

		if(!$importHelper->validateData($parsed['data'], $mapping)) {
			foreach($importHelper->errors as $error) {
				hikashop_display($error, 'error');
			}
			return;
		}

		$imported = $importHelper->processImport($parsed['data'], $mapping, $import_type);

		$msg = JText::sprintf('X_DISCOUNTS_IMPORTED', $imported);
		if(!empty($importHelper->errors)) {
			$msg .= ' ' . JText::sprintf('DISCOUNT_IMPORT_ERRORS', count($importHelper->errors));
			foreach($importHelper->errors as $error) {
				hikashop_display($error, 'error');
			}
		}
		if(!empty($importHelper->warnings)) {
			foreach($importHelper->warnings as $warning) {
				hikashop_display($warning, 'warning');
			}
		}

		hikashop_display($msg);
		echo '<script type="text/javascript">' . "\n";
		echo 'setTimeout(function(){ window.parent.location.href="' . hikashop_completeLink('discount', false, true) . '"; window.parent.hikashop.closeBox(); }, 2000);' . "\n";
		echo '</script>' . "\n";
		$app = JFactory::getApplication();
		$app->close();
	}

	function select_coupon(){
		hikaInput::get()->set( 'layout', 'select_coupon'  );
		return parent::display();
	}

	function add_coupon(){
		hikaInput::get()->set( 'layout', 'add_coupon'  );
		return parent::display();
	}

	function selection(){
		hikaInput::get()->set('layout', 'selection');
		return parent::display();
	}
	function useselection(){
		hikaInput::get()->set('layout', 'useselection');
		return parent::display();
	}

	public function findList() {
		$search = hikaInput::get()->getVar('search', '');
		$start = hikaInput::get()->getInt('start', 0);
		$type = hikaInput::get()->getVar('type', '');
		$displayFormat = hikaInput::get()->getVar('displayFormat', '');

		$types = array(
			'discount' => 'discount',
			'coupon' => 'coupon'
		);
		if(!empty($type) && !isset($types[$type])) {
			echo '[]';
			exit;
		}

		$options = array();

		if(!empty($displayFormat))
			$options['displayFormat'] = $displayFormat;
		if($start > 0)
			$options['start'] = $start;
		if(!empty($type))
			$options['type'] = $type;

		$nameboxType = hikashop_get('type.namebox');
		$elements = $nameboxType->getValues($search, 'discount', $options);
		echo json_encode($elements);
		exit;
	}
}
