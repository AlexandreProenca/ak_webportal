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

class ImportViewImport extends hikashopView{
	var $ctrl= 'import';
	var $icon = 'download';
	function display($tpl = null){
		$this->paramBase = HIKASHOP_COMPONENT.'.'.$this->getName();
		$function = $this->getLayout();
		if(method_exists($this,$function)) $this->$function();
		parent::display($tpl);
	}

	function show(){
		$app = JFactory::getApplication();
		$this->popup = hikashop_get('helper.popup');
		$pageInfo = new stdClass();
		$pageInfo->filter = new stdClass();
		$pageInfo->filter->order = new stdClass();
		$pageInfo->limit = new stdClass();
		$config =& hikashop_config();
		if(HIKASHOP_J40) {
			$wa = JFactory::getDocument()->getWebAssetManager();
			$wa->useScript('keepalive');
		}

		hikashop_setTitle(JText::_('IMPORT'),$this->icon,$this->ctrl.'&task=show');
		$importIcon = 'import';
		$this->toolbar = array(
			array('name' => 'custom', 'icon' => $importIcon, 'alt' => JText::_('IMPORT'), 'task' => 'import', 'check' => false),
			'|',
			array('name' => 'pophelp', 'target' => $this->ctrl),
			'dashboard'
		);

		$importData = array();

		$import = new stdClass();
		$import->text = JText::_('PRODUCTS_FROM_CSV');
		$import->key = 'file';
		$importData[] = $import;

		$textArea = new stdClass();
		$textArea->text = JText::_('PRODUCTS_FROM_TEXTAREA');
		$textArea->key = 'textarea';
		$importData[] = $textArea;

		$folder = new stdClass();
		$folder->text = JText::_('PRODUCTS_FROM_FOLDER');
		$folder->key = 'folder';
		$importData[] = $folder;

		$database = JFactory::getDBO();

		if(!defined('HIKASHOP_WORDPRESS')) {
			$query = 'SHOW TABLES LIKE '.$database->Quote($database->getPrefix().substr(hikashop_table('virtuemart_products',false),3));
			$database->setQuery($query);
			$table = $database->loadResult();
			if (empty($table))
			{
				$query='SHOW TABLES LIKE '.$database->Quote($database->getPrefix().substr(hikashop_table('vm_product',false),3));
				$database->setQuery($query);
				$table = $database->loadResult();
				if (empty($table))
					$vm_here = false;
				else
				{
					$vm_here = true;
					$version = 1;
					$this->assignRef('vmversion',$version);
				}
			}
			else
			{
				$vm_here = true;
				$version = 2;
				$this->assignRef('vmversion',$version);
			}
			$this->assignRef('vm',$vm_here);

			$vm = new stdClass();
			$vm->text = JText::sprintf('PRODUCTS_FROM_X','Virtuemart');
			$vm->key = 'vm';
			$importData[] = $vm;

			$mijo = new stdClass();
			$mijo->text = JText::sprintf('PRODUCTS_FROM_X','Mijoshop');
			$mijo->key = 'mijo';
			$importData[] = $mijo;

			$query='SHOW TABLES LIKE '.$database->Quote($database->getPrefix().substr(hikashop_table('mijoshop_product',false),3));
			$database->setQuery($query);
			$table = $database->loadResult();
			if (empty($table))
				$mijo_here = false;
			else
				$mijo_here = true;
			$this->assignRef('mijo',$mijo_here);

			$reds = new stdClass();
			$reds->text = JText::sprintf('PRODUCTS_FROM_X','Redshop 1.x or 2.x');
			$reds->key = 'redshop';
			$importData[] = $reds;

			$query='SHOW TABLES LIKE '.$database->Quote($database->getPrefix().substr(hikashop_table('redshop_product',false),3));
			$database->setQuery($query);
			$table = $database->loadResult();
			if (empty($table))
				$reds_here = false;
			else
				$reds_here = true;
			$this->assignRef('reds',$reds_here);

			$openc = new stdClass();
			$openc->text = JText::sprintf('PRODUCTS_FROM_X','Opencart');
			$openc->key = 'openc';
			$importData[] = $openc;

			$j2 = new stdClass();
			$j2->text = JText::sprintf('PRODUCTS_FROM_X','J2Store');
			$j2->key = 'j2';
			$importData[] = $j2;

			$query='SHOW TABLES LIKE '.$database->Quote($database->getPrefix().'j2store_products');
			$database->setQuery($query);
			$table = $database->loadResult();
			if (empty($table))
				$j2_here = false;
			else
				$j2_here = true;
			$this->assignRef('j2',$j2_here);
		}

		$woo = new stdClass();
		$woo->text = JText::sprintf('PRODUCTS_FROM_X','WooCommerce');
		$woo->key = 'woo';
		$importData[] = $woo;

		$ec = new stdClass();
		$ec->text = JText::sprintf('PRODUCTS_FROM_X','EC-CUBE');
		$ec->key = 'ec';
		$importData[] = $ec;

		$hk = new stdClass();
		$hk->text = JText::sprintf('PRODUCTS_FROM_X','HikaShop');
		$hk->key = 'hk';
		$importData[] = $hk;

		if(defined('HIKASHOP_WORDPRESS')) {
			$wooHelper = hikashop_get('helper.import-woo', null);
			$woo_here = $wooHelper->isWooCommerceDetected();
			$this->assignRef('woo', $woo_here);
		}

		JPluginHelper::importPlugin( 'hikashop' );
		$app = JFactory::getApplication();
		$app->triggerEvent( 'onDisplayImport', array( & $importData) );

		$this->assignRef('importData',$importData);
		$importValues = array();
		foreach($importData as $data){
			if(!empty($data->key)){
				$importValues[] = JHTML::_('select.option', $data->key,$data->text);
			}
		}
		$this->assignRef('importValues', $importValues);
		$importFolders = array(JHTML::_('select.option', 'images',JText::_('HIKA_IMAGES')),JHTML::_('select.option', 'files',JText::_('HIKA_FILES')),JHTML::_('select.option', 'both',JText::_('FILES').' & '.JText::_('HIKA_IMAGES')));
		$this->assignRef('importFolders', $importFolders);

		$savedMappings = array();
		$query = 'SELECT config_namekey, config_value FROM ' . hikashop_table('config') . 
				 ' WHERE config_namekey LIKE ' . $database->Quote('import_mapping_%');
		$database->setQuery($query);
		$mappingResults = $database->loadObjectList();
		if(!empty($mappingResults)) {
			foreach($mappingResults as $row) {
				$data = json_decode($row->config_value, true);
				if(!empty($data)) {
					$savedMappings[$row->config_namekey] = isset($data['name']) ? $data['name'] : $row->config_namekey;
				}
			}
		}
		$this->assignRef('savedMappings', $savedMappings);

		$this->popupLink = $this->popup->display('', JText::_('CONFIGURE_COLUMNS'), '#', 'hikashop_column_matching_link', 800, 600, 'style="display:none"', '', 'link');

		$js = '
		var currentoption = \'file\';
		function updateImport(newoption){
			document.getElementById(currentoption).style.display = "none";
			document.getElementById(newoption).style.display = \'block\';
			currentoption = newoption;
		}
		var currentoptionFolder = \'images\';
		function updateImportFolder(newoption){
			document.getElementById(currentoptionFolder).style.display = "none";
			document.getElementById(newoption).style.display = \'block\';
			currentoptionFolder = newoption;
		}

		var hikaImport = {
			savedMappings: ' . json_encode($savedMappings) . ',
			currentPrefix: "",

			init: function() {
				var mappings = this.savedMappings;
				["file", "textarea"].forEach(function(prefix) {
					var select = document.getElementById(prefix + "_column_mapping");
					if(select) {
						for(var key in mappings) {
							var opt = document.createElement("option");
							opt.value = key;
							opt.text = mappings[key];
							select.appendChild(opt);
						}
					}
				});
			},

			updateMappingUI: function(prefix, value) {
				var buttons = document.getElementById(prefix + "_mapping_buttons");
				var hiddenInput = document.getElementById(prefix + "_mapping");

				var deleteBtn = document.getElementById(prefix + "_delete_btn");

				if(value === "none") {
					buttons.style.display = "none";
					hiddenInput.value = "";
				} else {
					buttons.style.display = "inline";
					if(value !== "inline") {
						hiddenInput.value = "";
					}

					if(deleteBtn) {
						if(value.indexOf("import_mapping_") === 0) {
							deleteBtn.style.display = "inline-block";
						} else {
							deleteBtn.style.display = "none";
						}
					}

					if(value === "new") {
						this.openColumnMatching(prefix);
					}
				}
			},

			openColumnMatching: function(prefix) {
				this.currentPrefix = prefix;
				var self = this;

				var content = "";
				var formData = new FormData();
				formData.append("option", "com_hikashop");
				formData.append("ctrl", "import");
				formData.append("task", "previewcolumns");
				formData.append("source", prefix);
				formData.append("' . JSession::getFormToken() . '", 1);

				if(prefix === "textarea") {
					content = document.querySelector("textarea[name=textareaentries]").value;
					if(content) {
						formData.append("textareaentries", content);
					} else {
						alert("' . JText::_('PLEASE_ENTER_DATA_FIRST', true) . '");
						return;
					}
				} else {
					var fileInput = document.querySelector("input[name=importfile]");
					if(fileInput && fileInput.files.length > 0) {
						formData.append("importfile", fileInput.files[0]);
					} else {
						alert("' . JText::_('PLEASE_SELECT_FILE_FIRST', true) . '");
						return;
					}
				}

				fetch("index.php", {
					method: "POST",
					body: formData
				})
				.then(function(response) { return response.json(); })
				.then(function(data) {
					if(data.error) {
						alert(data.error);
						return;
					}

					var select = document.getElementById(prefix + "_column_mapping");
					var mappingId = select ? select.value : "";
					var url = "index.php?option=com_hikashop&ctrl=import&task=columnmatching&layout=columnmatching&tmpl=component&prefix=" + prefix + "&mapping_id=" + mappingId;

					if(window.hikashop && window.hikashop.openBox) {
						window.hikashop.openBox("hikashop_column_matching_link", url);
					} else {
						alert("HikaShop popup system not loaded");
					}
				})
				.catch(function(err) {
					console.error(err);
					alert("Error preparing column data");
				});
			},

			handlePopupMapping: function(mapping, prefix, saveName) {
				if(saveName) {
					this.saveMapping(saveName, mapping, prefix);
				}

				if(window.hikashop && window.hikashop.closeBox) {
					window.hikashop.closeBox();
				}
			},

			saveMapping: function(name, mapping, activePrefix) {
				var self = this;
				var formData = new FormData();
				formData.append("option", "com_hikashop");
				formData.append("ctrl", "import");
				formData.append("task", "savemapping");
				formData.append("mapping_name", name);
				formData.append("mapping", JSON.stringify(mapping));
				formData.append("' . JSession::getFormToken() . '", 1);

				fetch("index.php", {
					method: "POST",
					body: formData
				})
				.then(function(response) { return response.json(); })
				.then(function(data) {
					if(data.error) {
						alert(data.error);
						return;
					}

					self.savedMappings[data.key] = name;

					["file", "textarea"].forEach(function(prefix) {
						var select = document.getElementById(prefix + "_column_mapping");
						if(select) {
							var opt = select.querySelector("option[value=\'" + data.key + "\']");
							if(!opt) {
								opt = document.createElement("option");
								opt.value = data.key;
								opt.text = name;
								select.appendChild(opt);
							} else {
								opt.text = name; // Update name if renamed
							}

							if(prefix === activePrefix) {
								select.value = data.key;
								self.updateMappingUI(prefix, data.key);
							}
						}
					});
				})
				.catch(function(err) {
					console.error(err);
					alert("Error saving mapping");
				});
			},

			deleteMapping: function(prefix) {
				var select = document.getElementById(prefix + "_column_mapping");
				var key = select.value;
				if(!key || key.indexOf("import_mapping_") !== 0) return;

				if(!confirm("' . JText::_('PLEASE_CONFIRM_DELETION', true) . '")) return;

				var self = this;
				var formData = new FormData();
				formData.append("option", "com_hikashop");
				formData.append("ctrl", "import");
				formData.append("task", "deletemapping");
				formData.append("mapping_key", key);
				formData.append("' . JSession::getFormToken() . '", 1);

				fetch("index.php", {
					method: "POST",
					body: formData
				})
				.then(function(response) { return response.json(); })
				.then(function(data) {
					if(data.error) {
						alert(data.error);
						return;
					}

					delete self.savedMappings[key];

					["file", "textarea"].forEach(function(p) {
						var s = document.getElementById(p + "_column_mapping");
						if(s) {
							var opt = s.querySelector("option[value=\'" + key + "\']");
							if(opt) s.removeChild(opt);

							if(p === prefix) {
								s.value = "none";
								self.updateMappingUI(p, "none");
							}
						}
					});
				});
			},

		};

		document.addEventListener("DOMContentLoaded", function() {
			hikaImport.init();
		});
		';

		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration( $js );
		$this->nameboxType = hikashop_get('type.namebox');
		hikashop_loadJsLib('tooltip');

	}

	function columnmatching() {
		$prefix = hikaInput::get()->getString('prefix', 'file');
		$this->prefix = $prefix;

		$importHelper = hikashop_get('helper.import');
		$app = JFactory::getApplication();

		$csvColumns = $app->getUserState('com_hikashop.import.csv_columns');

		if(empty($csvColumns)) {
			$csvColumns = array();
		}

		$this->csvColumns = $csvColumns;
		$this->autoMatched = $importHelper->autoMatchColumns($csvColumns);
		$this->supportedColumns = $this->getColumnLabels($importHelper->getSupportedColumns());

		$mappingId = hikaInput::get()->getString('mapping_id');
		$this->loadedMapping = null;
		if($mappingId && strpos($mappingId, 'import_mapping_') === 0) {
			$db = JFactory::getDbo();
			$query = 'SELECT config_value FROM ' . hikashop_table('config') . ' WHERE config_namekey = ' . $db->quote($mappingId);
			$db->setQuery($query);
			$config = $db->loadResult();
			if($config) {
				$data = json_decode($config, true);
				if(isset($data['mapping'])) {
					$this->loadedMapping = $data['mapping'];
					$this->loadedMappingName = isset($data['name']) ? $data['name'] : '';
				}
			}
		}
	}

	protected function getColumnLabels($columns) {
		$keyMapping = array(
			'product_name' => 'HIKA_NAME',
			'product_code' => 'PRODUCT_CODE',
			'product_description' => 'DESCRIPTION',
			'product_meta_description' => 'META_DESCRIPTION',
			'product_keywords' => 'KEYWORDS',
			'product_url' => 'URL',
			'product_published' => 'PUBLISHED',
			'product_quantity' => 'PRODUCT_QUANTITY',
			'product_weight' => 'PRODUCT_WEIGHT',
			'product_weight_unit' => 'WEIGHT_UNIT',
			'product_width' => 'WIDTH',
			'product_height' => 'HEIGHT',
			'product_length' => 'LENGTH',
			'product_dimension_unit' => 'DIMENSION_UNIT',
			'product_type' => 'PRODUCT_TYPE',
			'product_parent_id' => 'PARENT_PRODUCT',
			'product_tax_id' => 'TAXATION_CATEGORY',
			'product_manufacturer_id' => 'MANUFACTURER',
			'product_vendor_id' => 'VENDOR',
			'product_sale_start' => 'SALE_START',
			'product_sale_end' => 'SALE_END',
			'product_created' => 'CREATED',
			'product_modified' => 'MODIFIED',
			'product_layout' => 'LAYOUT',
			'product_quantity_layout' => 'QUANTITY_LAYOUT',
			'product_max_per_order' => 'MAX_QUANTITY_PER_ORDER',
			'product_min_per_order' => 'MIN_QUANTITY_PER_ORDER',
			'product_alias' => 'ALIAS',
			'product_msrp' => 'MSRP',
			'product_warehouse_id' => 'WAREHOUSE',
			'product_condition' => 'CONDITION',
			'product_access' => 'ACCESS_LEVEL',
			'product_group_after_purchase' => 'GROUP_AFTER_PURCHASE',

			'price_value' => 'PRICE',
			'price_value_with_tax' => 'PRICE_WITH_TAX',
			'price_currency_id' => 'CURRENCY',
			'price_min_quantity' => 'MIN_QUANTITY',
			'price_access' => 'PRICE_ACCESS',
			'price_users' => 'USERS',

			'files' => 'HIKA_FILES',
			'files_name' => 'FILE_NAME',
			'files_description' => 'FILE_DESCRIPTION',
			'files_limit' => 'FILE_LIMIT',
			'files_ordering' => 'ORDERING',
			'files_free_download' => 'FREE_DOWNLOAD',

			'images' => 'HIKA_IMAGES',
			'images_name' => 'IMAGE_NAME',
			'images_description' => 'IMAGE_DESCRIPTION',
			'images_ordering' => 'ORDERING',

			'categories' => 'CATEGORIES',
			'parent_category' => 'PARENT_CATEGORY',
			'categories_namekey' => 'CATEGORY_NAMEKEY',
			'categories_image' => 'CATEGORY_IMAGE',
			'categories_ordering' => 'ORDERING',

			'related' => 'RELATED_PRODUCTS',
			'options' => 'HIKASHOP_OPTIONS',
			'bundle' => 'BUNDLE',
			'bundle_quantity' => 'QUANTITY',
		);

		$result = array();
		foreach($columns as $key => $label) {
			if(isset($keyMapping[$key])) {
				$translated = JText::_($keyMapping[$key]);
				if($translated !== $keyMapping[$key]) {
					$result[$key] = $translated;
					continue;
				}
			}
			$result[$key] = $label;
		}

		return $result;
	}

}
