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
	$form = ',0';
	if(!$this->config->get('ajax_add_to_cart', 1)) {
		$form = ',\'hikashop_product_form\'';
	}
?>
<div id="hikashop_product_top_part" class="hikashop_product_top_part hikashop_product_modern_layout">
<!-- TOP BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->topBegin)) { echo implode("\r\n",$this->element->extraData->topBegin); } ?>
<!-- EO TOP BEGIN EXTRA DATA -->
	<div class="hikashop_modern_header">
<!-- CODE -->
<?php if ($this->config->get('show_code')) { ?>
		<span id="hikashop_product_code_main" class="hikashop_product_code_main hikashop_modern_code"><?php
			echo $this->element->product_code;
		?></span>
<?php } ?>
<!-- EO CODE -->
	<h1 class="hikashop_modern_title">
<!-- NAME -->
		<span id="hikashop_product_name_main" class="hikashop_product_name_main"><?php
			if(hikashop_getCID('product_id') != $this->element->product_id && isset($this->element->main->product_name))
				echo $this->element->main->product_name;
			else
				echo $this->element->product_name;
		?></span>
<!-- EO NAME -->
	</h1>
<!-- VOTE -->
		<div id="hikashop_product_vote_mini" class="hikashop_product_vote_mini"><?php
	if($this->params->get('show_vote_product')) {
		$js = '';
		$this->params->set('vote_type', 'product');
		$this->params->set('vote_ref_id', isset($this->element->main) ? (int)$this->element->main->product_id : (int)$this->element->product_id );
		echo hikashop_getLayout('vote', 'mini', $this->params, $js);
	}
		?></div>
<!-- EO VOTE -->
	</div>
<!-- TOP END EXTRA DATA -->
<?php if(!empty($this->element->extraData->topEnd)) { echo implode("\r\n", $this->element->extraData->topEnd); } ?>
<!-- EO TOP END EXTRA DATA -->
<!-- SOCIAL NETWORKS -->
<?php
	$this->setLayout('show_block_social');
	echo $this->loadTemplate();
?>
<!-- EO SOCIAL NETWORKS -->
</div>

<div class="hk-row-fluid hikashop_modern_row">
	<div id="hikashop_product_left_part" class="hikashop_product_left_part hkc-md-7 hikashop_modern_images">
<!-- LEFT BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->leftBegin)) { echo implode("\r\n",$this->element->extraData->leftBegin); } ?>
<!-- EO LEFT BEGIN EXTRA DATA -->
<!-- IMAGE -->
<?php
	$this->row =& $this->element;
	$this->setLayout('show_block_img');
	echo $this->loadTemplate();
?>
<!-- EO IMAGE -->
<!-- LEFT END EXTRA DATA -->
<?php if(!empty($this->element->extraData->leftEnd)) { echo implode("\r\n",$this->element->extraData->leftEnd); } ?>
<!-- EO LEFT END EXTRA DATA -->
	</div>

	<div id="hikashop_product_right_part" class="hikashop_product_right_part hkc-md-5 hikashop_modern_purchase">
<!-- RIGHT BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->rightBegin)) { echo implode("\r\n",$this->element->extraData->rightBegin); } ?>
<!-- EO RIGHT BEGIN EXTRA DATA -->
<!-- PRICE -->
<?php
?>
		<span id="hikashop_product_price_main" class="hikashop_product_price_main hikashop_modern_price">
<?php
	if($this->params->get('show_price') && (empty($this->displayVariants['prices']) || $this->params->get('characteristic_display') != 'list')) {
		$this->row =& $this->element;
		$this->setLayout('listing_price');
		echo $this->loadTemplate();
	}
?>
		</span>
<!-- EO PRICE -->
<!-- RIGHT MIDDLE EXTRA DATA -->
<?php if(!empty($this->element->extraData->rightMiddle)) { echo implode("\r\n",$this->element->extraData->rightMiddle); } ?>
<!-- EO RIGHT MIDDLE EXTRA DATA -->
<!-- DIMENSIONS -->
<?php
	$this->setLayout('show_block_dimensions');
	echo $this->loadTemplate();
?>
<!-- EO DIMENSIONS -->
<!-- BUNDLED PRODUCTS -->
 <?php
	$this->setLayout('show_block_bundled');
	echo $this->loadTemplate();
?>
<!-- EO BUNDLED PRODUCTS -->
<!-- CHARACTERISTICS -->
<?php
	if($this->params->get('characteristic_display') != 'list') {
		$this->setFormProcessing('hikashop_product_form');
		$this->setLayout('show_block_characteristic');
		echo $this->loadTemplate();
		$this->setFormProcessing('', false);
	}
?>
<!-- EO CHARACTERISTICS -->
<!-- OPTIONS -->
<?php
	if(hikashop_level(1) && !empty ($this->element->options)) {
		$this->setFormProcessing('hikashop_product_form');
?>
		<div id="hikashop_product_options" class="hikashop_product_options"><?php
			$this->setLayout('option');
			echo $this->loadTemplate();
		?></div>
<?php
		$form = ',\'hikashop_product_form\'';
		if($this->config->get('redirect_url_after_add_cart', 'stay_if_cart') == 'ask_user') {
?>
		<input type="hidden" name="popup" value="1" form="hikashop_product_form"/>
<?php
		}
		$this->setFormProcessing('', false);
	}
?>
<!-- EO OPTIONS -->
<!-- CUSTOM ITEM FIELDS -->
<?php
	if(!empty($this->itemFields)) {
		$form = ',\'hikashop_product_form\'';
		if ($this->config->get('redirect_url_after_add_cart', 'stay_if_cart') == 'ask_user') {
?>
		<input type="hidden" name="popup" value="1" form="hikashop_product_form"/>
<?php
		}
		$this->setFormProcessing('hikashop_product_form');
		$this->setLayout('show_block_custom_item');
		echo $this->loadTemplate();
		$this->setFormProcessing('', false);
	}
?>
<!-- EO CUSTOM ITEM FIELDS -->
<!-- PRICE WITH OPTIONS -->
<?php
	if($this->params->get('show_price')) {
?>
		<span id="hikashop_product_price_with_options_main" class="hikashop_product_price_with_options_main">
		</span>
<?php
	}
?>
<!-- EO PRICE WITH OPTIONS -->
<!-- ADD TO CART BUTTON -->
<?php
	if(empty($this->element->characteristics) || $this->params->get('characteristic_display') != 'list') {
?>
		<div id="hikashop_product_quantity_main" class="hikashop_product_quantity_main hikashop_modern_cart"><?php
			$this->row =& $this->element;
			$this->formName = $form;
			$rawFormName = !empty($this->currentFormName) ? $this->currentFormName : 'hikashop_product_form';
			$this->ajax = 'if(hikashopCheckChangeForm(\'item\',\'' . $rawFormName . '\')){ return hikashopModifyQuantity(\'' . (int)$this->element->product_id . '\',field,1' . $form . ',\'cart\'); } else { return false; }';
			$this->setLayout('quantity');
			echo $this->loadTemplate();
		?></div>
		<div id="hikashop_product_quantity_alt" class="hikashop_product_quantity_main_alt hikashop_alt_hide">
			<?php echo JText::_('ADD_TO_CART_AVAILABLE_AFTER_CHARACTERISTIC_SELECTION'); ?>
		</div>
<?php
	}
?>
<!-- EO ADD TO CART BUTTON -->
<!-- CONTACT US BUTTON -->
		<div id="hikashop_product_contact_main" class="hikashop_product_contact_main"><?php
	$contact = (int)$this->config->get('product_contact', 0);
	if(hikashop_level(1) && ($contact == 2 || ($contact == 1 && !empty($this->element->product_contact)))) {
		$css_button = $this->config->get('css_button', 'hikabtn');
		$attributes = 'class="'.$css_button.'"';
		$fallback_url = hikashop_completeLink('product&task=contact&cid=' . (int)$this->element->product_id . $this->url_itemid);
		$content = JText::_('CONTACT_US_FOR_INFO');

		echo $this->loadHkLayout('button', array( 'attributes' => $attributes, 'content' => $content, 'fallback_url' => $fallback_url));
	}
?>
		</div>
<!-- EO CONTACT US BUTTON -->
<!-- TAGS -->
<?php
	if(HIKASHOP_J30) {
		$this->setLayout('show_block_tags');
		echo $this->loadTemplate();
	}
?>
<!-- EO TAGS -->
<!-- RIGHT END EXTRA DATA -->
<?php if(!empty($this->element->extraData->rightEnd)) { echo implode("\r\n",$this->element->extraData->rightEnd); } ?>
<!-- EO RIGHT END EXTRA DATA -->
<span id="hikashop_product_id_main" class="hikashop_product_id_main">
	<input type="hidden" name="product_id" value="<?php echo (int)$this->element->product_id; ?>" form="hikashop_product_form" />
</span>
</div>
</div>
<!-- END GRID -->
<div id="hikashop_product_bottom_part" class="hikashop_product_bottom_part hikashop_modern_bottom">
<!-- BOTTOM BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->bottomBegin)) { echo implode("\r\n",$this->element->extraData->bottomBegin); } ?>
<!-- EO BOTTOM BEGIN EXTRA DATA -->
<!-- DESCRIPTION -->
<?php if(!empty($this->element->product_description)) { ?>
	<div class="hikashop_modern_description_section">
		<h2 class="hikashop_modern_section_title"><?php echo JText::_('PRODUCT_DESCRIPTION'); ?></h2>
		<div id="hikashop_product_description_main" class="hikashop_product_description_main hikashop_modern_description_text"><?php
			echo JHTML::_('content.prepare',preg_replace('#<hr *id="system-readmore" */?>#i','',$this->element->product_description));
		?></div>
	</div>
<?php } ?>
<!-- EO DESCRIPTION -->
<!-- MANUFACTURER URL -->
	<span id="hikashop_product_url_main" class="hikashop_product_url_main"><?php
		if(!empty($this->element->product_url)) {
			echo JText::sprintf('MANUFACTURER_URL', '<a href="' . $this->element->product_url . '" target="_blank">' . $this->element->product_url . '</a>');
		}
	?></span>
<!-- EO MANUFACTURER URL -->
<!-- CUSTOM PRODUCT FIELDS -->
<?php
	if(!empty($this->fields)) {
		$this->setLayout('show_block_custom_main');
		echo $this->loadTemplate();
	}
?>
<!-- EO CUSTOM PRODUCT FIELDS -->
<!-- FILES -->
<?php
	$this->setLayout('show_block_product_files');
	echo $this->loadTemplate();
?>
<!-- EO FILES -->
<!-- BOTTOM MIDDLE EXTRA DATA -->
<?php if(!empty($this->element->extraData->bottomMiddle)) { echo implode("\r\n",$this->element->extraData->bottomMiddle); } ?>
<!-- EO BOTTOM MIDDLE EXTRA DATA -->
<!-- BOTTOM END EXTRA DATA -->
<?php if(!empty($this->element->extraData->bottomEnd)) { echo implode("\r\n",$this->element->extraData->bottomEnd); } ?>
<!-- EO BOTTOM END EXTRA DATA -->
</div>
<?php
$this->setLayout('show_microdata');
echo $this->loadTemplate();
?>
<style>
.hikashop_product_modern_layout {
	text-align: center;
	margin-bottom: 10px;
}
.hikashop_modern_header {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 4px;
}
.hikashop_modern_code {
	text-transform: uppercase;
	font-size: 0.75em;
	letter-spacing: 2px;
	color: #888;
}
.hikashop_modern_title {
	font-weight: 300;
	font-size: 2em;
	letter-spacing: 1px;
	margin: 0;
}
.hikashop_modern_row {
	display: flex;
	flex-wrap: wrap;
	gap: 40px;
}
.hikashop_modern_row .hikashop_modern_images {
	flex: 0 0 58%;
	max-width: 58%;
}

.hikashop_modern_images .hikashop_global_image_div {
	display: flex;
	flex-direction: row-reverse;
	gap: 12px;
}
.hikashop_modern_images .hikashop_main_image_div {
	flex: 1;
	min-width: 0;
}
.hikashop_modern_images .hikashop_product_main_image_thumb {
	height: auto !important;
}
.hikashop_modern_images .hikashop_product_main_image {
	height: auto !important;
	width: 100%;
}
.hikashop_modern_images .hikashop_product_main_image img {
	max-width: 100%;
	height: auto;
	width: 100%;
	object-fit: contain;
}
.hikashop_modern_images .hikashop_small_image_div {
	display: flex;
	flex-direction: column;
	gap: 8px;
	flex-shrink: 0;
	width: 70px;
}
.hikashop_modern_images .hikashop_small_image_div a {
	display: block;
}
.hikashop_modern_images .hikashop_small_image_div img {
	width: 70px;
	height: 70px;
	object-fit: cover;
	border: 2px solid transparent;
	border-radius: 2px;
	transition: border-color 0.2s;
}
.hikashop_modern_images .hikashop_small_image_div img.hikashop_child_image_active {
	border-color: #333;
}
.hikashop_modern_purchase {
	flex: 1;
	min-width: 0;
	padding-top: 10px;
}
.hikashop_modern_price {
	display: block;
	font-size: 1.4em;
	letter-spacing: 0.5px;
	margin-bottom: 20px;
	padding-bottom: 16px;
	border-bottom: 1px solid #e0e0e0;
}
.hikashop_modern_cart {
	margin: 20px 0;
}
.hikashop_modern_cart .hikabtn,
.hikashop_modern_cart .hikabtn_checkout,
.hikashop_modern_purchase .hikashop_product_contact_main .hikabtn {
	width: 100%;
	padding: 14px;
	text-transform: uppercase;
	letter-spacing: 1px;
	font-size: 0.9em;
}

.hikashop_modern_cart .hikashop_quantity_form .hikabtn {
	width: auto;
	padding: 2px 8px;
	text-transform: none;
	letter-spacing: 0;
	font-size: inherit;
}
.hikashop_modern_bottom {
	margin-top: 50px;
	padding-top: 40px;
	border-top: 1px solid #ddd;
}
.hikashop_modern_description_section {
	max-width: 900px;
	margin: 0 auto;
}
.hikashop_modern_section_title {
	text-align: center;
	font-weight: 300;
	font-size: 1.5em;
	letter-spacing: 1px;
	text-transform: uppercase;
	margin-bottom: 24px;
}
.hikashop_modern_description_text {
	column-count: 2;
	column-gap: 40px;
	line-height: 1.8;
}
@media (max-width: 768px) {
	.hikashop_modern_row .hikashop_modern_images,
	.hikashop_modern_purchase {
		flex: 0 0 100%;
		max-width: 100%;
	}
	.hikashop_modern_images .hikashop_global_image_div {
		flex-direction: column;
	}
	.hikashop_modern_images .hikashop_small_image_div {
		flex-direction: row;
		width: auto;
		overflow-x: auto;
	}
	.hikashop_modern_description_text {
		column-count: 1;
	}
	.hikashop_modern_title {
		font-size: 1.5em;
	}
}
</style>
