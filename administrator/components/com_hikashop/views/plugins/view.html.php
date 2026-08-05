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
class PluginsViewPlugins extends hikashopView{
	var $type = '';
	var $ctrl = 'plugins';
	var $nameListing = 'PLUGINS';
	var $nameForm = 'PLUGINS';
	var $icon = 'puzzle-piece';
	var $triggerView = true;

	function display($tpl = null) {
		$this->paramBase = HIKASHOP_COMPONENT.'.'.$this->getName();
		$function = $this->getLayout();
		if(!method_exists($this, $function) || $this->$function())
			parent::display($tpl);
	}

	function listing() {
		$app = JFactory::getApplication();
		$db = JFactory::getDBO();

		$config =& hikashop_config();
		$this->assignRef('config', $config);

		$this->loadRef(array(
			'toggleClass' => 'helper.toggle',
			'currencyClass' => 'class.currency',
			'zoneClass' => 'class.zone',
			'searchType' => 'type.search',
		));
		$manage = hikashop_isAllowed($config->get('acl_plugins_manage','all'));
		$this->assignRef('manage',$manage);

		$type = $app->getUserStateFromRequest(HIKASHOP_COMPONENT.'.plugin_type', 'plugin_type', 'shipping');
		$this->assignRef('plugin_type',$type);

		$query = 'SELECT * FROM '.hikashop_table('extensions',false).' WHERE type=\'plugin\' AND enabled = 1 AND access <> 1 AND (folder=\'hikashoppayment\' OR folder=\'hikashopshipping\') ORDER BY ordering ASC';
		$db->setQuery($query);
		$plugins = $db->loadObjectList();
		if(!empty($plugins)) {
			$s = '(';
			foreach ($plugins as $p)
				$s .= $p->name.', ';
			$s = rtrim($s,', ').')';
			$app->enqueueMessage(JText::sprintf('PLUGIN_ACCESS_WARNING',$s),'warning');
		}

		if(!in_array($type, array('shipping', 'payment', 'plugin'))) {
			hikashop_setTitle(JText::_($this->nameListing), $this->icon, $this->ctrl);
			return false;
		}

		if($type == 'payment') {
			$this->icon  = 'hand-holding-usd fa-money';
			$this->nameListing = 'PAYMENT_METHODS';
		}
		if($type == 'shipping') {
			$this->icon  = 'shipping-fast fa-truck';
			$this->nameListing = 'SHIPPING_METHODS';
		}
		hikashop_setTitle(JText::_($this->nameListing), $this->icon, $this->ctrl.'&plugin_type='.$type);

		$cfg = array(
			'table' => $type,
			'main_key' => $type.'_id',
			'order_sql_value' => 'plugin.'.$type.'_ordering'
		);
		$searchMap = array(
			'plugin.'.$type.'_name',
			'plugin.'.$type.'_type',
			'plugin.'.$type.'_id'
		);

		$pageInfo = $this->getPageInfo($cfg['order_sql_value'], 'asc',array('plugin' => '', 'published' => 0));

		$filters = array();
		$joins = '';

		if(!empty($pageInfo->filter->plugin)) {
			$filters[] = 'extensions.element = '.$db->Quote($pageInfo->filter->plugin);
			$joins.= 'LEFT JOIN ' . hikashop_table('extensions',false) . ' AS extensions ON extensions.element = plugin.'.$type.'_type AND extensions.type=\'plugin\' AND extensions.folder = '.$db->Quote('hikashop'.$type).'';
		}
		if(!empty($pageInfo->filter->published))
			$filters[] = 'plugin.'.$type.'_published = '.(int)($pageInfo->filter->published-1);

		$order = '';

		$this->processFilters($filters, $order, $searchMap);

		JPluginHelper::importPlugin('hikashop');
		if($type == 'plugin')
			JPluginHelper::importPlugin('system');
		if(in_array($type, array('shipping', 'payment')))
			JPluginHelper::importPlugin('hikashop'.$type);
		$app = JFactory::getApplication();
		$obj =& $this;
		$this->extrafilters = array();
		$extrafilters =& $this->extrafilters;
		$app->triggerEvent('onBeforeHikaPluginConfigurationListing', array($type, &$filters, &$order, &$searchMap, &$extrafilters, &$obj));

		if($type == 'plugin') {
			$extGroup = 'hikashop';
			$systemFilter = $this->_getSystemPluginFilter();
			$db->setQuery('SELECT extension_id as id, enabled, name, element, folder FROM ' . hikashop_table('extensions', false) . ' WHERE (folder = ' . $db->Quote('hikashop') . ' OR (' . $systemFilter . ')) AND type = \'plugin\' ORDER BY name ASC');
		} else {
			$extGroup = 'hikashop' . $type;
			$db->setQuery('SELECT extension_id as id, enabled, name, element, folder FROM ' . hikashop_table('extensions', false) . ' WHERE folder = ' . $db->Quote($extGroup) . ' AND type = \'plugin\' ORDER BY name ASC');
		}
		$allExtensions = $db->loadObjectList();
		$pluginBlacklist = $this->_getPluginBlacklist();
		if(!empty($allExtensions)) {
			foreach($allExtensions as $k => $ext) {
				$blKey = $ext->folder . '.' . $ext->element;
				if(isset($pluginBlacklist[$blKey]))
					unset($allExtensions[$k]);
			}
		}

		$query = 'FROM '.hikashop_table($cfg['table']).' AS plugin '.$joins.$filters.$order;

		$db->setQuery('SELECT plugin.'.$type.'_type '.$query);
		$existingTypes = array();
		$typeResults = $db->loadColumn();
		if(!empty($typeResults)) {
			foreach($typeResults as $t_val) {
				$existingTypes[$t_val] = true;
			}
		}

		$singleInstanceRows = array();
		if(!empty($allExtensions)) {
			foreach($allExtensions as $ext) {
				if(isset($existingTypes[$ext->element]))
					continue;

				$isMultiple = false;
				try {
					$importGroup = !empty($ext->folder) ? $ext->folder : $extGroup;
					$p = hikashop_import($importGroup, $ext->element);
					if(is_object($p) && method_exists($p, 'isMultiple'))
						$isMultiple = $p->isMultiple();
				} catch(Exception $e) {}

				if($isMultiple)
					continue;

				$row = new stdClass();
				$row->{$type . '_id'} = 0;
				$row->{$type . '_name'} = JText::_($ext->name);
				$row->{$type . '_alias'} = $ext->element;
				$row->{$type . '_type'} = $ext->element;
				$row->{$type . '_published'} = (int)$ext->enabled;
				$row->{$type . '_ordering'} = 999;
				$row->_single_instance = true;
				$row->_extension_id = (int)$ext->id;

				if(!empty($pageInfo->search)) {
					$searchLower = strtolower($pageInfo->search);
					$matchFields = $row->{$type . '_name'} . ' ' . $row->{$type . '_alias'} . ' ' . $row->{$type . '_type'};
					if(strpos(strtolower($matchFields), $searchLower) === false)
						continue;
				}

				$singleInstanceRows[] = $row;
			}
		}
		$singleCount = count($singleInstanceRows);

		$this->getPageInfoTotal($query, '*');
		$mainTotal = (int)$pageInfo->elements->total;
		$pageInfo->elements->total = $mainTotal + $singleCount;

		if((int)$pageInfo->limit->start >= $pageInfo->elements->total) {
			$pageInfo->limit->start = 0;
			$app->setUserState($this->paramBase.'.limitstart', 0);
		}

		$start = (int)$pageInfo->limit->start;
		$limit = (int)$pageInfo->limit->value;
		$rows = array();

		if($start < $mainTotal) {
			$db->setQuery('SELECT * '.$query, $start, $limit);
			$rows = $db->loadObjectList();
			if(!empty($pageInfo->search)) {
				$rows = hikashop_search($pageInfo->search, $rows, array($cfg['main_key'], $type.'_params', $type.'_type', $type.'_zone_namekey1', $type.'_zone_namekey'));
			}

			$remaining = $limit - count($rows);
			if($remaining > 0 && !empty($singleInstanceRows)) {
				$rows = array_merge($rows, array_slice($singleInstanceRows, 0, $remaining));
			}
		} else {
			$singleStart = $start - $mainTotal;
			$rows = array_slice($singleInstanceRows, $singleStart, $limit);
		}

		$this->assignRef('rows', $rows);
		$pageInfo->elements->page = count($rows);

		$db->setQuery('SELECT * FROM #__hikashop_warehouse ORDER BY warehouse_name;');
		$this->warehouses = $db->loadObjectList('warehouse_id');

		$listing_columns = array();
		$pluginInterfaceClass = null;
		switch($type) {
			case 'payment':
				$pluginInterfaceClass = hikashop_get('class.payment');
				break;
			case 'shipping':
				$pluginInterfaceClass = hikashop_get('class.shipping');
				break;
			case 'plugin':
			default:
				$pluginInterfaceClass = hikashop_get('class.plugin');
				break;
		}
		if(!empty($pluginInterfaceClass) && method_exists($pluginInterfaceClass, 'fillListingColumns'))
			$pluginInterfaceClass->fillListingColumns($rows, $listing_columns, $this, $type);

		$app->triggerEvent('onAfterHikaPluginConfigurationListing', array($type, &$rows, &$listing_columns, &$obj));

		$this->assignRef('listing_columns', $listing_columns);

		$this->getPagination();
		$this->getOrdering('plugin.'.$type.'_ordering', true);

		if($type == 'plugin') {
			$systemFilter = $this->_getSystemPluginFilter();
			$db->setQuery('SELECT extension_id as id, enabled as published, name, element, folder FROM '.hikashop_table('extensions',false).' WHERE (`folder` = '.$db->Quote('hikashop').' OR ('.$systemFilter.')) AND type=\'plugin\'');
		} else {
			$db->setQuery('SELECT extension_id as id, enabled as published, name, element, folder FROM '.hikashop_table('extensions',false).' WHERE `folder` = '.$db->Quote('hikashop'.$type).' AND type=\'plugin\'');
		}
		$plugins = $db->loadObjectList('element');
		$pluginBlacklist = $this->_getPluginBlacklist();
		foreach($plugins as $k => $plugin) {
			$folder = isset($plugin->folder) ? $plugin->folder : ('hikashop' . $type);
			if(isset($pluginBlacklist[$folder . '.' . $k]))
				unset($plugins[$k]);
		}
		$this->assignRef('plugins', $plugins);
		$this->pluginValues = array();
		$this->pluginValues[] = JHTML::_('select.option', '', JText::_('ALL_PLUGINS'));
		foreach($plugins as $plugin) {
			$this->pluginValues[] = JHTML::_('select.option', $plugin->element, $plugin->name);
		}
		$this->pulbishedType = hikashop_get('type.published');

		$this->toolbar = array(
			'|',
			array('name' => 'custom', 'icon' => 'copy', 'task' => 'copy', 'alt' => JText::_('HIKA_COPY'),'display'=>$manage),
			array('name' => 'publishList', 'display' => $manage),
			array('name' => 'unpublishList', 'display' => $manage),
			array('name' => 'addNew', 'display' => $manage),
		);
		if(defined('HIKASHOP_WORDPRESS') && $manage) {
			$this->toolbar[] = array('name' => 'link', 'icon' => 'upload', 'url' => admin_url('admin.php?page=hikashop-install-plugin&plugin_type=' . urlencode($type)), 'alt' => JText::_('INSTALL_HIKASHOP_PLUGIN'));
		}
		$this->toolbar = array_merge($this->toolbar, array(
			'|',
			array('name' => 'pophelp', 'target' => $this->ctrl.'-listing'),
			'dashboard'
		));
		if($type == 'shipping' || $type == 'payment') {
			array_unshift($this->toolbar, array('name' => 'export'));
			if($manage) {
				if(empty($this->popup))
					$this->popup = hikashop_get('helper.popup');

				$importUrl = hikashop_completeLink('plugins&task=import&tmpl=component&plugin_type='.$type);
				$hiddenImportLink = $this->popup->display('&nbsp;', JText::_('IMPORT'), $importUrl, 'hidden-import-link', 500, 350, 'style="display:none"');
				$doc = JFactory::getDocument();
				$doc->addCustomTag($hiddenImportLink);

				array_unshift($this->toolbar, array('name' => 'link', 'icon' => 'import', 'url' => 'javascript:window.hikashop.openBox(document.getElementById(\'hidden-import-link\'));', 'alt' => JText::_('IMPORT')));
			}
		}
		if($type == 'shipping' && $manage) {
			$this->popup = hikashop_get('helper.popup');
			$hiddenLink = $this->popup->display('&nbsp;', JText::_('GENERATE_COPIES'), '#', 'hidden-generate-link', 1200, 650, 'style="display:none"');
			$doc = JFactory::getDocument();
			$doc->addCustomTag($hiddenLink);
			$doc->addScriptDeclaration("
			window.hikashopGenerateCopies = function() {
				var form = document.getElementById('adminForm') || document.adminForm;
				if(!form || form.boxchecked.value==0){
					alert('".JText::_('Please select an item', true)."');
					return;
				}
				var url = '".html_entity_decode(hikashop_completeLink('plugins&task=generate&tmpl=component'))."';
				var inputs = document.getElementsByName('cid[]');
				for(var i=0; i<inputs.length; i++) {
					if(inputs[i].checked) url += '&cid[]=' + inputs[i].value;
				}
				var link = document.getElementById('hidden-generate-link');
				link.href = url;
				window.hikashop.openBox(link);
			};
			");
			array_unshift($this->toolbar, array('name' => 'link', 'icon' => 'copy', 'url' => 'javascript:window.hikashopGenerateCopies();', 'alt' => JText::_('GENERATE_COPIES')));
		}

		return true;
	}

	function selectnew() {
		$app = JFactory::getApplication();
		$db = JFactory::getDBO();

		$config =& hikashop_config();
		$this->assignRef('config', $config);

		$toggle = hikashop_get('helper.toggle');
		$this->assignRef('toggleClass',$toggle);

		$manage = hikashop_isAllowed($config->get('acl_plugins_manage','all'));
		$this->assignRef('manage',$manage);

		$type = $app->getUserStateFromRequest(HIKASHOP_COMPONENT.'.plugin_type', 'plugin_type', 'shipping');
		hikashop_setTitle(JText::_($this->nameListing), $this->icon, $this->ctrl.'&task=add&plugin_type='.$type);

		if($type == 'plugin')
			$group = 'hikashop';
		else
			$group = 'hikashop' . $type;
		$db->setQuery('SELECT extension_id as id, enabled as published,name,element FROM '.hikashop_table('extensions',false).' WHERE `folder` = '.$db->Quote($group).' AND type=\'plugin\' ORDER BY enabled DESC, name ASC, ordering ASC');
		$plugins = $db->loadObjectList();

		if($type == 'plugin')
			JPluginHelper::importPlugin('hikashop');
		else
			JPluginHelper::importPlugin('hikashop'.$type);
		$app = JFactory::getApplication();
		$obj =& $this;
		$app->triggerEvent('onAfterHikaPluginConfigurationSelectionListing', array($type, &$plugins, &$obj));

		$filteredPlugins = array();
		foreach($plugins as $plugin) {
			$isMultiple = false;
			try {
				$p = hikashop_import($group, $plugin->element);
				if(is_object($p) && method_exists($p, 'isMultiple'))
					$isMultiple = $p->isMultiple();
			} catch(Exception $e) {}

			if($isMultiple) {
				$filteredPlugins[] = $plugin;
			}
		}
		$plugins = $filteredPlugins;

		$query = 'SELECT * FROM '.hikashop_table($type);
		$db->setQuery($query);
		$obj = $db->loadObject();
		if(empty($obj)) {
			$app->enqueueMessage(JText::_('EDIT_PLUGINS_BEFORE_DISPLAY'));
		}

		$currencies = null;
		if($type == 'payment') {
			$currencyClass = hikashop_get('class.currency');
			$mainCurrency = $config->get('main_currency',1);
			$currencyIds = $currencyClass->publishedCurrencies();
			if(!in_array($mainCurrency, $currencyIds))
				$currencyIds = array_merge(array($mainCurrency), $currencyIds);
			$null = null;
			$currencies = $currencyClass->getCurrencies($currencyIds, $null);

			foreach($plugins as &$plugin) {
				try{
					$p = hikashop_import('hikashoppayment', $plugin->element);
				} catch(Exception $e) { $p = null; }
				$plugin->accepted_currencies = array();
				if(isset($p->accepted_currencies))
					$plugin->accepted_currencies = $p->accepted_currencies;
				unset($plugin);
			}
		}
		$this->assignRef('plugins', $plugins);
		$this->assignRef('plugin_type',$type);
		$this->assignRef('currencies', $currencies);

		if(defined('HIKASHOP_WORDPRESS')) {
			if($type === 'payment')
				$cancel_page = 'hikashop-payment';
			elseif($type === 'shipping')
				$cancel_page = 'hikashop-shipping';
			else
				$cancel_page = 'hikashop-plugins';
			$cancel_url = admin_url('admin.php?page=' . $cancel_page);
		} else {
			$cancel_url = hikashop_completeLink('plugins&plugin_type='.$type);
		}
		$this->toolbar = array(
			array('name' => 'link', 'alt' => 'HIKA_CANCEL', 'icon' => 'cancel', 'url' => $cancel_url),
			'|',
			array('name' => 'pophelp', 'target' => $this->ctrl.'-listing'),
			'dashboard'
		);

		return true;
	}

	function install() {
		$app = JFactory::getApplication();
		$type = $app->getUserStateFromRequest(HIKASHOP_COMPONENT.'.plugin_type', 'plugin_type', 'plugin');
		$this->assignRef('plugin_type', $type);

		hikashop_setTitle(JText::_('INSTALL_HIKASHOP_PLUGIN'), 'upload', $this->ctrl.'&plugin_type='.$type);

		if($type === 'payment')
			$cancel_page = 'hikashop-payment';
		elseif($type === 'shipping')
			$cancel_page = 'hikashop-shipping';
		else
			$cancel_page = 'hikashop-plugins';
		$cancel_url = admin_url('admin.php?page=' . $cancel_page);

		$this->toolbar = array(
			array('name' => 'link', 'alt' => 'HIKA_CANCEL', 'icon' => 'cancel', 'url' => $cancel_url),
			'|',
			array('name' => 'pophelp', 'target' => $this->ctrl.'-listing'),
			'dashboard'
		);

		return true;
	}

	function form() {
		$app = JFactory::getApplication();
		$db = JFactory::getDBO();
		$task = hikaInput::get()->getVar('task');
		if(HIKASHOP_J40) {
			$wa = JFactory::getDocument()->getWebAssetManager();
			$wa->useScript('keepalive');
		}

		$config = hikashop_config();
		$this->assignRef('config', $config);

		$toggle = hikashop_get('helper.toggle');
		$this->assignRef('toggle', $toggle);

		$popup = hikashop_get('helper.popup');
		$this->assignRef('popup',$popup);

		$this->content = '';
		$this->plugin_name = hikaInput::get()->getCmd('name', '');
		if(empty($this->plugin_name)) {
			hikashop_setTitle(JText::_($this->nameListing), $this->icon, $this->ctrl);
			return false;
		}

		$this->plugin_type = '';
		$type = $app->getUserStateFromRequest(HIKASHOP_COMPONENT.'.plugin_type', 'plugin_type', 'shipping');
		if(in_array($type, array('shipping', 'payment', 'plugin'))) {
			if($type == 'plugin') {
				$pluginFolder = 'hikashop';
				$db->setQuery('SELECT folder FROM '.hikashop_table('extensions',false).' WHERE type=\'plugin\' AND element='.$db->Quote($this->plugin_name).' AND (folder='.$db->Quote('hikashop').' OR folder='.$db->Quote('system').') LIMIT 1');
				$foundFolder = $db->loadResult();
				if(!empty($foundFolder))
					$pluginFolder = $foundFolder;

				$plugin = hikashop_import($pluginFolder, $this->plugin_name);

				if(!is_subclass_of($plugin, 'hikashopPlugin')) {
					if(defined('HIKASHOP_WORDPRESS')) {
						require_once(HIKASHOP_WP_PLUGIN_DIR . 'plugin-xml-form.php');

						$pluginsClass = hikashop_get('class.plugins');
						$extensionData = $pluginsClass->getByName($pluginFolder, $this->plugin_name, false);
						$this->content = hikashop_wp_render_xml_plugin_form($this->plugin_name, $pluginFolder, $extensionData);

						$plugin = new stdClass();
						$plugin->noForm = true;
						$plugin->title = !empty($extensionData->name) ? $extensionData->name : $this->plugin_name;
						$plugin->pluginView = '';

						$this->plugin_type = $type;
						$this->assignRef('name', $this->plugin_name);
						$this->assignRef('plugin', $plugin);
						$multiple_plugin = false;
						$multiple_interface = false;
						$this->assignRef('multiple_plugin', $multiple_plugin);
						$this->assignRef('multiple_interface', $multiple_interface);
						$this->assignRef('content', $this->content);
						$this->assignRef('plugin_type', $this->plugin_type);
						$this->data = array('noForm' => true, 'toolbar' => array(
							array('name' => 'group', 'buttons' => array('apply', 'save')),
							'cancel',
							'|'
						));
						$this->toolbar = $this->data['toolbar'];
						$this->assignRef('noForm', $this->data['noForm']);

						$element = !empty($extensionData) ? $extensionData : new stdClass();
						$this->assignRef('element', $element);
						$elements = array($element);
						$this->assignRef('elements', $elements);

						hikashop_setTitle(
							$plugin->title,
							$this->icon,
							$this->ctrl . '&plugin_type=' . $type . '&task=edit&name=' . $this->plugin_name
						);
						return true;
					}

					$url = 'index.php?option=com_plugins&task=plugin.edit&extension_id=';
					$db->setQuery("SELECT extension_id as id FROM `#__extensions` WHERE `folder` = ".$db->Quote($pluginFolder)." AND `type`='plugin' AND element=".$db->Quote($this->plugin_name));
					$plugin_id = $db->loadResult();

					$app->redirect($url.$plugin_id);
				}
			} else
				$plugin = hikashop_import('hikashop' . $type, $this->plugin_name);
			if(!$plugin) {
				hikashop_setTitle(JText::_($this->nameListing), $this->icon, $this->ctrl);
				$app->enqueueMessage(JText::sprintf('PLUGIN_FILES_NOT_FOUND', $this->plugin_name), 'error');
				return false;
			}
			$this->plugin_type = $type;
		} else {
			hikashop_setTitle(JText::_($this->nameListing), $this->icon, $this->ctrl);
			return false;
		}

		$multiple_plugin = false;
		$multiple_interface = false;
		if(method_exists($plugin, 'isMultiple')) {
			$multiple_interface = true;
			$multiple_plugin = $plugin->isMultiple();
		}

		$subtask = hikaInput::get()->getCmd('subtask', '');
		if($multiple_plugin && empty($subtask)) {
			$querySelect = array();
			$queryFrom = array();
			$queryWhere = array();
			$filters = array();

			JPluginHelper::importPlugin('hikashop');
			$app = JFactory::getApplication();
			$app->triggerEvent('onHikaPluginListing', array($type, &$querySelect, &$queryFrom, &$queryWhere, &$filters));

			if(!empty($querySelect)) $querySelect = ', ' . implode(',', $querySelect);
			else $querySelect = '';

			if(!empty($queryFrom)) $queryFrom = ', ' . implode(',', $queryFrom);
			else $queryFrom = '';

			if(!empty($queryWhere)) $queryWhere = ' AND (' . implode(') AND (', $queryWhere) . ') ';
			else $queryWhere = '';

			$this->assignRef('filters', $filters);
		} else {
			$querySelect = '';
			$queryFrom = '';
			$queryWhere = '';
		}

		$query = 'SELECT plugin.* ' . $querySelect .
			' FROM ' . hikashop_table($this->plugin_type) . ' as plugin ' . $queryFrom .
			' WHERE (plugin.' . $this->plugin_type . '_type = ' . $db->Quote($this->plugin_name) . ') ' . $queryWhere .
			' ORDER BY plugin.' . $this->plugin_type . '_ordering ASC';
		$db->setQuery($query);
		$elements = $db->loadObjectList($this->plugin_type.'_id');

		if(!empty($elements)){
			$params_name = $this->plugin_type.'_params';
			foreach($elements as $k => $el) {
				if(!empty($el->$params_name)) {
					$elements[$k]->$params_name = hikashop_unserialize($el->$params_name);
				}
			}
		}

		$function = 'pluginConfiguration';
		$ctrl = '&plugin_type='.$this->plugin_type.'&task='.$task.'&name='.$this->plugin_name;
		if($multiple_plugin === true) {
			$subtask = hikaInput::get()->getCmd('subtask','');
			$ctrl .= '&subtask='.$subtask;
			if(empty($subtask)) {
				$function = 'pluginMultipleConfiguration';
			} else {
				$typeFunction = 'on' . ucfirst($this->plugin_type) . 'Configuration';
				if(method_exists($plugin, $typeFunction)) {
					$function = $typeFunction;
				}
			}
			$cid = hikashop_getCID($this->plugin_type.'_id');
			if(isset($elements[$cid])) {
				$this->assignRef('element', $elements[$cid]);
				$configParam =& $elements[$cid];
				$ctrl .= '&' . $this->plugin_type . '_id=' . $cid;
			} else {
				$configParam = new stdClass;
				$this->assignRef('element', $configParam);
			}
		} else {
			$configParam =& $elements;

			$element = null;
			if(!empty($elements)) {
				$element = reset($elements);
			}
			$this->assignRef('element', $element);
			$typeFunction = 'on' . ucfirst($this->plugin_type) . 'Configuration';
			if(method_exists($plugin, $typeFunction)) {
				$function = $typeFunction;
			}
		}
		$this->assignRef('elements', $elements);

		if($multiple_interface && !isset($subtask) || !empty($subtask)) {
			$extra_config = array();
			$extra_blocks = array();

			JPluginHelper::importPlugin('hikashop');
			$app = JFactory::getApplication();
			$app->triggerEvent('onHikaPluginConfiguration', array($type, &$plugin, &$this->element, &$extra_config, &$extra_blocks));

			$this->assignRef('extra_config', $extra_config);
			$this->assignRef('extra_blocks', $extra_blocks);
		}

		$setTitle = true;
		if(method_exists($plugin, $function)) {
			if(empty($plugin->title))
				$plugin->title = JText::_('HIKA_PLUGIN').' '.$this->plugin_name;
			ob_start();
			$plugin->$function($configParam);
			$this->content = ob_get_clean();
			$this->data = $plugin->getProperties();
			$setTitle = false;
		}

		if(isset($this->data['toolbar'])) {
			$this->toolbar = $this->data['toolbar'];
		} else {
			if(defined('HIKASHOP_WORDPRESS')) {
				if($type === 'payment')
					$cancel_page = 'hikashop-payment';
				elseif($type === 'shipping')
					$cancel_page = 'hikashop-shipping';
				else
					$cancel_page = 'hikashop-plugins';
				$cancel = array('name' => 'link', 'alt' => 'HIKA_CANCEL', 'icon' => 'cancel', 'url' => admin_url('admin.php?page=' . $cancel_page));
			} else {
				$cancel = 'cancel';
			}
			$this->toolbar = array(
				array('name' => 'group', 'buttons' => array( 'apply', 'save')),
				$cancel,
				'|'
			);
		}

		if($this->plugin_type == 'shipping' && $multiple_interface && !empty($this->element->shipping_id)) {
			$button = array(
				'name' => 'popup',
				'alt' => 'GENERATE_COPIES',
				'icon' => 'copy',
				'url' => hikashop_completeLink('plugins&task=generate&tmpl=component&shipping_id='.$this->element->shipping_id),
				'width' => 1200,
				'height' => 650
			);
			array_unshift($this->toolbar, $button);
		}

		$this->assignRef('name', $this->plugin_name);
		$this->assignRef('plugin', $plugin);
		$this->assignRef('multiple_plugin', $multiple_plugin);
		$this->assignRef('multiple_interface', $multiple_interface);
		$this->assignRef('content', $this->content);
		$this->assignRef('plugin_type', $this->plugin_type);

		$categoryType = hikashop_get('type.categorysub');
		$categoryType->type = 'tax';
		$categoryType->field = 'category_id';
		$this->assignRef('categoryType', $categoryType);

		if($this->plugin_type == 'shipping') {
			$warehouseType = hikashop_get('type.warehouse');
			$this->assignRef('warehouseType', $warehouseType);
			if(!empty($this->element->shipping_params->override_tax_zone)){
				$zoneClass = hikashop_get('class.zone');
				$this->element->shipping_params->override_tax_zone = $zoneClass->get($this->element->shipping_params->override_tax_zone);
			}
		}

		$this->_noForm($type, $elements);

		$currencies = hikashop_get('type.currency');
		$column_name = $type.'_currency';
		$this->element->$column_name = explode(',',trim((string)@$this->element->$column_name,','));
		$this->assignRef('currencies',$currencies);

		if($type == 'payment')
			$this->_loadPayment();

		if(empty($plugin->pluginView)) {
			$this->content .= $this->loadPluginTemplate(@$plugin->view, $type);
		}

		if($setTitle)
			hikashop_setTitle(JText::_('HIKA_PLUGIN').' '.$this->name, $this->icon, $this->ctrl. $ctrl);

		return true;
	}

	function _noForm($type, $elements) {
		$this->assignRef('noForm', $this->data['noForm']);
		if(!empty($this->data['noForm']))
			return;

		$element = $this->element;
		if(empty($element))
			$element = new stdClass();
		$id = 0;
		if(is_array($elements) && count($elements)) {
			$id_name = $type.'_id';
			$id = hikashop_getCID($id_name);
			if(isset($elements[$id])) {
				$element = $elements[$id];
				$id = @$element->$id_name;
			} elseif(!$this->multiple_plugin && empty($this->data->multiple_entries)) {
				$element = array_pop($elements);
				$id = @$element->$id_name;
			}
		}

		$plugin_zone_namekey = $type .'_zone_namekey';
		if(!empty($element->$plugin_zone_namekey)){
			$zoneClass = hikashop_get('class.zone');
			$zone = $zoneClass->get($element->$plugin_zone_namekey);
			if(!empty($zone)) {
				foreach(get_object_vars($zone) as $k => $v){
					$element->$k = $v;
				}
			}
		}

		$translation = false;
		$transHelper = hikashop_get('helper.translation');

		$translatableParams = $this->_getTranslatableParams($type, $element);

		if($transHelper && $transHelper->isMulti()) {
			$translation = true;
			$payment_id = $type.'_id';
			$transHelper->load('hikashop_'.$type, @$element->$payment_id, $element);
		}

		$config =& hikashop_config();
		$multilang_display = $config->get('multilang_display','tabs');
		if($multilang_display == 'popups')
			$multilang_display = 'tabs';

		$tabs = hikashop_get('helper.tabs');
		$editor = hikashop_get('helper.editor');
		$editor->name = $type.'_description';
		$name = $editor->name;
		$editor->content = @$element->$name;
		$editor->height = 150;

		$this->assignRef('transHelper', $transHelper);
		$this->assignRef('tabs', $tabs);
		$this->assignRef('editor', $editor);
		$this->assignRef('translation', $translation);
		$this->assignRef('element', $element);
		$this->assignRef('id', $id);
		$this->assignRef('translatableParams', $translatableParams);
	}

	function generate() {
		$this->ids = hikaInput::get()->get('cid', array(), 'array');
		$shipping_id = hikaInput::get()->getInt('shipping_id');
		if(empty($this->ids) && $shipping_id) $this->ids = array($shipping_id);

		$shippingClass = hikashop_get('class.shipping');
		$id = !empty($this->ids) ? reset($this->ids) : $shipping_id;
		$this->source = $shippingClass->get($id);

		if(!$this->source) {
			echo JText::_('GENERATION_FAILED');
			return;
		}

		if(is_string($this->source->shipping_params)) {
			$this->source->shipping_params = unserialize($this->source->shipping_params);
		}

		$this->default_price = $this->source->shipping_price;
		$this->default_percentage = @$this->source->shipping_params->shipping_percentage;
		$this->default_formula = @$this->source->shipping_params->shipping_formula;

		$this->nameboxType = hikashop_get('type.namebox');
		$this->weight = hikashop_get('type.weight');
		$this->volume = hikashop_get('type.volume');
		$this->warehouseType = hikashop_get('type.warehouse');

		$this->setLayout('generate');
		parent::display();
	}

	function _getTranslatableParams($type, &$element) {
		$translatableParams = array();
		$type_field = $type . '_type';
		if(empty($element->$type_field))
			return $translatableParams;

		$pluginName = $element->$type_field;
		if($type == 'plugin')
			$plugin = hikashop_import('hikashop', $pluginName);
		else
			$plugin = hikashop_import('hikashop' . $type, $pluginName);

		if(empty($plugin) || empty($plugin->pluginConfig))
			return $translatableParams;

		$params_field = $type . '_params';
		$params = null;
		if(!empty($element->$params_field)) {
			$params = $element->$params_field;
			if(is_string($params))
				$params = hikashop_unserialize($params);
		}

		$translatableTypes = array('input', 'textarea', 'big-textarea', 'wysiwyg');
		foreach($plugin->pluginConfig as $key => $config) {
			if(empty($config['translatable']))
				continue;
			if(!isset($config[1]) || !in_array($config[1], $translatableTypes))
				continue;

			$prop = $params_field . '_' . $key;
			$element->$prop = '';
			if(!empty($params) && isset($params->$key))
				$element->$prop = $params->$key;

			$translatableParams[$key] = array(
				'label' => isset($config[0]) ? $config[0] : $key,
				'type' => $config[1],
				'field' => $prop,
			);
		}

		return $translatableParams;
	}

	function _loadPayment() {
		$shippingMethods = hikashop_get('type.plugins');
		$shippingMethods->type = 'shipping';
		$shippingMethods->manualOnly = true;

		if(!empty($this->element->payment_shipping_methods)) {
			$methods = explode("\n", $this->element->payment_shipping_methods);
			$this->element->payment_shipping_methods_id = array();
			$this->element->payment_shipping_methods_type = array();
			foreach($methods as $method) {
				list($shipping_type,$shipping_id) = explode('_', $method, 2);
				$this->element->payment_shipping_methods_id[] = $shipping_id;
				$this->element->payment_shipping_methods_type[] = $shipping_type;
			}

		} else {
			if(!isset($this->element))
				$this->element= new stdClass();
			$this->element->payment_shipping_methods_id = array();
			$this->element->payment_shipping_methods_type = array();
		}
		$this->assignRef('shippingMethods', $shippingMethods);
	}

	function edit_translation() {
		$language_id = hikaInput::get()->getInt('language_id',0);

		$type = hikaInput::get()->getString('type');
		$field = $type.'_id';
		$cid = hikashop_getCID($field);
		$class = hikashop_get('class.'.$type);
		$element = $class->getRaw($cid);

		$translatableParams = $this->_getTranslatableParams($type, $element);

		$translation = false;
		$transHelper = hikashop_get('helper.translation');
		if($transHelper && $transHelper->isMulti()) {
			$translation = true;
			$transHelper->load('hikashop_'.$type, @$element->$field, $element, $language_id);
			$this->assignRef('transHelper', $transHelper);
		}

		$editor = hikashop_get('helper.editor');
		$desc = $type.'_description';
		$editor->name = $desc;
		$editor->content = @$element->$desc;
		$editor->height=300;
		$this->assignRef('editor',$editor);
		$this->assignRef('element',$element);
		$this->assignRef('plugin_type',$type);
		$this->assignRef('translatableParams', $translatableParams);

		$tabs = hikashop_get('helper.tabs');
		$this->assignRef('tabs',$tabs);
		$toggle = hikashop_get('helper.toggle');
		$this->assignRef('toggle',$toggle);

		return true;
	}

	function uploadimage() {
		$type = hikaInput::get()->getCmd('type', 'shipping');
		if(!in_array($type, array('shipping', 'payment')))
			$type = 'shipping';
		$this->assignRef('type', $type);

		$uploaded = hikaInput::get()->getString('uploaded', '');
		$this->assignRef('uploaded', $uploaded);

		return true;
	}

	function selectimages(){
		$type = hikaInput::get()->getCmd('type','shipping');
		if(!in_array($type,array('shipping','payment'))){
			$type = 'shipping';
		}
		$path = HIKASHOP_MEDIA.'images'.DS.$type.DS;
		jimport('joomla.filesystem.folder');
		$images = JFolder::files($path);
		$rows = array();
		foreach($images as $image){
			$parts = explode('.',$image);
			$row = new stdClass();
			$row->ext = array_pop($parts);
			if(!in_array(strtolower($row->ext),array('gif','png','jpg','jpeg','svg'))) continue;
			$row->id = implode($parts);
			$row->name = str_replace('_',' ',$row->id);
			$row->file = $image;
			$row->full = HIKASHOP_IMAGES .$type.'/'. $row->file;
			$rows[]=$row;
		}

		$selectedImages = hikaInput::get()->getVar('values','','','string');

		if(strtolower($selectedImages) == 'all') {
			foreach($rows as $id => $oneRow) {
				$rows[$id]->selected = true;
			}
		} elseif(!empty($selectedImages)) {
			$selectedImages = explode(',',$selectedImages);
			foreach($rows as $id => $oneRow){
				if(in_array($oneRow->id,$selectedImages)){
					$rows[$id]->selected = true;
				}
			}
		}

		$this->assignRef('rows', $rows);
		$this->assignRef('selectedLists', $selectedImages);
		$this->assignRef('type', $type);

		return true;
	}

	function loadPluginTemplate($view = '', $type = '') {
		static $previousType = '';
		if(empty($type)) {
			$type = $previousType;
		} else {
			$previousType = $type;
		}

		$app = JFactory::getApplication();
		$this->subview = '';
		if(!empty($view)) {
			$this->subview = '_' . $view;
		}

		if(isset($this->data['pluginConfig'])) {
			$paramsType = $type.'_params';
			$html = '';
			foreach($this->data['pluginConfig'] as $key => $value){
				if(is_array($value[0])) {
					$a = array_shift($value[0]);
					$label = vsprintf(JText::_($a), $value[0]);
				} else {
					$label = JText::_($value[0]);
				}
				if(!empty($value['tooltip'])) {
					hikashop_loadJslib('tooltip');
					$label = hikashop_hktooltip(JText::_($value['tooltip']), '', $label, '');
				}

				$id = 'data_'.$type.'_'.$paramsType.'_'.$key;
				$lineAttribs = 'id="'.$id.'_line"';

				if(!empty($value['showon'])) {
					hikashop_loadJslib('showon');
					$lineAttribs .= ' hk-showon="data_'.$type.'_'.$paramsType.'_'.$value['showon'].'"';
				}
				if(isset($value[3]))
					$options = $value[3];
				else
					$options = array();

				switch ($value[1]) {
					case 'input':
						$html .= '<tr '.$lineAttribs.'><td class="key"><label for="data_'.$type.'_'.$paramsType.'_'.$key.'">'.$label.'</label></td><td>';
						$html .= '<input type="text" id="'.$id.'" name="data['.$type.']['.$paramsType.']['.$key.']" value="'.htmlentities((string)@$this->element->$paramsType->$key, ENT_COMPAT, 'UTF-8').'"/>';
						break;

					case 'textarea':
						$html .= '<tr '.$lineAttribs.'><td class="key"><label for="data_'.$type.'_'.$paramsType.'_'.$key.'">'.$label.'</label></td><td>';
						$html .= '<textarea id="'.$id.'" name="data['.$type.']['.$paramsType.']['.$key.']" rows="3">'.@$this->element->$paramsType->$key.'</textarea>';
						break;
					case 'big-textarea':
						$html .= '<tr '.$lineAttribs.'><td class="key"><label for="data_'.$type.'_'.$paramsType.'_'.$key.'">'.$label.'</label></td><td>';
						$html .= '<textarea id="'.$id.'" name="data['.$type.']['.$paramsType.']['.$key.']" rows="9" width="100%" style="width:100%;">'.@$this->element->$paramsType->$key.'</textarea>';
						break;
					case 'wysiwyg':
						$html .= '<tr '.$lineAttribs.'><td class="key"><label>'.$label.'</label></td><td>';
						if(empty($this->editorHelper)) {
							$this->editorHelper = hikashop_get('helper.editor');
							$config = hikashop_config();
							$this->editorHelper->setEditor($config->get('editor', ''));
							if($config->get('editor_disable_buttons', 0))
								$this->editorHelper->options = false;
						}
						$this->editorHelper->name = 'data['.$type.']['.$paramsType.']['.$key.']';
						$this->editorHelper->content = @$this->element->$paramsType->$key;
						$html .= $this->editorHelper->display() . '<div style="clear:both"></div>';
						break;
					case 'boolean':
						$html .= '<tr '.$lineAttribs.'><td class="key"><label>'.$label.'</label></td><td>';
						if(!isset($this->element->$paramsType) || !$this->element->$paramsType)
							$this->element->$paramsType = new stdClass();
						if(!isset($this->element->$paramsType->$key) && isset($value[2]))
							$this->element->$paramsType->$key = $value[2];
						if(!isset($this->element->$paramsType->$key))
							$this->element->$paramsType->$key=1;
						$html .= JHTML::_('hikaselect.booleanlist', 'data['.$type.']['.$paramsType.']['.$key.']' , '', @$this->element->$paramsType->$key);
						break;

					case 'checkbox':
						$html .= '<tr '.$lineAttribs.'><td class="key"><label>'.$label.'</label></td><td>';
						$i = 0;
						foreach($value[2] as $listKey => $listData){
							$checked = '';
							if(!empty($this->element->$paramsType->$key)){
								if(is_string($this->element->$paramsType->$key))
									$this->element->$paramsType->$key = explode(',', $this->element->$paramsType->$key);
								if(in_array($listKey, $this->element->$paramsType->$key))
									$checked = 'checked="checked"';
							}
							$html .= '<input id="'.$id.'_'.$i.'" name="data['.$type.']['.$paramsType.']['.$key.'][]" class="hikashop_plg_checkbox" type="checkbox" value="'.$listKey.'" '.$checked.' /><label for="data_'.$type.'_'.$paramsType.'_'.$key.'_'.$i.'">'.$listData.'</label><br/>';
							$i++;
						}
						break;

					case 'radio':
						$html .= '<tr '.$lineAttribs.'><td class="key"><label>'.$label.'</label></td><td>';
						$values = array();
						foreach($value[2] as $listKey => $listData){
							$values[] = JHTML::_('select.option', $listKey, JText::_($listData));
						}
						$html .= JHTML::_('hikaselect.radiolist', $values, 'data['.$type.']['.$paramsType.']['.$key.']' , 'class="custom-select" size="1"', 'value', 'text', @$this->element->$paramsType->$key);
						break;

					case 'list':
						$html .= '<tr '.$lineAttribs.'><td class="key"><label for="data'.$type.$paramsType.$key.'">'.$label.'</label></td><td>';
						$values = array();
						foreach($value[2] as $listKey => $listData){
							$values[] = JHTML::_('select.option', $listKey,JText::_($listData));
						}
						$html .= JHTML::_('select.genericlist', $values, 'data['.$type.']['.$paramsType.']['.$key.']' , 'class="custom-select" size="1"', 'value', 'text', @$this->element->$paramsType->$key);
						break;

					case 'orderstatus':
						$html .= '<tr '.$lineAttribs.'><td class="key"><label for="data'.$type.$paramsType.$key.'">'.$label.'</label></td><td>';
						$html .= $this->data['order_statuses']->display('data['.$type.']['.$paramsType.']['.$key.']',@$this->element->$paramsType->$key);
						break;

					case 'address':
						$html .= '<tr '.$lineAttribs.'><td class="key"><label for="data'.$type.$paramsType.$key.'">'.$label.'</label></td><td>';
						$addressType = hikashop_get('type.address');
						$html .= $addressType->display('data['.$type.']['.$paramsType.']['.$key.']',@$this->element->$paramsType->$key);
						break;
					case 'html':
						$html .= '<tr '.$lineAttribs.'><td class="key"><label>'.$label.'</label></td><td>';
						$html .= $value[2];
						break;
					case 'title':
						$html .= '<tr '.$lineAttribs.'><td colspan="2"><label><b>'.$label.'</b></label>';
						break;
					case 'content':
						$html .= '<tr '.$lineAttribs.'><td colspan="2">'.$label;
						break;
					case 'category':
						$html .= '<tr '.$lineAttribs.'><td class="key"><label for="data_'.$type.'_'.$paramsType.'_'.$key.'_text">'.$label.'</label></td><td>';
						if(!empty($this->element->$paramsType->$key)) {
							if(!is_array($this->element->$paramsType->$key)) {
								$this->element->$paramsType->$key = explode(',', trim(@$this->element->$paramsType->$key, ','));
							}
						}
						$nameboxType = hikashop_get('type.namebox');
						$html .= $nameboxType->display(
							'data['.$type.']['.$paramsType.']['.$key.']',
							@$this->element->$paramsType->$key,
							hikashopNameboxType::NAMEBOX_MULTIPLE,
							'category',
							array(
								'delete' => true,
								'default_text' => '<em>'.JText::_('HIKA_ALL').'</em>',
							)
						);
						break;
					case 'hidden':
						$html .= '<tr style="display:none"><td colspan="2"><input type="hidden" id="'.$id.'" name="data['.$type.']['.$paramsType.']['.$key.']" value="'.htmlentities((string)@$this->element->$paramsType->$key, ENT_COMPAT, 'UTF-8').'"/></td></tr>';
						break;
					default:
						$html .= '<tr '.$lineAttribs.'><td class="key"><label>'.$label.'</label></td><td>';
						if(method_exists($this->plugin, 'pluginConfigDisplay')) {
							$html .= $this->plugin->pluginConfigDisplay($value[1], @$value[2], $type, $paramsType, $key, $this->element, $options);
						}
						break;
				}

				$html .= '</td></tr>';
			}

			return $html;
		}

		if($type == 'plugin')
			$type = '';

		$name = $this->name.'_configuration'.$this->subview.'.php';
		$path = JPATH_THEMES.DS.$app->getTemplate().DS.'hikashop'.$type.DS.$name;

		if(!file_exists($path)) {
			$parentTemplate = hikashop_getParentTemplate();
			if(!empty($parentTemplate)) {
				$path = JPATH_THEMES.DS.$parentTemplate.DS.'hikashop'.$type.DS.$name;
			}
			if(empty($parentTemplate) || !file_exists($path)) {
				$path = JPATH_PLUGINS.DS.'hikashop'.$type.DS.$this->name.DS.$name;
				if(!file_exists($path)) {
					return '';
				}
			}
		}
		ob_start();
		require($path);
		return ob_get_clean();
	}

	protected function _getSystemPluginFilter() {
		static $filter = null;
		if($filter !== null)
			return $filter;
		$db = JFactory::getDBO();

		$elements = array();
		$updateHelper = hikashop_get('helper.update');
		$extensionInfo = $updateHelper->getExtensionInfo();
		foreach($extensionInfo as $dirName => $info) {
			$parts = explode('_', $dirName);
			$prefix = array_shift($parts);
			if($prefix !== 'plg')
				continue;
			$folder = array_shift($parts);
			if($folder !== 'system')
				continue;
			$elements[] = $db->Quote(implode('_', $parts));
		}

		$conditions = array();
		if(!empty($elements))
			$conditions[] = '`element` IN (' . implode(',', $elements) . ')';
		$conditions[] = '`element` LIKE \'%hikashop%\'';
		$conditions[] = '`element` LIKE \'%hika%\'';
		$conditions[] = '`name` LIKE \'%hikashop%\'';
		$conditions[] = '`name` LIKE \'%hika%\'';

		$filter = '`folder` = ' . $db->Quote('system') . ' AND (' . implode(' OR ', $conditions) . ')';
		return $filter;
	}

	protected function _getPluginBlacklist() {
		static $blacklist = null;
		if($blacklist !== null)
			return $blacklist;
		if(defined('HIKASHOP_WORDPRESS')) {
			$blacklist = array(
				'hikashop.acymailing' => true,
				'system.hikashopuser' => true,
				'system.mijo_redirect' => true,
				'system.reds_redirect' => true,
				'system.vm_redirect' => true,
				'system.nossloutsidecheckout' => true,
				'system.hikashopproductinsert' => true,
			);
		} else {
			$blacklist = array();
		}
		return $blacklist;
	}
}

