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
defined('_JEXEC') or defined('ABSPATH') or die;

$productClass = hikashop_get('class.product');
$productClass->generateVariantData($this->element);

$contact = $this->config->get('product_contact', 0);

$main_images =& $this->element->main->images;

foreach($this->element->variants as $variant) {
	$this->row =& $variant;
	$variant_name = array ();
	if(!empty($variant->characteristics)) {
		foreach($variant->characteristics as $k => $ch) {
			$variant_name[] = $ch->characteristic_id;
		}
	}
	$this->element->images =& $main_images;
	if(!empty($variant->images))
		$this->element->images =& $variant->images;
	$this->element->badges =& $variant->badges;

	$variant_name = implode('_', $variant_name);
	$this->variant_name = '_' . $variant_name;

	$variantFormName = 'hikashop_product_form_' . $variant_name;
?>
	<form action="<?php echo hikashop_completeLink('product&task=updatecart'); ?>" method="post" name="<?php echo $variantFormName; ?>" id="<?php echo $variantFormName; ?>" onsubmit="return hikashop_product_form_check();" enctype="multipart/form-data">
		<input type="hidden" name="cart_type" value="cart"/>
		<input type="hidden" name="add" value="<?php echo !$this->config->get('synchronized_add_to_cart', 0); ?>"/>
		<input type="hidden" name="ctrl" value="product"/>
		<input type="hidden" name="task" value="updatecart"/>
		<input type="hidden" name="return_url" value="<?php echo urlencode(base64_encode(urldecode($this->redirect_url))); ?>"/>
	</form>
<?php
	$this->formName = ',0';
	if(!$this->config->get('ajax_add_to_cart', 1)) {
		$this->formName = ',\'' . $variantFormName . '\'';
	}

	ob_start();

	$this->setLayout('show_block_img');
	echo $this->loadTemplate();

	if(!empty($variant->product_name)) {
?>
	<div id="hikashop_product_name_<?php echo $variant_name; ?>" style="display:none;"><?php
		echo $variant->product_name;
	?></div>
<?php
	}

	if($this->config->get('show_code') && !empty($variant->product_code)) {
?>
	<div id="hikashop_product_code_<?php echo $variant_name; ?>" style="display:none;"><?php
		echo $variant->product_code;
	?></div>
<?php
	}


	if(HIKASHOP_J30) {
		$this->setLayout('show_block_tags');
		echo $this->loadTemplate();
	}

?>
	<div id="hikashop_product_price_<?php echo $variant_name; ?>" style="display:none;"><?php
		if((int)$this->params->get('show_price', -1) == -1) {
			$this->params->set('show_price', (int)$this->config->get('show_price'));
		}
		if ($this->params->get('show_price')) {
			$this->setLayout('listing_price');
			echo $this->loadTemplate();
		}
	?></div>
<?php
	if(hikashop_level(1) && !empty($this->element->options)) {
		$priceUsed = 0;
		if(!empty($this->row->prices)) {
			foreach($this->row->prices as $price) {
				if(isset($price->price_min_quantity) && empty($this->cart_product_price) && $price->price_min_quantity <= 1)
					$priceUsed = ($this->params->get('price_with_tax')) ? $price->price_value_with_tax : $price->price_value;
			}
		}
?>
	<input type="hidden" name="hikashop_price_product" value="<?php echo $this->row->product_id; ?>" />
	<input type="hidden" id="hikashop_price_product_<?php echo $this->row->product_id; ?>" value="<?php echo $priceUsed; ?>" />
	<input type="hidden" id="hikashop_price_product_with_options_<?php echo $this->row->product_id; ?>" value="<?php echo $priceUsed; ?>" />
<?php
	}
?>
	<div id="hikashop_product_quantity_<?php echo $variant_name; ?>" style="display:none;"><?php
		$this->row = & $variant;
		$this->currentFormName = $variantFormName;
		$this->ajax = 'if(hikashopCheckChangeForm(\'item\',\'' . $variantFormName . '\')){ return hikashopModifyQuantity(\'' . $this->row->product_id . '\',field,1' . $this->formName . ',\'cart\'); } else { return false; }';
		$this->setLayout('quantity');
		echo $this->loadTemplate();
	?></div>
	<div id="hikashop_product_contact_<?php echo $variant_name; ?>" style="display:none;"><?php
		if(hikashop_level(1) && ($contact == 2 || ($contact == 1 && !empty ($this->element->main->product_contact)))) {
	$css_button = $this->config->get('css_button', 'hikabtn');
			$attributes = 'class="'.$css_button.'"';
			$fallback_url = hikashop_completeLink('product&task=contact&cid=' . (int)$this->row->product_id . $this->url_itemid);
			$content = JText::_('CONTACT_US_FOR_INFO');

			echo $this->loadHkLayout('button', array( 'attributes' => $attributes, 'content' => $content, 'fallback_url' => $fallback_url));

		}
	?></div>
<?php
	if(!empty($variant->product_description)) {
?>
		<div id="hikashop_product_description_<?php echo $variant_name; ?>" style="display:none;"><?php
			echo JHTML::_('content.prepare',preg_replace('#<hr *id="system-readmore" */?>#i','',$variant->product_description));
		?></div>
<?php
	}

	if ($this->config->get('weight_display', 0)) {
		if(!empty($variant->product_weight) && bccomp(sprintf('%F',$variant->product_weight), 0, 3)) {
?>
		<div id="hikashop_product_weight_<?php echo $variant_name; ?>" style="display:none;"><?php
			echo JText::_('PRODUCT_WEIGHT').': '.rtrim(rtrim($variant->product_weight,'0'),',.').' '.JText::_($variant->product_weight_unit);
		?><br /></div>
<?php
		}
	}

	$this->setLayout('show_block_bundled');
	echo $this->loadTemplate();

	if($this->config->get('dimensions_display', 0)) {
		if(!empty ($variant->product_width) && bccomp(sprintf('%F',$variant->product_width), 0, 3)) {
?>
		<div id="hikashop_product_width_<?php echo $variant_name; ?>" style="display:none;"><?php
			echo JText::_('PRODUCT_WIDTH').': '.rtrim(rtrim($variant->product_width, '0'), ',.').' '.JText::_($variant->product_dimension_unit);
		?><br /></div>
<?php
		}

		if(!empty($variant->product_length) && bccomp(sprintf('%F',$variant->product_length), 0, 3)) {
?>
		<div id="hikashop_product_length_<?php echo $variant_name; ?>" style="display:none;"><?php
			echo JText::_('PRODUCT_LENGTH').': '.rtrim(rtrim($variant->product_length, '0'), ',.').' '.JText::_($variant->product_dimension_unit);
		?><br /></div>
<?php
		}

		if(!empty($variant->product_height) && bccomp(sprintf('%F',$variant->product_height), 0, 3)) {
?>
		<div id="hikashop_product_height_<?php echo $variant_name; ?>" style="display:none;"><?php
			echo JText::_('PRODUCT_HEIGHT').': '.rtrim(rtrim($variant->product_height, '0'), ',.').' '.JText::_($variant->product_dimension_unit);
		?><br /></div>
<?php
		}
	}

	if(!empty($variant->product_url)) {
?>
		<span id="hikashop_product_url_<?php echo $variant_name; ?>" style="display:none;"><?php
			if(!empty ($variant->product_url))
				echo JText::sprintf('MANUFACTURER_URL', '<a href="' . $variant->product_url . '" target="_blank">' . $variant->product_url . '</a>');
		?></span>
<?php
	}
?>
		<span id="hikashop_product_id_<?php echo $variant_name; ?>">
			<input type="hidden" name="product_id" value="<?php echo $variant->product_id; ?>" />
		</span>
<?php
	if(!empty($this->fields)) {
?>
	<div id="hikashop_product_custom_info_<?php echo $variant_name; ?>" style="display:none;">
<?php
		if($this->productlayout != 'show_tabular') {
?>
		<h4><?php echo JText::_('SPECIFICATIONS');?></h4>
<?php
		}
?>
		<table class="hikashop_product_custom_info_<?php echo $variant_name; ?>">
<?php

		$this->fieldsClass->prefix = '';
		foreach($this->fields as $fieldName => $oneExtraField) {
			if(empty($variant->$fieldName) && !empty($this->element->main->$fieldName)) {
				$variant->$fieldName = $this->element->main->$fieldName;
			}
			if(!empty($variant->$fieldName))
				$variant->$fieldName = trim($variant->$fieldName);

			if(!empty($variant->$fieldName) || (isset($variant->$fieldName) && $variant->$fieldName === '0')) {
				$oneExtraField->currentElement = $variant;
?>
			<tr class="hikashop_product_custom_<?php echo $oneExtraField->field_namekey; ?>_line">
				<td class="key">
					<span id="hikashop_product_custom_name_<?php echo $oneExtraField->field_id; ?>_<?php echo $variant_name; ?>" class="hikashop_product_custom_name"><?php echo $this->fieldsClass->getFieldName($oneExtraField); ?></span>
				</td>
				<td>
					<span id="hikashop_product_custom_value_<?php echo $oneExtraField->field_id; ?>_<?php echo $variant_name; ?>" class="hikashop_product_custom_value"><?php echo $this->fieldsClass->show($oneExtraField,$variant->$fieldName); ?></span>
				</td>
			</tr>
<?php
			}
		}
?>
		</table>
	</div>
<?php
	}
?>
	<div id="hikashop_product_files_<?php echo $variant_name; ?>" style="display:none;">
<?php
	if(!empty($variant->files)) {
		$freeDownload = false;
		foreach($variant->files as $file) {
			if(!empty($file->file_free_download)) {
				$freeDownload = true;
				break;
			}
		}
		if($freeDownload) {
?>
		<fieldset class="hikashop_product_files_fieldset">
			<legend><?php echo JText::_('DOWNLOADS'); ?></legend>
<?php
			foreach($variant->files as $file) {
				if(empty($file->file_free_download))
					continue;

				if(empty($file->file_name))
					$file->file_name = $file->file_path;
?>
			<a class="hikashop_product_file_link" href="<?php echo hikashop_completeLink('product&task=download&file_id=' . $file->file_id); ?>"><?php echo $file->file_name; ?></a><br/>
<?php
			}
?>
		</fieldset>
<?php
		}
	}
?>
	</div>
<?php
	echo $this->addFormAttribute(ob_get_clean(), $variantFormName);
}
