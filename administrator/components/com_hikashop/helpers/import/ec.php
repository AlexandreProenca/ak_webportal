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

include_once HIKASHOP_HELPER . 'import.php';

class hikashopImportEcHelper extends hikashopImportHelper
{
	var $ecDatabase;
	var $ecPrefix;
	var $ecPath;
	var $sessionParams;

	function __construct(&$parent)
	{
		parent::__construct($parent);
		$this->importName = 'ec';
		$this->sessionParams = HIKASHOP_COMPONENT.'ec';
	}

	function isEcCubeDetected($dbName = '') {
		$database = JFactory::getDBO();
		$db = empty($dbName) ? '' : '`'.$dbName.'`.';
		try {
			$database->setQuery('SELECT COUNT(*) FROM '.$db.'`dtb_customer`');
			$database->loadResult();
			return true;
		} catch(Exception $e) {
			return false;
		}
	}

	function importFromEc()
	{
		@ob_clean();
		echo $this->getHtmlPage();

		$this->token = hikashop_getFormToken();

		flush();

		if( isset($_GET['import']) && $_GET['import'] == '1' )
		{
			$app = JFactory::getApplication();
			$this->ecDatabase = $app->getUserState($this->sessionParams.'dbName');
			$this->ecPrefix = $app->getUserState($this->sessionParams.'prefix');
			$this->ecPath = $app->getUserState($this->sessionParams.'rootPath');

			$time = microtime(true);
			$processed = $this->doImport();

			if( $processed )
			{
				$elapsed = microtime(true) - $time;

				if( !$this->refreshPage )
					echo '<p><br/><a'.$this->linkstyle.' href="'.hikashop_completeLink('import&task=import&importfrom=ec&'.$this->token.'=1&import=1&time='.time()).'">'.JText::_('HIKA_NEXT').'</a></p>';

				echo '<p style="font-size:0.85em; color:#605F5D;">'.JText::sprintf('HK_IMPORT_ELAPSED_TIME', round($elapsed * 1000, 2)).'</p>';
			}
			else
			{
				echo '<a'.$this->linkstyle.' href="'.hikashop_completeLink('import&task=show').'">'.JText::_('HIKA_BACK').'</a>';
			}
		}
		else
		{
			echo $this->getStartPage();
		}

		if( $this->refreshPage )
		{
			$url = hikashop_completeLink('import&task=import&importfrom=ec&'.$this->token.'=1&import=1&time='.time());
			$url = str_replace('&amp;', '&', $url);
			echo "<script type=\"text/javascript\">\r\n window.location.href = '".$url."'; \r\n</script>";
		}
		echo '</body></html>';
		exit;
	}

	function getStartPage()
	{
		$app = JFactory::getApplication();
		$database = JFactory::getDBO();

		$returnString = '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 0).'</span></p>';
		$continue = true;

		$this->ecDatabase = $app->getUserStateFromRequest($this->sessionParams.'dbName', 'ecDatabase', '', 'string');
		$this->ecPrefix = $app->getUserStateFromRequest($this->sessionParams.'prefix', 'ecPrefix', 'dtb_', 'string');
		$this->ecPath = $app->getUserStateFromRequest($this->sessionParams.'rootPath', 'ecPath', '', 'string');

		if(empty($this->ecDatabase))
		{
			$returnString .= '<p style="color:red">'.JText::_('HK_IMPORT_SPECIFY_DB').'</p>';
			$continue = false;
		}
		else
		{
			$query = 'SHOW TABLES FROM `'.$this->ecDatabase.'` LIKE '.$database->Quote($this->ecPrefix.'customer').';';
			try
			{
				$database->setQuery($query);
				$table = $database->loadResult();
			}
			catch(Exception $e)
			{
				$returnString .= '<p style="color:red">'.JText::sprintf('HK_IMPORT_DB_ERROR', $this->ecDatabase).'<br/><span style="font-size:0.75em">Mysql Error :'.$e.'</span></p>';
				$continue = false;
			}

			if($continue)
			{
				if(empty($table))
					$returnString .= '<p style="color:red">'.JText::sprintf('HK_IMPORT_DATA_NOT_FOUND', $this->ecPrefix.'customer', $this->ecDatabase).'</p>';
				else
					$returnString .= JText::sprintf('HK_IMPORT_START_FROM_DB', $this->ecDatabase).'<br/>'.
									JText::sprintf('HK_IMPORT_BACKUP_AND_START', '<a '.$this->linkstyle.' href="'.hikashop_completeLink('import&task=import&importfrom=ec&'.$this->token.'=1&import=1').'">'.JText::_('HIKA_NEXT').'</a>');
			}
		}
		$returnString .= '<a'.$this->linkstyle.' href="'.hikashop_completeLink('import&task=show').'">'.JText::_('HIKA_BACK').'</a>';
		return $returnString;
	}

	function doImport()
	{
		if($this->db == null)
			return false;

		$this->loadConfiguration();

		$current = $this->options->current;
		$ret = true;
		$next = false;

		$totalSteps = 10;
		$state = (int)$this->options->state;

		$baseProgress = ($state / $totalSteps) * 100;
		$stepProgress = 0;

		switch($state) {
			case 2:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->ecDatabase."`.`".$this->ecPrefix."category` WHERE discriminator_type = 'category'");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->ecDatabase."`.`".$this->ecPrefix."category` WHERE discriminator_type = 'category' AND id <= ".(int)$this->options->last_ec_cat) / $total);
				break;
			case 3:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->ecDatabase."`.`".$this->ecPrefix."product`");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->ecDatabase."`.`".$this->ecPrefix."product` WHERE id <= ".(int)$this->options->last_ec_prod) / $total);
				break;
			case 6:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->ecDatabase."`.`".$this->ecPrefix."customer` WHERE customer_status_id = 2");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->ecDatabase."`.`".$this->ecPrefix."customer` WHERE customer_status_id = 2 AND id <= ".(int)$this->options->last_ec_user) / $total);
				break;
			case 7:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->ecDatabase."`.`".$this->ecPrefix."customer` WHERE customer_status_id = 2");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->ecDatabase."`.`".$this->ecPrefix."customer` WHERE customer_status_id = 2 AND id <= ".(int)$this->options->last_ec_user) / $total);
				break;
			case 8:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->ecDatabase."`.`".$this->ecPrefix."order`");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->ecDatabase."`.`".$this->ecPrefix."order` WHERE id <= ".(int)$this->options->last_ec_order) / $total);
				break;
			case 9:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->ecDatabase."`.`".$this->ecPrefix."order_item` WHERE order_item_type_id = 1");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->ecDatabase."`.`".$this->ecPrefix."order_item` WHERE order_item_type_id = 1 AND id <= ".(int)$this->options->last_ec_item) / $total);
				break;
		}

		$this->displayProgressBar($baseProgress + ($stepProgress * (100 / $totalSteps)));

		switch($this->options->state)
		{
			case 0:
				$next = $this->createTables();
				break;
			case 1:
				$next = $this->importTaxes();
				break;
			case 2:
				$next = $this->importCategories();
				break;
			case 3:
				$next = $this->importProducts();
				break;
			case 4:
				$next = $this->importProductPrices();
				break;
			case 5:
				$next = $this->importProductImages();
				break;
			case 6:
				$next = $this->importUsers();
				break;
			case 7:
				$next = $this->importAddresses();
				break;
			case 8:
				$next = $this->importOrders();
				break;
			case 9:
				$next = $this->importOrderItems();
				break;
			case 10:
				$next = $this->finishImport();
				$ret = false;
				break;
			default:
				$ret = false;
				break;
		}

		if($ret && $next)
		{
			$sql = "UPDATE `#__hikashop_config` SET config_value=(config_value+1) WHERE config_namekey = 'ec_import_state'; ";
			$this->db->setQuery($sql);
			$this->db->execute();
			$sql = "UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'ec_import_current';";
			$this->db->setQuery($sql);
			$this->db->execute();
			$this->refreshPage = true;
		}
		else if($current != $this->options->current)
		{
			$sql = "UPDATE `#__hikashop_config` SET config_value=".$this->options->current." WHERE config_namekey = 'ec_import_current';";
			$this->db->setQuery($sql);
			$this->db->execute();
		}

		return $ret;
	}

	function loadConfiguration()
	{
		$this->options = new stdClass();
		$data = array(
			'uploadfolder',
			'uploadsecurefolder',
			'main_currency',
			'ec_import_state',
			'ec_import_current',
			'ec_import_main_cat_id',
			'ec_import_max_hk_cat',
			'ec_import_max_hk_prod',
			'ec_import_last_ec_cat',
			'ec_import_last_ec_prod',
			'ec_import_last_ec_user',
			'ec_import_last_ec_order',
			'ec_import_last_ec_item',
			'ec_import_last_ec_tax'
		);
		$this->db->setQuery('SELECT config_namekey, config_value FROM `#__hikashop_config` WHERE config_namekey IN ('."'".implode("','",$data)."'".');');
		$result = $this->db->loadObjectList();

		if(!empty($result))
		{
			foreach($result as $o)
			{
				if(substr($o->config_namekey, 0, 10) == 'ec_import_')
					$nk = substr($o->config_namekey, 10);
				else
					$nk = $o->config_namekey;
				$this->options->$nk = $o->config_value;
			}
		}

		$this->options->uploadfolder = rtrim((string)JPath::clean(html_entity_decode((string)$this->options->uploadfolder)),DS.' ').DS;
		if(!preg_match('#^([A-Z]:)?/.*#',$this->options->uploadfolder)){
			if(!$this->options->uploadfolder[0]=='/' || !is_dir($this->options->uploadfolder)){
				$this->options->uploadfolder = JPath::clean(HIKASHOP_ROOT.DS.trim($this->options->uploadfolder,DS.' ').DS);
			}
		}

		$this->options->uploadsecurefolder = rtrim((string)JPath::clean(html_entity_decode((string)$this->options->uploadsecurefolder)),DS.' ').DS;
		if(!preg_match('#^([A-Z]:)?/.*#',$this->options->uploadsecurefolder)){
			if(!$this->options->uploadsecurefolder[0]=='/' || !is_dir($this->options->uploadsecurefolder)){
				$this->options->uploadsecurefolder = JPath::clean(HIKASHOP_ROOT.DS.trim($this->options->uploadsecurefolder,DS.' ').DS);
			}
		}

		if(!isset($this->options->state) || empty($this->options->main_cat_id))
		{
			if(!isset($this->options->state)) {
				$this->options->state = 0;
				$this->options->current = 0;
				$this->options->last_ec_cat = 0;
				$this->options->last_ec_prod = 0;
				$this->options->last_ec_user = 0;
				$this->options->last_ec_order = 0;
				$this->options->last_ec_item = 0;
				$this->options->last_ec_tax = 0;
			}

			$element = 'product';
			$categoryClass = hikashop_get('class.category');
			$categoryClass->getMainElement($element);
			if(empty($element)) $element = 2;
			$this->options->main_cat_id = $element;

			$this->db->setQuery("SELECT max(category_id) as 'max' FROM `#__hikashop_category`;");
			$data = $this->db->loadObjectList();
			$this->options->max_hk_cat = (int)($data[0]->max);

			$this->db->setQuery("SELECT max(product_id) as 'max' FROM `#__hikashop_product`;");
			$data = $this->db->loadObjectList();
			$this->options->max_hk_prod = (int)($data[0]->max);

			$configData = array(
				'ec_import_state' => $this->options->state,
				'ec_import_current' => $this->options->current,
				'ec_import_main_cat_id' => $this->options->main_cat_id,
				'ec_import_max_hk_cat' => $this->options->max_hk_cat,
				'ec_import_max_hk_prod' => $this->options->max_hk_prod,
				'ec_import_last_ec_cat' => $this->options->last_ec_cat,
				'ec_import_last_ec_prod' => $this->options->last_ec_prod,
				'ec_import_last_ec_user' => $this->options->last_ec_user,
				'ec_import_last_ec_order' => $this->options->last_ec_order,
				'ec_import_last_ec_item' => $this->options->last_ec_item,
				'ec_import_last_ec_tax' => $this->options->last_ec_tax
			);

			foreach($configData as $nk => $nv) {
				$this->db->setQuery("INSERT INTO `#__hikashop_config` (config_namekey, config_value) VALUES (".$this->db->quote($nk).", ".$this->db->quote($nv).") ON DUPLICATE KEY UPDATE config_value = ".$this->db->quote($nv));
				$this->db->execute();
			}
		}
	}

	function createTables()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 1).' :</span> '.JText::_('HK_IMPORT_INITIALIZATION').'</p>';

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_ec_prod` (`ec_id` int(11) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`ec_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_ec_cat` (`ec_id` int(11) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`ec_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_ec_user` (`ec_id` int(11) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`ec_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_ec_order` (`ec_id` int(11) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`ec_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$databaseHelper = hikashop_get('helper.database');
		$databaseHelper->addColumns('order','`order_ec_id` int(11) NULL');
		$databaseHelper->addColumns('order','INDEX ( `order_ec_id` )');
		$databaseHelper->addColumns('taxation','`tax_ec_id` int(11) NULL');

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::_('HK_IMPORT_TABLES_CREATED').'</p>';

		return true;
	}

	function importTaxes()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 2).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('TAXES')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_ec_tax = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'ec_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'ec_import_last_ec_tax'");
			$this->db->execute();
		}

		$oldLastId = (int)$this->options->last_ec_tax;

		$sql = "SELECT id, tax_rate FROM `".$this->ecDatabase."`.`".$this->ecPrefix."tax_rule` WHERE id > ".$oldLastId." ORDER BY id ASC";
		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('TAXES'))).'</p>';
			return true;
		}

		$element = 'tax';
		$categoryClass = hikashop_get('class.category');
		$categoryClass->getMainElement($element);

		$imported = 0;
		foreach($rows as $row) {
			$nameKey = 'EC_TAX_' . $row->id;
			$taxRate = (float)$row->tax_rate / 100;

			$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_tax` (`tax_namekey`, `tax_rate`) VALUES (".$this->db->quote($nameKey).", ".$this->db->quote($taxRate).")");
			$this->db->execute();

			$catNameKey = 'EC_TAX_CAT_' . $row->id;
			$catName = 'EC-CUBE Tax ' . $row->tax_rate . '%';

			$cat = new stdClass();
			$cat->category_type = 'tax';
			$cat->category_name = $catName;
			$cat->category_published = 1;
			$cat->category_parent_id = (int)$element;
			$cat->category_namekey = $catNameKey;

			$categoryClass->save($cat);

			$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_taxation` (`zone_namekey`, `category_namekey`, `tax_namekey`, `taxation_published`, `taxation_type`, `tax_ec_id`) VALUES ('', ".$this->db->quote($catNameKey).", ".$this->db->quote($nameKey).", 1, '', ".(int)$row->id.")");
			$this->db->execute();

			$this->options->last_ec_tax = $row->id;
			$imported++;
		}

		$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_ec_tax." WHERE config_namekey = 'ec_import_last_ec_tax'");
		$this->db->execute();

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('TAXATIONS'), $imported).'</p>';

		return true;
	}

	function importCategories()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 3).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('HIKA_CATEGORIES')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_ec_cat = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'ec_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'ec_import_last_ec_cat'");
			$this->db->execute();
		}

		$categoryClass = hikashop_get('class.category');

		$count = 100;

		$sql = "SELECT id, category_name, parent_category_id ".
			   "FROM `".$this->ecDatabase."`.`".$this->ecPrefix."category` ".
			   "WHERE discriminator_type = 'category' ".
			   "AND id > " . (int)$this->options->last_ec_cat . " ".
			   "ORDER BY id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('HIKA_CATEGORIES'))).'</p>';
			$this->importRebuildTree();
			return true;
		}

		$imported = 0;

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_ec_cat` WHERE ec_id = ".(int)$row->id);
			if($this->db->loadResult()) {
				$this->options->last_ec_cat = $row->id;
				continue;
			}

			$cat = new stdClass();
			$cat->category_type = 'product';
			$cat->category_name = $row->category_name;
			$cat->category_published = 1;

			if(empty($row->parent_category_id)) {
				$cat->category_parent_id = $this->options->main_cat_id;
			} else {
				$this->db->setQuery("SELECT hk_id FROM `#__hikashop_ec_cat` WHERE ec_id = ".(int)$row->parent_category_id);
				$parentId = $this->db->loadResult();
				$cat->category_parent_id = $parentId ? $parentId : $this->options->main_cat_id;
			}

			$hkId = $categoryClass->save($cat);

			if($hkId) {
				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_ec_cat` (ec_id, hk_id) VALUES (".(int)$row->id.", ".(int)$hkId.")");
				$this->db->execute();
				$imported++;
			} else {
				echo '<p style="color:red">'.JText::sprintf('HK_IMPORT_SAVE_FAILED', $row->category_name, '(EC-CUBE ID: '.(int)$row->id.')').'</p>';
			}
			$this->options->last_ec_cat = $row->id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('HIKA_CATEGORIES'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_ec_cat." WHERE config_namekey = 'ec_import_last_ec_cat'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function importProducts()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 4).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('PRODUCTS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_ec_prod = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'ec_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'ec_import_last_ec_prod'");
			$this->db->execute();
		}

		$productClass = hikashop_get('class.product');

		$count = 50;
		$imported = 0;

		$sql = "SELECT p.id, p.name, p.description_detail, p.description_list, p.product_status_id, p.create_date, p.update_date ".
			   "FROM `".$this->ecDatabase."`.`".$this->ecPrefix."product` AS p ".
			   "WHERE p.id > " . (int)$this->options->last_ec_prod . " ".
			   "ORDER BY p.id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('PRODUCTS'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_ec_prod` WHERE ec_id = ".(int)$row->id);
			if($this->db->loadResult()) {
				$this->options->last_ec_prod = $row->id;
				continue;
			}

			$prod = new stdClass();
			$prod->product_name = $row->name;
			$prod->product_description = !empty($row->description_detail) ? $row->description_detail : $row->description_list;
			$prod->product_created = !empty($row->create_date) ? strtotime($row->create_date) : time();
			$prod->product_modified = !empty($row->update_date) ? strtotime($row->update_date) : $prod->product_created;
			$prod->product_published = ($row->product_status_id == 1) ? 1 : 0;
			$prod->product_type = 'main';

			$sqlClass = "SELECT id, product_code, stock, stock_unlimited, price01, price02 ".
						"FROM `".$this->ecDatabase."`.`".$this->ecPrefix."product_class` ".
						"WHERE product_id = ".(int)$row->id." ".
						"AND visible = 1 ".
						"ORDER BY id ASC LIMIT 1";
			$this->db->setQuery($sqlClass);
			$mainClass = $this->db->loadObject();

			if($mainClass) {
				$prod->product_code = !empty($mainClass->product_code) ? $mainClass->product_code : 'ec_product_' . $row->id;
				$prod->product_quantity = !empty($mainClass->stock_unlimited) ? -1 : (int)$mainClass->stock;
			} else {
				$prod->product_code = 'ec_product_' . $row->id;
				$prod->product_quantity = -1;
			}

			$sqlCats = "SELECT category_id FROM `".$this->ecDatabase."`.`".$this->ecPrefix."product_category` WHERE product_id = ".(int)$row->id;
			$this->db->setQuery($sqlCats);
			$ecCatIds = $this->db->loadColumn();

			$categories = array();
			if(!empty($ecCatIds)) {
				$this->db->setQuery("SELECT hk_id FROM `#__hikashop_ec_cat` WHERE ec_id IN (".implode(',', $ecCatIds).")");
				$categories = $this->db->loadColumn();
			}
			if(empty($categories)) $categories = array($this->options->main_cat_id);

			$prod->categories = $categories;

			$sqlTax = "SELECT tr.id FROM `".$this->ecDatabase."`.`".$this->ecPrefix."tax_rule` AS tr ".
					  "WHERE tr.product_id IS NULL OR tr.product_id = ".(int)$row->id." ".
					  "ORDER BY tr.id ASC LIMIT 1";
			$this->db->setQuery($sqlTax);
			$taxRuleId = $this->db->loadResult();
			if($taxRuleId) {
				$this->db->setQuery("SELECT category_id FROM `#__hikashop_category` WHERE category_namekey = ".$this->db->quote('EC_TAX_CAT_'.$taxRuleId));
				$prod->product_tax_id = (int)$this->db->loadResult();
			}

			$sqlVariantCount = "SELECT COUNT(*) FROM `".$this->ecDatabase."`.`".$this->ecPrefix."product_class` ".
							   "WHERE product_id = ".(int)$row->id." AND visible = 1";
			$this->db->setQuery($sqlVariantCount);
			$variantCount = (int)$this->db->loadResult();

			$hkId = $productClass->save($prod);

			if($hkId) {
				if(!empty($categories)) {
					foreach($categories as $catId) {
						$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_product_category` (product_id, category_id) VALUES (".(int)$hkId.", ".(int)$catId.")");
						$this->db->execute();
					}
				}

				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_ec_prod` (ec_id, hk_id) VALUES (".(int)$row->id.", ".(int)$hkId.")");
				$this->db->execute();

				if($variantCount > 1) {
					$this->importProductVariants($row->id, $hkId);
				}

				$imported++;
			}
			$this->options->last_ec_prod = $row->id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('PRODUCTS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_ec_prod." WHERE config_namekey = 'ec_import_last_ec_prod'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function importProductVariants($ecProductId, $hkParentId)
	{
		$productClass = hikashop_get('class.product');

		$sql = "SELECT pc.id, pc.product_code, pc.stock, pc.stock_unlimited, pc.class_category_id1, pc.class_category_id2 ".
			   "FROM `".$this->ecDatabase."`.`".$this->ecPrefix."product_class` AS pc ".
			   "WHERE pc.product_id = ".(int)$ecProductId." AND pc.visible = 1 ".
			   "ORDER BY pc.id ASC";
		$this->db->setQuery($sql);
		$classes = $this->db->loadObjectList();

		if(empty($classes) || count($classes) <= 1) return;

		foreach($classes as $pc) {
			if(empty($pc->class_category_id1) && empty($pc->class_category_id2)) continue;

			$variant = new stdClass();
			$variant->product_parent_id = $hkParentId;
			$variant->product_type = 'variant';
			$variant->product_code = !empty($pc->product_code) ? $pc->product_code : 'ec_var_' . $pc->id;
			$variant->product_quantity = !empty($pc->stock_unlimited) ? -1 : (int)$pc->stock;
			$variant->product_published = 1;

			$variantName = '';
			if(!empty($pc->class_category_id1)) {
				$this->db->setQuery("SELECT name FROM `".$this->ecDatabase."`.`".$this->ecPrefix."class_category` WHERE id = ".(int)$pc->class_category_id1);
				$ccName1 = $this->db->loadResult();
				if($ccName1) $variantName = $ccName1;
			}
			if(!empty($pc->class_category_id2)) {
				$this->db->setQuery("SELECT name FROM `".$this->ecDatabase."`.`".$this->ecPrefix."class_category` WHERE id = ".(int)$pc->class_category_id2);
				$ccName2 = $this->db->loadResult();
				if($ccName2) $variantName .= ($variantName ? ' / ' : '') . $ccName2;
			}
			$variant->product_name = $variantName ? $variantName : 'Variant ' . $pc->id;

			$varId = $productClass->save($variant);

			if($varId) {
				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_ec_prod` (ec_id, hk_id) VALUES (".(int)(1000000 + $pc->id).", ".(int)$varId.")");
				$this->db->execute();

				if(!empty($pc->class_category_id1)) {
					$this->importEcCharacteristic($pc->class_category_id1, $varId, $hkParentId);
				}
				if(!empty($pc->class_category_id2)) {
					$this->importEcCharacteristic($pc->class_category_id2, $varId, $hkParentId);
				}
			}
		}
	}

	function importEcCharacteristic($classCategoryId, $variantId, $parentProductId)
	{
		static $charCache = array();

		$this->db->setQuery("SELECT cc.id, cc.name, cc.class_name_id FROM `".$this->ecDatabase."`.`".$this->ecPrefix."class_category` AS cc WHERE cc.id = ".(int)$classCategoryId);
		$cc = $this->db->loadObject();
		if(!$cc) return;

		$this->db->setQuery("SELECT name FROM `".$this->ecDatabase."`.`".$this->ecPrefix."class_name` WHERE id = ".(int)$cc->class_name_id);
		$className = $this->db->loadResult();
		if(empty($className)) $className = 'Option ' . $cc->class_name_id;

		$charKey = 'ec_char_' . $cc->class_name_id;

		if(!isset($charCache[$charKey])) {
			$alias = JFilterOutput::stringURLSafe($className);
			if(empty($alias)) $alias = 'option-' . $cc->class_name_id;
			$this->db->setQuery("SELECT characteristic_id FROM `#__hikashop_characteristic` WHERE characteristic_parent_id = 0 AND characteristic_alias = ".$this->db->quote($alias));
			$charId = (int)$this->db->loadResult();

			if(!$charId) {
				$this->db->setQuery("INSERT INTO `#__hikashop_characteristic` (characteristic_value, characteristic_alias, characteristic_display_type) VALUES (".$this->db->quote($className).", ".$this->db->quote($alias).", 'list')");
				$this->db->execute();
				$charId = $this->db->insertid();
			}
			$charCache[$charKey] = $charId;
		}
		$charId = $charCache[$charKey];

		$valAlias = JFilterOutput::stringURLSafe($cc->name);
		if(empty($valAlias)) $valAlias = 'val-' . $cc->id;
		$valKey = 'ec_val_' . $charId . '_' . $valAlias;

		if(!isset($charCache[$valKey])) {
			$this->db->setQuery("SELECT characteristic_id FROM `#__hikashop_characteristic` WHERE characteristic_parent_id = ".(int)$charId." AND characteristic_alias = ".$this->db->quote($valAlias));
			$valId = (int)$this->db->loadResult();

			if(!$valId) {
				$this->db->setQuery("INSERT INTO `#__hikashop_characteristic` (characteristic_parent_id, characteristic_value, characteristic_alias) VALUES (".(int)$charId.", ".$this->db->quote($cc->name).", ".$this->db->quote($valAlias).")");
				$this->db->execute();
				$valId = $this->db->insertid();
			}
			$charCache[$valKey] = $valId;
		}
		$valId = $charCache[$valKey];

		$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_variant` (variant_product_id, variant_characteristic_id) VALUES (".(int)$parentProductId.", ".(int)$charId.")");
		$this->db->execute();

		$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_variant` (variant_product_id, variant_characteristic_id) VALUES (".(int)$variantId.", ".(int)$valId.")");
		$this->db->execute();
	}

	function importProductPrices()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 5).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('PRICES')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_ec_prod = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'ec_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'ec_import_last_ec_prod'");
			$this->db->execute();
		}

		$count = 50;
		$imported = 0;

		$sql = "SELECT id FROM `".$this->ecDatabase."`.`".$this->ecPrefix."product` ".
			   "WHERE id > " . (int)$this->options->last_ec_prod . " ".
			   "ORDER BY id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('PRICES'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$sqlClass = "SELECT pc.id, pc.price01, pc.price02, pc.class_category_id1, pc.class_category_id2 ".
						"FROM `".$this->ecDatabase."`.`".$this->ecPrefix."product_class` AS pc ".
						"WHERE pc.product_id = ".(int)$row->id." AND pc.visible = 1 ".
						"ORDER BY pc.id ASC";
			$this->db->setQuery($sqlClass);
			$classes = $this->db->loadObjectList();

			if(!empty($classes)) {
				foreach($classes as $pc) {
					$hkId = 0;
					if(!empty($pc->class_category_id1) || !empty($pc->class_category_id2)) {
						$this->db->setQuery("SELECT hk_id FROM `#__hikashop_ec_prod` WHERE ec_id = ".(int)(1000000 + $pc->id));
						$hkId = $this->db->loadResult();
					}
					if(!$hkId) {
						$this->db->setQuery("SELECT hk_id FROM `#__hikashop_ec_prod` WHERE ec_id = ".(int)$row->id);
						$hkId = $this->db->loadResult();
					}
					if(!$hkId) continue;

					$sellingPrice = isset($pc->price02) ? (float)$pc->price02 : 0;
					$regularPrice = isset($pc->price01) ? (float)$pc->price01 : 0;

					if($sellingPrice > 0) {
						$price = new stdClass();
						$price->price_product_id = $hkId;
						$price->price_currency_id = (int)$this->options->main_currency;
						if(!$price->price_currency_id) $price->price_currency_id = 1;
						$price->price_value = $sellingPrice;
						$price->price_min_quantity = 0;
						$price->price_access = 'all';
						$price->price_users = '';
						$this->db->insertObject('#__hikashop_price', $price);
					}

					if($regularPrice > 0 && $regularPrice != $sellingPrice) {
						$price = new stdClass();
						$price->price_product_id = $hkId;
						$price->price_currency_id = (int)$this->options->main_currency;
						if(!$price->price_currency_id) $price->price_currency_id = 1;
						$price->price_value = $regularPrice;
						$price->price_min_quantity = 0;
						$price->price_access = 'all';
						$price->price_users = '';
						$this->db->insertObject('#__hikashop_price', $price);
					}

					$imported++;
				}
			}

			$this->options->last_ec_prod = $row->id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('PRICES'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_ec_prod." WHERE config_namekey = 'ec_import_last_ec_prod'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function importProductImages()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 6).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('HIKA_IMAGES')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_ec_prod = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'ec_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'ec_import_last_ec_prod'");
			$this->db->execute();
		}

		$fileClass = hikashop_get('class.file');

		$count = 20;
		$imported = 0;

		$sql = "SELECT id FROM `".$this->ecDatabase."`.`".$this->ecPrefix."product` ".
			   "WHERE id > " . (int)$this->options->last_ec_prod . " ".
			   "ORDER BY id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('HIKA_IMAGES'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_ec_prod` WHERE ec_id = ".(int)$row->id);
			$hkId = $this->db->loadResult();

			if(!$hkId) {
				$this->options->last_ec_prod = $row->id;
				continue;
			}

			$sqlImg = "SELECT id, file_name, sort_no FROM `".$this->ecDatabase."`.`".$this->ecPrefix."product_image` ".
					  "WHERE product_id = ".(int)$row->id." ".
					  "ORDER BY sort_no ASC";
			$this->db->setQuery($sqlImg);
			$images = $this->db->loadObjectList();

			if(!empty($images)) {
				foreach($images as $img) {
					if(empty($img->file_name)) continue;

					if(!empty($this->ecPath)) {
						$source = rtrim($this->ecPath, DS) . DS . 'html' . DS . 'upload' . DS . 'save_image' . DS . $img->file_name;
						$dest = $this->options->uploadfolder . $img->file_name;

						if(file_exists($source) && !file_exists($dest)) {
							jimport('joomla.filesystem.file');
							JFile::copy($source, $dest);
						}
					}

					$file = new stdClass();
					$file->file_name = $img->file_name;
					$file->file_description = '';
					$file->file_path = $img->file_name;
					$file->file_type = 'product';
					$file->file_ref_id = $hkId;
					$fileClass->save($file);
				}
			}

			$this->options->last_ec_prod = $row->id;
			$imported++;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('HIKA_IMAGES'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_ec_prod." WHERE config_namekey = 'ec_import_last_ec_prod'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function importUsers()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 7).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('USERS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_ec_user = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'ec_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'ec_import_last_ec_user'");
			$this->db->execute();
		}

		jimport('joomla.user.helper');
		$userClass = hikashop_get('class.user');

		$count = 50;
		$imported = 0;

		$sql = "SELECT id, email, password, name01, name02, point, create_date ".
			   "FROM `".$this->ecDatabase."`.`".$this->ecPrefix."customer` ".
			   "WHERE customer_status_id = 2 ".
			   "AND id > " . (int)$this->options->last_ec_user . " ".
			   "ORDER BY id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('USERS'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_ec_user` WHERE ec_id = ".(int)$row->id);
			if($this->db->loadResult()) {
				$this->options->last_ec_user = $row->id;
				continue;
			}

			$userId = 0;
			$this->db->setQuery("SELECT id FROM `#__users` WHERE email = ".$this->db->quote($row->email));
			$existingId = $this->db->loadResult();

			if($existingId) {
				$userId = $existingId;
				echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> User '.$row->email.' already exists (ID: '.$userId.'). Mapping only.</p>';
			} else {
				$fullName = trim($row->name01 . ' ' . $row->name02);

				$user = new JUser();
				$user->name = $fullName;
				$user->username = $row->email; // Email as username (Japanese convention)
				$user->email = $row->email;
				$user->password = $row->password; // EC-CUBE uses bcrypt ($2y$), works natively in Joomla 4+
				$user->registerDate = !empty($row->create_date) ? $row->create_date : date('Y-m-d H:i:s');
				$user->groups = array(2); // Registered

				$this->db->setQuery("SELECT id FROM `#__users` WHERE username = ".$this->db->quote($row->email));
				if($this->db->loadResult()) {
					$user->username = $row->email . '_' . $row->id;
				}

				if(!$user->save()) {
					echo '<p style="color:red">'.JText::sprintf('HK_IMPORT_USER_SAVE_ERROR', $row->email, $user->getError()).'</p>';
					$this->options->last_ec_user = $row->id;
					continue;
				}
				$userId = $user->id;
			}

			if($userId) {
				$hkUser = $userClass->get($userId);
				if(empty($hkUser)) {
					$hkUser = new stdClass();
					$hkUser->user_cms_id = $userId;
					$hkUser->user_email = $row->email;
					if(!empty($row->point)) {
						$hkUser->user_points = (int)$row->point;
					}
					$userClass->save($hkUser);
				} else {
					if(!empty($row->point) && empty($hkUser->user_points)) {
						$hkUser->user_points = (int)$row->point;
						$userClass->save($hkUser);
					}
				}

				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_ec_user` (ec_id, hk_id) VALUES (".(int)$row->id.", ".(int)$userId.")");
				$this->db->execute();

				$imported++;
			}

			$this->options->last_ec_user = $row->id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('USERS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_ec_user." WHERE config_namekey = 'ec_import_last_ec_user'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function importAddresses()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 8).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('ADDRESSES')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_ec_user = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'ec_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'ec_import_last_ec_user'");
			$this->db->execute();
		}

		$addressClass = hikashop_get('class.address');

		$count = 50;
		$imported = 0;

		$sql = "SELECT c.id, map.hk_id, c.name01, c.name02, c.kana01, c.kana02, c.postal_code, c.addr01, c.addr02, c.pref_id, c.phone_number, c.company_name ".
			   "FROM `".$this->ecDatabase."`.`".$this->ecPrefix."customer` AS c ".
			   "INNER JOIN `#__hikashop_ec_user` AS map ON c.id = map.ec_id ".
			   "WHERE c.id > " . (int)$this->options->last_ec_user . " ".
			   "ORDER BY c.id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('ADDRESSES'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			if(!empty($row->addr01) || !empty($row->postal_code)) {
				$addr = new stdClass();
				$addr->address_user_id = $row->hk_id;
				$addr->address_lastname = isset($row->name01) ? $row->name01 : '';
				$addr->address_firstname = isset($row->name02) ? $row->name02 : '';
				$addr->address_company = isset($row->company_name) ? $row->company_name : '';
				$addr->address_post_code = isset($row->postal_code) ? $row->postal_code : '';
				$addr->address_city = isset($row->addr01) ? $row->addr01 : '';
				$addr->address_street = isset($row->addr02) ? $row->addr02 : '';
				$addr->address_telephone = isset($row->phone_number) ? $row->phone_number : '';

				$addr->address_country = $this->getZoneNamekey('JP', 'country');

				if(!empty($row->pref_id)) {
					$prefName = $this->getPrefectureName($row->pref_id);
					if($prefName) {
						$addr->address_state = $this->getZoneNamekey($prefName, 'state', $addr->address_country);
					}
				}

				$addressClass->save($addr);
				$imported++;
			}

			$sqlAddr = "SELECT name01, name02, kana01, kana02, postal_code, addr01, addr02, pref_id, phone_number, company_name ".
					   "FROM `".$this->ecDatabase."`.`".$this->ecPrefix."customer_address` ".
					   "WHERE customer_id = ".(int)$row->id;
			$this->db->setQuery($sqlAddr);
			$extraAddrs = $this->db->loadObjectList();

			if(!empty($extraAddrs)) {
				foreach($extraAddrs as $ea) {
					$addr = new stdClass();
					$addr->address_user_id = $row->hk_id;
					$addr->address_lastname = isset($ea->name01) ? $ea->name01 : '';
					$addr->address_firstname = isset($ea->name02) ? $ea->name02 : '';
					$addr->address_company = isset($ea->company_name) ? $ea->company_name : '';
					$addr->address_post_code = isset($ea->postal_code) ? $ea->postal_code : '';
					$addr->address_city = isset($ea->addr01) ? $ea->addr01 : '';
					$addr->address_street = isset($ea->addr02) ? $ea->addr02 : '';
					$addr->address_telephone = isset($ea->phone_number) ? $ea->phone_number : '';
					$addr->address_country = $this->getZoneNamekey('JP', 'country');

					if(!empty($ea->pref_id)) {
						$prefName = $this->getPrefectureName($ea->pref_id);
						if($prefName) {
							$addr->address_state = $this->getZoneNamekey($prefName, 'state', $addr->address_country);
						}
					}

					$addressClass->save($addr);
					$imported++;
				}
			}

			$this->options->last_ec_user = $row->id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('ADDRESSES'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_ec_user." WHERE config_namekey = 'ec_import_last_ec_user'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function getPrefectureName($prefId)
	{
		static $prefCache = null;
		if($prefCache === null) {
			$this->db->setQuery("SELECT id, name FROM `".$this->ecDatabase."`.`mtb_pref`");
			$prefs = $this->db->loadObjectList('id');
			$prefCache = array();
			if(!empty($prefs)) {
				foreach($prefs as $p) {
					$prefCache[$p->id] = $p->name;
				}
			}
		}
		return isset($prefCache[$prefId]) ? $prefCache[$prefId] : null;
	}

	function getZoneNamekey($code, $type, $parentKey = '')
	{
		static $zoneCache = array();
		$key = $code . '_' . $type . '_' . $parentKey;
		if(isset($zoneCache[$key])) return $zoneCache[$key];

		$query = "SELECT zone_namekey FROM `#__hikashop_zone` WHERE zone_type = " . $this->db->quote($type);

		if($type == 'country') {
			$query .= " AND zone_code_2 = " . $this->db->quote($code);
		} elseif($type == 'state') {
			$query .= " AND (zone_code_2 = " . $this->db->quote($code) . " OR zone_name = " . $this->db->quote($code) . " OR zone_name_english = " . $this->db->quote($code) . ")";
			if($parentKey) {
				$query .= " AND zone_namekey IN (SELECT zone_child_namekey FROM `#__hikashop_zone_link` WHERE zone_parent_namekey = " . $this->db->quote($parentKey) . ")";
			}
		}

		$this->db->setQuery($query);
		$namekey = $this->db->loadResult();
		$zoneCache[$key] = $namekey;
		return $namekey;
	}

	function importOrders()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 9).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('ORDERS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_ec_order = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'ec_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'ec_import_last_ec_order'");
			$this->db->execute();
		}

		$orderClass = hikashop_get('class.order');
		$orderClass->sendEmailAfterOrderCreation = false;
		$addressClass = hikashop_get('class.address');
		$config = hikashop_config();

		$oldLockedSchemas = $config->get('order_locked_statuses');
		$config->set('order_locked_statuses', '');

		$count = 50;
		$imported = 0;

		$sql = "SELECT o.id, o.order_no, o.customer_id, o.order_status_id, o.total, o.subtotal, ".
			   "o.discount, o.delivery_fee_total, o.tax, o.use_point, o.add_point, ".
			   "o.name01, o.name02, o.postal_code, o.addr01, o.addr02, o.pref_id, o.phone_number, o.company_name, ".
			   "o.email, o.order_date, o.create_date, o.currency_code, o.payment_method ".
			   "FROM `".$this->ecDatabase."`.`".$this->ecPrefix."order` AS o ".
			   "WHERE o.id > " . (int)$this->options->last_ec_order . " ".
			   "ORDER BY o.id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('ORDERS'))).'</p>';
			$config->set('order_locked_statuses', $oldLockedSchemas);
			return true;
		}

		if(empty($this->options->main_currency)) {
			$currencyClass = hikashop_get('class.currency');
			$mainCurrency = $currencyClass->getMain();
			if(!empty($mainCurrency)) {
				$this->options->main_currency = $mainCurrency->currency_id;
			}
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_ec_order` WHERE ec_id = ".(int)$row->id);
			if($this->db->loadResult()) {
				$this->options->last_ec_order = $row->id;
				continue;
			}

			$order = new stdClass();
			$order->order_number = !empty($row->order_no) ? $row->order_no : 'EC-'.$row->id;
			$order->order_created = !empty($row->order_date) ? strtotime($row->order_date) : (!empty($row->create_date) ? strtotime($row->create_date) : time());
			$order->order_type = 'sale';

			switch((int)$row->order_status_id) {
				case 1: $order->order_status = 'created'; break;
				case 3: $order->order_status = 'cancelled'; break;
				case 4: $order->order_status = 'confirmed'; break;
				case 5: $order->order_status = 'shipped'; break;
				case 6: $order->order_status = 'confirmed'; break;
				case 7: $order->order_status = 'pending'; break;
				case 8: $order->order_status = 'pending'; break;
				case 9: $order->order_status = 'refunded'; break;
				default: $order->order_status = 'created'; break;
			}

			$hkUserId = 0;
			if(!empty($row->customer_id)) {
				$this->db->setQuery("SELECT hk_id FROM `#__hikashop_ec_user` WHERE ec_id = ".(int)$row->customer_id);
				$cmsId = $this->db->loadResult();
				if($cmsId) {
					$this->db->setQuery("SELECT user_id FROM `#__hikashop_user` WHERE user_cms_id = ".(int)$cmsId);
					$hkUserId = (int)$this->db->loadResult();
				}
			}

			if(empty($hkUserId) && !empty($row->email)) {
				$userClass = hikashop_get('class.user');
				$existingUser = $userClass->get($row->email, 'email');
				if($existingUser) {
					$hkUserId = $existingUser->user_id;
				} else {
					$hkUser = new stdClass();
					$hkUser->user_cms_id = 0;
					$hkUser->user_email = $row->email;
					$userClass->save($hkUser);
					$hkUserId = (int)$hkUser->user_id;
				}
			}
			$order->order_user_id = $hkUserId ? $hkUserId : 0;

			$order->order_full_price = isset($row->total) ? (float)$row->total : 0;
			$order->order_discount_price = isset($row->discount) ? (float)$row->discount : 0;
			$order->order_shipping_price = isset($row->delivery_fee_total) ? (float)$row->delivery_fee_total : 0;

			$totalTax = isset($row->tax) ? (float)$row->tax : 0;
			if($totalTax > 0) {
				$taxInfo = new stdClass();
				$taxInfo->tax_namekey = 'VAT';
				$taxInfo->tax_amount = $totalTax;
				$taxInfo->amount = $order->order_full_price - $totalTax;
				$taxInfo->tax_rate = 0;
				$order->order_tax_info = array('VAT' => $taxInfo);
			}

			if(!empty($row->currency_code)) {
				$this->db->setQuery("SELECT currency_id FROM `#__hikashop_currency` WHERE currency_code = ".$this->db->quote($row->currency_code));
				$currId = $this->db->loadResult();
				$order->order_currency_id = $currId ? $currId : $this->options->main_currency;
			} else {
				$order->order_currency_id = $this->options->main_currency;
			}

			if(!empty($row->payment_method)) {
				$order->order_payment_method = $row->payment_method;
			}

			$addr = new stdClass();
			$addr->address_user_id = $hkUserId;
			$addr->address_lastname = isset($row->name01) ? $row->name01 : '';
			$addr->address_firstname = isset($row->name02) ? $row->name02 : '';
			$addr->address_company = isset($row->company_name) ? $row->company_name : '';
			$addr->address_post_code = isset($row->postal_code) ? $row->postal_code : '';
			$addr->address_city = isset($row->addr01) ? $row->addr01 : '';
			$addr->address_street = isset($row->addr02) ? $row->addr02 : '';
			$addr->address_telephone = isset($row->phone_number) ? $row->phone_number : '';
			$addr->address_published = 0;
			$addr->address_country = $this->getZoneNamekey('JP', 'country');

			if(!empty($row->pref_id)) {
				$prefName = $this->getPrefectureName($row->pref_id);
				if($prefName) {
					$addr->address_state = $this->getZoneNamekey($prefName, 'state', $addr->address_country);
				}
			}

			$order->order_billing_address_id = $addressClass->save($addr);

			$sqlShip = "SELECT name01, name02, postal_code, addr01, addr02, pref_id, phone_number, company_name ".
					   "FROM `".$this->ecDatabase."`.`".$this->ecPrefix."shipping` ".
					   "WHERE order_id = ".(int)$row->id." LIMIT 1";
			$this->db->setQuery($sqlShip);
			$shipping = $this->db->loadObject();

			if($shipping) {
				$sAddr = new stdClass();
				$sAddr->address_user_id = $hkUserId;
				$sAddr->address_lastname = isset($shipping->name01) ? $shipping->name01 : '';
				$sAddr->address_firstname = isset($shipping->name02) ? $shipping->name02 : '';
				$sAddr->address_company = isset($shipping->company_name) ? $shipping->company_name : '';
				$sAddr->address_post_code = isset($shipping->postal_code) ? $shipping->postal_code : '';
				$sAddr->address_city = isset($shipping->addr01) ? $shipping->addr01 : '';
				$sAddr->address_street = isset($shipping->addr02) ? $shipping->addr02 : '';
				$sAddr->address_telephone = isset($shipping->phone_number) ? $shipping->phone_number : '';
				$sAddr->address_published = 0;
				$sAddr->address_country = $this->getZoneNamekey('JP', 'country');

				if(!empty($shipping->pref_id)) {
					$prefName = $this->getPrefectureName($shipping->pref_id);
					if($prefName) {
						$sAddr->address_state = $this->getZoneNamekey($prefName, 'state', $sAddr->address_country);
					}
				}

				$order->order_shipping_address_id = $addressClass->save($sAddr);
			} else {
				$order->order_shipping_address_id = $order->order_billing_address_id;
			}

			$order->order_ec_id = $row->id;

			$hkId = $orderClass->save($order);

			if($hkId) {
				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_ec_order` (ec_id, hk_id) VALUES (".(int)$row->id.", ".(int)$hkId.")");
				$this->db->execute();
				$imported++;
			} else {
				echo '<p style="color:red">'.JText::sprintf('HK_IMPORT_SAVE_FAILED', JText::_('ORDERS'), $row->id).'</p>';
			}

			$this->options->last_ec_order = $row->id;
		}

		$config->set('order_locked_statuses', $oldLockedSchemas);

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('ORDERS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_ec_order." WHERE config_namekey = 'ec_import_last_ec_order'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function importOrderItems()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 10).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('ORDER_PRODUCTS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_ec_item = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'ec_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'ec_import_last_ec_item'");
			$this->db->execute();
		}

		$orderProductClass = hikashop_get('class.order_product');

		$count = 100;
		$imported = 0;

		$sql = "SELECT oi.id, oi.order_id, oi.product_name, oi.product_code, oi.price, oi.quantity, oi.tax ".
			   "FROM `".$this->ecDatabase."`.`".$this->ecPrefix."order_item` AS oi ".
			   "WHERE oi.order_item_type_id = 1 ".
			   "AND oi.id > " . (int)$this->options->last_ec_item . " ".
			   "ORDER BY oi.id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('ORDER_PRODUCTS'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_ec_order` WHERE ec_id = ".(int)$row->order_id);
			$orderId = $this->db->loadResult();

			if(!$orderId) {
				$this->db->setQuery("SELECT order_id FROM `#__hikashop_order` WHERE order_ec_id = ".(int)$row->order_id);
				$orderId = $this->db->loadResult();
			}

			if(!$orderId) {
				$this->options->last_ec_item = $row->id;
				continue;
			}

			$hkProductId = 0;
			if(!empty($row->product_code)) {
				$this->db->setQuery("SELECT product_id FROM `#__hikashop_product` WHERE product_code = ".$this->db->quote($row->product_code)." LIMIT 1");
				$hkProductId = (int)$this->db->loadResult();
			}

			$op = new stdClass();
			$op->order_id = $orderId;
			$op->product_id = $hkProductId;
			$op->order_product_name = $row->product_name;
			$op->order_product_code = !empty($row->product_code) ? $row->product_code : '';
			$op->order_product_quantity = isset($row->quantity) ? (int)$row->quantity : 1;

			$price = isset($row->price) ? (float)$row->price : 0;
			$tax = isset($row->tax) ? (float)$row->tax : 0;

			$op->order_product_price = $price;
			$op->order_product_tax = $op->order_product_quantity > 0 ? ($tax / $op->order_product_quantity) : 0;
			$op->order_product_total_price = $price * $op->order_product_quantity;
			$op->order_product_total_price_no_vat = $price * $op->order_product_quantity;

			$ops = array($op);
			if($orderProductClass->save($ops)) {
				$imported++;
			}

			$this->options->last_ec_item = $row->id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('ORDER_PRODUCTS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_ec_item." WHERE config_namekey = 'ec_import_last_ec_item'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function finishImport()
	{
		$this->importRebuildTree();
		return true;
	}

	function countSourceRows($sql)
	{
		$this->db->setQuery($sql);
		return (int)$this->db->loadResult();
	}

	function displayProgressBar($percent)
	{
		$percent = round($percent);
		if($percent > 100) $percent = 100;
		if($percent < 0) $percent = 0;

		$state = (int)$this->options->state;
		$stepName = $this->getStepName($state);

		$subText = JText::_('HIKA_IMPORT_PROCESSING');
		if($state >= 10) {
			$subText = JText::_('HIKA_IMPORT_SUCCESS');
		}

		echo '
		<style>
			.hk-progress-header { text-align: center; margin-bottom: 25px; }
			.hk-progress-subtitle { margin-top: 5px; font-size: 14px; }
			.hk-progress-card { background: #f8f9fa; border-radius: 8px; padding: 25px; border: 1px solid #e9ecef; }
			.hk-progress-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
			.hk-progress-step-title { font-weight: 600; font-size: 18px; color: #333; }
			.hk-progress-highlight { color: #c7000b; }
			.hk-progress-badge { color: #666; font-weight: 500; font-size: 14px; background: #fff; padding: 4px 10px; border-radius: 20px; border: 1px solid #dee2e6; }
			.hk-progress-track { width: 100%; background: #e9ecef; border-radius: 12px; height: 16px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1); }
			.hk-progress-fill { height: 100%; background: linear-gradient(90deg, #c7000b, #e85d5d); width: '.$percent.'%; transition: width 0.5s ease-in-out; border-radius: 12px; }
			.hk-progress-note { margin-top: 15px; text-align: center; font-size: 13px; color: #888; }
			.hk-import-details { margin-top: 20px; padding: 15px; border-left: 3px solid #c7000b; background: #fafafa; font-size: 14px; color: #555; }
		</style>';

		echo '
			<div class="hk-progress-header">
				<p class="hk-progress-subtitle" style="color: '.($state >= 10 ? '#28a745' : '#666').'; font-weight: '.($state >= 10 ? '600' : 'normal').';">
					'.$subText.'
				</p>
			</div>

			<div class="hk-progress-card">
				<div class="hk-progress-info">
					<span class="hk-progress-step-title">
						'.JText::sprintf('STEP_X', $state).': <span class="hk-progress-highlight">'.$stepName.'</span>
					</span>
					<span class="hk-progress-badge">
						'.JText::_('HIKA_OVERALL_PROGRESS').': '.$percent.'%
					</span>
				</div>

				<div class="hk-progress-track">
					<div class="hk-progress-fill"></div>
				</div>

				<div class="hk-progress-note">
					'.JText::_('HIKA_IMPORT_PLEASE_DO_NOT_CLOSE_THIS_WINDOW_THE_PAGE_WILL_REFRESH_AUTOMATICALLY').'
				</div>
			</div>

			<div class="hk-import-details">
		';
	}

	function getStepName($state)
	{
		$steps = array(
			0 => JText::_('HK_IMPORT_INITIALIZATION'),
			1 => JText::_('TAXES'),
			2 => JText::_('HIKA_CATEGORIES'),
			3 => JText::_('PRODUCTS'),
			4 => JText::_('PRICES'),
			5 => JText::_('HIKA_IMAGES'),
			6 => JText::_('USERS'),
			7 => JText::_('ADDRESSES'),
			8 => JText::_('ORDERS'),
			9 => JText::_('ORDER_PRODUCTS'),
			10 => JText::_('HK_IMPORT_FINISHING')
		);
		return isset($steps[$state]) ? $steps[$state] : JText::_('HIKA_IMPORT_PROCESSING');
	}
}
