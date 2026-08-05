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
class ZoneController extends hikashopController{
	var $type='zone';
	var $pkey = 'zone_id';
	var $table = 'zone';
	var $orderingMap = 'zone_ordering';
	var $toggle = array('zone_published'=>'zone_id');
	var $modify = array('apply','save','save2new','store','orderdown','orderup','saveorder','savechild','toggle','copy', 'step2importcsv', 'doprocesscsv');
	function __construct($config = array()){
		parent::__construct($config);
		$this->modify_views[] = 'addchild';
		$this->modify_views[] = 'unpublish';
		$this->modify_views[] = 'publish';
		$this->modify_views[] = 'selectchildlisting';
		$this->modify_views[] = 'importcsv';
		$this->modify_views[] = 'step2importcsv';
		$this->display[] = 'addchild';
		$this->display[] = 'getTree';
	}

	function importcsv() {
		hikaInput::get()->set('layout', 'importcsv');

		$csv_content = hikaInput::get()->get('csv_content', '', 'raw');
		if(!empty($csv_content)) {
			$view = $this->getView('zone', 'html');
			$view->assignRef('csv_content', $csv_content);
		}

		return parent::display();
	}

	function step2importcsv() {
		$csv_content = hikaInput::get()->get('csv_content', '', 'raw');
		if(empty($csv_content) && !empty($_FILES['csv_file']['tmp_name'])) {
			$csv_content = file_get_contents($_FILES['csv_file']['tmp_name']);
		}

		if(empty($csv_content)) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('NO_CSV_CONTENT_FOUND'), 'error');
			return $this->importcsv();
		}

		$zoneClass = hikashop_get('class.zone');
		$parsed = $zoneClass->parseCSV($csv_content);

		if(empty($parsed['header'])) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('INVALID_CSV_FORMAT'), 'error');
			return $this->importcsv();
		}

		hikaInput::get()->set('layout', 'step2importcsv');
		$view = $this->getView('zone', 'html');
		$view->assignRef('csv_header', $parsed['header']);
		$view->assignRef('csv_data', $parsed['data']);
		$view->assignRef('csv_content', $csv_content);
		$main_id = hikaInput::get()->getInt('main_id');
		$view->assignRef('main_id', $main_id);

		return parent::display();
	}

	function doprocesscsv() {
		$csv_content = hikaInput::get()->get('csv_content', '', 'raw');
		$mapping = hikaInput::get()->get('mapping', array(), 'array');
		$main_id = hikaInput::get()->getInt('main_id');

		if(empty($csv_content) || empty($mapping) || empty($main_id)) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('IMPORT_FAILED_MISSING_DATA'), 'error');
			return $this->importcsv();
		}

		$zoneClass = hikashop_get('class.zone');
		$parsed = $zoneClass->parseCSV($csv_content);
		$res = $zoneClass->importFromCSV($main_id, $parsed['data'], $mapping);

		$msg = JText::sprintf('X_ZONES_IMPORTED', $res['imported']);
		if($res['skipped'] > 0) $msg .= ' ' . JText::sprintf('X_ZONES_SKIPPED', $res['skipped']);

		if(!empty($res['errors'])) {
			foreach($res['errors'] as $err) $msg .= '\n' . $err;
		}

		$url = 'index.php?option=com_hikashop&ctrl=zone&task=edit&zone_id='.$main_id;
		hikashop_display($msg);
		echo '<script type="text/javascript">'."\n";
		echo 'window.parent.location.href="'.$url.'";'."\n";
		echo 'window.parent.hikashop.closeBox();'."\n";
		echo '</script>'."\n";
		$app = JFactory::getApplication();
		$app->close();
	}

	function copy(){
		$zones = hikaInput::get()->get('cid', array(), 'array' );
		$result = true;
		if(!empty($zones)){
			$zoneClass = hikashop_get('class.zone');
			foreach($zones as $zone){
				$data = $zoneClass->get($zone);
				if($data){
					$childs = $zoneClass->getChildren($data->zone_id);
					unset($data->zone_id);
					unset($data->zone_namekey);
					if(!$zoneClass->save($data)){
						$result=false;
					}elseif(!empty($childs)){
						$childNamekeys = array();
						foreach($childs as $child){
							$childNamekeys[]=$child->zone_namekey;
						}
						$zoneClass->addChildren($data->zone_namekey,$childNamekeys);
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
		}
		return $this->listing();
	}

	function savechild(){
		$new_id = $this->store();
		$main_id = hikaInput::get()->getInt('main_id');
		if($main_id && $new_id){
			$zoneClass = hikashop_get('class.zone');
			$insertedNamekeys = $zoneClass->addChildren($main_id,array($new_id));
			hikaInput::get()->set('cid',$new_id);
			hikaInput::get()->set( 'layout', 'savechild'  );
			return parent::display();
		}else{
			$this->selectchildlisting();
		}
	}

	function selectchildlisting(){
		hikaInput::get()->set( 'task', 'selectchildlisting'  );
		hikaInput::get()->set( 'layout', 'selectchildlisting'  );
		return parent::display();
	}

	function addchild(){
		$type=hikaInput::get()->getWord('type');
		if(!in_array($type,array('discount','shipping','payment','config','tax'))){
			$childNamekeys = hikaInput::get()->get('cid', array(), 'array');
			$mainNamekey = hikaInput::get()->getInt( 'main_id', 0);
			$zoneClass = hikashop_get('class.zone');
			$insertedNamekeys = $zoneClass->addChildren($mainNamekey,$childNamekeys);
			hikaInput::get()->set( 'cid', $insertedNamekeys );
			hikaInput::get()->set( 'layout', 'newchild'  );
		}else{
			hikaInput::get()->set( 'layout', 'addchild'  );
		}
		return parent::display();
	}

	function newchild(){
		hikaInput::get()->set( 'layout', 'newchildform'  );
		return parent::display();
	}

	function getTree() {
		$zone_key = hikaInput::get()->getVar('zone_key', null);
		$displayFormat = hikaInput::get()->getVar('displayFormat', '');
		$search = hikaInput::get()->getVar('search', null);

		$nameboxType = hikashop_get('type.namebox');
		$options = array(
			'zone_key' => $zone_key,
			'displayFormat' => $displayFormat
		);

		$return_zonetype = hikaInput::get()->getVar('return_zonetype', null);
		if(!empty($return_zonetype))
			$options['type'] = $return_zonetype;

		$ret = $nameboxType->getValues($search, 'zone', $options);
		if(!empty($ret)) {
			echo json_encode($ret);
			exit;
		}
		echo '[]';
		exit;
	}
}
