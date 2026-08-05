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

class hikashopImportJ2Helper extends hikashopImportHelper
{
	var $j2_version = 3; // Default to 3, but compatible with recent versions
	var $sessionParams = '';
	var $j2prefix;
    var $copyImgDir = '';
    var $copyImgValue = '';

	function __construct(&$parent)
	{
		parent::__construct();
		$this->importName = 'j2';
		$this->sessionParams = HIKASHOP_COMPONENT.'j2';
		jimport('joomla.filesystem.file');
	}

	function importFromJ2()
	{
		@ob_clean();
        hikashop_setTitle(JText::sprintf('PRODUCTS_FROM_X','J2Store'), 'import', 'import&task=show');
		echo $this->getHtmlPage();

		$this->token = hikashop_getFormToken();
		$app = JFactory::getApplication();
		flush();

		if( isset($_GET['import']) && $_GET['import'] == '1' )
		{
			$time = microtime(true);
			$this->j2prefix = $app->getUserState($this->sessionParams.'j2Prefix');
			$processed = $this->doImport();

			if($processed)
			{
				$elasped = microtime(true) - $time;

				if( !$this->refreshPage )
					echo '<p><a'.$this->linkstyle.' href="'.hikashop_completeLink('import&task=import&importfrom=j2&'.$this->token.'=1&import=1&time='.time()).'">'.JText::_('HIKA_NEXT').'</a></p>';

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

		if( $this->refreshPage == true )
		{
            $url = hikashop_completeLink('import&task=import&importfrom=j2&'.$this->token.'=1&import=1&time='.time());
            $url = str_replace('&amp;', '&', $url); // Ensure raw URL for JS
			echo "<script type=\"text/javascript\">\r\n window.location.href = '".$url."'; \r\n</script>";
		}
		echo '</body></html>';
		exit;
	}

	function getStartPage()
	{
		$app = JFactory::getApplication();

		$returnString = '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 0).'</span></p>';
		$continue=true;

		$this->j2prefix = $app->getUserStateFromRequest($this->sessionParams.'j2Prefix', 'j2Prefix', '', 'string' );
		if (empty($this->j2prefix))
			$this->j2prefix = $this->db->getPrefix().'j2store_'; 
		elseif(substr($this->j2prefix, -1, 1) != '_')
			$this->j2prefix .= '_';

		if(strpos($this->j2prefix, '__') !== false && strpos($this->j2prefix, '#__') !== 0)
			$this->j2prefix = str_replace('__', '_', $this->j2prefix);

		$app->setUserState($this->sessionParams.'j2Prefix',$this->j2prefix);

		$actualPrefix = str_replace('#__', $this->db->getPrefix(), $this->j2prefix);
		$this->db->setQuery("SHOW TABLES LIKE '".$actualPrefix."products'");
		$table = $this->db->loadObjectList();

		if (!$table)
		{
			$returnString .= '<p style="color:red; font-size:0.9em;">'.JText::sprintf('HK_IMPORT_DATA_NOT_FOUND', $this->j2prefix.'products', 'Joomla').'</p>';
			$continue = false;
		}

		if ($continue)
		{
			$returnString = JText::sprintf('HK_IMPORT_BACKUP_AND_START', '<a '.$this->linkstyle.' href="'.hikashop_completeLink('import&task=import&importfrom=j2&'.$this->token.'=1&import=1').'">'.JText::_('HIKA_NEXT').'</a>');
		}
		$returnString .= '<a'.$this->linkstyle.' href="'.hikashop_completeLink('import&task=show').'">'.JText::_('HIKA_BACK').'</a>';
		return $returnString;
	}

	function doImport() {
		if( $this->db == null )
			return false;

		$this->loadConfiguration();
		$current = $this->options->current;

		$ret = true;
		$next = false;

        $totalSteps = 12;
        $state = (int)$this->options->state;
        $baseProgress = ($state / $totalSteps) * 100;
        $stepProgress = 0;

        switch($state) {
            case 1: // Taxes
                $total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."taxrates`");
                if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."taxrates` WHERE j2store_taxrate_id <= ".(int)$this->options->last_j2_tax) / $total);
                break;
            case 2: // Manufacturers
                $total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."manufacturers`");
                if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."manufacturers` WHERE j2store_manufacturer_id <= ".(int)$this->options->last_j2_manuf) / $total);
                break;
            case 3: // Categories
                $total = $this->countSourceRows("SELECT COUNT(*) FROM `#__categories` WHERE extension='com_content'");
                if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `#__categories` WHERE extension='com_content' AND id <= ".(int)$this->options->current) / $total);
                break;
            case 4: // Products
                $total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."products`");
                if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."products` WHERE j2store_product_id <= ".(int)$this->options->current) / $total);
                break;
            case 5: // Prices (Variants)
                $total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."variants`");
                if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."variants` WHERE j2store_variant_id <= ".(int)$this->options->current) / $total);
                break;
            case 6: // Category Links (Products same)
                 $total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."products`");
                 if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."products` WHERE j2store_product_id <= ".(int)$this->options->current) / $total);
                 break;
            case 7: // Users
                 $total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."vendors`"); // Assuming vendors table use
                 if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."vendors` WHERE j2store_user_id <= ".(int)$this->options->last_j2_user) / $total);
                 break;
            case 8: // Coupons
                 $total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."coupons`");
                 if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."coupons` WHERE j2store_coupon_id <= ".(int)$this->options->last_j2_coupon) / $total);
                 break;
            case 9: // Orders
                 $total = $this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."orders`");
                 if($total > 0) $stepProgress = ($this->countSourceRows("SELECT COUNT(*) FROM `".$this->j2prefix."orders` WHERE j2store_order_id <= ".(int)$this->options->last_j2_order) / $total);
                 break;
            case 10: // Order Items (using order ID iteration usually)
                 break;
        }

        $this->displayProgressBar($baseProgress + ($stepProgress * (100 / $totalSteps)));

		switch( $this->options->state ) {
			case 0:
				$next = $this->createTables();
				break;
			case 1:
				$next = $this->importTaxes();
				break;
			case 2:
				$next = $this->importManufacturers();
				break;
			case 3:
				$next = $this->importCategories();
				break;
			case 4:
				$next = $this->importProducts();
				break;
			case 5:
				$next = $this->importProductPrices();
				break;
            case 6:
                $next = $this->importProductCategory(); // Link products to categories
                break;
            case 7:
                $next = $this->importUsers(); 
                break;
            case 8:
                $next = $this->importCoupons(); 
                break;
            case 9:
                $next = $this->importOrders();
                break;
            case 10:
                $next = $this->importOrderItems();
                break;
            case 11:
                $next = $this->importDownloads();
                break;
			case 12:
				$next = $this->finishImport();
				$ret = false;
				break;
			default:
				$ret = false;
				break;
		}

		if( $ret && $next ) {
			$sql =  "UPDATE `#__hikashop_config` SET config_value=(config_value+1) WHERE config_namekey = 'j2_import_state'; ";
			$this->db->setQuery($sql);
			$this->db->execute();
			$sql = "UPDATE `#__hikashop_config` SET config_value=0 WHERE config_namekey = 'j2_import_current';";
			$this->db->setQuery($sql);
			$this->db->execute();

            $this->refreshPage = true;
		} else if( $current != $this->options->current ) {
			$sql =  "UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->current." WHERE config_namekey = 'j2_import_current';";
			$this->db->setQuery($sql);
			$this->db->execute();
		}

		return $ret;
	}

	function loadConfiguration()
	{
		if( $this->db == null )
			return false;

        $this->copyImgDir = HIKASHOP_ROOT;
        $this->copyImgValue = '';

		$data = array(
			'uploadfolder',
			'uploadsecurefolder',
			'main_currency',
			'j2_import_state',
			'j2_import_current',
			'j2_import_main_cat_id',
			'j2_import_max_hk_cat',
			'j2_import_max_hk_prod',
			'j2_import_last_j2_cat',
			'j2_import_last_j2_prod',
            'j2_import_last_j2_order',
            'j2_import_last_j2_user',
            'j2_import_last_j2_tax',
            'j2_import_last_j2_manuf',
            'j2_import_last_j2_coupon'
		);
		$this->db->setQuery('SELECT config_namekey, config_value FROM `#__hikashop_config` WHERE config_namekey IN ('."'".implode("','",$data)."'".');');
		$options = $this->db->loadObjectList();

		$this->options = new stdClass();
		if (!empty($options))
		{
			foreach($options as $o) {
				if( substr($o->config_namekey, 0, 10) == 'j2_import_' ) {
					$nk = substr($o->config_namekey, 10);
				} else {
					$nk = $o->config_namekey;
				}
				$this->options->$nk = $o->config_value;
			}
		}

		$this->options->uploadfolder = rtrim((string)JPath::clean((string)html_entity_decode((string)$this->options->uploadfolder)),DS.' ').DS;
		if(!preg_match('#^([A-Z]:)?/.*#',$this->options->uploadfolder)){
			if(!$this->options->uploadfolder[0]=='/' || !is_dir($this->options->uploadfolder)){
				$this->options->uploadfolder = JPath::clean(HIKASHOP_ROOT.DS.trim($this->options->uploadfolder,DS.' ').DS);
			}
		}

        $this->options->uploadsecurefolder = rtrim((string)JPath::clean((string)html_entity_decode((string)$this->options->uploadsecurefolder)),DS.' ').DS;
		if(!preg_match('#^([A-Z]:)?/.*#',$this->options->uploadsecurefolder)){
			if(!$this->options->uploadsecurefolder[0]=='/' || !is_dir($this->options->uploadsecurefolder)){
				$this->options->uploadsecurefolder = JPath::clean(HIKASHOP_ROOT.DS.trim($this->options->uploadsecurefolder,DS.' ').DS);
			}
		}

		if( !isset($this->options->state) ) {
			$this->options->state = 0;
			$this->options->current = 0;
            $this->options->last_j2_tax = 0;
            $this->options->last_j2_manuf = 0;
            $this->options->last_j2_coupon = 0;
        }

        if( empty($this->options->main_cat_id) ) {
			$element = 'product';
			$categoryClass = hikashop_get('class.category');
			$categoryClass->getMainElement($element);
			$this->options->main_cat_id = $element;
            $this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$element." WHERE config_namekey='j2_import_main_cat_id'");
            $this->db->execute();

			$this->db->setQuery("SELECT max(category_id) as 'max' FROM `#__hikashop_category`;");
			$data = $this->db->loadObjectList();
			$this->options->max_hk_cat = (int)($data[0]->max);
            $this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->max_hk_cat." WHERE config_namekey='j2_import_max_hk_cat'");
            $this->db->execute();

			$this->db->setQuery("SELECT max(product_id) as 'max' FROM `#__hikashop_product`;");
			$data = $this->db->loadObjectList();
			$this->options->max_hk_prod = (int)($data[0]->max);
            $this->db->setQuery("UPDATE `#__hikashop_config` SET config_value=".(int)$this->options->max_hk_prod." WHERE config_namekey='j2_import_max_hk_prod'");
            $this->db->execute();


            $this->db->setQuery("SHOW TABLES LIKE '".$this->j2prefix."products'");
            if($this->db->loadResult()) {
                $this->db->setQuery("SELECT max(id) as 'max' FROM `#__categories` WHERE extension='com_content'");
                $data = $this->db->loadObjectList();
                $this->options->last_j2_cat = !empty($data) ? (int)($data[0]->max) : 0;

                $this->db->setQuery("SELECT max(j2store_product_id) as 'max' FROM `".$this->j2prefix."products`");
                $data = $this->db->loadObjectList();
                $this->options->last_j2_prod = !empty($data) ? (int)($data[0]->max) : 0;

                $this->db->setQuery("SELECT max(j2store_order_id) as 'max' FROM `".$this->j2prefix."orders`");
                $data = $this->db->loadObjectList();
                $this->options->last_j2_order = !empty($data) ? (int)($data[0]->max) : 0;

                 $this->db->setQuery("SELECT max(j2store_user_id) as 'max' FROM `".$this->j2prefix."vendors`");
                $data = $this->db->loadObjectList();
                $this->options->last_j2_user = !empty($data) ? (int)($data[0]->max) : 0;
            } else {
                $this->options->last_j2_cat = 0;
                $this->options->last_j2_prod = 0;
                $this->options->last_j2_order = 0;
                $this->options->last_j2_user = 0;
            }
        }

			$sql = 'INSERT IGNORE INTO `#__hikashop_config` (`config_namekey`,`config_value`,`config_default`) VALUES '.
				"('j2_import_state',".$this->options->state.",".$this->options->state.")".
				",('j2_import_current',".$this->options->current.",".$this->options->current.")".
				",('j2_import_main_cat_id',".$this->options->main_cat_id.",".$this->options->main_cat_id.")".
				",('j2_import_max_hk_cat',".$this->options->max_hk_cat.",".$this->options->max_hk_cat.")".
				",('j2_import_max_hk_prod',".$this->options->max_hk_prod.",".$this->options->max_hk_prod.")".
				",('j2_import_last_j2_cat',".$this->options->last_j2_cat.",".$this->options->last_j2_cat.")".
				",('j2_import_last_j2_prod',".$this->options->last_j2_prod.",".$this->options->last_j2_prod.")".
                ",('j2_import_last_j2_order',".$this->options->last_j2_order.",".$this->options->last_j2_order.")".
                ",('j2_import_last_j2_user',".$this->options->last_j2_user.",".$this->options->last_j2_user.")".
				",('j2_import_last_j2_tax',".$this->options->last_j2_tax.",".$this->options->last_j2_tax.")".
				",('j2_import_last_j2_manuf',".$this->options->last_j2_manuf.",".$this->options->last_j2_manuf.")".
				",('j2_import_last_j2_coupon',".$this->options->last_j2_coupon.",".$this->options->last_j2_coupon.")".
				';';
			$this->db->setQuery($sql);
			$this->db->execute();
	}

	function createTables()
	{
		if( $this->db == null )
			return false;

		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 1).' :</span> '.JText::_('HK_IMPORT_INITIALIZATION').'</p>';

		$query='SHOW TABLES LIKE '.$this->db->Quote($this->db->getPrefix().'hikashop_j2_prod');
		$this->db->setQuery($query);
		$table = $this->db->loadResult();

		if( empty($table) )
		{
			$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_j2_prod` (`j2_id` int(10) unsigned NOT NULL DEFAULT '0', `hk_id` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`j2_id`)) ENGINE=MyISAM");
			$this->db->execute();
			$this->db->execute();
			$this->db->setQuery("CREATE TABLE IF NOT EXISTS `#__hikashop_j2_cat` (`j2_id` int(10) unsigned NOT NULL DEFAULT '0', `hk_id` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`j2_id`)) ENGINE=MyISAM");
			$this->db->execute();

            $databaseHelper = hikashop_get('helper.database');
            $databaseHelper->addColumns('address','`address_j2_order_id` INT(11) NULL');
            $databaseHelper->addColumns('order','`order_j2_id` INT(11) NULL');
            $databaseHelper->addColumns('order','INDEX ( `order_j2_id` )');
            $databaseHelper->addColumns('taxation','`tax_j2_id` INT(11) NULL');

			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::_('HK_IMPORT_TABLES_CREATED').'</p>';
		}
		else
		{
			echo '<p>'.JText::_('HK_IMPORT_TABLES_ALREADY_CREATED').'</p>';
		}

        $fields = $this->db->getTableColumns('#__hikashop_order');
        if(!isset($fields['order_customer_note'])) {
            $this->db->setQuery("ALTER TABLE `#__hikashop_order` ADD `order_customer_note` TEXT DEFAULT NULL");
            $this->db->execute();
            $this->db->setQuery("INSERT IGNORE INTO `#__hikashop_field` (`field_table`, `field_realname`, `field_namekey`, `field_type`, `field_value`, `field_published`, `field_backend`) VALUES ('order', 'order_customer_note', 'order_customer_note', 'area', 'Customer Note', 1, 1)");
            $this->db->execute();
        }
        if(!isset($fields['order_tracking_number'])) {
            $this->db->setQuery("ALTER TABLE `#__hikashop_order` ADD `order_tracking_number` VARCHAR(255) DEFAULT ''");
            $this->db->execute();
                $this->db->setQuery("INSERT IGNORE INTO `#__hikashop_field` (`field_table`, `field_realname`, `field_namekey`, `field_type`, `field_value`, `field_published`, `field_backend`) VALUES ('order', 'order_tracking_number', 'order_tracking_number', 'text', 'Tracking Number', 1, 1)");
                $this->db->execute();
        }

        $fields_addr = $this->db->getTableColumns('#__hikashop_address');
        if(!isset($fields_addr['address_j2store_order_id'])) {
            $this->db->setQuery("ALTER TABLE `#__hikashop_address` ADD `address_j2store_order_id` INT(11) DEFAULT 0");
            $this->db->execute();
        }

		return true;
	}

    function importTaxes() {
		if( $this->db == null ) return false;
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 2).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('TAXES')).'</p>';


        $sql = "INSERT IGNORE INTO `#__hikashop_tax` (`tax_namekey`, `tax_rate`) 
                SELECT CONCAT('J2_TAX_', j2tr.j2store_taxrate_id), j2tr.tax_percent / 100 
                FROM `".$this->j2prefix."taxrates` AS j2tr 
                WHERE j2tr.j2store_taxrate_id > " . (int)$this->options->last_j2_tax;
        $this->db->setQuery($sql);
        $this->db->execute();
        $total = $this->db->getAffectedRows();
		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('TAXES'), $total).'</p>';



        $element = 'tax';
		$categoryClass = hikashop_get('class.category');
		$categoryClass->getMainElement($element);

        $sql = "INSERT IGNORE INTO `#__hikashop_category` (`category_type`, `category_name`, `category_published`, `category_parent_id`, `category_namekey`)
                SELECT 'tax', j2tr.taxrate_name, CAST(j2tr.enabled AS UNSIGNED), ".(int)$element.", CONCAT('J2_TAX_CAT_', j2tr.j2store_taxrate_id)
                FROM `".$this->j2prefix."taxrates` AS j2tr
                WHERE j2tr.j2store_taxrate_id > " . (int)$this->options->last_j2_tax;
        $this->db->setQuery($sql);
        $this->db->execute();

        $sql = "INSERT IGNORE INTO `#__hikashop_taxation` (`zone_namekey`, `category_namekey`, `tax_namekey`, `taxation_published`, `taxation_type`, `tax_j2_id`)
                SELECT '', CONCAT('J2_TAX_CAT_', j2tr.j2store_taxrate_id), j2tr.taxrate_name, CAST(j2tr.enabled AS UNSIGNED), '', j2tr.j2store_taxrate_id
                FROM `".$this->j2prefix."taxrates` AS j2tr
                 WHERE j2tr.j2store_taxrate_id > " . (int)$this->options->last_j2_tax;
        $this->db->setQuery($sql);
        $this->db->execute();

        return true;
    }

    function importManufacturers() {
        if( $this->db == null ) return false;
		echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 3).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('MANUFACTURERS')).'</p>';

        $element = 'manufacturer';
		$categoryClass = hikashop_get('class.category');
		$categoryClass->getMainElement($element);

        $sql = "INSERT IGNORE INTO `#__hikashop_category` (`category_type`, `category_name`, `category_published`, `category_parent_id`, `category_namekey`, `category_description`, `category_menu`)
                SELECT 'manufacturer', 
                       CASE WHEN j2a.company <> '' THEN j2a.company ELSE CONCAT(j2a.first_name, ' ', j2a.last_name) END,
                       j2m.enabled,
                       ".(int)$element.", 
                       CONCAT('J2_MANUF_', j2m.j2store_manufacturer_id),
                       '',
                       j2m.j2store_manufacturer_id
                FROM `".$this->j2prefix."manufacturers` AS j2m
                LEFT JOIN `".$this->j2prefix."addresses` AS j2a ON j2m.address_id = j2a.j2store_address_id
                WHERE j2m.j2store_manufacturer_id > " . (int)$this->options->last_j2_manuf;

        $this->db->setQuery($sql);
        $this->db->execute();
        $total = $this->db->getAffectedRows();
		echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('MANUFACTURERS'), $total).'</p>';

        if($total > 0) {
            $this->options->max_hk_cat += $total;
            $this->db->setQuery("UPDATE `#__hikashop_config` SET config_value = ".$this->options->max_hk_cat." WHERE config_namekey = 'j2_import_max_hk_cat'; ");
			$this->db->execute();
			$this->importRebuildTree();
        }
        return true;
    }

    function importCategories() {
        echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 4).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('HIKA_CATEGORIES')).'</p>';

		if( $this->db == null )
			return false;

		jimport('joomla.filesystem.file');
		$categoryClass = hikashop_get('class.category');

		$ret = false;
		$offset = (int)$this->options->current;
		$count = 100;

        echo '<p style="margin-left:20px;">'.JText::sprintf('HK_IMPORT_PROCESSING_FROM_ID', '<strong>'.$offset.'</strong>').'</p>';


        $query = "SELECT id FROM `#__categories` WHERE extension='com_content' AND id > ".$offset." ORDER BY id ASC LIMIT 1"; // Check if any exist beyond offset
        $this->db->setQuery($query);
        $res = $this->db->loadResult();

        if(!$res) {
			echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('HIKA_CATEGORIES'), 0).'</p>';
            $this->importRebuildTree();
            return true;
        }


        $data = array(
            'category_type' => "'product'",
            'category_name' => 'c.title',
            'category_description' => 'c.description',
            'category_published' => 'c.published',
            'category_created' => 'UNIX_TIMESTAMP(c.created_time)',
            'category_modified' => 'UNIX_TIMESTAMP(c.modified_time)',
            'category_ordering' => 'c.lft', // Use lft for ordering? Or standard ordering column?
            'category_access' => "'all'",
            'category_menu' => '0',
            'category_parent_id' => (int)$this->options->main_cat_id
        );

        $data['category_namekey'] = "CONCAT('category_', c.alias)"; 

        $sql0 = "INSERT IGNORE INTO `#__hikashop_category` (`".implode('`,`',array_keys($data))."`) ".
                "SELECT ".implode(',',$data)." FROM `#__categories` AS c ".
                "LEFT JOIN `#__hikashop_j2_cat` AS hkc ON c.id = hkc.j2_id ".
                "WHERE c.extension='com_content' AND hkc.hk_id IS NULL AND c.id > ".$offset." ".
                "ORDER BY c.id ASC LIMIT ".$count;

        $this->db->setQuery($sql0);
        $this->db->execute();
        $total = $this->db->getAffectedRows();
        echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('HIKA_CATEGORIES'), $total).'</p>';


        $sql_map = "INSERT IGNORE INTO `#__hikashop_j2_cat` (`j2_id`, `hk_id`) ".
                   "SELECT c.id, hk.category_id FROM `#__categories` AS c ".
                   "INNER JOIN `#__hikashop_category` AS hk ON hk.category_namekey = CONCAT('category_', c.alias) ".
                   "WHERE c.extension='com_content' AND hk.category_type='product'";
        $this->db->setQuery($sql_map);
        $this->db->execute();


        $sql_parent = "UPDATE `#__hikashop_category` AS hk ".
                      "INNER JOIN `#__hikashop_j2_cat` AS map ON hk.category_id = map.hk_id ".
                      "INNER JOIN `#__categories` AS j2c ON map.j2_id = j2c.id ".
                      "INNER JOIN `#__hikashop_j2_cat` AS pmap ON j2c.parent_id = pmap.j2_id ".
                      "SET hk.category_parent_id = pmap.hk_id ".
                      "WHERE hk.category_type='product'";
        $this->db->setQuery($sql_parent);
        $this->db->execute();

        $sql_root = "UPDATE `#__hikashop_category` AS hk ".
                      "INNER JOIN `#__hikashop_j2_cat` AS map ON hk.category_id = map.hk_id ".
                      "INNER JOIN `#__categories` AS j2c ON map.j2_id = j2c.id ".
                      "SET hk.category_parent_id = " . (int)$this->options->main_cat_id . " ".
                      "WHERE j2c.parent_id <= 1 ". // Assuming 0 or 1 is root
                      "AND hk.category_type='product'";
        $this->db->setQuery($sql_root);
        $this->db->execute();

        $this->db->setQuery(
            "SELECT map.hk_id, j2c.params ".
            "FROM `#__hikashop_j2_cat` AS map ".
            "INNER JOIN `#__categories` AS j2c ON map.j2_id = j2c.id ".
            "WHERE j2c.params != '' AND j2c.params != '{}'"
        );
        $catImages = $this->db->loadObjectList();
        $imgCount = 0;
        foreach($catImages as $cat) {
            $params = json_decode($cat->params);
            if(!empty($params->image)) {
                $imagePath = $params->image;
                if(strpos($imagePath, '#') !== false) {
                    $imagePath = substr($imagePath, 0, strpos($imagePath, '#'));
                }

                $this->db->setQuery("SELECT file_id FROM `#__hikashop_file` WHERE file_ref_id = ".(int)$cat->hk_id." AND file_type = 'category'");
                if(!$this->db->loadResult()) {
                    $srcPath = HIKASHOP_ROOT . $imagePath;
                    $fileName = basename($imagePath);
                    $destPath = $this->options->uploadfolder . $fileName;

                    if(file_exists($srcPath) && !file_exists($destPath)) {
                        JFile::copy($srcPath, $destPath);
                    }

                    $file = new stdClass();
                    $file->file_name = $fileName;
                    $file->file_path = $fileName;
                    $file->file_type = 'category';
                    $file->file_ref_id = (int)$cat->hk_id;
                    $file->file_ordering = 1;
                    $this->db->insertObject('#__hikashop_file', $file);
                    $imgCount++;
                }
            }
        }
        if($imgCount > 0) {
            echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('HIKA_IMAGES'), $imgCount).'</p>';
        }

        if($total > 0) {
            $this->options->max_hk_cat += $total;
        }

        $this->db->setQuery("SELECT MAX(id) FROM `#__categories` WHERE extension='com_content' AND id > ".$offset." LIMIT ".$count);
        $max = $this->db->loadResult();
        if($max) {
             $this->options->current = $max;
             $this->refreshPage = true;
             return false;
        }

        $this->importRebuildTree();
        return true;
    }

    function importProducts() {
        echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 5).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('PRODUCTS')).'</p>';

		if( $this->db == null )
			return false;

		jimport('joomla.filesystem.file');
		$categoryClass = hikashop_get('class.category');

		$ret = false;
		$count = 100;
		$offset = (int)$this->options->current;

        echo '<p style="margin-left:20px;">'.JText::sprintf('HK_IMPORT_PROCESSING_FROM_ID', '<strong>'.$offset.'</strong>').'</p>';

        $this->db->setQuery("SELECT j2store_product_id FROM `".$this->j2prefix."products` WHERE j2store_product_id > ".$offset." ORDER BY j2store_product_id ASC LIMIT ".$count);
        $res = $this->db->loadResult();
        if(!$res) return true;

        $data = array(
            'product_name' => 'c.title',
            'product_description' => "CONCAT(c.introtext, '<hr id=\"system-readmore\" />', c.fulltext)",
            'product_alias' => 'c.alias',
            'product_created' => 'UNIX_TIMESTAMP(c.created)',
            'product_sale_start' => 'UNIX_TIMESTAMP(c.publish_up)',
            'product_published' => 'c.state',
            'product_type' => "'main'",
            'product_code' => "CONCAT('J2_', j2p.j2store_product_id)",
            'product_parent_id' => '0'
        );

        $sql = "INSERT IGNORE INTO `#__hikashop_product` (`".implode('`,`',array_keys($data))."`) ".
               "SELECT ".implode(',',$data)." FROM `".$this->j2prefix."products` AS j2p ".
               "INNER JOIN `#__content` AS c ON j2p.product_source_id = c.id ".
               "WHERE j2p.product_source='com_content' AND j2p.j2store_product_id > ".$offset." ".
               "ORDER BY j2p.j2store_product_id ASC LIMIT ".$count;

        $this->db->setQuery($sql);
        $this->db->execute();
        $total = $this->db->getAffectedRows();
        echo '<p '.$this->pmarginstyle.'><span'.$this->bullstyle.'>&#149;</span> '.JText::sprintf('HK_IMPORT_IMPORTED_X', JText::_('PRODUCTS'), $total).'</p>';

        $sql_map = "INSERT IGNORE INTO `#__hikashop_j2_prod` (`j2_id`, `hk_id`) ".
                   "SELECT j2p.j2store_product_id, hkp.product_id FROM `".$this->j2prefix."products` AS j2p ".
                   "INNER JOIN `#__hikashop_product` AS hkp ON hkp.product_code = CONCAT('J2_', j2p.j2store_product_id) ".
                   "LEFT JOIN `#__hikashop_j2_prod` AS map ON j2p.j2store_product_id = map.j2_id ".
                   "WHERE map.hk_id IS NULL";
        $this->db->setQuery($sql_map);
        $this->db->execute();

        $this->db->setQuery("SELECT hk.product_id, j2img.main_image, j2img.additional_images FROM `".$this->j2prefix."productimages` AS j2img INNER JOIN `#__hikashop_j2_prod` AS map ON j2img.product_id=map.j2_id INNER JOIN `#__hikashop_product` AS hk ON map.hk_id=hk.product_id WHERE map.j2_id > ".$offset." ORDER BY map.j2_id ASC LIMIT ".$count);
        $prods = $this->db->loadObjectList();

        if(!empty($prods)) {
            $fileClass = hikashop_get('class.file');
            foreach($prods as $p) {
                if(!empty($p->main_image)) {
                    $file = new stdClass();
                    $file->file_ref_id = $p->product_id;
                    $file->file_type = 'product';
                    $file->file_name = basename($p->main_image);

                    $src = JPATH_ROOT . DS . $p->main_image;
                    $src = JPath::clean($src);

                    if($src && file_exists($src)) {
                         $dest = $this->options->uploadfolder . $file->file_name;
                         if(JFile::copy($src, $dest)) {
                             $file->file_path = $file->file_name;
                         } else {
                             $file->file_path = $p->main_image; 
                         }
                    } else {
                        $file->file_path = $p->main_image;
                    }

                    $this->db->setQuery("SELECT file_id FROM #__hikashop_file WHERE file_ref_id=".(int)$p->product_id." AND file_path=".$this->db->Quote($file->file_path)." AND file_type='product'");
                    if(!$this->db->loadResult()) $fileClass->save($file);
                }

                if(!empty($p->additional_images)) {
                    $imgs = explode("\n", str_replace(array("\r", "|"), "\n", $p->additional_images));
                    foreach($imgs as $img) {
                        $img = trim($img);
                        if(empty($img)) continue;

                        $file = new stdClass();
                        $file->file_ref_id = $p->product_id;
                        $file->file_type = 'product';
                        $file->file_name = basename($img);

                        $src = JPATH_ROOT . DS . $img;
                        $src = JPath::clean($src);

                        if($src && file_exists($src)) {
                             $dest = $this->options->uploadfolder . $file->file_name;
                             if(JFile::copy($src, $dest)) {
                                 $file->file_path = $file->file_name;
                             } else {
                                 $file->file_path = $img;
                             }
                        } else {
                            $file->file_path = $img;
                        }

                        $this->db->setQuery("SELECT file_id FROM #__hikashop_file WHERE file_ref_id=".(int)$p->product_id." AND file_path=".$this->db->Quote($file->file_path)." AND file_type='product'");
                        if(!$this->db->loadResult()) $fileClass->save($file);
                    }
                }
            }
        }

        $this->db->setQuery("SELECT MAX(j2store_product_id) FROM `".$this->j2prefix."products` WHERE j2store_product_id > ".$offset." LIMIT ".$count);
        $max = $this->db->loadResult();
        if($max) {
             $this->options->current = $max;
             $this->refreshPage = true;
             return false;
        }

        return true;
    }

    function importProductPrices() {
         echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 6).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('VARIANTS').' & '.JText::_('PRICES')).'</p>';

         $offset = (int)$this->options->current;
		 $count = 50;

         echo '<p style="margin-left:20px;">'.JText::sprintf('HK_IMPORT_PROCESSING_FROM_ID', '<strong>'.$offset.'</strong>').'</p>';

         $query = "SELECT * FROM `".$this->j2prefix."variants` WHERE j2store_variant_id > ".$offset." ORDER BY j2store_variant_id ASC LIMIT ".$count;
         $this->db->setQuery($query);
         $variants = $this->db->loadObjectList();

         if(empty($variants)) return true;

         $productClass = hikashop_get('class.product');
         $currencyClass = hikashop_get('class.currency');
         $currency_id = 1; // Fallback

         foreach($variants as $var) {
             $this->db->setQuery("SELECT hk_id FROM `#__hikashop_j2_prod` WHERE j2_id=".(int)$var->product_id);
             $hk_prod_id = $this->db->loadResult();

             if(!$hk_prod_id) continue;


             if($var->is_master || $var->isdefault_variant) {
                 $code = $var->sku;
                 $this->db->setQuery("SELECT product_id FROM `#__hikashop_product` WHERE product_code = ".$this->db->Quote($code)." AND product_id <> ".(int)$hk_prod_id);
                 $existing = $this->db->loadResult();

                 if($existing) {
                     $code .= '_' . $var->j2store_variant_id;
                 }

                 $update = new stdClass();
                 $update->product_id = $hk_prod_id;
                 $update->product_code = $code;
                 $update->product_weight = $var->weight;
                 $update->product_weight_unit = 'kg'; // Default assumption or map from unit class
                 $update->product_width = $var->width;
                 $update->product_length = $var->length;
                 $update->product_height = $var->height;
                 $update->product_dimension_unit = 'cm'; // Default assumption

                 $productClass->save($update);

                 if($var->price > 0) {
                     $this->db->setQuery("INSERT INTO `#__hikashop_price` 
                         (`price_product_id`, `price_value`, `price_currency_id`, `price_min_quantity`, `price_access`) 
                         VALUES 
                         (".(int)$hk_prod_id.", ".(float)$var->price.", ".(int)$this->getHikaCurrencyId($this->options->main_currency).", 0, 'all')");
                     $this->db->execute();
                 }

                 $this->db->setQuery("SELECT quantity FROM `".$this->j2prefix."productquantities` WHERE variant_id=".(int)$var->j2store_variant_id);
                 $qty = $this->db->loadResult();
                 if($qty !== null) {
                     $update = new stdClass();
                     $update->product_id = $hk_prod_id;
                     $update->product_quantity = $qty;
                     $productClass->save($update);
                 }
             }
         }

         $this->options->current = $variants[count($variants)-1]->j2store_variant_id;
         $this->refreshPage = true;
         return false;
    }

    function importProductCategory() {
         echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 7).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('PRODUCT_CATEGORIES')).'</p>';

         $offset = (int)$this->options->current;
         $count = 100;

         echo '<p style="margin-left:20px;">'.JText::sprintf('HK_IMPORT_PROCESSING_FROM_ID', '<strong>'.$offset.'</strong>').'</p>';

         $query = "SELECT j2p.j2store_product_id, c.catid 
                   FROM `".$this->j2prefix."products` AS j2p
                   INNER JOIN `#__content` AS c ON j2p.product_source_id = c.id
                   WHERE j2p.j2store_product_id > ".$offset."
                   ORDER BY j2p.j2store_product_id ASC LIMIT ".$count;
         $this->db->setQuery($query);
         $items = $this->db->loadObjectList();

         if(empty($items)) return true;

         $categoryClass = hikashop_get('class.category');

         foreach($items as $item) {
             $this->db->setQuery("SELECT hk_id FROM `#__hikashop_j2_prod` WHERE j2_id=".(int)$item->j2store_product_id);
             $hk_prod_id = $this->db->loadResult();

             $this->db->setQuery("SELECT hk_id FROM `#__hikashop_j2_cat` WHERE j2_id=".(int)$item->catid);
             $hk_cat_id = $this->db->loadResult();

             if($hk_prod_id && $hk_cat_id) {
                 $this->db->setQuery("INSERT IGNORE INTO `#__hikashop_product_category` (product_id, category_id) VALUES (".(int)$hk_prod_id.", ".(int)$hk_cat_id.")");
                 $this->db->execute();
             }
         }

         $this->options->current = $items[count($items)-1]->j2store_product_id;
         $this->refreshPage = true;
         return false;
    }

    function importUsers() {
        if( $this->db == null ) return false;

        echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 8).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('USERS').' ('.JText::_('ADDRESSES').')').'</p>';

        $offset = (int)$this->options->current;

        echo '<p style="margin-left:20px;">'.JText::sprintf('HK_IMPORT_PROCESSING_FROM_ID', '<strong>'.$offset.'</strong>').'</p>';
        $tables = $this->db->getTableList();
        $prefix = str_replace('#__', $this->db->getPrefix(), $this->j2prefix);
        if(!in_array($prefix.'addresses', $tables)) {
             echo '<p>'.JText::sprintf('HK_IMPORT_TABLE_NOT_FOUND', JText::_('ADDRESSES')).'</p>';
             $this->refreshPage = true;
             return true;
        }

        $count = 100;

        $sql_users = "INSERT IGNORE INTO `#__hikashop_user` (`user_cms_id`,`user_email`,`user_created`) ".
                     "SELECT IFNULL(u.id, 0), IFNULL(u.email, j2a.email), UNIX_TIMESTAMP(IFNULL(u.registerDate, NOW())) ".
                     "FROM `".$this->j2prefix."addresses` AS j2a ".
                     "LEFT JOIN `#__users` AS u ON j2a.user_id = u.id ".
                     "LEFT JOIN `#__hikashop_user` AS hkusr ON IFNULL(u.id, 0) = hkusr.user_cms_id ".
                     "WHERE hkusr.user_cms_id IS NULL AND j2a.j2store_address_id > ".$offset.
                     " GROUP BY j2a.email"; // Avoid duplicates if guest has multiple addresses with same email

        $this->db->setQuery($sql_users);
        $this->db->execute();


        $addr_data = array(
            'address_user_id' => 'IFNULL(u.id, 0)',
            'address_firstname' => 'j2a.first_name',
            'address_lastname' => 'j2a.last_name',
            'address_street' => "CONCAT(j2a.address_1, CHAR(10), j2a.address_2)", 
            'address_city' => 'j2a.city',
            'address_post_code' => 'j2a.zip',
            'address_telephone' => 'j2a.phone_1',
            'address_country' => 'hkz_c.zone_namekey',
            'address_state' => 'hkz_s.zone_namekey',
            'address_published' => '1'
        );

        $sql_addr = "INSERT INTO `#__hikashop_address` (`".implode('`,`',array_keys($addr_data))."`) ".
                    "SELECT ".implode(',',$addr_data)." FROM `".$this->j2prefix."addresses` AS j2a ".
                    "LEFT JOIN `#__users` AS u ON j2a.user_id = u.id ".
                    "LEFT JOIN `".$this->j2prefix."countries` AS j2c ON j2a.country_id = j2c.j2store_country_id ".
                    "LEFT JOIN `".$this->j2prefix."zones` AS j2z ON j2a.zone_id = j2z.j2store_zone_id ".
                    "LEFT JOIN `#__hikashop_zone` AS hkz_c ON j2c.country_isocode_2 = hkz_c.zone_code_2 AND hkz_c.zone_type='country' ".
                    "LEFT JOIN `#__hikashop_zone` AS hkz_s ON j2z.zone_code = hkz_s.zone_code_2 AND hkz_s.zone_type='state' ".
                    "WHERE j2a.j2store_address_id > ".$offset." ".
                    "ORDER BY j2a.j2store_address_id ASC LIMIT ".$count;

        $this->db->setQuery($sql_addr);
        $this->db->execute();
        $imported = $this->db->getAffectedRows();

        if($imported == 0) {
            $this->refreshPage = true;
            return true;
        }

        $this->db->setQuery("SELECT MAX(j2store_address_id) FROM `".$this->j2prefix."addresses` WHERE j2store_address_id > ".$offset." ORDER BY j2store_address_id ASC LIMIT ".$count);
        $max_id = $this->db->loadResult();
        if($max_id) {
            $this->options->current = $max_id;
        } else {
             $this->options->current += $count;
        }

        $this->refreshPage = true;
        return false;
    }

    function importCoupons() {
        echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 9).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('COUPONS')).'</p>';

        $offset = (int)$this->options->current;
        $count = 50;

        echo '<p style="margin-left:20px;">'.JText::sprintf('HK_IMPORT_PROCESSING_FROM_ID', '<strong>'.$offset.'</strong>').'</p>';

        $tables = $this->db->getTableList();
        $prefix = str_replace('#__', $this->db->getPrefix(), $this->j2prefix);
        if(!in_array($prefix.'coupons', $tables)) {
             echo '<p>'.JText::sprintf('HK_IMPORT_TABLE_NOT_FOUND', JText::_('COUPONS')).'</p>';
             $this->refreshPage = true;
             return true;
        }

        $query = "SELECT * FROM `".$this->j2prefix."coupons` WHERE j2store_coupon_id > ".$offset." ORDER BY j2store_coupon_id ASC LIMIT ".$count;
        $this->db->setQuery($query);
        $coupons = $this->db->loadObjectList();

        if(empty($coupons)) {
            $this->refreshPage = true;
            return true;
        }

        foreach($coupons as $coupon) {
            $discount = new stdClass();
            $discount->discount_code = $coupon->coupon_code;
            $discount->discount_published = $coupon->enabled;
            $discount->discount_start = strtotime($coupon->valid_from);
            $discount->discount_end = strtotime($coupon->valid_to);
            $discount->discount_type = 'coupon';

            $isPercent = (strpos($coupon->value_type, 'percent') !== false);
            if($isPercent) {
                $discount->discount_percent_amount = (float)$coupon->value;
                $discount->discount_flat_amount = 0;
            } else {
                $discount->discount_flat_amount = (float)$coupon->value;
                $discount->discount_percent_amount = 0;
                $discount->discount_currency_id = $this->getHikaCurrencyId($this->options->main_currency);
            }
            $discount->discount_access = 'all';
            $discount->discount_minimum_order = (float)$coupon->min_subtotal;
            $discount->discount_quota = (int)$coupon->max_uses;
            $discount->discount_quota_per_user = (int)$coupon->max_customer_uses;

            $this->db->setQuery("SELECT discount_id FROM `#__hikashop_discount` WHERE discount_code=".$this->db->Quote($discount->discount_code));
            $exists = $this->db->loadResult();

            if(!$exists) {
                $this->db->insertObject('#__hikashop_discount', $discount);
            }
        }

        $this->options->current = $coupons[count($coupons)-1]->j2store_coupon_id;
        $this->refreshPage = true;
        return false;
    }

    function importOrders() {
        if( $this->db == null ) return false;

        echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 10).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('ORDERS')).'</p>';

        $offset = (int)$this->options->current;

        echo '<p style="margin-left:20px;">'.JText::sprintf('HK_IMPORT_PROCESSING_FROM_ID', '<strong>'.$offset.'</strong>').'</p>';




        $sql_guest = "INSERT IGNORE INTO `#__hikashop_user` (`user_email`, `user_cms_id`, `user_created_ip`, `user_created`) ".
                     "SELECT DISTINCT j2o.user_email, 0, j2o.ip_address, UNIX_TIMESTAMP(j2o.created_on) ".
                     "FROM `".$this->j2prefix."orders` AS j2o ".
                     "WHERE j2o.user_email != '' AND j2o.user_email IS NOT NULL ".
                     "AND j2o.j2store_order_id > ".$offset." AND j2o.j2store_order_id <= " . ($offset + 100);
        $this->db->setQuery($sql_guest);
        $this->db->execute();

        $data = array(
            'order_j2_id' => 'j2o.j2store_order_id',
            'order_number' => "j2o.order_id",
            'order_user_id' => 'COALESCE(hku.user_id, hku_email.user_id, 0)', // Try ID match, then Email match, else 0
            'order_created' => 'UNIX_TIMESTAMP(j2o.created_on)',
            'order_full_price' => 'j2o.order_total',
            'order_currency_id' => 'hkc.currency_id',
            'order_status' => "CASE 
                WHEN j2o.order_state LIKE '%confirm%' THEN 'confirmed'
                WHEN j2o.order_state LIKE '%cancel%' THEN 'cancelled'
                WHEN j2o.order_state LIKE '%ship%' THEN 'shipped'
                ELSE 'created' END",
            'order_type' => "'sale'",
            'order_customer_note' => 'j2o.customer_note',
            'order_tracking_number' => "j2os.ordershipping_tracking_id" 
        );

        $sql = "INSERT INTO `#__hikashop_order` (`".implode('`,`',array_keys($data))."`) ".
               "SELECT ".implode(',',$data)." FROM `".$this->j2prefix."orders` AS j2o ".
               "LEFT JOIN `#__hikashop_currency` AS hkc ON j2o.currency_code = hkc.currency_code ".
               "LEFT JOIN `#__hikashop_user` AS hku ON j2o.user_id = hku.user_cms_id ".
               "LEFT JOIN `#__hikashop_user` AS hku_email ON j2o.user_email = hku_email.user_email ". // Email fallback join
               "LEFT JOIN `".$this->j2prefix."ordershippings` AS j2os ON j2o.order_id = j2os.order_id ".
               "WHERE j2o.j2store_order_id > ".$offset." ".
               "ORDER BY j2o.j2store_order_id ASC LIMIT 100";

        $this->db->setQuery($sql);
        $this->db->execute();
        $num_orders = $this->db->getAffectedRows();

        if($num_orders == 0) {
            $this->refreshPage = true;
            return true;
        }



        $billing_data = array(
            'address_user_id' => 'j2o.user_id',
            'address_firstname' => 'j2oi.billing_first_name',
            'address_lastname' => 'j2oi.billing_last_name',
            'address_street' => "CONCAT(j2oi.billing_address_1, ' ', IFNULL(j2oi.billing_address_2, ''))",
            'address_city' => 'j2oi.billing_city',
            'address_post_code' => 'j2oi.billing_zip',
            'address_telephone' => 'j2oi.billing_phone_1',
            'address_country' => 'hkz_bc.zone_namekey',
            'address_state' => 'hkz_bs.zone_namekey',
            'address_published' => '1',
            'address_j2store_order_id' => 'j2o.j2store_order_id', // Permanent link
            'address_type' => "'billing'"
        );

        $sql_billing = "INSERT INTO `#__hikashop_address` (`".implode('`,`',array_keys($billing_data))."`) ".
                    "SELECT ".implode(',',$billing_data)." FROM `".$this->j2prefix."orderinfos` AS j2oi ".
                    "INNER JOIN `".$this->j2prefix."orders` AS j2o ON j2oi.order_id = j2o.order_id ".
                    "LEFT JOIN `".$this->j2prefix."countries` AS j2c ON j2oi.billing_country_id = j2c.j2store_country_id ".
                    "LEFT JOIN `".$this->j2prefix."zones` AS j2z ON j2oi.billing_zone_id = j2z.j2store_zone_id ".
                    "LEFT JOIN `#__hikashop_zone` AS hkz_bc ON j2c.country_isocode_2 = hkz_bc.zone_code_2 AND hkz_bc.zone_type='country' ".
                    "LEFT JOIN `#__hikashop_zone` AS hkz_bs ON j2z.zone_code = hkz_bs.zone_code_2 AND hkz_bs.zone_type='state' ".
                    "WHERE j2o.j2store_order_id > ".$offset." AND j2o.j2store_order_id <= " . ($offset + 100);

        $this->db->setQuery($sql_billing);
        $this->db->execute();

        $shipping_data = array(
            'address_user_id' => 'j2o.user_id',
            'address_firstname' => 'j2oi.shipping_first_name',
            'address_lastname' => 'j2oi.shipping_last_name',
            'address_street' => "CONCAT(j2oi.shipping_address_1, ' ', IFNULL(j2oi.shipping_address_2, ''))",
            'address_city' => 'j2oi.shipping_city',
            'address_post_code' => 'j2oi.shipping_zip',
            'address_telephone' => 'j2oi.shipping_phone_1',
            'address_country' => 'hkz_sc.zone_namekey',
            'address_state' => 'hkz_ss.zone_namekey',
            'address_published' => '1',
            'address_j2store_order_id' => 'j2o.j2store_order_id', // Permanent link
            'address_type' => "'shipping'"
        );

        $sql_shipping = "INSERT INTO `#__hikashop_address` (`".implode('`,`',array_keys($shipping_data))."`) ".
                    "SELECT ".implode(',',$shipping_data)." FROM `".$this->j2prefix."orderinfos` AS j2oi ".
                    "INNER JOIN `".$this->j2prefix."orders` AS j2o ON j2oi.order_id = j2o.order_id ".
                    "LEFT JOIN `".$this->j2prefix."countries` AS j2c ON j2oi.shipping_country_id = j2c.j2store_country_id ".
                    "LEFT JOIN `".$this->j2prefix."zones` AS j2z ON j2oi.shipping_zone_id = j2z.j2store_zone_id ".
                    "LEFT JOIN `#__hikashop_zone` AS hkz_sc ON j2c.country_isocode_2 = hkz_sc.zone_code_2 AND hkz_sc.zone_type='country' ".
                    "LEFT JOIN `#__hikashop_zone` AS hkz_ss ON j2z.zone_code = hkz_ss.zone_code_2 AND hkz_ss.zone_type='state' ".
                    "WHERE j2o.j2store_order_id > ".$offset." AND j2o.j2store_order_id <= " . ($offset + 100);

        $this->db->setQuery($sql_shipping);
        $this->db->execute();











        $sql_link_billing = "UPDATE `#__hikashop_order` AS o ".
                            "INNER JOIN `#__hikashop_address` AS a ON a.address_j2store_order_id = o.order_j2_id AND a.address_type='billing' ".
                            "SET o.order_billing_address_id = a.address_id ".
                            "WHERE o.order_j2_id > ".$offset. " AND o.order_j2_id <= " . ($offset + 100);
        $this->db->setQuery($sql_link_billing);
        $this->db->execute();

        $sql_link_shipping = "UPDATE `#__hikashop_order` AS o ".
                            "INNER JOIN `#__hikashop_address` AS a ON a.address_j2store_order_id = o.order_j2_id AND a.address_type='shipping' ".
                            "SET o.order_shipping_address_id = a.address_id ".
                            "WHERE o.order_j2_id > ".$offset. " AND o.order_j2_id <= " . ($offset + 100);
        $this->db->setQuery($sql_link_shipping);
        $this->db->execute();

        $this->db->setQuery("SELECT MAX(j2store_order_id) FROM `".$this->j2prefix."orders` WHERE j2store_order_id > ".$offset." ORDER BY j2store_order_id ASC LIMIT 100");
        $max_id = $this->db->loadResult();
        if($max_id) {
            $this->options->current = $max_id;
        } else {
             $this->options->current += 100;
        }

        $this->refreshPage = true;
        return false;
    }
    function importOrderItems() {
        echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 11).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('ORDER_PRODUCTS')).'</p>';

        $offset = (int)$this->options->current;
        $count = 100;

        echo '<p style="margin-left:20px;">'.JText::sprintf('HK_IMPORT_PROCESSING_FROM_ID', '<strong>'.$offset.'</strong>').'</p>';

        $query = "SELECT * FROM `".$this->j2prefix."orderitems` WHERE j2store_orderitem_id > ".$offset." ORDER BY j2store_orderitem_id ASC LIMIT ".$count;
        $this->db->setQuery($query);
        $items = $this->db->loadObjectList();

        if(empty($items)) {
            $this->refreshPage = true;
            return true;
        }

        foreach($items as $item) {
            $this->db->setQuery("SELECT order_id FROM `#__hikashop_order` WHERE order_number=".$this->db->Quote($item->order_id));
            $order_id = $this->db->loadResult();

            if(!$order_id) continue;

            $this->db->setQuery("SELECT hk_id FROM `#__hikashop_j2_prod` WHERE j2_id=".(int)$item->product_id);
            $product_id = $this->db->loadResult();

            if(!$product_id) continue;

            $this->db->setQuery("SELECT order_product_id FROM `#__hikashop_order_product` WHERE order_id=".(int)$order_id." AND product_id=".(int)$product_id);
            if($this->db->loadResult()) continue;

            $this->db->setQuery("INSERT INTO `#__hikashop_order_product` (`order_id`, `product_id`, `order_product_code`, `order_product_name`, `order_product_quantity`, `order_product_price`, `order_product_tax`) VALUES (".(int)$order_id.", ".(int)$product_id.", ".$this->db->Quote($item->orderitem_sku).", ".$this->db->Quote($item->orderitem_name).", ".(int)$item->orderitem_quantity.", ".(float)$item->orderitem_price.", ".(float)$item->orderitem_tax.")");
            $this->db->execute();
        }

        $this->options->current = $items[count($items)-1]->j2store_orderitem_id;
        $this->refreshPage = true;
        return false;
    }
    function importDownloads() {
        echo '<p '.$this->titlefont.'><span'.$this->titlestyle.'>'.JText::sprintf('STEP_X', 12).' :</span> '.JText::sprintf('HK_IMPORT_X', JText::_('DOWNLOADS')).'</p>';

        $offset = (int)$this->options->current;
        $count = 50;

        echo '<p style="margin-left:20px;">'.JText::sprintf('HK_IMPORT_PROCESSING_FROM_ID', '<strong>'.$offset.'</strong>').'</p>';

        $tables = $this->db->getTableList();
        $prefix = str_replace('#__', $this->db->getPrefix(), $this->j2prefix);
        if(!in_array($prefix.'productfiles', $tables)) {
             echo '<p>'.JText::sprintf('HK_IMPORT_TABLE_NOT_FOUND', JText::_('DOWNLOADS')).'</p>';
             $this->refreshPage = true;
             return true;
        }

        $query = "SELECT * FROM `".$this->j2prefix."productfiles` WHERE j2store_productfile_id > ".$offset." ORDER BY j2store_productfile_id ASC LIMIT ".$count;
        $this->db->setQuery($query);
        $files = $this->db->loadObjectList();

        if(empty($files)) {
            $this->refreshPage = true;
            return true;
        }

        $fileClass = hikashop_get('class.file');

        foreach($files as $row) {
            $this->db->setQuery("SELECT hk_id FROM `#__hikashop_j2_prod` WHERE j2_id=".(int)$row->product_id);
            $hk_prod_id = $this->db->loadResult();

            if(!$hk_prod_id) continue;

            $file = new stdClass();
            $file->file_ref_id = $hk_prod_id;
            $file->file_type = 'product'; 
            $file->file_path = $row->product_file_save_name; // J2Store often uses relative path
            $file->file_name = $row->product_file_display_name ? $row->product_file_display_name : basename($row->product_file_save_name);
            $file->file_description = '';

            $this->db->setQuery("SELECT file_id FROM `#__hikashop_file` WHERE file_ref_id=".(int)$hk_prod_id." AND file_path=".$this->db->Quote($file->file_path));
            if(!$this->db->loadResult()) {
                $fileClass->save($file);
            }
        }

        $this->options->current = $files[count($files)-1]->j2store_productfile_id;
        $this->refreshPage = true;
        return false;
    }

	function finishImport()
	{
		echo '<p'.$this->titlefont.'>'.JText::_('HIKA_IMPORT_SUCCESS').'</p>';
		$databaseHelper = hikashop_get('helper.database');
		$databaseHelper->checkdb();
        return true;
	}

    function getHikaCurrencyId($currency_code) {
        if(is_numeric($currency_code) && $currency_code > 0) {
            return (int)$currency_code;
        }

        static $currencies = array();
        if(empty($currencies)) {
             $this->db->setQuery("SELECT currency_id, currency_code FROM `#__hikashop_currency`");
             $rows = $this->db->loadObjectList();
             foreach($rows as $row) {
                 $currencies[$row->currency_code] = $row->currency_id;
             }
        }
        return isset($currencies[$currency_code]) ? $currencies[$currency_code] : 0;
    }

    function importRebuildTree() {
        $categoryClass = hikashop_get('class.category');
		$categoryClass->rebuildTree(null, 0, 0);
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

		$headerTitle = JText::sprintf('PRODUCTS_FROM_X', 'J2Store');
		$subText = JText::_('HIKA_IMPORT_PROCESSING');
		$headerColor = '#96588a';
		$icon = 'icon-loop';

		if($state >= 12) {
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
				<p class="hk-progress-subtitle" style="color: '.($state >= 12 ? '#28a745' : '#666').'; font-weight: '.($state >= 12 ? '600' : 'normal').';">
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
			2 => JText::_('MANUFACTURERS'),
			3 => JText::_('HIKA_CATEGORIES'),
			4 => JText::_('PRODUCTS'),
			5 => JText::_('VARIANTS').' & '.JText::_('PRICES'),
			6 => JText::_('PRODUCT_CATEGORIES'),
			7 => JText::_('USERS'),
			8 => JText::_('COUPONS'),
			9 => JText::_('ORDERS'),
			10 => JText::_('ORDER_PRODUCTS'),
			11 => JText::_('DOWNLOADS'),
			12 => JText::_('HK_IMPORT_FINISHING')
		);
		return isset($steps[$state]) ? $steps[$state] : JText::_('IMPORT');
	}
}
