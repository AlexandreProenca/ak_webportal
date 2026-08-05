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
hikashop_cleanBuffers();

$config =& hikashop_config();
$format = $config->get('export_format','csv');
$separator = $config->get('csv_separator',';');
$force_quote = $config->get('csv_force_quote',1);
$force_text = $config->get('csv_force_text', false);
$decimal_separator = $config->get('csv_decimal_separator','.');

$export = hikashop_get('helper.spreadsheet');
$export->init($format, 'hikashopexport', $separator, $force_quote, $decimal_separator, $force_text);

$characteristic = hikashop_get('class.characteristic');
$classProduct = hikashop_get('class.product');
$characteristic->loadConversionTables($this);

$db = JFactory::getDBO();
if(!HIKASHOP_J30){
	$columnsTable = $db->getTableFields(hikashop_table('product'));
	$columnsArray = reset($columnsTable);
} else {
	$columnsArray = $db->getTableColumns(hikashop_table('product'));
}

$columnsArray['categories_ordering'] = 'varchar';
$columnsArray['product_parent_id'] = 'varchar';
$columnsArray['product_manufacturer_id'] = 'varchar';



$classProduct = hikashop_get('class.product');
$columns = $classProduct->getExportColumns(true);
$products_columns = array_keys($columnsArray);
$product_table_count = count($products_columns);

$products_columns = array_combine($products_columns, $products_columns);

$characteristicsColumns = array();
if(!empty($this->characteristics)) {
	foreach($this->characteristics as $characteristic) {
		if(empty($characteristic->characteristic_parent_id)) {
			if(!empty($characteristic->characteristic_alias)){
				$characteristic->characteristic_value = $characteristic->characteristic_alias;
			}
			$colName = 'char_'.$characteristic->characteristic_id;
			$characteristicsColumns[$colName] = $characteristic->characteristic_value;
		}
	}
}

$after_category_count = count($columns)-($product_table_count+3);
$export->writeline($columns);

$types = array();
$keys = array_keys($columns);
foreach($keys as $col) {
	if(isset($columnsArray[$col])) {
		$type = $columnsArray[$col];
		if(in_array($type, array('varchar', 'text', 'longtext')))
			$types[] = 'text';
		else
			$types[] = 'number';
	} else {
		$types[] = 'text';
	}
}
$export->setTypes($types);

$has_category_columns = count(array_intersect(array_keys($columns), array('parent_category', 'categories_image', 'categories'))) > 0;
if(!empty($this->categories) && $has_category_columns) {
	foreach($this->categories as $category) {
		$row = array();
		if(!empty($category->category_parent_id) && isset($this->categories[$category->category_parent_id]))
			$row['parent_category'] = $this->categories[$category->category_parent_id]->category_name;
		else
			$row['parent_category'] = '';

		if(!empty($category->file_path))
			 $row['categories_image'] = $category->file_path;
		else
			$row['categories_image'] = '';

		$row['categories'] = $category->category_name;

		$data = array();
		$keys = array_keys($columns);
		foreach($keys as $col) {
			$data[] = isset($row[$col]) ? $row[$col] : '';
		}
		$export->writeline($data);
	}
}

if(!empty($this->products)) {
	foreach($this->products as $k => $product) {
		if($product->product_type == 'variant' && !empty($product->product_parent_id) && !empty($this->products[$product->product_parent_id]))
			$this->products[$k]->product_parent_id = $this->products[$product->product_parent_id]->product_code;
	}
	foreach($this->products as $product) {
		$row = array();

		if(!empty($product->product_manufacturer_id) && !empty($this->brands[$product->product_manufacturer_id]))
			$product->product_manufacturer_id = $this->brands[$product->product_manufacturer_id]->category_name;
		else
			$product->product_manufacturer_id = '';

		foreach($products_columns as $column) {
			if(!empty($product->$column) && is_array($product->$column))
				$product->$column = implode($separator,$product->$column);
			$row[$column] = @$product->$column;
		}

		$categories = array();
		if(!empty($product->categories)) {
			foreach($product->categories as $category) {
				if(!empty($this->categories[$category]))
					$categories[] = $this->categories[$category]->category_name;
			}
		}
		$row['parent_category'] = '';
		$row['categories_image'] = '';
		$row['categories'] = '';

		if(!empty($categories)){
			if(count($categories)>1){
			}else{
				if(isset($categories[0]->category_parent_id)){
					$parent_id = $categories[0]->category_parent_id;
					$row['categories_image'] = $this->categories[$parent_id]->category_name;
				}
			}
			$row['categories'] = implode($separator,$categories);
		}

		$values = array();
		$codes = array();
		$qtys = array();
		$accesses = array();
		$users = array();
		if(!empty($product->prices)) {
			foreach($product->prices as $price) {
				$floatValue = (float)hikashop_toFloat($price->price_value);
				if($floatValue == (int)$floatValue)
					$price_value = (int)$floatValue;
				else
					$price_value = number_format($floatValue, 5, '.', '');
				$values[] = $price_value;
				if(isset($this->currencies[$price->price_currency_id])) {
					$codes[] = $this->currencies[$price->price_currency_id]->currency_code;
				} else {
					hikashop_writeToLog('currency with id '.$price->price_currency_id.' not found for price with id '.$price->price_id. ' for product with id '.$product->product_id, 'export');
					$codes[] = '';
				}
				$qtys[] = $price->price_min_quantity;
				$accesses[] = $price->price_access;
				$users[] = $price->price_users;
			}

		}
		if(!empty($values)) {
			$row['price_value'] = implode('|', $values);
			$row['price_currency_id'] = implode('|', $codes);
			$row['price_min_quantity'] = implode('|', $qtys);
			$row['price_access'] = implode('|', $accesses);
			$row['price_users'] = implode('|', $users);
		}

		$files = array();
		if(!empty($product->files)) {
			foreach($product->files as $file) {
				$files[] = $file->file_path;
			}
		}
		if(!empty($files)) {
			$row['files'] = implode($separator, $files);
		}

		$images = array();
		if(!empty($product->images)) {
			foreach($product->images as $image) {
				$images[] = $image->file_path;
			}
		}
		if(!empty($images)) {
			$row['images'] = implode($separator, $images);
		}

		$related = array();
		if(!empty($product->related)) {
			foreach($product->related as $rel) {
				if(!isset($this->products[$rel]->product_code)) $this->products[$rel] = $classProduct->get($rel);
				$related[] = @$this->products[$rel]->product_code;
			}
		}
		if(!empty($related)) {
			$row['related'] = implode($separator, $related);
		}

		$options = array();
		if(!empty($product->options)) {
			foreach($product->options as $rel) {
				if(!isset($this->products[$rel]->product_code)) $this->products[$rel] = $classProduct->get($rel);
				$options[] = @$this->products[$rel]->product_code;
			}
		}
		if(!empty($options)) {
			$row['options'] = implode($separator, $options);
		}

		$bundle = array();
		$bundle_quantity = array();
		if(!empty($product->bundle)) {
			foreach($product->bundle as $k => $rel) {
				if(!isset($this->products[$rel]->product_code)) $this->products[$rel] = $classProduct->get($rel);
				$bundle[] = @$this->products[$rel]->product_code;
				$qty = 0;
				if(!empty($product->bundle_quantity[$k]))
					$qty = $product->bundle_quantity[$k];
				$bundle_quantity[] = $qty;
			}
		}
		if(!empty($bundle)) {
			$row['bundle'] = implode($separator, $bundle);
			$row['bundle_quantity'] = implode($separator, $bundle_quantity);
		}

		$characteristics = array();
		if(!empty($product->variant_links) && !empty($characteristicsColumns)) {
			foreach($product->variant_links as $char_id) {
				if(!empty($this->characteristics[$char_id])) {
					$char = $this->characteristics[$char_id];
					if(!empty($this->characteristics[$char->characteristic_parent_id])) {
						$characteristics['char_'.$char->characteristic_parent_id] = $char->characteristic_value;
					}
				}
			}
			foreach($characteristicsColumns as $key => $characteristic){
				$row[$key] = @$characteristics[$key];
			}
		}

		$data = array();
		$keys = array_keys($columns);
		foreach($keys as $col) {
			$data[] = isset($row[$col]) ? $row[$col] : '';
		}
		$export->writeLine($data);
	}
}

$export->send();
exit;
