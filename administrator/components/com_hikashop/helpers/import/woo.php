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

class hikashopImportWooHelper extends hikashopImportHelper
{
	var $wooDatabase;
	var $wooPrefix;
	var $wooPath;
	var $sessionParams;
	var $importcurrencies;

	function __construct(&$parent)
	{
		parent::__construct($parent);
		$this->importName = 'woo';
		$this->sessionParams = HIKASHOP_COMPONENT.'woo';
		$this->copyImgDir = '';
		jimport('joomla.filesystem.file');
	}

	function isWooCommerceDetected() {
		$database = JFactory::getDBO();
		$wooPrefix = $database->getPrefix();
		$query = 'SHOW TABLES LIKE ' . $database->Quote($wooPrefix . 'woocommerce_tax_rates');
		$database->setQuery($query);
		$table = $database->loadResult();
		if(empty($table)) {
			try {
				$database->setQuery('SELECT COUNT(*) FROM `' . $wooPrefix . 'posts` WHERE post_type = ' . $database->Quote('product'));
				return ((int)$database->loadResult() > 0);
			} catch(Exception $e) {
				return false;
			}
		}
		return true;
	}

	function importFromWoo()
	{
		@ob_clean();
		echo $this->getHtmlPage();

		$this->token = hikashop_getFormToken();

		flush();

		if( isset($_GET['import']) && $_GET['import'] == '1' )
		{
			$app = JFactory::getApplication();
			$this->wooDatabase = $app->getUserState($this->sessionParams.'dbName');
			$this->wooPath = $app->getUserState($this->sessionParams.'rootPath');
			$this->wooPrefix = $app->getUserState($this->sessionParams.'prefix');

			$time = microtime(true);
			$processed = $this->doImport();

			if( $processed )
			{
				$elasped = microtime(true) - $time;

				if( !$this->refreshPage )
					echo '<p><br/><a'.$this->linkstyle.' href="'.hikashop_completeLink('import&task=import&importfrom=woo&'.$this->token.'=1&import=1&time='.time()).'">'.JText::_('HIKA_NEXT').'</a></p>';

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
			$url = hikashop_completeLink('import&task=import&importfrom=woo&'.$this->token.'=1&import=1&time='.time());
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

		$this->wooDatabase = $app->getUserStateFromRequest($this->sessionParams.'dbName', 'wooDatabase', '', 'string' );
		$this->wooPath = $app->getUserStateFromRequest($this->sessionParams.'rootPath', 'wooPath', '', 'string' );
		$this->wooPrefix = $app->getUserStateFromRequest($this->sessionParams.'prefix', 'wooPrefix', '', 'string' );

		if(defined('HIKASHOP_WORDPRESS')) {
			if(empty($this->wooDatabase) && defined('DB_NAME')) {
				$this->wooDatabase = DB_NAME;
				$app->setUserState($this->sessionParams.'dbName', $this->wooDatabase);
			}
			if(empty($this->wooPrefix)) {
				$this->wooPrefix = $database->getPrefix();
				$app->setUserState($this->sessionParams.'prefix', $this->wooPrefix);
			}
			if(empty($this->wooPath) && defined('ABSPATH')) {
				$this->wooPath = rtrim(ABSPATH, '/\\');
				$app->setUserState($this->sessionParams.'rootPath', $this->wooPath);
			}
		}

		if (empty($this->wooDatabase))
		{
			$returnString .= '<p style="color:red">'.JText::_('HK_IMPORT_SPECIFY_DB').'</p>';
			$continue = false;
		}
		elseif (empty($this->wooPrefix))
		{
			$returnString .= '<p style="color:red">'.JText::_('HK_IMPORT_SPECIFY_PREFIX').'</p>';
			$continue = false;
		}
		else
		{
			$query = 'SHOW TABLES FROM `'.$this->wooDatabase.'` LIKE '.$database->Quote($this->wooPrefix.'posts').';';
			try
			{
				$database->setQuery($query);
				$table = $database->loadResult();
			}
			catch(Exception $e)
			{
				$returnString .= '<p style="color:red">'.JText::sprintf('HK_IMPORT_DB_ERROR', $this->wooDatabase).'<br/><span style="font-size:0.75em">Mysql Error :'.$e.'</span></p>';
				$continue = false;
			}

			if ($continue)
			{
				if (empty($table))
					$returnString .= '<p style="color:red">'.JText::sprintf('HK_IMPORT_DATA_NOT_FOUND', $this->wooPrefix.'posts', $this->wooDatabase).'</p>';
				else
					$returnString .= JText::sprintf('HK_IMPORT_START_FROM_DB', $this->wooDatabase).'<br/>'.
									JText::sprintf('HK_IMPORT_BACKUP_AND_START', '<a '.$this->linkstyle.' href="'.hikashop_completeLink('import&task=import&importfrom=woo&'.$this->token.'=1&import=1').'">'.JText::_('HIKA_NEXT').'</a>');
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

		$totalSteps = 13;
		$state = (int)$this->options->state;

		$baseProgress = ($state / $totalSteps) * 100;
		$stepProgress = 0;

		switch($state) {
			case 1: // Taxes
				$total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix."woocommerce_tax_rates` ");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix."woocommerce_tax_rates` WHERE tax_rate_id <= ".(int)$this->options->last_woo_taxrate) / $total);
				break;
			case 2: // Categories
				$total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix."term_taxonomy` WHERE taxonomy = 'product_cat'");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix."term_taxonomy` AS tt INNER JOIN `".$this->wooDatabase."`.`".$this->wooPrefix."terms` AS t ON t.term_id = tt.term_id WHERE tt.taxonomy = 'product_cat' AND t.term_id <= ".(int)$this->options->last_woo_cat) / $total);
				break;
			case 3: // Products
				$total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` WHERE post_type = 'product'");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` WHERE post_type = 'product' AND ID <= ".(int)$this->options->last_woo_prod) / $total);
				break;
			case 4: // Variations
				$total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` WHERE post_type = 'product_variation'");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` WHERE post_type = 'product_variation' AND ID <= ".(int)$this->options->last_woo_var) / $total);
				break;
			case 5: // Prices
			case 6: // Images
				$total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` WHERE post_type IN ('product','product_variation')");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` WHERE post_type IN ('product','product_variation') AND ID <= ".(int)$this->options->last_woo_prod) / $total);
				break;
			case 7: // Users
			case 8: // Addresses
				$total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix."users` ");
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix."users` WHERE ID <= ".(int)$this->options->last_woo_user) / $total);
				break;
			case 10: // Orders
				$table = $this->options->useHPOS ? "wc_orders" : "posts";
				$where = $this->options->useHPOS ? "type = 'shop_order'" : "post_type = 'shop_order'";
				$idCol = $this->options->useHPOS ? "id" : "ID";
				$total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix.$table."` WHERE ".$where);
				if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->wooDatabase."`.`".$this->wooPrefix.$table."` WHERE ".$where." AND ".$idCol." <= ".(int)$this->options->last_woo_order) / $total);
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
				$next = $this->importProductVariations();
				break;
			case 5:
				$next = $this->importProductPrices();
				break;
			case 6:
				$next = $this->importProductImages();
				break;
			case 7:
				$next = $this->importUsers();
				break;
			case 8:
				$next = $this->importAddresses();
				break;
			case 9:
				$next = $this->importCoupons();
				break;
			case 10:
				$next = $this->importOrders();
				break;
			case 11:
				$next = $this->importOrderItems();
				break;
			case 12:
				$next = $this->importDownloads();
				break;
			case 13:
				$next = $this->finishImport();
				$ret = false;
				break;
			default:
				$ret = false;
				break;
		}

		if( $ret && $next )
		{
			$sql =  "UPDATE `#__hikashop_config` SET config_value=(config_value+1) WHERE config_namekey = 'woo_import_state'; ";
			$this->db->setQuery($sql);
			$this->db->execute();
			$sql = "UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'woo_import_current';";
			$this->db->setQuery($sql);
			$this->db->execute();
			$this->refreshPage = true;
		}
		else if( $current != $this->options->current )
		{
			$sql =  "UPDATE `#__hikashop_config` SET config_value=".$this->options->current." WHERE config_namekey = 'woo_import_current';";
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
			'woo_import_state',
			'woo_import_current',
			'woo_import_main_cat_id',
			'woo_import_max_hk_cat',
			'woo_import_max_hk_prod',
			'woo_import_last_woo_cat',
			'woo_import_last_woo_prod',
			'woo_import_last_woo_user',
			'woo_import_last_woo_order',
			'woo_import_last_woo_taxrate',
			'woo_import_last_woo_coupon',
			'woo_import_last_woo_item',
			'woo_import_last_woo_var'
		);
		$this->db->setQuery('SELECT config_namekey, config_value FROM `#__hikashop_config` WHERE config_namekey IN ('."'".implode("','",$data)."'".');');
		$result = $this->db->loadObjectList();

		if (!empty($result))
		{
			foreach($result as $o)
			{
				if( substr($o->config_namekey, 0, 11) == 'woo_import_' )
					$nk = substr($o->config_namekey, 11);
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
				$this->options->last_woo_cat = 0;
				$this->options->last_woo_prod = 0;
				$this->options->last_woo_user = 0;
				$this->options->last_woo_order = 0;
				$this->options->last_woo_taxrate = 0;
				$this->options->last_woo_coupon = 0;
				$this->options->last_woo_item = 0;
				$this->options->last_woo_var = 0;
			}

			$element = 'product';
			$categoryClass = hikashop_get('class.category');
			$categoryClass->getMainElement($element);
			if(empty($element)) $element = 2; // Default to 2 if not found
			$this->options->main_cat_id = $element;

			$this->db->setQuery("SELECT max(category_id) as 'max' FROM `#__hikashop_category`;");
			$data = $this->db->loadObjectList();
			$this->options->max_hk_cat = (int)($data[0]->max);

			$this->db->setQuery("SELECT max(product_id) as 'max' FROM `#__hikashop_product`;");
			$data = $this->db->loadObjectList();
			$this->options->max_hk_prod = (int)($data[0]->max);

			$configData = array(
				'woo_import_state' => $this->options->state,
				'woo_import_current' => $this->options->current,
				'woo_import_main_cat_id' => $this->options->main_cat_id,
				'woo_import_max_hk_cat' => $this->options->max_hk_cat,
				'woo_import_max_hk_prod' => $this->options->max_hk_prod,
				'woo_import_last_woo_cat' => $this->options->last_woo_cat,
				'woo_import_last_woo_prod' => $this->options->last_woo_prod,
				'woo_import_last_woo_user' => $this->options->last_woo_user,
				'woo_import_last_woo_order' => $this->options->last_woo_order,
				'woo_import_last_woo_taxrate' => $this->options->last_woo_taxrate,
				'woo_import_last_woo_coupon' => $this->options->last_woo_coupon,
				'woo_import_last_woo_item' => $this->options->last_woo_item,
				'woo_import_last_woo_var' => $this->options->last_woo_var
			);

			foreach($configData as $nk => $nv) {
				$this->db->setQuery("INSERT INTO `#__hikashop_config` (config_namekey, config_value) VALUES (".$this->db->quote($nk).", ".$this->db->quote($nv).") ON DUPLICATE KEY UPDATE config_value = ".$this->db->quote($nv));
				$this->db->execute();
			}
		}

		$this->db->setQuery("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ".$this->db->quote($this->wooDatabase)." AND TABLE_NAME = ".$this->db->quote($this->wooPrefix."wc_orders"));
		$this->options->useHPOS = ((int)$this->db->loadResult() > 0);

	}

	function createTables()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 1).' :</span> '.JText::_('HK_IMPORT_INITIALIZATION').'</p>';

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_woo_prod` (`woo_id` bigint(20) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`woo_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_woo_cat` (`woo_id` bigint(20) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`woo_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_woo_user` (`woo_id` bigint(20) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`woo_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_woo_order` (`woo_id` bigint(20) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`woo_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$databaseHelper = hikashop_get('helper.database');
		$databaseHelper->addColumns('order','`order_woo_id` bigint(20) NULL');
		$databaseHelper->addColumns('order','INDEX ( `order_woo_id` )');
		$databaseHelper->addColumns('taxation','`tax_woo_id` bigint(20) NULL');

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::_('HK_IMPORT_TABLES_CREATED').'</p>';

		return true;
	}

	function importTaxes()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 2).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('TAXES')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_woo_taxrate = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'woo_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'woo_import_last_woo_taxrate'");
			$this->db->execute();
		}

		$data = array(
			'tax_namekey' => "CONCAT('WOO_TAX_', wtr.tax_rate_id)",
			'tax_rate' => 'wtr.tax_rate / 100'
		);


		$oldLastId = (int)$this->options->last_woo_taxrate;
		$sql = "INSERT IGNORE INTO `#__hikashop_tax` (`".implode('`,`',array_keys($data))."`) ".
			"SELECT ".implode(',',$data)." FROM `".$this->wooDatabase."`.`".$this->wooPrefix."woocommerce_tax_rates` AS wtr ".
			"WHERE wtr.tax_rate_id > " . $oldLastId;

		$this->db->setQuery($sql);
		$this->db->execute();

		$this->db->setQuery("SELECT MAX(tax_rate_id) FROM `".$this->wooDatabase."`.`".$this->wooPrefix."woocommerce_tax_rates` WHERE tax_rate_id > " . (int)$this->options->last_woo_taxrate);
		$maxId = (int)$this->db->loadResult();

		if($maxId > $this->options->last_woo_taxrate) {
			$this->options->last_woo_taxrate = $maxId;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_woo_taxrate." WHERE config_namekey = 'woo_import_last_woo_taxrate'");
			$this->db->execute();
		}




		$element = 'tax';
		$categoryClass = hikashop_get('class.category');
		$categoryClass->getMainElement($element);

		$element = 'tax';
		$categoryClass = hikashop_get('class.category');
		$categoryClass->getMainElement($element); // Updates $element to root ID

		$this->db->setQuery("SELECT DISTINCT tax_rate_class FROM `".$this->wooDatabase."`.`".$this->wooPrefix."woocommerce_tax_rates`");
		$classes = $this->db->loadColumn();
		if(empty($classes)) $classes = array(''); // Ensure at least standard exists if empty

		foreach($classes as $c) {
			$className = empty($c) ? 'Standard' : $c;
			$cleanName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $className));
			$nameKey = 'WOO_TAX_CAT_' . $cleanName;

			$cat = new stdClass();
			$cat->category_type = 'tax';
			$cat->category_name = $className;
			$cat->category_published = 1;
			$cat->category_parent_id = (int)$element;
			$cat->category_namekey = $nameKey;

			$categoryClass->save($cat);
		}

		$data = array(
			'zone_namekey' => "''", 
			'category_namekey' => "CONCAT('WOO_TAX_CAT_', IF(wtr.tax_rate_class='', 'standard', LOWER(wtr.tax_rate_class)))",
			'tax_namekey' => "CONCAT('WOO_TAX_', wtr.tax_rate_id)",
			'taxation_published' => '1',
			'taxation_type' => "''",
			'tax_woo_id' => 'wtr.tax_rate_id'
		);

		$sql = "INSERT IGNORE INTO `#__hikashop_taxation` (`".implode('`,`',array_keys($data))."`) ".
			"SELECT ".implode(',',$data)." FROM `".$this->wooDatabase."`.`".$this->wooPrefix."woocommerce_tax_rates` AS wtr ".
			"WHERE wtr.tax_rate_id > " . $oldLastId;

		$this->db->setQuery($sql);
		$this->db->execute();
		$totalTaxation = $this->db->getAffectedRows();
		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('TAXATIONS'), $totalTaxation).'</p>';

		return true;
	}
	function importCategories()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 3).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('HIKA_CATEGORIES')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_woo_cat = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'woo_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'woo_import_last_woo_cat'");
			$this->db->execute();
		}

		jimport('joomla.filesystem.file');
		$categoryClass = hikashop_get('class.category');

		$ret = false;
		$rebuild = false;
		$cpt = 0;
		$count = 100;

		$sql = "SELECT t.term_id, t.name, t.slug, tt.parent, tt.description ".
			   "FROM `".$this->wooDatabase."`.`".$this->wooPrefix."terms` AS t ".
			   "INNER JOIN `".$this->wooDatabase."`.`".$this->wooPrefix."term_taxonomy` AS tt ON t.term_id = tt.term_id ".
			   "WHERE tt.taxonomy = 'product_cat' ".
			   "AND t.term_id > " . (int)$this->options->last_woo_cat . " ".
			   "ORDER BY t.term_id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('HIKA_CATEGORIES'))).'</p>';

			$this->importRebuildTree();
			return true; 
		}

		$imported = 0;

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_cat` WHERE woo_id = ".(int)$row->term_id);
			if($this->db->loadResult()) {
				$this->options->last_woo_cat = $row->term_id;
				continue;
			}

			$cat = new stdClass();
			$cat->category_type = 'product';
			$cat->category_name = $row->name;
			$cat->category_description = $row->description;
			$cat->category_published = 1;
			$cat->category_alias = $row->slug;
			$cat->category_namekey = 'category_' . $row->slug; // or auto generated


			if($row->parent == 0) {
				$cat->category_parent_id = $this->options->main_cat_id;
			} else {
				$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_cat` WHERE woo_id = ".(int)$row->parent);
				$parentId = $this->db->loadResult();
				if($parentId) {
					$cat->category_parent_id = $parentId;
				} else {
					$cat->category_parent_id = $this->options->main_cat_id; // Temporary fallback
				}
			}

			$hkId = $categoryClass->save($cat);

			if($hkId) {
				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_woo_cat` (woo_id, hk_id) VALUES (".(int)$row->term_id.", ".(int)$hkId.")");
				$this->db->execute();

				$imported++;
			} else {
				echo '<p style="color:red">'.JText::sprintf('HK_IMPORT_SAVE_FAILED', $row->name, '(Woo ID: '.(int)$row->term_id.')').'</p>';
				if(method_exists($categoryClass, 'getError')) {
					echo '<p style="color:red;font-size:0.7em">' . $categoryClass->getError() . '</p>';
				}
			}
			$this->options->last_woo_cat = $row->term_id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('HIKA_CATEGORIES'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_woo_cat." WHERE config_namekey = 'woo_import_last_woo_cat'");
			$this->db->execute();
			$this->refreshPage = true;
			return false; // Continue in next batch
		}

		return true; // Done
	}
	function importProducts()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 4).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('PRODUCTS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_woo_prod = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'woo_import_current'");
			$this->db->execute();
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'woo_import_last_woo_prod'");
			$this->db->execute();
		}

		$productClass = hikashop_get('class.product');
		$categoryClass = hikashop_get('class.category');

		$count = 50; 
		$imported = 0;


		$sql = "SELECT p.ID, p.post_title, p.post_content, p.post_excerpt, p.post_name, p.post_status, p.post_date, p.post_modified ".
			   "FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` AS p ".
			   "WHERE p.post_type = 'product' ".
			   "AND p.post_status IN ('publish','draft','private') ".
			   "AND p.ID > " . (int)$this->options->last_woo_prod . " ".
			   "ORDER BY p.ID ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('PRODUCTS'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_prod` WHERE woo_id = ".(int)$row->ID);
			if($this->db->loadResult()) {
				$this->options->last_woo_prod = $row->ID;
				continue;
			}

			$prod = new stdClass();
			$prod->product_name = $row->post_title;
			$prod->product_description = $row->post_content;
			$prod->product_alias = $row->post_name;
			$prod->product_created = !empty($row->post_date) && substr($row->post_date,0,10) != '0000-00-00' ? strtotime($row->post_date) : time();
			$prod->product_modified = !empty($row->post_modified) && substr($row->post_modified,0,10) != '0000-00-00' ? strtotime($row->post_modified) : $prod->product_created;
			$prod->product_published = ($row->post_status == 'publish') ? 1 : 0;
			$prod->product_type = 'main'; 

			$sqlMeta = "SELECT meta_key, meta_value FROM `".$this->wooDatabase."`.`".$this->wooPrefix."postmeta` WHERE post_id = ".(int)$row->ID;
			$this->db->setQuery($sqlMeta);
			$metas = $this->db->loadObjectList();
			$metaMap = array();
			foreach($metas as $m) $metaMap[$m->meta_key] = $m->meta_value;

			$prod->product_code = isset($metaMap['_sku']) ? $metaMap['_sku'] : '';
			if(empty($prod->product_code)) $prod->product_code = 'product_' . $row->ID; // Fallback

			$prod->product_quantity = isset($metaMap['_stock']) ? (int)$metaMap['_stock'] : -1; // -1 for unlimited if not managed?
			if (isset($metaMap['_manage_stock']) && $metaMap['_manage_stock'] === 'no') {
				$prod->product_quantity = -1;
			}

			$prod->product_weight = isset($metaMap['_weight']) ? (float)$metaMap['_weight'] : 0;
			$prod->product_width = isset($metaMap['_width']) ? (float)$metaMap['_width'] : 0;
			$prod->product_length = isset($metaMap['_length']) ? (float)$metaMap['_length'] : 0;
			$prod->product_height = isset($metaMap['_height']) ? (float)$metaMap['_height'] : 0;

			$customFields = $this->importCustomFields('product', $metaMap);
			foreach($customFields as $k => $v) {
				$prod->$k = $v;
			}

			$sqlCats = "SELECT t.term_id FROM `".$this->wooDatabase."`.`".$this->wooPrefix."term_relationships` tr ".
					   "INNER JOIN `".$this->wooDatabase."`.`".$this->wooPrefix."term_taxonomy` tt ON tr.term_taxonomy_id = tt.term_taxonomy_id ".
					   "INNER JOIN `".$this->wooDatabase."`.`".$this->wooPrefix."terms` t ON tt.term_id = t.term_id ".
					   "WHERE tr.object_id = ".(int)$row->ID." AND tt.taxonomy = 'product_cat'";
			$this->db->setQuery($sqlCats);
			$wooCatIds = $this->db->loadColumn();

			$categories = array();
			if(!empty($wooCatIds)) {
				$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_cat` WHERE woo_id IN (".implode(',', $wooCatIds).")");
				$categories = $this->db->loadColumn();
			}
			if(empty($categories)) $categories = array($this->options->main_cat_id);

			$prod->categories = $categories;

			$taxClass = isset($metaMap['_tax_class']) ? $metaMap['_tax_class'] : '';
			if(empty($taxClass)) $taxClass = 'standard';
			if($taxClass != 'inherit') {
				$this->db->setQuery("SELECT category_id FROM `#__hikashop_category` WHERE category_namekey = " . $this->db->quote('WOO_TAX_CAT_' . strtolower($taxClass)));
				$prod->product_tax_id = (int)$this->db->loadResult();
			}

			$hkId = $productClass->save($prod);

			if($hkId) {
				if(!empty($categories)) {
					foreach($categories as $catId) {
						$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_product_category` (product_id, category_id) VALUES (".(int)$hkId.", ".(int)$catId.")");
						$this->db->execute();
					}
				}

				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_woo_prod` (woo_id, hk_id) VALUES (".(int)$row->ID.", ".(int)$hkId.")");
				$this->db->execute();
				$imported++;
			}
			$this->options->last_woo_prod = $row->ID;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('PRODUCTS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_woo_prod." WHERE config_namekey = 'woo_import_last_woo_prod'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}
	function importProductVariations()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', '4.5').' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('VARIANTS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_woo_var = 0;
			$this->options->current = 1;

			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'woo_import_current'");
			$this->db->execute();

			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'woo_import_last_woo_var'");
			$this->db->execute();
		}

		$productClass = hikashop_get('class.product');

		$count = 50; 
		$imported = 0;

		$sql = "SELECT p.ID, p.post_title, p.post_excerpt, p.post_parent, p.post_date, p.post_modified, p.post_status ".
			   "FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` AS p ".
			   "WHERE p.post_type = 'product_variation' ".
			   "AND p.post_status IN ('publish','private') ".
			   "AND p.ID > " . (int)$this->options->last_woo_var . " ".
			   "ORDER BY p.ID ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('VARIANTS'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_prod` WHERE woo_id = ".(int)$row->ID);
			if($this->db->loadResult()) {
				$this->options->last_woo_var = $row->ID;
				continue;
			}

			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_prod` WHERE woo_id = ".(int)$row->post_parent);
			$parentId = $this->db->loadResult();

			if(!$parentId) {
				$this->options->last_woo_var = $row->ID;
				continue;
			}

			$prod = new stdClass();
			$prod->product_parent_id = $parentId;
			$prod->product_type = 'variant';
			$prod->product_name = $row->post_title; // Often "Variation #123 of Parent"
			if(empty($prod->product_name)) $prod->product_name = 'Variation ' . $row->ID;

			$prod->product_created = !empty($row->post_date) && substr($row->post_date,0,10) != '0000-00-00' ? strtotime($row->post_date) : time();
			$prod->product_modified = !empty($row->post_modified) && substr($row->post_modified,0,10) != '0000-00-00' ? strtotime($row->post_modified) : $prod->product_created;
			$prod->product_published = ($row->post_status == 'publish') ? 1 : 0;

			$sqlMeta = "SELECT meta_key, meta_value FROM `".$this->wooDatabase."`.`".$this->wooPrefix."postmeta` WHERE post_id = ".(int)$row->ID;
			$this->db->setQuery($sqlMeta);
			$metas = $this->db->loadObjectList();
			$metaMap = array();
			foreach($metas as $m) $metaMap[$m->meta_key] = $m->meta_value;

			$prod->product_code = isset($metaMap['_sku']) ? $metaMap['_sku'] : '';
			if(empty($prod->product_code)) $prod->product_code = 'file_' . $row->ID; // fallback?

			$prod->product_quantity = isset($metaMap['_stock']) ? (int)$metaMap['_stock'] : -1;
			if (isset($metaMap['_manage_stock']) && $metaMap['_manage_stock'] === 'no') {
				$prod->product_quantity = -1;
			}

			$prod->product_weight = isset($metaMap['_weight']) ? (float)$metaMap['_weight'] : 0;
			$prod->product_width = isset($metaMap['_width']) ? (float)$metaMap['_width'] : 0;
			$prod->product_length = isset($metaMap['_length']) ? (float)$metaMap['_length'] : 0;
			$prod->product_height = isset($metaMap['_height']) ? (float)$metaMap['_height'] : 0;

			$taxClass = isset($metaMap['_tax_class']) ? $metaMap['_tax_class'] : '';
			if(empty($taxClass)) $taxClass = 'standard';
			if($taxClass != 'inherit') {
				$this->db->setQuery("SELECT category_id FROM `#__hikashop_category` WHERE category_namekey = " . $this->db->quote('WOO_TAX_CAT_' . strtolower($taxClass)));
				$prod->product_tax_id = (int)$this->db->loadResult();
			}

			$customFields = $this->importCustomFields('product', $metaMap);
			foreach($customFields as $k => $v) {
				$prod->$k = $v;
			}

			$hkId = $productClass->save($prod);

			if($hkId) {
				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_woo_prod` (woo_id, hk_id) VALUES (".(int)$row->ID.", ".(int)$hkId.")");
				$this->db->execute();


				foreach($metaMap as $k => $v) {
					if(strpos($k, 'attribute_') === 0 && !empty($v)) {
						$attrName = substr($k, 10); // pa_color or custom

						$charName = str_replace('pa_', '', $attrName);

						$this->importCharacteristic($charName, $v, $hkId);
					}
				}

				$imported++;
			}

			$this->options->last_woo_var = $row->ID;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('VARIANTS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_woo_var." WHERE config_namekey = 'woo_import_last_woo_var'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function importCharacteristic($name, $value, $variantId) {
		static $charCache = array();

		$this->db->setQuery("SELECT product_parent_id FROM `#__hikashop_product` WHERE product_id = ".(int)$variantId);
		$parentId = (int)$this->db->loadResult();
		if(!$parentId) return;

		$alias = JFilterOutput::stringURLSafe($name);
		$charKey = 'char_'.$alias;

		if(!isset($charCache[$charKey])) {
			$this->db->setQuery("SELECT characteristic_id FROM `#__hikashop_characteristic` WHERE characteristic_parent_id = 0 AND characteristic_alias = ".$this->db->quote($alias));
			$charId = (int)$this->db->loadResult();

			if(!$charId) {
				$this->db->setQuery("INSERT INTO `#__hikashop_characteristic` (characteristic_name, characteristic_alias, characteristic_published, characteristic_display_type) VALUES (".$this->db->quote(ucfirst(str_replace('_',' ',$name))).", ".$this->db->quote($alias).", 1, 'list')");
				$this->db->execute();
				$charId = $this->db->insertid();
			}
			$charCache[$charKey] = $charId;
		}
		$charId = $charCache[$charKey];

		$valAlias = JFilterOutput::stringURLSafe($value);
		if(empty($valAlias)) $valAlias = 'default';
		$valKey = 'val_'.$charId.'_'.$valAlias;

		if(!isset($charCache[$valKey])) {
			$this->db->setQuery("SELECT characteristic_id FROM `#__hikashop_characteristic` WHERE characteristic_parent_id = ".(int)$charId." AND characteristic_alias = ".$this->db->quote($valAlias));
			$valId = (int)$this->db->loadResult();

			if(!$valId) {
				$this->db->setQuery("INSERT INTO `#__hikashop_characteristic` (characteristic_parent_id, characteristic_value, characteristic_alias, characteristic_published) VALUES (".(int)$charId.", ".$this->db->quote($value).", ".$this->db->quote($valAlias).", 1)");
				$this->db->execute();
				$valId = $this->db->insertid();
			}
			$charCache[$valKey] = $valId;
		}
		$valId = $charCache[$valKey];

		$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_variant` (variant_product_id, variant_characteristic_id) VALUES (".(int)$parentId.", ".(int)$charId.")");
		$this->db->execute();

		$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_variant` (variant_product_id, variant_characteristic_id) VALUES (".(int)$variantId.", ".(int)$valId.")");
		$this->db->execute();
	}

	function importProductPrices()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 5).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('PRICES')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_woo_prod = 0;
			$this->options->current = 1;

			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'woo_import_current'");
			$this->db->execute();

			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'woo_import_last_woo_prod'");
			$this->db->execute();
		}

		$count = 50; 
		$imported = 0;

		$sql = "SELECT p.ID ".
			   "FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` AS p ".
			   "WHERE p.post_type IN ('product', 'product_variation') ".
			   "AND p.post_status IN ('publish','draft','private') ".
			   "AND p.ID > " . (int)$this->options->last_woo_prod . " ".
			   "ORDER BY p.ID ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('PRICES'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_prod` WHERE woo_id = ".(int)$row->ID);
			$hkId = $this->db->loadResult();

			if(!$hkId) {
				$this->options->last_woo_prod = $row->ID;
				continue;
			}

			$sqlMeta = "SELECT meta_key, meta_value FROM `".$this->wooDatabase."`.`".$this->wooPrefix."postmeta` WHERE post_id = ".(int)$row->ID;
			$this->db->setQuery($sqlMeta);
			$metas = $this->db->loadObjectList();
			$metaMap = array();
			foreach($metas as $m) $metaMap[$m->meta_key] = $m->meta_value;

			$regularPrice = isset($metaMap['_regular_price']) ? $metaMap['_regular_price'] : '';
			$salePrice = isset($metaMap['_sale_price']) ? $metaMap['_sale_price'] : '';

			if($regularPrice === '' && $salePrice === '') {
				$this->options->last_woo_prod = $row->ID;
				continue;
			}

			if($regularPrice !== '') {
				$price = new stdClass();
				$price->price_product_id = $hkId;
				$price->price_currency_id = (int)$this->options->main_currency; // Default from config (usually 1 or set)
				if(!$price->price_currency_id) $price->price_currency_id = 1;

				$price->price_value = (float)$regularPrice;
				$price->price_min_quantity = 0;
				$price->price_access = 'all';
				$price->price_users = '';

				$this->db->insertObject('#__hikashop_price', $price);
			}

			if($salePrice !== '' && $salePrice != $regularPrice) {
				$price = new stdClass();
				$price->price_product_id = $hkId;
				$price->price_currency_id = (int)$this->options->main_currency;
				if(!$price->price_currency_id) $price->price_currency_id = 1;

				$price->price_value = (float)$salePrice;
				$price->price_min_quantity = 0;
				$price->price_access = 'all';
				$price->price_users = '';

				if(!empty($metaMap['_sale_price_dates_from'])) {
					$price->price_start_date = (int)$metaMap['_sale_price_dates_from'];
				}
				if(!empty($metaMap['_sale_price_dates_to'])) {
					$price->price_end_date = (int)$metaMap['_sale_price_dates_to'];
				}

				$this->db->insertObject('#__hikashop_price', $price);
			}

			$this->options->last_woo_prod = $row->ID;
			$imported++;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('PRICES'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_woo_prod." WHERE config_namekey = 'woo_import_last_woo_prod'");
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
			$this->options->last_woo_prod = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'woo_import_current'");
			$this->db->execute();

			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'woo_import_last_woo_prod'");
			$this->db->execute();
		}

		$fileClass = hikashop_get('class.file');

		$count = 20; // Images take longer (copying), lower batch size
		$imported = 0;

		$sql = "SELECT p.ID ".
			   "FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` AS p ".
			   "WHERE p.post_type IN ('product', 'product_variation') ".
			   "AND p.post_status IN ('publish','draft','private') ".
			   "AND p.ID > " . (int)$this->options->last_woo_prod . " ".
			   "ORDER BY p.ID ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('HIKA_IMAGES'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_prod` WHERE woo_id = ".(int)$row->ID);
			$hkId = $this->db->loadResult();

			if(!$hkId) {
				$this->options->last_woo_prod = $row->ID;
				continue;
			}

			$sqlMeta = "SELECT meta_key, meta_value FROM `".$this->wooDatabase."`.`".$this->wooPrefix."postmeta` WHERE post_id = ".(int)$row->ID." AND meta_key IN ('_thumbnail_id', '_product_image_gallery')";
			$this->db->setQuery($sqlMeta);
			$metas = $this->db->loadObjectList();

			$imageIds = array();
			foreach($metas as $m) {
				if($m->meta_key == '_thumbnail_id' && !empty($m->meta_value)) {
					array_unshift($imageIds, $m->meta_value);
				} elseif($m->meta_key == '_product_image_gallery' && !empty($m->meta_value)) {
					$gallery = explode(',', $m->meta_value);
					foreach($gallery as $gid) $imageIds[] = trim($gid);
				}
			}
			$imageIds = array_unique($imageIds);

			if(!empty($imageIds)) {
				$sqlAtt = "SELECT p.ID, p.post_title, pm.meta_value as filepath ".
						  "FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` AS p ".
						  "LEFT JOIN `".$this->wooDatabase."`.`".$this->wooPrefix."postmeta` AS pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attached_file' ".
						  "WHERE p.ID IN (".implode(',', $imageIds).")";
				$this->db->setQuery($sqlAtt);
				$attachments = $this->db->loadObjectList();

				foreach($attachments as $att) {
					if(empty($att->filepath)) continue;

					$source = rtrim($this->wooPath, DS) . DS . 'wp-content' . DS . 'uploads' . DS . str_replace('/', DS, $att->filepath);

					$fileName = basename($att->filepath);

					$dest = $this->options->uploadfolder . $fileName;

					if(file_exists($source)) {
						if(JFile::copy($source, $dest)) {
							$file = new stdClass();
							$file->file_name = $fileName;
							$file->file_description = $att->post_title;
							$file->file_path = $fileName;
							$file->file_type = 'product';
							$file->file_ref_id = $hkId;
							$fileClass->save($file);
						}
					}
				}
			}

			$this->options->last_woo_prod = $row->ID;
			$imported++;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('HIKA_IMAGES'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_woo_prod." WHERE config_namekey = 'woo_import_last_woo_prod'");
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
			$this->options->last_woo_user = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'woo_import_current'");
			$this->db->execute();

			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'woo_import_last_woo_user'");
			$this->db->execute();
		}

		jimport('joomla.user.helper');
		$userClass = hikashop_get('class.user');

		$count = 50;
		$imported = 0;

		$sql = "SELECT ID, user_login, user_pass, user_nicename, user_email, user_registered, display_name ".
			   "FROM `".$this->wooDatabase."`.`".$this->wooPrefix."users` ".
			   "WHERE ID > " . (int)$this->options->last_woo_user . " ".
			   "ORDER BY ID ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('USERS'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_user` WHERE woo_id = ".(int)$row->ID);
			if($this->db->loadResult()) {
				$this->options->last_woo_user = $row->ID;
				continue;
			}


			$userId = 0;
			$this->db->setQuery("SELECT id FROM `#__users` WHERE email = ".$this->db->quote($row->user_email));
			$existingId = $this->db->loadResult();

			if($existingId) {
				$userId = $existingId;
				echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> User '.$row->user_email.' already exists (ID: '.$userId.'). Mapping only.</p>';
			} else {
				$user = new JUser();
				$user->name = $row->display_name ? $row->display_name : $row->user_nicename;
				$user->username = $row->user_login;
				$user->email = $row->user_email;
				$user->password = $row->user_pass; // Store WP Hash directly
				$user->registerDate = $row->user_registered;
				$user->groups = array(2); // Registered group usually

				$this->db->setQuery("SELECT id FROM `#__users` WHERE username = ".$this->db->quote($row->user_login));
				if($this->db->loadResult()) {
					$user->username = $row->user_login . '_' . $row->ID;
				}

				if(!$user->save()) {
					echo '<p style="color:red">'.JText::sprintf('HK_IMPORT_USER_SAVE_ERROR', $row->user_email, $user->getError()).'</p>';
					$this->options->last_woo_user = $row->ID;
					continue;
				}
				$userId = $user->id;
			}

			if($userId) {

				$hkUser = $userClass->get($userId);
				if(empty($hkUser)) {
					$hkUser = new stdClass();
					$hkUser->user_cms_id = $userId;
					$hkUser->user_email = $row->user_email;

					$sqlMeta = "SELECT meta_key, meta_value FROM `".$this->wooDatabase."`.`".$this->wooPrefix."usermeta` WHERE user_id = ".(int)$row->ID;
					$this->db->setQuery($sqlMeta);
					$metas = $this->db->loadObjectList();
					$metaMap = array();
					if(!empty($metas)){
						foreach($metas as $m) $metaMap[$m->meta_key] = $m->meta_value;

						$customFields = $this->importCustomFields('user', $metaMap);
						foreach($customFields as $k => $v) {
							$hkUser->$k = $v;
						}
					}
					$userClass->save($hkUser);
				}

				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_woo_user` (woo_id, hk_id) VALUES (".(int)$row->ID.", ".(int)$userId.")");
				$this->db->execute();

				$imported++;
			}

			$this->options->last_woo_user = $row->ID;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('USERS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_woo_user." WHERE config_namekey = 'woo_import_last_woo_user'");
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
			$this->options->last_woo_user = 0; 
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'woo_import_current'");
			$this->db->execute();

			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'woo_import_last_woo_user'");
			$this->db->execute();
		}

		$addressClass = hikashop_get('class.address');

		$count = 50; 
		$imported = 0;

		$sql = "SELECT u.ID, map.hk_id ".
			   "FROM `".$this->wooDatabase."`.`".$this->wooPrefix."users` AS u ".
			   "INNER JOIN `#__hikashop_woo_user` AS map ON u.ID = map.woo_id ".
			   "WHERE u.ID > " . (int)$this->options->last_woo_user . " ".
			   "ORDER BY u.ID ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('ADDRESSES'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$sqlMeta = "SELECT meta_key, meta_value FROM `".$this->wooDatabase."`.`".$this->wooPrefix."usermeta` WHERE user_id = ".(int)$row->ID;
			$this->db->setQuery($sqlMeta);
			$metas = $this->db->loadObjectList();
			$metaMap = array();
			foreach($metas as $m) $metaMap[$m->meta_key] = $m->meta_value;

			$billing = array();
			foreach($metaMap as $k => $v) {
				if(strpos($k, 'billing_') === 0 && !empty($v)) {
					$billing[substr($k, 8)] = $v;
				}
			}

			if(!empty($billing)) {
				$addr = new stdClass();
				$addr->address_user_id = $row->hk_id;
				$addr->address_firstname = isset($billing['first_name']) ? $billing['first_name'] : '';
				$addr->address_lastname = isset($billing['last_name']) ? $billing['last_name'] : '';
				$addr->address_company = isset($billing['company']) ? $billing['company'] : '';
				$addr->address_street = isset($billing['address_1']) ? $billing['address_1'] : '';
				$addr->address_street2 = isset($billing['address_2']) ? $billing['address_2'] : '';
				$addr->address_city = isset($billing['city']) ? $billing['city'] : '';
				$addr->address_post_code = isset($billing['postcode']) ? $billing['postcode'] : '';
				$addr->address_telephone = isset($billing['phone']) ? $billing['phone'] : '';
				$addr->address_type = 'billing'; // Or both?

				if(isset($billing['country'])) {
					$addr->address_country = $this->getZoneNamekey($billing['country'], 'country');
					if(isset($billing['state'])) {
						$addr->address_state = $this->getZoneNamekey($billing['state'], 'state', $addr->address_country);
					}
				}

				$addressClass->save($addr);
			}

			$shipping = array();
			foreach($metaMap as $k => $v) {
				if(strpos($k, 'shipping_') === 0 && !empty($v)) {
					$shipping[substr($k, 9)] = $v;
				}
			}

			if(!empty($shipping)) {
				$addr = new stdClass();
				$addr->address_user_id = $row->hk_id;
				$addr->address_firstname = isset($shipping['first_name']) ? $shipping['first_name'] : '';
				$addr->address_lastname = isset($shipping['last_name']) ? $shipping['last_name'] : '';
				$addr->address_company = isset($shipping['company']) ? $shipping['company'] : '';
				$addr->address_street = isset($shipping['address_1']) ? $shipping['address_1'] : '';
				$addr->address_street2 = isset($shipping['address_2']) ? $shipping['address_2'] : '';
				$addr->address_city = isset($shipping['city']) ? $shipping['city'] : '';
				$addr->address_post_code = isset($shipping['postcode']) ? $shipping['postcode'] : '';
				$addr->address_type = 'shipping';

				if(isset($shipping['country'])) {
					$addr->address_country = $this->getZoneNamekey($shipping['country'], 'country');
					if(isset($shipping['state'])) {
						$addr->address_state = $this->getZoneNamekey($shipping['state'], 'state', $addr->address_country);
					}
				}

				$addressClass->save($addr);
			}

			$this->options->last_woo_user = $row->ID;
			$imported++;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('ADDRESSES'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_woo_user." WHERE config_namekey = 'woo_import_last_woo_user'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}

	function getZoneNamekey($code, $type, $parentKey = '') {
		static $zoneCache = array();
		$key = $code . '_' . $type . '_' . $parentKey;
		if(isset($zoneCache[$key])) return $zoneCache[$key];

		$query = "SELECT zone_namekey FROM `#__hikashop_zone` WHERE zone_type = " . $this->db->quote($type);

		if($type == 'country') {
			$query .= " AND zone_code_2 = " . $this->db->quote($code);
		} elseif($type == 'state') {
			$query .= " AND (zone_code_2 = " . $this->db->quote($code) . " OR zone_name = " . $this->db->quote($code) . ")";
			if($parentKey) {

				$this->db->setQuery("SELECT zone_id FROM `#__hikashop_zone` WHERE zone_namekey = " . $this->db->quote($parentKey));
				$parentId = (int)$this->db->loadResult();
				if($parentId) {
					$query .= " AND zone_parent_id = " . $parentId;
				}
			}
		}

		$this->db->setQuery($query);
		$namekey = $this->db->loadResult();
		$zoneCache[$key] = $namekey;
		return $namekey;
	}
	function importCoupons()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 9).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('COUPONS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_woo_coupon = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'woo_import_current'");
			$this->db->execute();

			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'woo_import_last_woo_coupon'");
			$this->db->execute();
		}

		$discountClass = hikashop_get('class.discount');

		$count = 50; 
		$imported = 0;

		$sql = "SELECT p.ID, p.post_title, p.post_excerpt ".
			   "FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` AS p ".
			   "WHERE p.post_type = 'shop_coupon' ".
			   "AND p.post_status = 'publish' ".
			   "AND p.ID > " . (int)$this->options->last_woo_coupon . " ".
			   "ORDER BY p.ID ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('COUPONS'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$code = $row->post_title;
			$this->db->setQuery("SELECT discount_id FROM `#__hikashop_discount` WHERE discount_code = ".$this->db->quote($code));
			if($this->db->loadResult()) {
				$this->options->last_woo_coupon = $row->ID;
				continue;
			}

			$sqlMeta = "SELECT meta_key, meta_value FROM `".$this->wooDatabase."`.`".$this->wooPrefix."postmeta` WHERE post_id = ".(int)$row->ID;
			$this->db->setQuery($sqlMeta);
			$metas = $this->db->loadObjectList();
			$metaMap = array();
			foreach($metas as $m) $metaMap[$m->meta_key] = $m->meta_value;

			$discount = new stdClass();
			$discount->discount_code = $code;
			$discount->discount_published = 1;
			$discount->discount_access = 'all';
			$discount->discount_currency_id = $this->options->main_currency; // Required?

			$amount = isset($metaMap['coupon_amount']) ? (float)$metaMap['coupon_amount'] : 0;
			$type = isset($metaMap['discount_type']) ? $metaMap['discount_type'] : 'fixed_cart';

			$discount->discount_type = 'coupon';

			switch($type) {
				case 'percent':
					$discount->discount_percent_amount = $amount / 100;
					break;
				case 'fixed_cart':
				default:
					$discount->discount_flat_amount = $amount;
					break;
			}

			if(!empty($metaMap['minimum_amount'])) {
				$discount->discount_minimum_order_value = (float)$metaMap['minimum_amount'];
			}

			if(!empty($metaMap['expiry_date'])) {
				$discount->discount_end = strtotime($metaMap['expiry_date']);
			}

			if(!empty($metaMap['usage_limit'])) {
				$discount->discount_quota = (int)$metaMap['usage_limit'];
			}

			$discountClass->save($discount);

			$this->options->last_woo_coupon = $row->ID;
			$imported++;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('COUPONS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_woo_coupon." WHERE config_namekey = 'woo_import_last_woo_coupon'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}
	function importOrders()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 10).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('ORDERS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_woo_order = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'woo_import_current'");
			$this->db->execute();

			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'woo_import_last_woo_order'");
			$this->db->execute();
		}

		$orderClass = hikashop_get('class.order');
		$orderClass->sendEmailAfterOrderCreation = false;
		$addressClass = hikashop_get('class.address');
		$config = hikashop_config();

		$oldLockedSchemas = $config->get('order_locked_statuses');
		$config->set('order_locked_statuses', '');

		$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_woo_order` (`woo_id` bigint(20) unsigned NOT NULL, `hk_id` int(11) unsigned NOT NULL, PRIMARY KEY (`woo_id`)) ENGINE=MyISAM");
		$this->db->execute();

		$count = 50; 
		$imported = 0;

		if($this->options->useHPOS) {
			$sql = "SELECT id as ID, date_created_gmt as post_date, status as post_status, customer_note as post_excerpt, customer_id as post_author ".
				   "FROM `".$this->wooDatabase."`.`".$this->wooPrefix."wc_orders` ".
				   "WHERE type = 'shop_order' ".
				   "AND id > " . (int)$this->options->last_woo_order . " ".
				   "ORDER BY id ASC LIMIT " . $count;
		} else {
			$sql = "SELECT p.ID, p.post_date, p.post_status, p.post_excerpt, p.post_author ".
				   "FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` AS p ".
				   "WHERE p.post_type = 'shop_order' ".
				   "AND p.ID > " . (int)$this->options->last_woo_order . " ".
				   "ORDER BY p.ID ASC LIMIT " . $count;
		}

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('ORDERS'))).'</p>';
			return true;
		}

		if(empty($this->options->main_currency)) {
			$params = null;
			$currencyClass = hikashop_get('class.currency');
			$mainCurrency = $currencyClass->getMain();
			if(!empty($mainCurrency)) {
				$this->options->main_currency = $mainCurrency->currency_id;
			} else {
				echo '<div style="color:red; font-weight:bold;">ERROR: No Main Currency Found. Please ensure HikaShop has a default currency.</div>';
			}
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_order` WHERE woo_id = ".(int)$row->ID);
			if($this->db->loadResult()) {
				$this->options->last_woo_order = $row->ID;
				continue;
			}

			if($this->options->useHPOS) {
				$sqlMeta = "SELECT meta_key, meta_value FROM `".$this->wooDatabase."`.`".$this->wooPrefix."wc_orders_meta` WHERE order_id = ".(int)$row->ID;
			} else {
				$sqlMeta = "SELECT meta_key, meta_value FROM `".$this->wooDatabase."`.`".$this->wooPrefix."postmeta` WHERE post_id = ".(int)$row->ID;
			}
			$this->db->setQuery($sqlMeta);
			$metas = $this->db->loadObjectList();
			$metaMap = array();
			foreach($metas as $m) $metaMap[$m->meta_key] = $m->meta_value;

			if($this->options->useHPOS) {
				$sqlAddr = "SELECT * FROM `".$this->wooDatabase."`.`".$this->wooPrefix."wc_order_addresses` WHERE order_id = ".(int)$row->ID;
				$this->db->setQuery($sqlAddr);
				$addrs = $this->db->loadObjectList();
				foreach($addrs as $a) {
					$prefix = ($a->address_type == 'billing') ? '_billing_' : '_shipping_';
					$metaMap[$prefix.'first_name'] = $a->first_name;
					$metaMap[$prefix.'last_name'] = $a->last_name;
					$metaMap[$prefix.'company'] = $a->company;
					$metaMap[$prefix.'address_1'] = $a->address_1;
					$metaMap[$prefix.'address_2'] = $a->address_2;
					$metaMap[$prefix.'city'] = $a->city;
					$metaMap[$prefix.'state'] = $a->state;
					$metaMap[$prefix.'postcode'] = $a->postcode;
					$metaMap[$prefix.'country'] = $a->country;
					if($a->address_type == 'billing') {
						$metaMap['_billing_email'] = $a->email;
						$metaMap['_billing_phone'] = $a->phone;
					}
				}

				$this->db->setQuery("SELECT * FROM `".$this->wooDatabase."`.`".$this->wooPrefix."wc_orders` WHERE id = ".(int)$row->ID);
				$orderRow = $this->db->loadObject();
				if($orderRow) {
					if(!isset($metaMap['_order_total'])) $metaMap['_order_total'] = $orderRow->total_amount;
					if(!isset($metaMap['_order_tax'])) $metaMap['_order_tax'] = $orderRow->tax_amount;
					if(!isset($metaMap['_order_currency'])) $metaMap['_order_currency'] = $orderRow->currency;
					if(!isset($metaMap['_customer_user'])) $metaMap['_customer_user'] = $orderRow->customer_id;
				}
			}


			$order = new stdClass();
			$order->order_number = $row->ID; // Or get meta
			$order->order_created = strtotime($row->post_date);
			$order->order_type = 'sale';

			switch($row->post_status) {
				case 'wc-completed': $order->order_status = 'confirmed'; break;
				case 'wc-processing': $order->order_status = 'confirmed'; break; // or created?
				case 'wc-pending': $order->order_status = 'pending'; break;
				case 'wc-on-hold': $order->order_status = 'pending'; break;
				case 'wc-cancelled': $order->order_status = 'cancelled'; break;
				case 'wc-refunded': $order->order_status = 'refunded'; break;
				case 'wc-failed': $order->order_status = 'cancelled'; break;
				default: $order->order_status = 'created'; break;
			}

			$wooUserId = isset($metaMap['_customer_user']) ? (int)$metaMap['_customer_user'] : 0;
			$hkUserId = 0;
			if($wooUserId) {
				$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_user` WHERE woo_id = ".$wooUserId);
				$cmsId = $this->db->loadResult();
				if($cmsId) {
					$this->db->setQuery("SELECT user_id FROM `#__hikashop_user` WHERE user_cms_id = ".(int)$cmsId);
					$hkUserId = $this->db->loadResult();
				}
			}

			if(empty($hkUserId) && !empty($metaMap['_billing_email'])) {
				$userClass = hikashop_get('class.user');
				$existingUser = $userClass->get($metaMap['_billing_email'], 'user_email');
				if($existingUser) {
					$hkUserId = $existingUser->user_id;
				} else {
					$hkUser = new stdClass();
					$hkUser->user_cms_id = 0; // Guest
					$hkUser->user_email = $metaMap['_billing_email'];
					$userClass->save($hkUser);
					$hkUserId = $hkUser->user_id;
				}
			}

			$order->order_user_id = $hkUserId ? $hkUserId : 0;

			$order->order_full_price = isset($metaMap['_order_total']) ? (float)$metaMap['_order_total'] : 0;
			$order->order_discount_price = isset($metaMap['_order_discount']) ? (float)$metaMap['_order_discount'] : 0; // Legacy
			if(isset($metaMap['_cart_discount'])) $order->order_discount_price += (float)$metaMap['_cart_discount'];


			$order->order_shipping_price = isset($metaMap['_order_shipping']) ? (float)$metaMap['_order_shipping'] : 0;

			$totalTax = (isset($metaMap['_order_tax']) ? (float)$metaMap['_order_tax'] : 0) +
						(isset($metaMap['_order_shipping_tax']) ? (float)$metaMap['_order_shipping_tax'] : 0) + 
						(isset($metaMap['_cart_tax']) ? (float)$metaMap['_cart_tax'] : 0);

			if($totalTax > 0) {

				$taxInfo = new stdClass();
				$taxInfo->tax_namekey = 'VAT'; // Unique key
				$taxInfo->tax_amount = $totalTax;
				$taxInfo->amount = $order->order_full_price - $totalTax; // rough base amount
				$taxInfo->tax_rate = 0;

				$order->order_tax_info = array('VAT' => $taxInfo);
			}

			$order->order_currency_id = $this->options->main_currency; // Map from _order_currency?

			$addr = new stdClass();
			$addr->address_user_id = $hkUserId;
			$addr->address_firstname = isset($metaMap['_billing_first_name']) ? $metaMap['_billing_first_name'] : '';
			$addr->address_lastname = isset($metaMap['_billing_last_name']) ? $metaMap['_billing_last_name'] : '';
			$addr->address_company = isset($metaMap['_billing_company']) ? $metaMap['_billing_company'] : '';
			$addr->address_street = isset($metaMap['_billing_address_1']) ? $metaMap['_billing_address_1'] : '';
			$addr->address_street2 = isset($metaMap['_billing_address_2']) ? $metaMap['_billing_address_2'] : '';
			$addr->address_city = isset($metaMap['_billing_city']) ? $metaMap['_billing_city'] : '';
			$addr->address_post_code = isset($metaMap['_billing_postcode']) ? $metaMap['_billing_postcode'] : '';
			$addr->address_telephone = isset($metaMap['_billing_phone']) ? $metaMap['_billing_phone'] : '';
			$addr->address_type = 'billing';
			$addr->address_published = 0;

			if(isset($metaMap['_billing_country'])) {
				$addr->address_country = $this->getZoneNamekey($metaMap['_billing_country'], 'country');
				if(isset($metaMap['_billing_state'])) {
					$addr->address_state = $this->getZoneNamekey($metaMap['_billing_state'], 'state', $addr->address_country);
				}
			}
			$order->order_billing_address_id = $addressClass->save($addr);
			if(!$order->order_billing_address_id) {
				echo '<fieldset style="border:1px solid red; padding:5px; margin:5px;"><legend>Billing Address Save Failed</legend>';
				echo 'Error: ' .$addressClass->getError() . '<br/>';
				echo 'Zone Map (FR): ' . $this->getZoneNamekey('FR', 'country') . '<br/>';
				echo '<pre>' . print_r($addr, true) . '</pre></fieldset>';
				exit;
			}

			$addr = clone $addr; // Base on billing 
			$addr->address_id = 0;
			$addr->address_type = 'shipping';
			$addr->address_firstname = isset($metaMap['_shipping_first_name']) ? $metaMap['_shipping_first_name'] : '';
			$addr->address_lastname = isset($metaMap['_shipping_last_name']) ? $metaMap['_shipping_last_name'] : '';
			$addr->address_company = isset($metaMap['_shipping_company']) ? $metaMap['_shipping_company'] : '';
			$addr->address_street = isset($metaMap['_shipping_address_1']) ? $metaMap['_shipping_address_1'] : '';
			$addr->address_street2 = isset($metaMap['_shipping_address_2']) ? $metaMap['_shipping_address_2'] : '';
			$addr->address_city = isset($metaMap['_shipping_city']) ? $metaMap['_shipping_city'] : '';
			$addr->address_post_code = isset($metaMap['_shipping_postcode']) ? $metaMap['_shipping_postcode'] : '';
			$addr->address_published = 0;
			if(isset($metaMap['_shipping_country'])) {
				$addr->address_country = $this->getZoneNamekey($metaMap['_shipping_country'], 'country');
				if(isset($metaMap['_shipping_state'])) {
					$addr->address_state = $this->getZoneNamekey($metaMap['_shipping_state'], 'state', $addr->address_country);
				}
			}
			$order->order_shipping_address_id = $addressClass->save($addr);
			if(!$order->order_shipping_address_id) {
				echo '<fieldset style="border:1px solid red; padding:5px; margin:5px;"><legend>Shipping Address Save Failed</legend>';
				echo 'Error: ' .$addressClass->getError() . '<br/>';
				echo '<pre>' . print_r($addr, true) . '</pre></fieldset>';
				exit;
			}

			$order->order_woo_id = $row->ID;

			$customFields = $this->importCustomFields('order', $metaMap);
			foreach($customFields as $k => $v) {
				$order->$k = $v;
			}


			$hkId = $orderClass->save($order);

			if($hkId) {
				$this->db->setQuery("INSERT IGNORE INTO `#__hikashop_woo_order` (woo_id, hk_id) VALUES (".(int)$row->ID.", ".(int)$hkId.")");
				$this->db->execute();
				$imported++;
			} else {
				echo '<p style="color:red">'.JText::sprintf('HK_IMPORT_SAVE_FAILED', JText::_('ORDERS'), $row->ID).'</p>';
			}

			$this->options->last_woo_order = $row->ID;
		}

		$config->set('order_locked_statuses', $oldLockedSchemas);

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('ORDERS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_woo_order." WHERE config_namekey = 'woo_import_last_woo_order'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}
	function importOrderItems()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 11).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('ORDER_PRODUCTS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_woo_item = 0;
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'woo_import_current'");
			$this->db->execute();

			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'woo_import_last_woo_item'");
			$this->db->execute();
		}

		$orderProductClass = hikashop_get('class.order_product');

		$count = 100; // Items are plenty, proceed slightly faster
		$imported = 0;

		$sql = "SELECT order_item_id, order_item_name, order_id ".
			   "FROM `".$this->wooDatabase."`.`".$this->wooPrefix."woocommerce_order_items` ".
			   "WHERE order_item_type = 'line_item' ".
			   "AND order_item_id > " . (int)$this->options->last_woo_item . " ".
			   "ORDER BY order_item_id ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('ORDER_PRODUCTS'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_order` WHERE woo_id = ".(int)$row->order_id);
			$orderId = $this->db->loadResult();

			if(!$orderId) {
				$this->db->setQuery("SELECT order_id FROM `#__hikashop_order` WHERE order_woo_id = ".(int)$row->order_id);
				$orderId = $this->db->loadResult();
			}

			if(!$orderId) {
				$this->options->last_woo_item = $row->order_item_id;
				continue;
			}

			$sqlMeta = "SELECT meta_key, meta_value FROM `".$this->wooDatabase."`.`".$this->wooPrefix."woocommerce_order_itemmeta` WHERE order_item_id = ".(int)$row->order_item_id;
			$this->db->setQuery($sqlMeta);
			$metas = $this->db->loadObjectList();
			$metaMap = array();
			foreach($metas as $m) $metaMap[$m->meta_key] = $m->meta_value;

			$productId = isset($metaMap['_product_id']) ? (int)$metaMap['_product_id'] : 0;
			$hkProductId = 0;

			if($productId) {
				$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_prod` WHERE woo_id = ".$productId);
				$hkProductId = $this->db->loadResult();
			}

			$op = new stdClass();
			$op->order_id = $orderId;
			$op->product_id = $hkProductId ? $hkProductId : 0; // If 0, it's a deleted product or untracked
			$op->order_product_name = $row->order_item_name;
			$op->order_product_code = 'product_'.$productId; // Fallback or fetch from product?
			if($hkProductId) {
				$this->db->setQuery("SELECT product_code FROM `#__hikashop_product` WHERE product_id = ".$hkProductId);
				$code = $this->db->loadResult();
				if($code) $op->order_product_code = $code;
			}

			$op->order_product_quantity = isset($metaMap['_qty']) ? (int)$metaMap['_qty'] : 1;

			$lineTotal = isset($metaMap['_line_total']) ? (float)$metaMap['_line_total'] : 0;
			$lineTax = isset($metaMap['_line_tax']) ? (float)$metaMap['_line_tax'] : 0;

			if($op->order_product_quantity > 0) {
				$op->order_product_price = $lineTotal / $op->order_product_quantity;
				$op->order_product_tax = $lineTax / $op->order_product_quantity;
			} else {
				$op->order_product_price = 0;
				$op->order_product_tax = 0;
			}

			$op->order_product_total_price = $lineTotal;
			$op->order_product_total_price_no_vat = $lineTotal; // Assuming Woo stores ex-tax in line_total. Yes it does.

			$ops = array($op);
			if($orderProductClass->save($ops)) {
				$imported++;
			} else {
				echo '<p style="color:red">'.JText::sprintf('HK_IMPORT_SAVE_FAILED', $row->order_item_name, $row->order_id).'</p>';
			}

			$this->options->last_woo_item = $row->order_item_id;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('ORDER_PRODUCTS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_woo_item." WHERE config_namekey = 'woo_import_last_woo_item'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}
	function importDownloads()
	{
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 12).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('DOWNLOADS')).'</p>';

		if($this->options->current == 0) {
			$this->options->last_woo_prod = 0; // Reuse product tracker
			$this->options->current = 1;
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=1 WHERE config_namekey = 'woo_import_current'");
			$this->db->execute();

			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'woo_import_last_woo_prod'");
			$this->db->execute();
		}

		$fileClass = hikashop_get('class.file');

		$count = 50; 
		$imported = 0;

		$sql = "SELECT p.ID ".
			   "FROM `".$this->wooDatabase."`.`".$this->wooPrefix."posts` AS p ".
			   "INNER JOIN `".$this->wooDatabase."`.`".$this->wooPrefix."postmeta` AS pm ON p.ID = pm.post_id ".
			   "WHERE p.post_type = 'product' ".
			   "AND pm.meta_key = '_downloadable' AND pm.meta_value = 'yes' ".
			   "AND p.ID > " . (int)$this->options->last_woo_prod . " ".
			   "GROUP BY p.ID ".
			   "ORDER BY p.ID ASC LIMIT " . $count;

		$this->db->setQuery($sql);
		$rows = $this->db->loadObjectList();

		if(empty($rows)) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_NO_MORE', strtolower(JText::_('DOWNLOADS'))).'</p>';
			return true;
		}

		foreach($rows as $row) {
			$this->db->setQuery("SELECT hk_id FROM `#__hikashop_woo_prod` WHERE woo_id = ".(int)$row->ID);
			$hkId = $this->db->loadResult();

			if(!$hkId) {
				$this->options->last_woo_prod = $row->ID;
				continue;
			}

			$sqlMeta = "SELECT meta_value FROM `".$this->wooDatabase."`.`".$this->wooPrefix."postmeta` WHERE post_id = ".(int)$row->ID." AND meta_key = '_downloadable_files'";
			$this->db->setQuery($sqlMeta);
			$serialized = $this->db->loadResult();

			if(!empty($serialized)) {
				$files = unserialize($serialized);
				if(is_array($files)) {
					foreach($files as $f) {
						$fileUrl = isset($f['file']) ? $f['file'] : '';
						$fileName = isset($f['name']) ? $f['name'] : basename($fileUrl);

						if(empty($fileUrl)) continue;

						$source = '';
						$isRemote = false;

						if(strpos($fileUrl, 'http') === 0) {
							if(strpos($fileUrl, '/wp-content/uploads/') !== false) {
								$parts = explode('/wp-content/uploads/', $fileUrl);
								$relPath = end($parts);
								$possibleSource = rtrim($this->wooPath, DS) . DS . 'wp-content' . DS . 'uploads' . DS . str_replace('/', DS, $relPath);
								if(file_exists($possibleSource)) {
									$source = $possibleSource;
								} else {
									$isRemote = true; // File not found locally, keep as URL
								}
							} else {
								$isRemote = true;
							}
						} else {
							$source = rtrim($this->wooPath, DS) . DS . str_replace('/', DS, $fileUrl);
						}

						if($isRemote) {
							$file = new stdClass();
							$file->file_name = $fileName;
							$file->file_path = $fileUrl; 
							$file->file_type = 'product';
							$file->file_ref_id = $hkId;
							$file->file_free_download = 0;
							$fileClass->save($file);
						} elseif($source && file_exists($source)) {
							$destName = basename($source);
							$dest = $this->options->uploadsecurefolder . $destName; // Use secure folder for downloads

							if(JFile::copy($source, $dest)) {
								$file = new stdClass();
								$file->file_name = $fileName;
								$file->file_path = $destName; 
								$file->file_type = 'product';
								$file->file_ref_id = $hkId;
								$file->file_free_download = 0; 
								$fileClass->save($file);
							}
						}
					}
				}
			}

			$this->options->last_woo_prod = $row->ID;
			$imported++;
		}

		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('DOWNLOADS'), $imported).'</p>';

		if(!empty($rows)) {
			$this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->last_woo_prod." WHERE config_namekey = 'woo_import_last_woo_prod'");
			$this->db->execute();
			$this->refreshPage = true;
			return false;
		}

		return true;
	}
	function importCustomFields($table, $metas) {
		if(!isset($this->fieldCache)) $this->fieldCache = array();
		if(!isset($this->fieldCache[$table])) {
			$this->db->setQuery("SELECT field_namekey FROM `#__hikashop_field` WHERE field_table = ".$this->db->quote($table));
			$this->fieldCache[$table] = $this->db->loadColumn();
			if(empty($this->fieldCache[$table])) $this->fieldCache[$table] = array();
		}

		$data = array();
		$fieldClass = hikashop_get('class.field');

		foreach($metas as $k => $v) {
			if(strpos($k, '_') === 0) continue;
			if(strpos($k, 'attribute_') === 0) continue; 
			if($k === 'total_sales') continue; 
			if(strpos($k, 'billing_') === 0) continue;
			if(strpos($k, 'shipping_') === 0) continue;
			if(strpos($k, 'wp_') === 0) continue;

			$namekey = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $k));

			if(empty($namekey) || is_numeric($namekey)) continue;
			if(in_array($namekey, array('product_id','order_id','user_id','category_id','address_id'))) continue;

			if(!in_array($namekey, $this->fieldCache[$table])) {
				$field = new stdClass();
				$field->field_realname = $k;
				$field->field_namekey = $namekey;
				$field->field_table = $table;
				$field->field_type = 'text';
				if(is_array($v) || is_object($v)) $v = json_encode($v);

				if(strlen((string)$v) > 200) $field->field_type = 'area';
				$field->field_published = 1;
				$field->field_access = 'all';
				$field->field_backend = 1;
				$field->field_frontcomp = 1; 

				if($fieldClass->save($field)) {
					$this->fieldCache[$table][] = $namekey;
				} else {
					continue;
				}
			}

			if(is_array($v) || is_object($v)) $v = json_encode($v);
			$data[$namekey] = $v;
		}
		return $data;
	}

    function finishImport() { 
        $this->importRebuildTree();
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

		$headerTitle = JText::sprintf('PRODUCTS_FROM_X', 'WooCommerce');
		$subText = JText::_('HIKA_IMPORT_PROCESSING');
		$headerColor = '#96588a';
		$icon = 'icon-loop';

		if($state >= 13) {
			$subText = JText::_('HIKA_IMPORT_SUCCESS');
			$headerColor = '#28a745';
			$icon = 'icon-checkmark';
		}

		echo '
		<style>
			.hk-progress-header { text-align: center; margin-bottom: 25px; }
			.hk-progress-subtitle { margin-top: 5px; font-size: 14px; }
			.hk-progress-card { background: #f8f9fa; border-radius: 8px; padding: 25px; border: 1px solid #e9ecef; }
			.hk-progress-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
			.hk-progress-step-title { font-weight: 600; font-size: 18px; color: #333; }
			.hk-progress-highlight { color: #96588a; }
			.hk-progress-badge { color: #666; font-weight: 500; font-size: 14px; background: #fff; padding: 4px 10px; border-radius: 20px; border: 1px solid #dee2e6; }
			.hk-progress-track { width: 100%; background: #e9ecef; border-radius: 12px; height: 16px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1); }
			.hk-progress-fill { height: 100%; background: linear-gradient(90deg, #96588a, #b370a6); width: '.$percent.'%; transition: width 0.5s ease-in-out; border-radius: 12px; }
			.hk-progress-note { margin-top: 15px; text-align: center; font-size: 13px; color: #888; }
			.hk-import-details { margin-top: 20px; padding: 15px; border-left: 3px solid #96588a; background: #fafafa; font-size: 14px; color: #555; }
		</style>';

		echo '
			<div class="hk-progress-header">
				<p class="hk-progress-subtitle" style="color: '.($state >= 13 ? '#28a745' : '#666').'; font-weight: '.($state >= 13 ? '600' : 'normal').';">
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
			1 => JText::_('TAXES'),
			2 => JText::_('HIKA_CATEGORIES'),
			3 => JText::_('PRODUCTS'),
			4 => JText::_('VARIANTS'),
			5 => JText::_('PRICES'),
			6 => JText::_('HIKA_IMAGES'),
			7 => JText::_('USERS'),
			8 => JText::_('ADDRESSES'),
			9 => JText::_('COUPONS'),
			10 => JText::_('ORDERS'),
			11 => JText::_('ORDER_PRODUCTS'),
			12 => JText::_('DOWNLOADS'),
			13 => JText::_('HK_IMPORT_FINISHING')
		);
		return isset($steps[$state]) ? $steps[$state] : JText::_('HIKA_IMPORT_PROCESSING');
	}

}
