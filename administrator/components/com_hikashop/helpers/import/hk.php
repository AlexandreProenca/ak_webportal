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

class hikashopImportHkHelper extends hikashopImportHelper
{
	var $hkDatabase;
	var $hkPrefix;
	var $hkCmsPrefix;
	var $hkCmsType;
	var $hkPath;
	var $sessionParams;

	function __construct(&$parent)
	{
		parent::__construct($parent);
		$this->importName = 'hk';
		$this->sessionParams = HIKASHOP_COMPONENT.'hk';
		$this->copyImgDir = '';
		jimport('joomla.filesystem.file');
	}

	function importFromHk()
	{
		@ob_clean();
		echo $this->getHtmlPage();

		$this->token = hikashop_getFormToken();

		flush();

		if( isset($_GET['import']) && $_GET['import'] == '1' )
		{
			$app = JFactory::getApplication();
			$this->hkDatabase = $app->getUserState($this->sessionParams.'dbName');
			$this->hkPath = $app->getUserState($this->sessionParams.'rootPath');
			$this->hkCmsPrefix = $app->getUserState($this->sessionParams.'cmsPrefix');
			$this->hkPrefix = $this->hkCmsPrefix . 'hikashop_';
			$this->hkCmsType = $app->getUserState($this->sessionParams.'cmsType');

			$time = microtime(true);
			$processed = $this->doImport();

			if( $processed )
			{
				$elasped = microtime(true) - $time;

				if( !$this->refreshPage )
					echo '<p><br/><a'.$this->linkstyle.' href="'.hikashop_completeLink('import&task=import&importfrom=hk&'.$this->token.'=1&import=1&time='.time()).'">'.JText::_('HIKA_NEXT').'</a></p>';

				echo '<p style="font-size:0.85em; color:#605F5D;">'.JText::sprintf('HK_IMPORT_ELAPSED_TIME', round($elasped * 1000, 2)).'</p>';
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
			$url = hikashop_completeLink('import&task=import&importfrom=hk&'.$this->token.'=1&import=1&time='.time());
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

		$this->hkDatabase = $app->getUserStateFromRequest($this->sessionParams.'dbName', 'hkDatabase', '', 'string' );
		$this->hkPath = $app->getUserStateFromRequest($this->sessionParams.'rootPath', 'hkPath', '', 'string' );
		$this->hkCmsPrefix = $app->getUserStateFromRequest($this->sessionParams.'cmsPrefix', 'hkCmsPrefix', '', 'string' );
		$this->hkPrefix = $this->hkCmsPrefix . 'hikashop_';
		$this->hkCmsType = $app->getUserStateFromRequest($this->sessionParams.'cmsType', 'hkCmsType', 'joomla', 'string' );

		if (empty($this->hkDatabase))
		{
			$returnString .= '<p style="color:red">'.JText::_('HK_IMPORT_SPECIFY_DB').'</p>';
			$continue = false;
		}
		elseif (empty($this->hkCmsPrefix))
		{
			$returnString .= '<p style="color:red">'.JText::_('HK_IMPORT_SPECIFY_PREFIX').'</p>';
			$continue = false;
		}
		else
		{
			$query = 'SHOW TABLES FROM `'.$this->hkDatabase.'` LIKE '.$database->Quote($this->hkPrefix.'product').';';
			try
			{
				$database->setQuery($query);
				$table = $database->loadResult();
			}
			catch(Exception $e)
			{
				$returnString .= '<p style="color:red">'.JText::sprintf('HK_IMPORT_DB_ERROR', $this->hkDatabase).'<br/><span style="font-size:0.75em">Mysql Error :'.$e.'</span></p>';
				$continue = false;
			}

			if ($continue)
			{
				if (empty($table))
					$returnString .= '<p style="color:red">'.JText::sprintf('HK_IMPORT_DATA_NOT_FOUND', $this->hkPrefix.'product', $this->hkDatabase).'</p>';
				else {
					$returnString .= JText::sprintf('HK_IMPORT_START_FROM_DB', $this->hkDatabase).'<br/>';
					if(empty($this->hkPath))
						$returnString .= '<p style="color:#c09853; font-weight:bold;">&#9888; '.JText::_('HK_IMPORT_NO_PATH_NOTE').'</p>';
					elseif(!is_dir($this->hkPath))
						$returnString .= '<p style="color:red; font-weight:bold;">&#9888; '.JText::sprintf('HK_IMPORT_INVALID_PATH', $this->hkPath).'</p>';
					$returnString .= JText::sprintf('HK_IMPORT_BACKUP_AND_START', '<a '.$this->linkstyle.' href="'.hikashop_completeLink('import&task=import&importfrom=hk&'.$this->token.'=1&import=1').'">'.JText::_('HIKA_NEXT').'</a>');
				}
			}
		}
		$returnString .= '<a'.$this->linkstyle.' href="'.hikashop_completeLink('import&task=show').'">'.JText::_('HIKA_BACK').'</a>';
		return $returnString;
	}

	function doImport()
	{
		if( $this->db == null )
			return false;

		$this->loadConfiguration();

		$current = $this->options->current;
		$ret = true;
		$next = false;

		$totalSteps = 20;
		$state = (int)$this->options->state;

		$baseProgress = ($state / $totalSteps) * 100;
		$stepProgress = 0;

		switch($state) {
			case 8:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('tax'));
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('tax')." WHERE tax_namekey <= ".$this->db->quote($this->options->last_hk_taxrate)) / $total);
				break;
			case 9:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('category')." WHERE category_type = 'product'");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('category')." WHERE category_type = 'product' AND category_id <= ".(int)$this->options->last_hk_cat) / $total);
				break;
			case 10:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('product')." WHERE product_type = 'main'");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('product')." WHERE product_type = 'main' AND product_id <= ".(int)$this->options->last_hk_prod) / $total);
				break;
			case 11:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('product')." WHERE product_type = 'variant'");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('product')." WHERE product_type = 'variant' AND product_id <= ".(int)$this->options->last_hk_var) / $total);
				break;
			case 12:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('price'));
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('price')." WHERE price_id <= ".(int)$this->options->last_hk_price) / $total);
				break;
			case 13:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('file'));
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('file')." WHERE file_id <= ".(int)$this->options->last_hk_file) / $total);
				break;
			case 14:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('user'));
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('user')." WHERE user_id <= ".(int)$this->options->last_hk_user) / $total);
				break;
			case 15:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('address'));
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('address')." WHERE address_id <= ".(int)$this->options->last_hk_address) / $total);
				break;
			case 16:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('discount'));
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('discount')." WHERE discount_id <= ".(int)$this->options->last_hk_coupon) / $total);
				break;
			case 17:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('order'));
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('order')." WHERE order_id <= ".(int)$this->options->last_hk_order) / $total);
				break;
			case 18:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('order_product'));
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('order_product')." WHERE order_product_id <= ".(int)$this->options->last_hk_item) / $total);
				break;
			case 19:
				$total = $this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('limit'));
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM ".$this->sourceQuery('limit')." WHERE limit_id <= ".(int)$this->options->last_hk_limit) / $total);
				break;
		}

		$this->displayProgressBar($baseProgress + ($stepProgress * (100 / $totalSteps)));

		switch($this->options->state)
		{
			case 0:
				$next = $this->createTables();
				break;
			case 1:
				$next = $this->importConfig();
				break;
			case 2:
				$next = $this->importCustomFields();
				break;
			case 3:
				$next = $this->importZones();
				break;
			case 4:
				$next = $this->importWarehouses();
				break;
			case 5:
				$next = $this->importOrderStatuses();
				break;
			case 6:
				$next = $this->importShippingMethods();
				break;
			case 7:
				$next = $this->importPaymentMethods();
				break;
			case 8:
				$next = $this->importTaxes();
				break;
			case 9:
				$next = $this->importCategories();
				break;
			case 10:
				$next = $this->importProducts();
				break;
			case 11:
				$next = $this->importProductVariations();
				break;
			case 12:
				$next = $this->importProductPrices();
				break;
			case 13:
				$next = $this->importProductImages();
				break;
			case 14:
				$next = $this->importUsers();
				break;
			case 15:
				$next = $this->importAddresses();
				break;
			case 16:
				$next = $this->importCoupons();
				break;
			case 17:
				$next = $this->importOrders();
				break;
			case 18:
				$next = $this->importOrderProducts();
				break;
			case 19:
				$next = $this->importLimits();
				break;
			case 20:
				$next = $this->finishImport();
				$ret = false;
				break;
			default:
				$ret = false;
				break;
		}

		if( $ret && $next )
		{
			$sql =  "UPDATE `#__hikashop_config` SET config_value=(config_value+1) WHERE config_namekey = 'hk_import_state'; ";
			$this->db->setQuery($sql);
			$this->db->execute();
			$sql = "UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'hk_import_current';";
			$this->db->setQuery($sql);
			$this->db->execute();
			$this->refreshPage = true;
		}
		else if( $current != $this->options->current )
		{
			$sql =  "UPDATE `#__hikashop_config` SET config_value=".$this->options->current." WHERE config_namekey = 'hk_import_current';";
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
			'hk_import_state',
			'hk_import_current',
			'hk_import_main_cat_id',
			'hk_import_max_hk_cat',
			'hk_import_max_hk_prod',
			'hk_import_last_hk_cat',
			'hk_import_last_hk_prod',
			'hk_import_last_hk_user',
			'hk_import_last_hk_order',
			'hk_import_last_hk_taxrate',
			'hk_import_last_hk_coupon',
			'hk_import_last_hk_item',
			'hk_import_last_hk_var',
			'hk_import_last_hk_price',
			'hk_import_last_hk_file',
			'hk_import_last_hk_address',
			'hk_import_last_hk_limit'
		);
		$this->db->setQuery('SELECT config_namekey, config_value FROM `#__hikashop_config` WHERE config_namekey IN ('."'".implode("','",$data)."'".');');
		$result = $this->db->loadObjectList();

		if (!empty($result))
		{
			foreach($result as $o)
			{
				if( substr($o->config_namekey, 0, 10) == 'hk_import_' )
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

		if( !isset($this->options->state) || empty($this->options->main_cat_id) )
		{
			if(!isset($this->options->state)) {
				$this->options->state = 0;
				$this->options->current = 0;
				$this->options->last_hk_cat = 0;
				$this->options->last_hk_prod = 0;
				$this->options->last_hk_user = 0;
				$this->options->last_hk_order = 0;
				$this->options->last_hk_taxrate = 0;
				$this->options->last_hk_coupon = 0;
				$this->options->last_hk_item = 0;
				$this->options->last_hk_var = 0;
				$this->options->last_hk_price = 0;
				$this->options->last_hk_file = 0;
				$this->options->last_hk_address = 0;
				$this->options->last_hk_limit = 0;
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
				'hk_import_state' => $this->options->state,
				'hk_import_current' => $this->options->current,
				'hk_import_main_cat_id' => $this->options->main_cat_id,
				'hk_import_max_hk_cat' => $this->options->max_hk_cat,
				'hk_import_max_hk_prod' => $this->options->max_hk_prod,
				'hk_import_last_hk_cat' => $this->options->last_hk_cat,
				'hk_import_last_hk_prod' => $this->options->last_hk_prod,
				'hk_import_last_hk_user' => $this->options->last_hk_user,
				'hk_import_last_hk_order' => $this->options->last_hk_order,
				'hk_import_last_hk_taxrate' => $this->options->last_hk_taxrate,
				'hk_import_last_hk_coupon' => $this->options->last_hk_coupon,
				'hk_import_last_hk_item' => $this->options->last_hk_item,
				'hk_import_last_hk_var' => $this->options->last_hk_var,
				'hk_import_last_hk_price' => $this->options->last_hk_price,
				'hk_import_last_hk_file' => $this->options->last_hk_file,
				'hk_import_last_hk_address' => $this->options->last_hk_address,
				'hk_import_last_hk_limit' => $this->options->last_hk_limit
			);

			foreach($configData as $nk => $nv) {
				$this->db->setQuery("INSERT INTO `#__hikashop_config` (config_namekey, config_value) VALUES (".$this->db->quote($nk).", ".$this->db->quote($nv).") ON DUPLICATE KEY UPDATE config_value = ".$this->db->quote($nv));
				$this->db->execute();
			}
		}
	}

	function sourceQuery($table) {
		return '`'.$this->hkDatabase.'`.`'.$this->hkPrefix.$table.'`';
	}

	function cmsSourceQuery($table) {
		return '`'.$this->hkDatabase.'`.`'.$this->hkCmsPrefix.$table.'`';
	}

	function createTables()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 0).' :</span> '.JText::_('HK_IMPORT_INITIALIZATION').'</p>';

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_hkimport_prod` (`source_id` int(11) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`source_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_hkimport_cat` (`source_id` int(11) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`source_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_hkimport_user` (`source_id` int(11) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`source_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_hkimport_order` (`source_id` int(11) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`source_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_hkimport_shipping` (`source_id` int(11) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`source_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_hkimport_payment` (`source_id` int(11) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`source_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_hkimport_warehouse` (`source_id` int(11) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`source_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$databaseHelper = hikashop_get('helper.database');
		$databaseHelper->addColumns('order','`order_hkimport_id` int(11) NULL');
		$databaseHelper->addColumns('order','INDEX ( `order_hkimport_id` )');

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::_('HK_IMPORT_TABLES_CREATED').'</p>';

		return true;
	}

	function importTaxes()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 8).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('TAXES')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_hk_taxrate = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'hk_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'hk_import_last_hk_taxrate'");
			$this->db->execute();
		}

		$importedTax = 0;
		$importedCat = 0;
		$importedRules = 0;

		$sql = "SELECT * FROM ".$this->sourceQuery('tax')." ORDER BY tax_namekey ASC";
		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(!empty($rows)) {
			foreach($rows as $row) {
				if(!isset($row->tax_namekey) || trim((string)$row->tax_namekey) === '')
					continue;

				$this->db->setQuery("SELECT tax_namekey FROM `#__hikashop_tax` WHERE tax_namekey = ".$this->db->quote($row->tax_namekey));
				if($this->db->loadResult() !== null) continue;

				$tax = new stdClass();
				$tax->tax_namekey = $row->tax_namekey;
				$tax->tax_rate = $row->tax_rate;
				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_tax` (`tax_namekey`, `tax_rate`) VALUES (".$this->db->quote($tax->tax_namekey).", ".$this->db->quote($tax->tax_rate).")");
				$this->db->execute();
				$importedTax++;
			}
		}

		$sql = "SELECT * FROM ".$this->sourceQuery('category')." WHERE category_type = 'tax' ORDER BY category_id ASC";
		$this->db->setQuery($sql);
		$taxCats = $this->db->loadObjectList();

		$taxCatMap = array();
		if(!empty($taxCats)) {
			$element = 'tax';
			$categoryClass = hikashop_get('class.category');
			$categoryClass->getMainElement($element);

			foreach($taxCats as $row) {
				$this->db->setQuery("SELECT category_id FROM `#__hikashop_category` WHERE category_namekey = ".$this->db->quote($row->category_namekey));
				$existingId = (int)$this->db->loadResult();
				if($existingId) {
					$taxCatMap[$row->category_id] = $existingId;
					continue;
				}

				$cat = new stdClass();
				$cat->category_type = 'tax';
				$cat->category_name = $row->category_name;
				$cat->category_published = $row->category_published;
				$cat->category_parent_id = (int)$element;
				$cat->category_namekey = $row->category_namekey;

				$hkId = $categoryClass->save($cat);
				if($hkId) {
					$taxCatMap[$row->category_id] = $hkId;
					$importedCat++;
				}
			}
		}

		$sql = "SELECT * FROM ".$this->sourceQuery('taxation')." ORDER BY taxation_id ASC";
		$this->db->setQuery($sql);
		$taxations = $this->db->loadObjectList();

		if(!empty($taxations)) {
			foreach($taxations as $row) {
				$this->db->setQuery("SELECT taxation_id FROM `#__hikashop_taxation` WHERE tax_namekey = ".$this->db->quote($row->tax_namekey)." AND category_namekey = ".$this->db->quote($row->category_namekey)." AND zone_namekey = ".$this->db->quote($row->zone_namekey));
				if($this->db->loadResult()) continue;

				$taxation = new stdClass();
				$taxation->zone_namekey = $row->zone_namekey;
				$taxation->category_namekey = $row->category_namekey;
				$taxation->tax_namekey = $row->tax_namekey;
				$taxation->taxation_published = $row->taxation_published;
				$taxation->taxation_type = isset($row->taxation_type) ? $row->taxation_type : '';
				$taxation->taxation_site_id = isset($row->taxation_site_id) ? $row->taxation_site_id : '';
				$taxation->taxation_internal_code = isset($row->taxation_internal_code) ? $row->taxation_internal_code : '';
				$taxation->taxation_cumulative = isset($row->taxation_cumulative) ? $row->taxation_cumulative : 0;
				$taxation->taxation_post_code = isset($row->taxation_post_code) ? $row->taxation_post_code : '';
				$taxation->taxation_date_start = isset($row->taxation_date_start) ? $row->taxation_date_start : 0;
				$taxation->taxation_date_end = isset($row->taxation_date_end) ? $row->taxation_date_end : 0;

				$this->db->insertObject('#__hikashop_taxation', $taxation);
				$importedRules++;
			}
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('RATES'), $importedTax).'</p>';
		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('TAXATION_CATEGORIES'), $importedCat).'</p>';
		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('TAXATIONS'), $importedRules).'</p>';

		return true;
	}

	function importCategories()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 9).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('HIKA_CATEGORIES')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_hk_cat = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'hk_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'hk_import_last_hk_cat'");
			$this->db->execute();
		}

		$categoryClass = hikashop_get('class.category');
		$count = 100;

		$sql = "SELECT * FROM ".$this->sourceQuery('category').
			   " WHERE category_type = 'product'".
			   " AND category_id > " . (int)$this->options->last_hk_cat .
			   " ORDER BY category_depth ASC, category_ordering ASC, category_id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('HIKA_CATEGORIES'))).'</p>';
			$this->importRebuildTree();
			return true;
		}

		$imported = 0;

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_hkimport_cat` WHERE source_id = ".(int)$row->category_id);
			if($this->db->loadResult()) {
				$this->options->last_hk_cat = $row->category_id;
				continue;
			}

			if(!empty($row->category_namekey)) {
				$this->db->setQuery("SELECT category_id FROM #__hikashop_category WHERE category_namekey = ".$this->db->quote($row->category_namekey));
				$existingId = (int)$this->db->loadResult();
				if($existingId) {
					$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_hkimport_cat` (source_id, hk_id) VALUES (".(int)$row->category_id.", ".$existingId.")");
					$this->db->execute();
					$this->options->last_hk_cat = $row->category_id;
					continue;
				}
			}

			$cat = new stdClass();
			$cat->category_type = 'product';
			$cat->category_name = $row->category_name;
			$cat->category_description = isset($row->category_description) ? $row->category_description : '';
			$cat->category_published = $row->category_published;
			$cat->category_alias = isset($row->category_alias) ? $row->category_alias : '';
			$cat->category_ordering = isset($row->category_ordering) ? $row->category_ordering : 0;
			$cat->category_meta_description = isset($row->category_meta_description) ? $row->category_meta_description : '';
			$cat->category_keywords = isset($row->category_keywords) ? $row->category_keywords : '';
			$cat->category_created = isset($row->category_created) ? $row->category_created : time();
			$cat->category_modified = isset($row->category_modified) ? $row->category_modified : time();
			$cat->category_access = isset($row->category_access) ? $row->category_access : 'all';
			$cat->category_layout = isset($row->category_layout) ? $row->category_layout : '';
			$cat->category_quantity_layout = isset($row->category_quantity_layout) ? $row->category_quantity_layout : '';
			$cat->category_menu = isset($row->category_menu) ? $row->category_menu : 0;

			if(empty($row->category_parent_id)) {
				$cat->category_parent_id = $this->options->main_cat_id;
			} else {
				$this->db->setQuery("SELECT hk_id FROM `#__hikashop_hkimport_cat` WHERE source_id = ".(int)$row->category_parent_id);
				$parentId = $this->db->loadResult();
				if($parentId) {
					$cat->category_parent_id = $parentId;
				} else {
					$cat->category_parent_id = $this->options->main_cat_id;
				}
			}

			$hkId = $categoryClass->save($cat);

			if($hkId) {
				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_hkimport_cat` (source_id, hk_id) VALUES (".(int)$row->category_id.", ".(int)$hkId.")");
				$this->db->execute();
				$imported++;
			} else {
				echo '<p style="color:red">'.JText::sprintf('HK_IMPORT_SAVE_FAILED', $row->category_name, '(Source ID: '.(int)$row->category_id.')').'</p>';
			}

			$this->options->last_hk_cat = $row->category_id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('HIKA_CATEGORIES'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_hk_cat." WHERE config_namekey = 'hk_import_last_hk_cat'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function importProducts()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 10).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('PRODUCTS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_hk_prod = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'hk_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'hk_import_last_hk_prod'");
			$this->db->execute();
		}

		$count = 500;
		$imported = 0;

		$sql = "SELECT * FROM ".$this->sourceQuery('product').
			   " WHERE product_type = 'main'".
			   " AND product_id > " . (int)$this->options->last_hk_prod .
			   " ORDER BY product_id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('PRODUCTS'))).'</p>';
			return true;
		}

		$sourceIds = array();
		$sourceWarehouseIds = array();
		$sourceTaxCatIds = array();
		foreach($rows as $row) {
			$sourceIds[] = (int)$row->product_id;
			if(!empty($row->product_warehouse_id))
				$sourceWarehouseIds[(int)$row->product_warehouse_id] = 1;
			if(!empty($row->product_tax_id))
				$sourceTaxCatIds[(int)$row->product_tax_id] = 1;
		}

		$alreadyMapped = array();
		$this->db->setQuery("SELECT source_id FROM `#__hikashop_hkimport_prod` WHERE source_id IN (".implode(',', $sourceIds).")");
		$mapped = $this->db->loadColumn();
		if(!empty($mapped))
			$alreadyMapped = array_flip($mapped);

		$warehouseMap = array();
		if(!empty($sourceWarehouseIds)) {
			$this->db->setQuery("SELECT source_id, hk_id FROM `#__hikashop_hkimport_warehouse` WHERE source_id IN (".implode(',', array_keys($sourceWarehouseIds)).")");
			$whRows = $this->db->loadObjectList();
			if(!empty($whRows)) {
				foreach($whRows as $w)
					$warehouseMap[(int)$w->source_id] = (int)$w->hk_id;
			}
		}

		$taxCatMap = array();
		if(!empty($sourceTaxCatIds)) {
			$this->db->setQuery("SELECT category_id, category_namekey FROM ".$this->sourceQuery('category')." WHERE category_id IN (".implode(',', array_keys($sourceTaxCatIds)).")");
			$srcTaxCats = $this->db->loadObjectList();
			if(!empty($srcTaxCats)) {
				$namekeys = array();
				$idToNamekey = array();
				foreach($srcTaxCats as $tc) {
					$namekeys[] = $this->db->quote($tc->category_namekey);
					$idToNamekey[(int)$tc->category_id] = $tc->category_namekey;
				}
				$this->db->setQuery("SELECT category_id, category_namekey FROM `#__hikashop_category` WHERE category_namekey IN (".implode(',', $namekeys).")");
				$localTaxCats = $this->db->loadObjectList();
				$namekeyToLocalId = array();
				if(!empty($localTaxCats)) {
					foreach($localTaxCats as $ltc)
						$namekeyToLocalId[$ltc->category_namekey] = (int)$ltc->category_id;
				}
				foreach($idToNamekey as $srcId => $nk) {
					if(isset($namekeyToLocalId[$nk]))
						$taxCatMap[$srcId] = $namekeyToLocalId[$nk];
				}
			}
		}

		$this->db->setQuery("SELECT product_id, category_id FROM ".$this->sourceQuery('product_category')." WHERE product_id IN (".implode(',', $sourceIds).")");
		$srcProdCats = $this->db->loadObjectList();
		$prodCatMap = array();
		if(!empty($srcProdCats)) {
			foreach($srcProdCats as $pc) {
				$pid = (int)$pc->product_id;
				if(!isset($prodCatMap[$pid]))
					$prodCatMap[$pid] = array();
				$prodCatMap[$pid][] = (int)$pc->category_id;
			}
		}

		$allSrcCatIds = array();
		foreach($prodCatMap as $catIds) {
			foreach($catIds as $cid)
				$allSrcCatIds[$cid] = 1;
		}

		$catMap = array();
		if(!empty($allSrcCatIds)) {
			$this->db->setQuery("SELECT source_id, hk_id FROM `#__hikashop_hkimport_cat` WHERE source_id IN (".implode(',', array_keys($allSrcCatIds)).")");
			$catRows = $this->db->loadObjectList();
			if(!empty($catRows)) {
				foreach($catRows as $c)
					$catMap[(int)$c->source_id] = (int)$c->hk_id;
			}
		}

		foreach($rows as $row) {
			if(isset($alreadyMapped[(int)$row->product_id])) {
				$this->options->last_hk_prod = $row->product_id;
				continue;
			}

			$prod = new stdClass();
			$prod->product_name = $row->product_name;
			$prod->product_description = $row->product_description;
			$prod->product_code = $row->product_code;
			$prod->product_published = $row->product_published;
			$prod->product_type = 'main';
			$prod->product_quantity = $row->product_quantity;
			$prod->product_alias = isset($row->product_alias) ? $row->product_alias : '';
			$prod->product_created = $row->product_created;
			$prod->product_modified = $row->product_modified;
			$prod->product_sale_start = isset($row->product_sale_start) ? $row->product_sale_start : 0;
			$prod->product_sale_end = isset($row->product_sale_end) ? $row->product_sale_end : 0;
			$prod->product_meta_description = isset($row->product_meta_description) ? $row->product_meta_description : '';
			$prod->product_keywords = isset($row->product_keywords) ? $row->product_keywords : '';
			$prod->product_url = isset($row->product_url) ? $row->product_url : '';
			$prod->product_weight = isset($row->product_weight) ? $row->product_weight : 0;
			$prod->product_weight_unit = isset($row->product_weight_unit) ? $row->product_weight_unit : 'kg';
			$prod->product_dimension_unit = isset($row->product_dimension_unit) ? $row->product_dimension_unit : 'm';
			$prod->product_width = isset($row->product_width) ? $row->product_width : 0;
			$prod->product_length = isset($row->product_length) ? $row->product_length : 0;
			$prod->product_height = isset($row->product_height) ? $row->product_height : 0;
			$prod->product_max_per_order = isset($row->product_max_per_order) ? $row->product_max_per_order : 0;
			$prod->product_min_per_order = isset($row->product_min_per_order) ? $row->product_min_per_order : 0;
			$prod->product_layout = isset($row->product_layout) ? $row->product_layout : '';
			$prod->product_quantity_layout = isset($row->product_quantity_layout) ? $row->product_quantity_layout : '';
			$prod->product_warehouse_id = 0;
			if(!empty($row->product_warehouse_id))
				$prod->product_warehouse_id = isset($warehouseMap[(int)$row->product_warehouse_id]) ? $warehouseMap[(int)$row->product_warehouse_id] : 0;
			$prod->product_condition = isset($row->product_condition) ? $row->product_condition : '';
			$prod->product_access = isset($row->product_access) ? $row->product_access : 'all';
			$prod->product_group_after_purchase = isset($row->product_group_after_purchase) ? $row->product_group_after_purchase : '';
			$prod->product_msrp = isset($row->product_msrp) ? $row->product_msrp : 0;

			if(!empty($row->product_tax_id) && isset($taxCatMap[(int)$row->product_tax_id]))
				$prod->product_tax_id = $taxCatMap[(int)$row->product_tax_id];

			$categories = array();
			$srcCatIds = isset($prodCatMap[(int)$row->product_id]) ? $prodCatMap[(int)$row->product_id] : array();
			if(!empty($srcCatIds)) {
				foreach($srcCatIds as $srcCatId) {
					if(isset($catMap[$srcCatId]))
						$categories[] = $catMap[$srcCatId];
				}
			}
			if(empty($categories)) $categories = array($this->options->main_cat_id);

			$this->db->insertObject('#__hikashop_product', $prod, 'product_id');
			$hkId = (int)$prod->product_id;

			if($hkId) {
				foreach($categories as $catId) {
					$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_product_category` (product_id, category_id) VALUES (".(int)$hkId.", ".(int)$catId.")");
					$this->db->execute();
				}

				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_hkimport_prod` (source_id, hk_id) VALUES (".(int)$row->product_id.", ".(int)$hkId.")");
				$this->db->execute();
				$this->copyCustomFieldValues('product', $row, $hkId);
				$imported++;
			}
			$this->options->last_hk_prod = $row->product_id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('PRODUCTS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_hk_prod." WHERE config_namekey = 'hk_import_last_hk_prod'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function importProductVariations()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 11).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('VARIANTS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_hk_var = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'hk_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'hk_import_last_hk_var'");
			$this->db->execute();
		}

		$count = 500;
		$imported = 0;

		$sql = "SELECT * FROM ".$this->sourceQuery('product').
			   " WHERE product_type = 'variant'".
			   " AND product_id > " . (int)$this->options->last_hk_var .
			   " ORDER BY product_id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('VARIANTS'))).'</p>';
			return true;
		}

		$sourceIds = array();
		$sourceParentIds = array();
		$sourceTaxCatIds = array();
		foreach($rows as $row) {
			$sourceIds[] = (int)$row->product_id;
			if(!empty($row->product_parent_id))
				$sourceParentIds[(int)$row->product_parent_id] = 1;
			if(!empty($row->product_tax_id))
				$sourceTaxCatIds[(int)$row->product_tax_id] = 1;
		}

		$alreadyMapped = array();
		$this->db->setQuery("SELECT source_id FROM `#__hikashop_hkimport_prod` WHERE source_id IN (".implode(',', $sourceIds).")");
		$mapped = $this->db->loadColumn();
		if(!empty($mapped))
			$alreadyMapped = array_flip($mapped);

		$parentMap = array();
		if(!empty($sourceParentIds)) {
			$this->db->setQuery("SELECT source_id, hk_id FROM `#__hikashop_hkimport_prod` WHERE source_id IN (".implode(',', array_keys($sourceParentIds)).")");
			$parentRows = $this->db->loadObjectList();
			if(!empty($parentRows)) {
				foreach($parentRows as $pr)
					$parentMap[(int)$pr->source_id] = (int)$pr->hk_id;
			}
		}

		$taxCatMap = array();
		if(!empty($sourceTaxCatIds)) {
			$this->db->setQuery("SELECT category_id, category_namekey FROM ".$this->sourceQuery('category')." WHERE category_id IN (".implode(',', array_keys($sourceTaxCatIds)).")");
			$srcTaxCats = $this->db->loadObjectList();
			if(!empty($srcTaxCats)) {
				$namekeys = array();
				$idToNamekey = array();
				foreach($srcTaxCats as $tc) {
					$namekeys[] = $this->db->quote($tc->category_namekey);
					$idToNamekey[(int)$tc->category_id] = $tc->category_namekey;
				}
				$this->db->setQuery("SELECT category_id, category_namekey FROM `#__hikashop_category` WHERE category_namekey IN (".implode(',', $namekeys).")");
				$localTaxCats = $this->db->loadObjectList();
				$namekeyToLocalId = array();
				if(!empty($localTaxCats)) {
					foreach($localTaxCats as $ltc)
						$namekeyToLocalId[$ltc->category_namekey] = (int)$ltc->category_id;
				}
				foreach($idToNamekey as $srcId => $nk) {
					if(isset($namekeyToLocalId[$nk]))
						$taxCatMap[$srcId] = $namekeyToLocalId[$nk];
				}
			}
		}

		foreach($rows as $row) {
			if(isset($alreadyMapped[(int)$row->product_id])) {
				$this->options->last_hk_var = $row->product_id;
				continue;
			}

			$parentId = isset($parentMap[(int)$row->product_parent_id]) ? $parentMap[(int)$row->product_parent_id] : 0;

			if(!$parentId) {
				$this->options->last_hk_var = $row->product_id;
				continue;
			}

			$prod = new stdClass();
			$prod->product_parent_id = $parentId;
			$prod->product_type = 'variant';
			$prod->product_name = $row->product_name;
			$prod->product_code = $row->product_code;
			$prod->product_published = $row->product_published;
			$prod->product_quantity = $row->product_quantity;
			$prod->product_alias = isset($row->product_alias) ? $row->product_alias : '';
			$prod->product_created = $row->product_created;
			$prod->product_modified = $row->product_modified;
			$prod->product_weight = isset($row->product_weight) ? $row->product_weight : 0;
			$prod->product_weight_unit = isset($row->product_weight_unit) ? $row->product_weight_unit : '';
			$prod->product_dimension_unit = isset($row->product_dimension_unit) ? $row->product_dimension_unit : '';
			$prod->product_width = isset($row->product_width) ? $row->product_width : 0;
			$prod->product_length = isset($row->product_length) ? $row->product_length : 0;
			$prod->product_height = isset($row->product_height) ? $row->product_height : 0;

			if(!empty($row->product_tax_id) && isset($taxCatMap[(int)$row->product_tax_id]))
				$prod->product_tax_id = $taxCatMap[(int)$row->product_tax_id];

			$this->db->insertObject('#__hikashop_product', $prod, 'product_id');
			$hkId = (int)$prod->product_id;

			if($hkId) {
				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_hkimport_prod` (source_id, hk_id) VALUES (".(int)$row->product_id.", ".(int)$hkId.")");
				$this->db->execute();

				$sql = "SELECT v.variant_characteristic_id, c.characteristic_parent_id, c.characteristic_value, c.characteristic_alias, ".
					   "c.characteristic_display_type, c.characteristic_ordering ".
					   "FROM ".$this->sourceQuery('variant')." AS v ".
					   "INNER JOIN ".$this->sourceQuery('characteristic')." AS c ON v.variant_characteristic_id = c.characteristic_id ".
					   "WHERE v.variant_product_id = ".(int)$row->product_id;
				$this->db->setQuery($sql);
				$varChars = $this->db->loadObjectList();

				if(!empty($varChars)) {
					foreach($varChars as $vc) {
						if($vc->characteristic_parent_id == 0) {
							$localCharId = $this->findOrCreateCharacteristic($vc, 0);
							if($localCharId) {
								$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_variant` (variant_product_id, variant_characteristic_id, ordering) VALUES (".(int)$parentId.", ".(int)$localCharId.", 0)");
								$this->db->execute();
							}
						} else {
							$this->db->setQuery("SELECT * FROM ".$this->sourceQuery('characteristic')." WHERE characteristic_id = ".(int)$vc->characteristic_parent_id);
							$parentChar = $this->db->loadObject();
							$localParentCharId = 0;
							if($parentChar) {
								$localParentCharId = $this->findOrCreateCharacteristic($parentChar, 0);
								if($localParentCharId) {
									$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_variant` (variant_product_id, variant_characteristic_id, ordering) VALUES (".(int)$parentId.", ".(int)$localParentCharId.", 0)");
									$this->db->execute();
								}
							}

							$localValId = $this->findOrCreateCharacteristic($vc, $localParentCharId);
							if($localValId) {
								$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_variant` (variant_product_id, variant_characteristic_id, ordering) VALUES (".(int)$hkId.", ".(int)$localValId.", 0)");
								$this->db->execute();
								$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_variant` (variant_product_id, variant_characteristic_id, ordering) VALUES (".(int)$parentId.", ".(int)$localValId.", ".(int)$vc->characteristic_ordering.")");
								$this->db->execute();
							}
						}
					}
				}

				$this->copyCustomFieldValues('product', $row, $hkId);
				$imported++;
			}

			$this->options->last_hk_var = $row->product_id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('VARIANTS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_hk_var." WHERE config_namekey = 'hk_import_last_hk_var'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function findOrCreateCharacteristic($charObj, $parentCharId) {
		static $charCache = array();

		$cacheKey = (int)$parentCharId . '_' . $charObj->characteristic_alias;
		if(isset($charCache[$cacheKey])) return $charCache[$cacheKey];

		if($parentCharId == 0) {
			$this->db->setQuery("SELECT characteristic_id FROM `#__hikashop_characteristic` WHERE characteristic_parent_id = 0 AND characteristic_alias = ".$this->db->quote($charObj->characteristic_alias));
		} else {
			$this->db->setQuery("SELECT characteristic_id FROM `#__hikashop_characteristic` WHERE characteristic_parent_id = ".(int)$parentCharId." AND characteristic_alias = ".$this->db->quote($charObj->characteristic_alias));
		}
		$existingId = (int)$this->db->loadResult();

		if($existingId) {
			$charCache[$cacheKey] = $existingId;
			return $existingId;
		}

		$newChar = new stdClass();
		$newChar->characteristic_parent_id = $parentCharId;
		$newChar->characteristic_value = $charObj->characteristic_value;
		$newChar->characteristic_alias = $charObj->characteristic_alias;
		if($parentCharId == 0) {
			$newChar->characteristic_display_type = isset($charObj->characteristic_display_type) ? $charObj->characteristic_display_type : 'list';
		}
		$newChar->characteristic_ordering = isset($charObj->characteristic_ordering) ? $charObj->characteristic_ordering : 0;

		$this->db->insertObject('#__hikashop_characteristic', $newChar, 'characteristic_id');
		$newId = (int)$newChar->characteristic_id;

		$charCache[$cacheKey] = $newId;
		return $newId;
	}

	function importProductPrices()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 12).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('PRICES')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_hk_price = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'hk_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'hk_import_last_hk_price'");
			$this->db->execute();
		}

		$count = 500;
		$imported = 0;

		$sql = "SELECT * FROM ".$this->sourceQuery('price').
			   " WHERE price_id > " . (int)$this->options->last_hk_price .
			   " ORDER BY price_id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('PRICES'))).'</p>';
			return true;
		}

		$sourceProductIds = array();
		foreach($rows as $row) {
			$sourceProductIds[(int)$row->price_product_id] = 1;
		}

		$prodMap = array();
		if(!empty($sourceProductIds)) {
			$this->db->setQuery("SELECT source_id, hk_id FROM `#__hikashop_hkimport_prod` WHERE source_id IN (".implode(',', array_keys($sourceProductIds)).")");
			$prodRows = $this->db->loadObjectList();
			if(!empty($prodRows)) {
				foreach($prodRows as $p)
					$prodMap[(int)$p->source_id] = (int)$p->hk_id;
			}
		}

		foreach($rows as $row) {
			$hkProdId = isset($prodMap[(int)$row->price_product_id]) ? $prodMap[(int)$row->price_product_id] : 0;

			if(!$hkProdId) {
				$this->options->last_hk_price = $row->price_id;
				continue;
			}

			$price = new stdClass();
			$price->price_product_id = $hkProdId;
			$price->price_value = $row->price_value;
			$price->price_currency_id = $row->price_currency_id;
			$price->price_min_quantity = isset($row->price_min_quantity) ? $row->price_min_quantity : 0;
			$price->price_access = isset($row->price_access) ? $row->price_access : 'all';
			$price->price_users = isset($row->price_users) ? $row->price_users : '';
			$price->price_start_date = isset($row->price_start_date) ? $row->price_start_date : 0;
			$price->price_end_date = isset($row->price_end_date) ? $row->price_end_date : 0;

			$this->db->insertObject('#__hikashop_price', $price);
			$imported++;

			$this->options->last_hk_price = $row->price_id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('PRICES'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_hk_price." WHERE config_namekey = 'hk_import_last_hk_price'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function importProductImages()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 13).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('IMAGES_AND_FILES')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_hk_file = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'hk_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'hk_import_last_hk_file'");
			$this->db->execute();

			if(empty($this->hkPath)) {
				echo '<p style="color:#c09853; font-weight:bold; margin: 8px 0 8px 20px;">&#9888; '.JText::_('HK_IMPORT_NO_PATH_WARNING').'</p>';
			}
		}

		$count = 100;
		$imported = 0;
		$filesCopied = 0;
		$filesNotFound = 0;
		$filesFailed = 0;

		$sql = "SELECT * FROM ".$this->sourceQuery('file').
			   " WHERE file_id > " . (int)$this->options->last_hk_file .
			   " ORDER BY file_id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('IMAGES_AND_FILES'))).'</p>';
			return true;
		}

		$sourceUploadFolder = $this->getSourceConfig('uploadfolder');
		$sourceSecureFolder = $this->getSourceConfig('uploadsecurefolder');
		if(empty($sourceUploadFolder)) {
			if($this->hkCmsType === 'wordpress')
				$sourceUploadFolder = 'wp-content/uploads/hikashop/';
			else
				$sourceUploadFolder = 'images/com_hikashop/upload/';
		}
		if(empty($sourceSecureFolder)) $sourceSecureFolder = 'media/com_hikashop/upload/secure/';

		$sourceRefIds = array();
		foreach($rows as $row) {
			if($row->file_ref_id > 0)
				$sourceRefIds[(int)$row->file_ref_id] = 1;
		}

		$prodMap = array();
		if(!empty($sourceRefIds)) {
			$this->db->setQuery("SELECT source_id, hk_id FROM `#__hikashop_hkimport_prod` WHERE source_id IN (".implode(',', array_keys($sourceRefIds)).")");
			$prodRows = $this->db->loadObjectList();
			if(!empty($prodRows)) {
				foreach($prodRows as $p)
					$prodMap[(int)$p->source_id] = (int)$p->hk_id;
			}
		}

		$sourceCatIds = array();
		foreach($rows as $row) {
			if($row->file_type === 'category' && $row->file_ref_id > 0)
				$sourceCatIds[(int)$row->file_ref_id] = 1;
		}
		$catMap = array();
		if(!empty($sourceCatIds)) {
			$this->db->setQuery("SELECT source_id, hk_id FROM `#__hikashop_hkimport_cat` WHERE source_id IN (".implode(',', array_keys($sourceCatIds)).")");
			$catRows = $this->db->loadObjectList();
			if(!empty($catRows)) {
				foreach($catRows as $c)
					$catMap[(int)$c->source_id] = (int)$c->hk_id;
			}
		}

		if(!empty($this->hkPath)) {
			if(!is_dir($this->options->uploadfolder)) {
				jimport('joomla.filesystem.folder');
				JFolder::create($this->options->uploadfolder);
			}
			if(!is_dir($this->options->uploadsecurefolder)) {
				jimport('joomla.filesystem.folder');
				JFolder::create($this->options->uploadsecurefolder);
			}
		}

		foreach($rows as $row) {
			$hkRefId = 0;
			if($row->file_ref_id > 0) {
				if($row->file_type === 'category')
					$hkRefId = isset($catMap[(int)$row->file_ref_id]) ? $catMap[(int)$row->file_ref_id] : 0;
				else
					$hkRefId = isset($prodMap[(int)$row->file_ref_id]) ? $prodMap[(int)$row->file_ref_id] : 0;
			}

			if(!$hkRefId && $row->file_ref_id > 0) {
				$this->options->last_hk_file = $row->file_id;
				continue;
			}

			$fileCopied = false;
			$isSecure = (isset($row->file_free_download) && $row->file_free_download == 0 && $row->file_type == 'file');

			if(!empty($this->hkPath) && !empty($row->file_path)) {
				$sourceFolder = $isSecure ? $sourceSecureFolder : $sourceUploadFolder;
				$sourcePath = rtrim($this->hkPath, '/\\') . DS . trim(str_replace('/', DS, $sourceFolder), DS) . DS . str_replace('/', DS, $row->file_path);

				$destFolder = $isSecure ? $this->options->uploadsecurefolder : $this->options->uploadfolder;
				$destPath = $destFolder . basename($row->file_path);

				if(file_exists($sourcePath)) {
					$fileCopied = JFile::copy($sourcePath, $destPath);
					if($fileCopied)
						$filesCopied++;
					else
						$filesFailed++;
				} else {
					$filesNotFound++;
				}
			}

			$file = new stdClass();
			$file->file_name = isset($row->file_name) ? $row->file_name : '';
			$file->file_description = isset($row->file_description) ? $row->file_description : '';
			$file->file_path = $row->file_path;
			$file->file_type = $row->file_type;
			$file->file_ref_id = $hkRefId;
			$file->file_free_download = isset($row->file_free_download) ? $row->file_free_download : 1;
			$file->file_ordering = isset($row->file_ordering) ? $row->file_ordering : 0;
			$file->file_limit = isset($row->file_limit) ? $row->file_limit : 0;

			$this->db->insertObject('#__hikashop_file', $file);
			$imported++;

			$this->options->last_hk_file = $row->file_id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('IMAGES_AND_FILES'), $imported).'</p>';

		if(!empty($this->hkPath) && ($filesCopied || $filesNotFound || $filesFailed)) {
			echo '<p '.$this->pmarginstyle.' style="font-size:0.9em; color:#605F5D;">';
			echo JText::sprintf('HK_IMPORT_FILES_COPIED', $filesCopied);
			if($filesNotFound)
				echo ' &mdash; '.JText::sprintf('HK_IMPORT_FILES_NOT_FOUND', $filesNotFound);
			if($filesFailed)
				echo ' &mdash; '.JText::sprintf('HK_IMPORT_FILES_FAILED', $filesFailed);
			echo '</p>';
		}

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_hk_file." WHERE config_namekey = 'hk_import_last_hk_file'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function getSourceConfig($key) {
		$sql = "SELECT config_value FROM ".$this->sourceQuery('config')." WHERE config_namekey = ".$this->db->quote($key);
		$this->db->setQuery($sql);
		return $this->db->loadResult();
	}

	function importUsers()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 14).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('USERS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_hk_user = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'hk_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'hk_import_last_hk_user'");
			$this->db->execute();
		}

		jimport('joomla.user.helper');
		$userClass = hikashop_get('class.user');

		$count = 50;
		$imported = 0;

		$sql = "SELECT * FROM ".$this->sourceQuery('user').
			   " WHERE user_id > " . (int)$this->options->last_hk_user .
			   " ORDER BY user_id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('USERS'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_hkimport_user` WHERE source_id = ".(int)$row->user_id);
			if($this->db->loadResult()) {
				$this->options->last_hk_user = $row->user_id;
				continue;
			}

			$sourceCmsId = (int)$row->user_cms_id;
			$localCmsId = 0;

			if($sourceCmsId > 0) {
				$cmsUser = $this->readSourceCmsUser($sourceCmsId);

				if($cmsUser && !empty($cmsUser->email)) {
					if(defined('HIKASHOP_WORDPRESS')) {
						$existingWpUser = get_user_by('email', $cmsUser->email);
						if($existingWpUser) {
							$localCmsId = $existingWpUser->ID;
						}
					} else {
						$this->db->setQuery("SELECT id FROM `#__users` WHERE email = ".$this->db->quote($cmsUser->email));
						$localCmsId = (int)$this->db->loadResult();
					}

					if(!$localCmsId) {
						$localCmsId = $this->createCmsUser($cmsUser);
					}
				}
			}

			$hkUser = null;
			if($localCmsId) {
				$hkUser = $userClass->get($localCmsId, 'cms');
			}

			if(empty($hkUser) || empty($hkUser->user_id)) {
				$hkUser = new stdClass();
				$hkUser->user_cms_id = $localCmsId;
				$hkUser->user_email = isset($row->user_email) ? $row->user_email : '';
				if(empty($hkUser->user_email) && isset($cmsUser) && !empty($cmsUser->email)) {
					$hkUser->user_email = $cmsUser->email;
				}
				$hkUser->user_created = isset($row->user_created) ? $row->user_created : time();
				$hkUser->user_partner_activated = isset($row->user_partner_activated) ? $row->user_partner_activated : 0;
				$hkUser->user_params = isset($row->user_params) ? $row->user_params : '';
				$hkUser->user_partner_email = isset($row->user_partner_email) ? $row->user_partner_email : '';

				$userClass->save($hkUser);

				if(!empty($hkUser->user_id) && !empty($row->user_currency_id)) {
					$this->db->setQuery("UPDATE `#__hikashop_user` SET user_currency_id = ".(int)$row->user_currency_id." WHERE user_id = ".(int)$hkUser->user_id);
					$this->db->execute();
				}
			}

			$hkUserId = 0;
			if(!empty($hkUser->user_id)) {
				$hkUserId = $hkUser->user_id;
			} else {
				if($localCmsId) {
					$this->db->setQuery("SELECT user_id FROM `#__hikashop_user` WHERE user_cms_id = ".(int)$localCmsId);
					$hkUserId = (int)$this->db->loadResult();
				}
			}

			if($hkUserId) {
				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_hkimport_user` (source_id, hk_id) VALUES (".(int)$row->user_id.", ".(int)$hkUserId.")");
				$this->db->execute();
				$this->copyCustomFieldValues('user', $row, $hkUserId);
				$imported++;
			}

			$this->options->last_hk_user = $row->user_id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('USERS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_hk_user." WHERE config_namekey = 'hk_import_last_hk_user'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function readSourceCmsUser($sourceCmsId) {
		$user = new stdClass();

		if($this->hkCmsType == 'wordpress') {
			$sql = "SELECT ID, user_login, user_email, user_pass, display_name, user_registered ".
				   "FROM ".$this->cmsSourceQuery('users').
				   " WHERE ID = ".(int)$sourceCmsId;
			$this->db->setQuery($sql);
			$row = $this->db->loadObject();
			if(!$row) return null;

			$user->id = $row->ID;
			$user->name = $row->display_name;
			$user->username = $row->user_login;
			$user->email = $row->user_email;
			$user->password = $row->user_pass;
			$user->registerDate = $row->user_registered;
		} else {
			$sql = "SELECT id, name, username, email, password, registerDate ".
				   "FROM ".$this->cmsSourceQuery('users').
				   " WHERE id = ".(int)$sourceCmsId;
			$this->db->setQuery($sql);
			$row = $this->db->loadObject();
			if(!$row) return null;

			$user->id = $row->id;
			$user->name = $row->name;
			$user->username = $row->username;
			$user->email = $row->email;
			$user->password = $row->password;
			$user->registerDate = $row->registerDate;
		}

		return $user;
	}

	function createCmsUser($cmsUser) {
		if(defined('HIKASHOP_WORDPRESS')) {
			$userdata = array(
				'user_login' => $cmsUser->username,
				'user_email' => $cmsUser->email,
				'display_name' => !empty($cmsUser->name) ? $cmsUser->name : $cmsUser->username,
				'user_pass' => wp_generate_password(12, true, true),
				'role' => 'subscriber',
			);

			$user_id = wp_insert_user($userdata);
			if(is_wp_error($user_id)) {
				$userdata['user_login'] = $cmsUser->username . '_' . $cmsUser->id;
				$user_id = wp_insert_user($userdata);
				if(is_wp_error($user_id)) return 0;
			}

			global $wpdb;
			$wpdb->update($wpdb->users, array('user_pass' => $cmsUser->password), array('ID' => $user_id));

			return $user_id;
		} else {
			$user = new JUser();
			$user->name = $cmsUser->name;
			$user->username = $cmsUser->username;
			$user->email = $cmsUser->email;
			$user->password = $cmsUser->password; // Store source hash directly
			$user->registerDate = $cmsUser->registerDate;
			$user->groups = array(2);

			$this->db->setQuery("SELECT id FROM `#__users` WHERE username = ".$this->db->quote($cmsUser->username));
			if($this->db->loadResult()) {
				$user->username = $cmsUser->username . '_' . $cmsUser->id;
			}

			if(!$user->save()) {
				echo '<p style="color:red">'.JText::sprintf('HK_IMPORT_USER_SAVE_ERROR', $cmsUser->email, $user->getError()).'</p>';
				return 0;
			}
			return $user->id;
		}
	}

	function importAddresses()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 15).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('ADDRESSES')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_hk_address = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'hk_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'hk_import_last_hk_address'");
			$this->db->execute();
		}

		$count = 500;
		$imported = 0;

		$sql = "SELECT * FROM ".$this->sourceQuery('address').
			   " WHERE address_id > " . (int)$this->options->last_hk_address .
			   " ORDER BY address_id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('ADDRESSES'))).'</p>';
			return true;
		}

		$addressFields = $this->getTableColumns('address');

		$sourceUserIds = array();
		foreach($rows as $row) {
			if(!empty($row->address_user_id))
				$sourceUserIds[(int)$row->address_user_id] = 1;
		}

		$userMap = array();
		if(!empty($sourceUserIds)) {
			$this->db->setQuery("SELECT source_id, hk_id FROM `#__hikashop_hkimport_user` WHERE source_id IN (".implode(',', array_keys($sourceUserIds)).")");
			$userRows = $this->db->loadObjectList();
			if(!empty($userRows)) {
				foreach($userRows as $u)
					$userMap[(int)$u->source_id] = (int)$u->hk_id;
			}
		}

		foreach($rows as $row) {
			$hkUserId = 0;
			if(!empty($row->address_user_id))
				$hkUserId = isset($userMap[(int)$row->address_user_id]) ? $userMap[(int)$row->address_user_id] : 0;

			if(!$hkUserId && !empty($row->address_user_id)) {
				$this->options->last_hk_address = $row->address_id;
				continue;
			}

			$addr = new stdClass();
			foreach($addressFields as $field) {
				if($field == 'address_id') continue;
				if(isset($row->$field)) $addr->$field = $row->$field;
			}
			$addr->address_user_id = $hkUserId;

			$this->db->insertObject('#__hikashop_address', $addr, 'address_id');
			$imported++;

			$this->options->last_hk_address = $row->address_id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('ADDRESSES'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_hk_address." WHERE config_namekey = 'hk_import_last_hk_address'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function getTableColumns($table) {
		static $columnsCache = array();
		if(isset($columnsCache[$table])) return $columnsCache[$table];

		$this->db->setQuery("SHOW COLUMNS FROM ".$this->sourceQuery($table));
		$cols = $this->db->loadColumn();
		$columnsCache[$table] = $cols;
		return $cols;
	}

	function importCoupons()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 16).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('COUPONS').' / '.JText::_('DISCOUNTS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_hk_coupon = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'hk_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'hk_import_last_hk_coupon'");
			$this->db->execute();
		}

		$count = 500;
		$imported = 0;

		$sql = "SELECT * FROM ".$this->sourceQuery('discount').
			   " WHERE discount_id > " . (int)$this->options->last_hk_coupon .
			   " ORDER BY discount_id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('COUPONS').' / '.JText::_('DISCOUNTS'))).'</p>';
			return true;
		}

		$sourceCodes = array();
		foreach($rows as $row) {
			if(!empty($row->discount_code))
				$sourceCodes[] = $this->db->quote($row->discount_code);
		}

		$existingCodes = array();
		if(!empty($sourceCodes)) {
			$this->db->setQuery("SELECT discount_code FROM `#__hikashop_discount` WHERE discount_code IN (".implode(',', $sourceCodes).")");
			$found = $this->db->loadColumn();
			if(!empty($found))
				$existingCodes = array_flip($found);
		}

		foreach($rows as $row) {
			if(!empty($row->discount_code) && isset($existingCodes[$row->discount_code])) {
				$this->options->last_hk_coupon = $row->discount_id;
				continue;
			}

			$discount = new stdClass();
			$discount->discount_type = $row->discount_type;
			$discount->discount_code = isset($row->discount_code) ? $row->discount_code : '';
			$discount->discount_published = $row->discount_published;
			$discount->discount_percent_amount = isset($row->discount_percent_amount) ? $row->discount_percent_amount : 0;
			$discount->discount_flat_amount = isset($row->discount_flat_amount) ? $row->discount_flat_amount : 0;
			$discount->discount_currency_id = isset($row->discount_currency_id) ? $row->discount_currency_id : 0;
			$discount->discount_start = isset($row->discount_start) ? $row->discount_start : 0;
			$discount->discount_end = isset($row->discount_end) ? $row->discount_end : 0;
			$discount->discount_minimum_order = isset($row->discount_minimum_order) ? $row->discount_minimum_order : 0;
			$discount->discount_minimum_products = isset($row->discount_minimum_products) ? $row->discount_minimum_products : 0;
			$discount->discount_quota = isset($row->discount_quota) ? $row->discount_quota : 0;
			$discount->discount_quota_per_user = isset($row->discount_quota_per_user) ? $row->discount_quota_per_user : 0;
			$discount->discount_access = isset($row->discount_access) ? $row->discount_access : 'all';
			$discount->discount_auto_load = isset($row->discount_auto_load) ? $row->discount_auto_load : 0;
			$discount->discount_tax_id = isset($row->discount_tax_id) ? $row->discount_tax_id : 0;
			$discount->discount_product_id = $this->remapCommaSeparatedIds(isset($row->discount_product_id) ? $row->discount_product_id : '', 'hkimport_prod');
			$discount->discount_category_id = $this->remapCommaSeparatedIds(isset($row->discount_category_id) ? $row->discount_category_id : '', 'hkimport_cat');

			$this->db->insertObject('#__hikashop_discount', $discount);
			$imported++;

			$this->options->last_hk_coupon = $row->discount_id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('COUPONS').' / '.JText::_('DISCOUNTS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_hk_coupon." WHERE config_namekey = 'hk_import_last_hk_coupon'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function importOrders()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 17).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('ORDERS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_hk_order = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'hk_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'hk_import_last_hk_order'");
			$this->db->execute();
		}

		$count = 500;
		$imported = 0;

		$sql = "SELECT * FROM ".$this->sourceQuery('order').
			   " WHERE order_id > " . (int)$this->options->last_hk_order .
			   " ORDER BY order_id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('ORDERS'))).'</p>';
			return true;
		}

		$sourceIds = array();
		$sourceUserIds = array();
		$sourcePaymentIds = array();
		foreach($rows as $row) {
			$sourceIds[] = (int)$row->order_id;
			if(!empty($row->order_user_id))
				$sourceUserIds[(int)$row->order_user_id] = 1;
			if(!empty($row->order_payment_id))
				$sourcePaymentIds[(int)$row->order_payment_id] = 1;
		}

		$alreadyMapped = array();
		$this->db->setQuery("SELECT source_id FROM `#__hikashop_hkimport_order` WHERE source_id IN (".implode(',', $sourceIds).")");
		$mapped = $this->db->loadColumn();
		if(!empty($mapped))
			$alreadyMapped = array_flip($mapped);

		$userMap = array();
		if(!empty($sourceUserIds)) {
			$this->db->setQuery("SELECT source_id, hk_id FROM `#__hikashop_hkimport_user` WHERE source_id IN (".implode(',', array_keys($sourceUserIds)).")");
			$userRows = $this->db->loadObjectList();
			if(!empty($userRows)) {
				foreach($userRows as $u)
					$userMap[(int)$u->source_id] = (int)$u->hk_id;
			}
		}

		$paymentMap = array();
		if(!empty($sourcePaymentIds)) {
			$this->db->setQuery("SELECT source_id, hk_id FROM `#__hikashop_hkimport_payment` WHERE source_id IN (".implode(',', array_keys($sourcePaymentIds)).")");
			$paymentRows = $this->db->loadObjectList();
			if(!empty($paymentRows)) {
				foreach($paymentRows as $p)
					$paymentMap[(int)$p->source_id] = (int)$p->hk_id;
			}
		}

		foreach($rows as $row) {
			if(isset($alreadyMapped[(int)$row->order_id])) {
				$this->options->last_hk_order = $row->order_id;
				continue;
			}

			$order = new stdClass();
			$order->order_number = isset($row->order_number) ? $row->order_number : '';
			$order->order_created = $row->order_created;
			$order->order_modified = isset($row->order_modified) ? $row->order_modified : $row->order_created;
			$order->order_status = $row->order_status;
			$order->order_type = isset($row->order_type) ? $row->order_type : 'sale';
			$order->order_full_price = isset($row->order_full_price) ? $row->order_full_price : 0;
			$order->order_discount_price = isset($row->order_discount_price) ? $row->order_discount_price : 0;
			$order->order_shipping_price = isset($row->order_shipping_price) ? $row->order_shipping_price : 0;
			$order->order_currency_id = isset($row->order_currency_id) ? $row->order_currency_id : 0;
			$order->order_payment_method = isset($row->order_payment_method) ? $row->order_payment_method : '';
			$order->order_payment_id = 0;
			if(!empty($row->order_payment_id))
				$order->order_payment_id = isset($paymentMap[(int)$row->order_payment_id]) ? $paymentMap[(int)$row->order_payment_id] : 0;
			$order->order_shipping_method = isset($row->order_shipping_method) ? $row->order_shipping_method : '';
			$order->order_shipping_id = $this->remapShippingIds(isset($row->order_shipping_id) ? $row->order_shipping_id : '0');
			$order->order_discount_code = isset($row->order_discount_code) ? $row->order_discount_code : '';
			$order->order_ip = isset($row->order_ip) ? $row->order_ip : '';
			$order->order_invoice_number = isset($row->order_invoice_number) ? $row->order_invoice_number : '';
			$order->order_invoice_created = isset($row->order_invoice_created) ? $row->order_invoice_created : 0;
			$order->order_tax_info = isset($row->order_tax_info) ? $row->order_tax_info : '';
			$order->order_partner_id = isset($row->order_partner_id) ? $row->order_partner_id : 0;
			$order->order_partner_price = isset($row->order_partner_price) ? $row->order_partner_price : 0;
			$order->order_partner_paid = isset($row->order_partner_paid) ? $row->order_partner_paid : 0;
			$order->order_partner_currency_id = isset($row->order_partner_currency_id) ? $row->order_partner_currency_id : 0;
			$order->order_payment_price = isset($row->order_payment_price) ? $row->order_payment_price : 0;

			if(!empty($row->order_user_id)) {
				$order->order_user_id = isset($userMap[(int)$row->order_user_id]) ? $userMap[(int)$row->order_user_id] : 0;
			} else {
				$order->order_user_id = 0;
			}

			if(!empty($row->order_billing_address_id)) {
				$order->order_billing_address_id = $this->importOrderAddress($row->order_billing_address_id, $order->order_user_id);
			}

			if(!empty($row->order_shipping_address_id)) {
				$order->order_shipping_address_id = $this->importOrderAddress($row->order_shipping_address_id, $order->order_user_id);
			}

			$this->db->insertObject('#__hikashop_order', $order, 'order_id');
			$hkId = (int)$order->order_id;

			if($hkId) {
				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_hkimport_order` (source_id, hk_id) VALUES (".(int)$row->order_id.", ".(int)$hkId.")");
				$this->db->execute();
				$this->copyCustomFieldValues('order', $row, $hkId);
				$imported++;
			} else {
				echo '<p style="color:red">'.JText::sprintf('HK_IMPORT_SAVE_FAILED', JText::_('ORDERS'), $row->order_id).'</p>';
			}

			$this->options->last_hk_order = $row->order_id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('ORDERS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_hk_order." WHERE config_namekey = 'hk_import_last_hk_order'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function importOrderAddress($sourceAddressId, $hkUserId) {
		$sql = "SELECT * FROM ".$this->sourceQuery('address')." WHERE address_id = ".(int)$sourceAddressId;
		$this->db->setQuery($sql);
		$row = $this->db->loadObject();

		if(!$row) return 0;

		$addr = new stdClass();
		$addressFields = $this->getTableColumns('address');
		foreach($addressFields as $field) {
			if($field == 'address_id') continue;
			if(isset($row->$field)) $addr->$field = $row->$field;
		}
		$addr->address_user_id = $hkUserId;
		$addr->address_published = 0; // Order snapshot addresses

		$this->db->insertObject('#__hikashop_address', $addr, 'address_id');
		return (int)$addr->address_id;
	}

	function importOrderProducts()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 18).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('ORDER_PRODUCTS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_hk_item = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'hk_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'hk_import_last_hk_item'");
			$this->db->execute();
		}

		$count = 500;
		$imported = 0;

		$sql = "SELECT * FROM ".$this->sourceQuery('order_product').
			   " WHERE order_product_id > " . (int)$this->options->last_hk_item .
			   " ORDER BY order_product_id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('ORDER_PRODUCTS'))).'</p>';
			return true;
		}

		$sourceOrderIds = array();
		$sourceProductIds = array();
		foreach($rows as $row) {
			$sourceOrderIds[(int)$row->order_id] = 1;
			if(!empty($row->product_id))
				$sourceProductIds[(int)$row->product_id] = 1;
		}

		$orderMap = array();
		if(!empty($sourceOrderIds)) {
			$this->db->setQuery("SELECT source_id, hk_id FROM `#__hikashop_hkimport_order` WHERE source_id IN (".implode(',', array_keys($sourceOrderIds)).")");
			$orderRows = $this->db->loadObjectList();
			if(!empty($orderRows)) {
				foreach($orderRows as $o)
					$orderMap[(int)$o->source_id] = (int)$o->hk_id;
			}
		}

		$prodMap = array();
		if(!empty($sourceProductIds)) {
			$this->db->setQuery("SELECT source_id, hk_id FROM `#__hikashop_hkimport_prod` WHERE source_id IN (".implode(',', array_keys($sourceProductIds)).")");
			$prodRows = $this->db->loadObjectList();
			if(!empty($prodRows)) {
				foreach($prodRows as $p)
					$prodMap[(int)$p->source_id] = (int)$p->hk_id;
			}
		}

		foreach($rows as $row) {
			$hkOrderId = isset($orderMap[(int)$row->order_id]) ? $orderMap[(int)$row->order_id] : 0;

			if(!$hkOrderId) {
				$this->options->last_hk_item = $row->order_product_id;
				continue;
			}

			$hkProdId = 0;
			if(!empty($row->product_id))
				$hkProdId = isset($prodMap[(int)$row->product_id]) ? $prodMap[(int)$row->product_id] : 0;

			$op = new stdClass();
			$op->order_id = $hkOrderId;
			$op->product_id = $hkProdId ? $hkProdId : 0;
			$op->order_product_name = isset($row->order_product_name) ? $row->order_product_name : '';
			$op->order_product_code = isset($row->order_product_code) ? $row->order_product_code : '';
			$op->order_product_quantity = isset($row->order_product_quantity) ? $row->order_product_quantity : 1;
			$op->order_product_price = isset($row->order_product_price) ? $row->order_product_price : 0;
			$op->order_product_tax = isset($row->order_product_tax) ? $row->order_product_tax : 0;
			$op->order_product_tax_info = isset($row->order_product_tax_info) ? $row->order_product_tax_info : '';
			$op->order_product_options = isset($row->order_product_options) ? $row->order_product_options : '';
			$op->order_product_option_parent_id = isset($row->order_product_option_parent_id) ? $row->order_product_option_parent_id : 0;
			$op->order_product_wishlist_id = isset($row->order_product_wishlist_id) ? $row->order_product_wishlist_id : 0;
			$op->order_product_wishlist_product_id = isset($row->order_product_wishlist_product_id) ? $row->order_product_wishlist_product_id : 0;
			$op->order_product_shipping_id = $this->remapShippingIds(isset($row->order_product_shipping_id) ? $row->order_product_shipping_id : '0');
			$op->order_product_shipping_method = isset($row->order_product_shipping_method) ? $row->order_product_shipping_method : '';
			$op->order_product_shipping_price = isset($row->order_product_shipping_price) ? $row->order_product_shipping_price : 0;
			$op->order_product_shipping_tax = isset($row->order_product_shipping_tax) ? $row->order_product_shipping_tax : 0;
			$op->order_product_shipping_params = isset($row->order_product_shipping_params) ? $row->order_product_shipping_params : '';

			$this->db->insertObject('#__hikashop_order_product', $op, 'order_product_id');
			$this->copyCustomFieldValues('item', $row, (int)$op->order_product_id);
			$imported++;

			$this->options->last_hk_item = $row->order_product_id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('ORDER_PRODUCTS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_hk_item." WHERE config_namekey = 'hk_import_last_hk_item'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function finishImport() {
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 20).' :</span> '.JText::_('HK_IMPORT_FINISHING').'</p>';

		$this->importRebuildTree();
		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::_('HK_IMPORT_TREE_REBUILT').'</p>';

		$this->db->setQuery("SELECT field_id, field_categories, field_products FROM `#__hikashop_field` WHERE field_core = 0 AND (field_categories != '' OR field_products != '')");
		$customFields = $this->db->loadObjectList();
		if(!empty($customFields)) {
			foreach($customFields as $cf) {
				$updates = array();
				if(!empty($cf->field_categories)) {
					$remapped = $this->remapCommaSeparatedIds($cf->field_categories, 'hkimport_cat');
					if($remapped !== $cf->field_categories) {
						$updates[] = "field_categories = ".$this->db->quote($remapped);
					}
				}
				if(!empty($cf->field_products)) {
					$remapped = $this->remapCommaSeparatedIds($cf->field_products, 'hkimport_prod');
					if($remapped !== $cf->field_products) {
						$updates[] = "field_products = ".$this->db->quote($remapped);
					}
				}
				if(!empty($updates)) {
					$this->db->setQuery("UPDATE `#__hikashop_field` SET ".implode(', ', $updates)." WHERE field_id = ".(int)$cf->field_id);
					$this->db->execute();
				}
			}
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::_('HK_IMPORT_FIELDS_REMAPPED').'</p>';
		}

		$mappingTables = array('hkimport_prod', 'hkimport_cat', 'hkimport_user', 'hkimport_order', 'hkimport_shipping', 'hkimport_payment', 'hkimport_warehouse');
		foreach($mappingTables as $t) {
			$this->db->setQuery("DROP TABLE IF EXISTS `#__hikashop_".$t."`");
			$this->db->execute();
		}
		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::_('HK_IMPORT_MAPPINGS_CLEANED').'</p>';

		$this->db->setQuery("DELETE FROM `#__hikashop_config` WHERE config_namekey LIKE 'hk_import_%'");
		$this->db->execute();

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::_('HIKA_IMPORT_SUCCESS').'</p>';

		return true;
	}

	function countSourceRows($sql) {
		$this->db->setQuery($sql);
		return (int)$this->db->loadResult();
	}

	function displayProgressBar($percent) {
		$percent = round($percent);
		if($percent > 100) $percent = 100;
		if($percent < 0) $percent = 0;

		$state = (int)$this->options->state;
		$stepName = $this->getStepName($state);

		$subText = JText::_('HIKA_IMPORT_PROCESSING');
		$headerColor = '#297F93';

		if($state >= 20) {
			$subText = JText::_('HIKA_IMPORT_SUCCESS');
			$headerColor = '#28a745';
		}

		echo '
		<style>
			.hk-progress-header { text-align: center; margin-bottom: 25px; }
			.hk-progress-subtitle { margin-top: 5px; font-size: 14px; }
			.hk-progress-card { background: #f8f9fa; border-radius: 8px; padding: 25px; border: 1px solid #e9ecef; }
			.hk-progress-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
			.hk-progress-step-title { font-weight: 600; font-size: 18px; color: #333; }
			.hk-progress-highlight { color: #297F93; }
			.hk-progress-badge { color: #666; font-weight: 500; font-size: 14px; background: #fff; padding: 4px 10px; border-radius: 20px; border: 1px solid #dee2e6; }
			.hk-progress-track { width: 100%; background: #e9ecef; border-radius: 12px; height: 16px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1); }
			.hk-progress-fill { height: 100%; background: linear-gradient(90deg, #297F93, #3AABC6); width: '.$percent.'%; transition: width 0.5s ease-in-out; border-radius: 12px; }
			.hk-progress-note { margin-top: 15px; text-align: center; font-size: 13px; color: #888; }
			.hk-import-details { margin-top: 20px; padding: 15px; border-left: 3px solid #297F93; background: #fafafa; font-size: 14px; color: #555; }
		</style>';

		echo '
			<div class="hk-progress-header">
				<p class="hk-progress-subtitle" style="color: '.($state >= 20 ? '#28a745' : '#666').'; font-weight: '.($state >= 20 ? '600' : 'normal').';">
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

	function getStepName($state) {
		$steps = array(
			0 => JText::_('HK_IMPORT_INITIALIZATION'),
			1 => JText::_('HIKA_CONFIGURATION'),
			2 => JText::_('FIELDS'),
			3 => JText::_('ZONES'),
			4 => JText::_('WAREHOUSE'),
			5 => JText::_('ORDER_STATUSES'),
			6 => JText::_('SHIPPING_METHODS'),
			7 => JText::_('PAYMENT_METHODS'),
			8 => JText::_('TAXES'),
			9 => JText::_('HIKA_CATEGORIES'),
			10 => JText::_('PRODUCTS'),
			11 => JText::_('VARIANTS'),
			12 => JText::_('PRICES'),
			13 => JText::_('IMAGES_AND_FILES'),
			14 => JText::_('USERS'),
			15 => JText::_('ADDRESSES'),
			16 => JText::_('COUPONS').' / '.JText::_('DISCOUNTS'),
			17 => JText::_('ORDERS'),
			18 => JText::_('ORDER_PRODUCTS'),
			19 => JText::_('LIMIT'),
			20 => JText::_('HK_IMPORT_FINISHING')
		);
		return isset($steps[$state]) ? $steps[$state] : JText::_('HIKA_IMPORT_PROCESSING');
	}

	function importConfig()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 1).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('HIKA_CONFIGURATION')).'</p>';

		$skipPatterns = array('_import_', 'uploadfolder', 'uploadsecurefolder', 'cron_savepath', 'payment_log_file', 'installcomplete', 'version', 'level', 'update_', 'sef');

		$sql = "SELECT * FROM ".$this->sourceQuery('config');
		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		$imported = 0;
		$skipped = 0;

		if(!empty($rows)) {
			foreach($rows as $row) {
				$skip = false;
				foreach($skipPatterns as $pattern) {
					if(strpos($row->config_namekey, $pattern) !== false) {
						$skip = true;
						break;
					}
				}
				if($skip) {
					$skipped++;
					continue;
				}

				$this->db->setQuery("INSERT INTO `#__hikashop_config` (config_namekey, config_value, config_default) VALUES ("
					.$this->db->quote($row->config_namekey).", "
					.$this->db->quote($row->config_value).", "
					.$this->db->quote(isset($row->config_default) ? $row->config_default : '')
					.") ON DUPLICATE KEY UPDATE config_value = ".$this->db->quote($row->config_value));
				$this->db->execute();
				$imported++;
			}
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('HIKA_SETTINGS'), $imported).'</p>';
		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_SKIPPED_X', JText::_('HIKA_SETTINGS'), $skipped).'</p>';

		return true;
	}

	function importCustomFields()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 2).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('FIELDS')).'</p>';

		$sql = "SELECT * FROM ".$this->sourceQuery('field')." WHERE field_core = 0 ORDER BY field_id ASC";
		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		$imported = 0;
		$skipped = 0;

		if(!empty($rows)) {
			foreach($rows as $row) {
				$this->db->setQuery("SELECT field_id FROM `#__hikashop_field` WHERE field_namekey = ".$this->db->quote($row->field_namekey));
				if($this->db->loadResult()) {
					$skipped++;
					continue;
				}

				$field = new stdClass();
				$props = get_object_vars($row);
				foreach($props as $k => $v) {
					if($k == 'field_id') continue;
					$field->$k = $v;
				}

				$this->db->insertObject('#__hikashop_field', $field, 'field_id');

				if(!empty($field->field_id) && $field->field_type != 'customtext') {
					$tables = array($field->field_table);
					if($field->field_table == 'item')
						$tables = array('cart_product', 'order_product');

					foreach($tables as $tableName) {
						if(in_array($tableName, array('contact')))
							continue;

						$query = 'ALTER TABLE `#__hikashop_'.$tableName.'` ADD `'.$field->field_namekey.'` LONGTEXT NULL';
						try {
							$this->db->setQuery($query);
							$this->db->execute();
						} catch(Exception $e) {
						}
					}
				}

				$imported++;
			}
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('FIELDS'), $imported).'</p>';
		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_SKIPPED_EXISTING', $skipped).'</p>';

		return true;
	}

	function importZones()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 3).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('ZONES')).'</p>';

		$sql = "SELECT * FROM ".$this->sourceQuery('zone')." WHERE zone_type NOT IN ('country', 'state') ORDER BY zone_id ASC";
		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		$importedZones = 0;
		$importedLinks = 0;

		if(!empty($rows)) {
			$customNamekeys = array();
			foreach($rows as $row) {
				$customNamekeys[] = $row->zone_namekey;

				$this->db->setQuery("SELECT zone_id FROM `#__hikashop_zone` WHERE zone_namekey = ".$this->db->quote($row->zone_namekey));
				if($this->db->loadResult()) continue;

				$zone = new stdClass();
				$zone->zone_namekey = $row->zone_namekey;
				$zone->zone_name = $row->zone_name;
				$zone->zone_name_english = isset($row->zone_name_english) ? $row->zone_name_english : $row->zone_name;
				$zone->zone_code_2 = isset($row->zone_code_2) ? $row->zone_code_2 : '';
				$zone->zone_code_3 = isset($row->zone_code_3) ? $row->zone_code_3 : '';
				$zone->zone_type = $row->zone_type;
				$zone->zone_published = isset($row->zone_published) ? $row->zone_published : 1;
				$zone->zone_currency_id = isset($row->zone_currency_id) ? $row->zone_currency_id : 0;
				$zone->zone_tax_namekey = isset($row->zone_tax_namekey) ? $row->zone_tax_namekey : '';

				$this->db->insertObject('#__hikashop_zone', $zone);
				$importedZones++;
			}

			if(!empty($customNamekeys)) {
				$quotedKeys = array();
				foreach($customNamekeys as $nk) {
					$quotedKeys[] = $this->db->quote($nk);
				}
				$inClause = implode(',', $quotedKeys);

				$sql = "SELECT * FROM ".$this->sourceQuery('zone_link')." WHERE zone_parent_namekey IN (".$inClause.") OR zone_child_namekey IN (".$inClause.")";
				$this->db->setQuery($sql);
				$links = $this->db->loadObjectList();

				if(!empty($links)) {
					foreach($links as $link) {
						$zl = new stdClass();
						$zl->zone_parent_namekey = $link->zone_parent_namekey;
						$zl->zone_child_namekey = $link->zone_child_namekey;

						$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_zone_link` (zone_parent_namekey, zone_child_namekey) VALUES ("
							.$this->db->quote($zl->zone_parent_namekey).", ".$this->db->quote($zl->zone_child_namekey).")");
						$this->db->execute();
						$importedLinks++;
					}
				}
			}
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('ZONES'), $importedZones).'</p>';
		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('ZONES'), $importedLinks).'</p>';

		return true;
	}

	function importWarehouses()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 4).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('WAREHOUSE')).'</p>';

		$sql = "SELECT * FROM ".$this->sourceQuery('warehouse')." ORDER BY warehouse_id ASC";
		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		$imported = 0;

		if(!empty($rows)) {
			foreach($rows as $row) {
				$wh = new stdClass();
				$props = get_object_vars($row);
				foreach($props as $k => $v) {
					if($k == 'warehouse_id') continue;
					$wh->$k = $v;
				}

				$this->db->insertObject('#__hikashop_warehouse', $wh, 'warehouse_id');

				if(!empty($wh->warehouse_id)) {
					$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_hkimport_warehouse` (source_id, hk_id) VALUES (".(int)$row->warehouse_id.", ".(int)$wh->warehouse_id.")");
					$this->db->execute();
					$imported++;
				}
			}
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('WAREHOUSE'), $imported).'</p>';

		return true;
	}

	function importOrderStatuses()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 5).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('ORDER_STATUSES')).'</p>';

		$sql = "SELECT * FROM ".$this->sourceQuery('orderstatus')." ORDER BY orderstatus_id ASC";
		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		$imported = 0;
		$skipped = 0;

		if(!empty($rows)) {
			foreach($rows as $row) {
				$this->db->setQuery("SELECT orderstatus_id FROM `#__hikashop_orderstatus` WHERE orderstatus_namekey = ".$this->db->quote($row->orderstatus_namekey));
				if($this->db->loadResult()) {
					$skipped++;
					continue;
				}

				$status = new stdClass();
				$status->orderstatus_namekey = $row->orderstatus_namekey;
				$status->orderstatus_name = isset($row->orderstatus_name) ? $row->orderstatus_name : '';
				$status->orderstatus_description = isset($row->orderstatus_description) ? $row->orderstatus_description : '';
				$status->orderstatus_published = isset($row->orderstatus_published) ? $row->orderstatus_published : 1;
				$status->orderstatus_ordering = isset($row->orderstatus_ordering) ? $row->orderstatus_ordering : 0;
				$status->orderstatus_email_params = isset($row->orderstatus_email_params) ? $row->orderstatus_email_params : '';
				$status->orderstatus_links_params = isset($row->orderstatus_links_params) ? $row->orderstatus_links_params : '';
				$status->orderstatus_color = isset($row->orderstatus_color) ? $row->orderstatus_color : '';

				$this->db->insertObject('#__hikashop_orderstatus', $status);
				$imported++;
			}
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('ORDER_STATUSES'), $imported).'</p>';
		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_SKIPPED_EXISTING', $skipped).'</p>';

		return true;
	}

	function importShippingMethods()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 6).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('SHIPPING_METHODS')).'</p>';

		$sql = "SELECT * FROM ".$this->sourceQuery('shipping')." ORDER BY shipping_id ASC";
		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		$imported = 0;

		if(!empty($rows)) {
			foreach($rows as $row) {
				$ship = new stdClass();
				$props = get_object_vars($row);
				foreach($props as $k => $v) {
					if($k == 'shipping_id') continue;
					$ship->$k = $v;
				}
				$ship->shipping_tax_id = 0;

				$this->db->insertObject('#__hikashop_shipping', $ship, 'shipping_id');

				if(!empty($ship->shipping_id)) {
					$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_hkimport_shipping` (source_id, hk_id) VALUES (".(int)$row->shipping_id.", ".(int)$ship->shipping_id.")");
					$this->db->execute();
					$imported++;
				}
			}
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('SHIPPING_METHODS'), $imported).'</p>';

		return true;
	}

	function importPaymentMethods()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 7).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('PAYMENT_METHODS')).'</p>';

		$sql = "SELECT * FROM ".$this->sourceQuery('payment')." ORDER BY payment_id ASC";
		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		$imported = 0;

		if(!empty($rows)) {
			foreach($rows as $row) {
				$pay = new stdClass();
				$props = get_object_vars($row);
				foreach($props as $k => $v) {
					if($k == 'payment_id') continue;
					$pay->$k = $v;
				}

				$this->db->insertObject('#__hikashop_payment', $pay, 'payment_id');

				if(!empty($pay->payment_id)) {
					$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_hkimport_payment` (source_id, hk_id) VALUES (".(int)$row->payment_id.", ".(int)$pay->payment_id.")");
					$this->db->execute();
					$imported++;
				}
			}
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('PAYMENT_METHODS'), $imported).'</p>';

		return true;
	}

	function importLimits()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 19).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('LIMIT')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_hk_limit = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'hk_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'hk_import_last_hk_limit'");
			$this->db->execute();
		}

		$count = 500;
		$imported = 0;

		$sql = "SELECT * FROM ".$this->sourceQuery('limit').
			   " WHERE limit_id > " . (int)$this->options->last_hk_limit .
			   " ORDER BY limit_id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('LIMIT'))).'</p>';
			return true;
		}

		$sourceProductIds = array();
		foreach($rows as $row) {
			if(!empty($row->limit_product_id))
				$sourceProductIds[(int)$row->limit_product_id] = 1;
		}

		$prodMap = array();
		if(!empty($sourceProductIds)) {
			$this->db->setQuery("SELECT source_id, hk_id FROM `#__hikashop_hkimport_prod` WHERE source_id IN (".implode(',', array_keys($sourceProductIds)).")");
			$prodRows = $this->db->loadObjectList();
			if(!empty($prodRows)) {
				foreach($prodRows as $p)
					$prodMap[(int)$p->source_id] = (int)$p->hk_id;
			}
		}

		foreach($rows as $row) {
			$limit = new stdClass();
			$props = get_object_vars($row);
			foreach($props as $k => $v) {
				if($k == 'limit_id') continue;
				$limit->$k = $v;
			}

			if(!empty($limit->limit_product_id)) {
				$mappedProd = isset($prodMap[(int)$limit->limit_product_id]) ? $prodMap[(int)$limit->limit_product_id] : 0;
				$limit->limit_product_id = $mappedProd ? $mappedProd : 0;
			}

			if(!empty($limit->limit_category_id)) {
				$limit->limit_category_id = $this->remapCommaSeparatedIds($limit->limit_category_id, 'hkimport_cat');
			}

			$this->db->insertObject('#__hikashop_limit', $limit);
			$imported++;

			$this->options->last_hk_limit = $row->limit_id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('LIMIT'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_hk_limit." WHERE config_namekey = 'hk_import_last_hk_limit'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function remapShippingIds($shippingIdStr)
	{
		if(empty($shippingIdStr) || $shippingIdStr === '0') return $shippingIdStr;

		$parts = explode(';', $shippingIdStr);
		$result = array();

		foreach($parts as $part) {
			$part = trim($part);
			if(empty($part)) continue;

			$suffix = '';
			$id = $part;
			$atPos = strpos($part, '@');
			if($atPos !== false) {
				$id = substr($part, 0, $atPos);
				$suffix = substr($part, $atPos);
			}

			$numId = (int)$id;
			if($numId > 0) {
				$this->db->setQuery("SELECT hk_id FROM `#__hikashop_hkimport_shipping` WHERE source_id = ".$numId);
				$mapped = (int)$this->db->loadResult();
				if($mapped) {
					$result[] = $mapped . $suffix;
				} else {
					$result[] = $part;
				}
			} else {
				$result[] = $part;
			}
		}

		return implode(';', $result);
	}

	function copyCustomFieldValues($table, $sourceRow, $targetId)
	{
		static $fieldCache = array();

		if(!isset($fieldCache[$table])) {
			$this->db->setQuery("SELECT field_namekey FROM `#__hikashop_field` WHERE field_table = ".$this->db->quote($table)." AND field_core = 0");
			$fieldCache[$table] = $this->db->loadColumn();
			if(empty($fieldCache[$table])) $fieldCache[$table] = array();
		}

		if(empty($fieldCache[$table])) return;

		$pkMap = array(
			'product' => 'product_id',
			'order' => 'order_id',
			'address' => 'address_id',
			'user' => 'user_id',
			'item' => 'order_product_id'
		);
		$pk = isset($pkMap[$table]) ? $pkMap[$table] : $table.'_id';

		$tableMap = array(
			'item' => 'order_product'
		);
		$dbTable = isset($tableMap[$table]) ? $tableMap[$table] : $table;

		$sets = array();
		foreach($fieldCache[$table] as $fieldNamekey) {
			if(isset($sourceRow->$fieldNamekey) && $sourceRow->$fieldNamekey !== '') {
				$sets[] = '`'.$fieldNamekey.'` = '.$this->db->quote($sourceRow->$fieldNamekey);
			}
		}

		if(empty($sets)) return;

		$sql = "UPDATE `#__hikashop_".$dbTable."` SET ".implode(', ', $sets)." WHERE `".$pk."` = ".(int)$targetId;
		try {
			$this->db->setQuery($sql);
			$this->db->execute();
		} catch(Exception $e) {
		}
	}

	function remapCommaSeparatedIds($idString, $mappingTable)
	{
		if(empty($idString)) return $idString;

		$hasLeadingComma = (substr($idString, 0, 1) === ',');
		$hasTrailingComma = (substr($idString, -1) === ',');

		$ids = array_filter(explode(',', $idString), function($v) { return $v !== ''; });
		if(empty($ids)) return $idString;

		$mapped = array();
		foreach($ids as $srcId) {
			$srcId = trim($srcId);
			if(!is_numeric($srcId)) {
				$mapped[] = $srcId;
				continue;
			}
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_".$mappingTable."` WHERE source_id = ".(int)$srcId);
			$hkId = (int)$this->db->loadResult();
			$mapped[] = $hkId ? $hkId : $srcId;
		}

		$result = implode(',', $mapped);
		if($hasLeadingComma) $result = ',' . $result;
		if($hasTrailingComma) $result = $result . ',';

		return $result;
	}

}
