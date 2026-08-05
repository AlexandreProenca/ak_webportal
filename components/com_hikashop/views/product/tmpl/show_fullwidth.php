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
<div id="hikashop_product_top_part" class="hikashop_product_top_part hikashop_product_fullwidth_layout">
<!-- TOP BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->topBegin)) { echo implode("\r\n",$this->element->extraData->topBegin); } ?>
<!-- EO TOP BEGIN EXTRA DATA -->
<!-- TOP END EXTRA DATA -->
<?php if(!empty($this->element->extraData->topEnd)) { echo implode("\r\n", $this->element->extraData->topEnd); } ?>
<!-- EO TOP END EXTRA DATA -->
</div>

<!-- IMAGE -->
<div class="hikashop_fullwidth_hero">
<!-- LEFT BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->leftBegin)) { echo implode("\r\n",$this->element->extraData->leftBegin); } ?>
<!-- EO LEFT BEGIN EXTRA DATA -->
	<div id="hikashop_product_left_part" class="hikashop_product_left_part hikashop_fullwidth_image_wrap">
<?php
	$this->row =& $this->element;
	$this->setLayout('show_block_img');
	echo $this->loadTemplate();
?>
	</div>
<!-- LEFT END EXTRA DATA -->
<?php if(!empty($this->element->extraData->leftEnd)) { echo implode("\r\n",$this->element->extraData->leftEnd); } ?>
<!-- EO LEFT END EXTRA DATA -->
</div>
<!-- EO IMAGE -->

<div id="hikashop_product_right_part" class="hikashop_product_right_part hikashop_fullwidth_purchase">
	<div class="hikashop_fullwidth_purchase_inner">
	<h1 class="hikashop_fullwidth_title">
<!-- NAME -->
		<span id="hikashop_product_name_main" class="hikashop_product_name_main"><?php
			if(hikashop_getCID('product_id') != $this->element->product_id && isset($this->element->main->product_name))
				echo $this->element->main->product_name;
			else
				echo $this->element->product_name;
		?></span>
<!-- EO NAME -->
<!-- CODE -->
<?php if ($this->config->get('show_code')) { ?>
		<span id="hikashop_product_code_main" class="hikashop_product_code_main hikashop_fullwidth_code"><?php
			echo $this->element->product_code;
		?></span>
<?php } ?>
<!-- EO CODE -->
	</h1>

<!-- RIGHT BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->rightBegin)) { echo implode("\r\n",$this->element->extraData->rightBegin); } ?>
<!-- EO RIGHT BEGIN EXTRA DATA -->
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
<!-- PRICE -->
<?php
?>
		<span id="hikashop_product_price_main" class="hikashop_product_price_main hikashop_fullwidth_price">
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
		<div id="hikashop_product_quantity_main" class="hikashop_product_quantity_main hikashop_fullwidth_cart"><?php
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
<!-- SOCIAL NETWORKS -->
<?php
	$this->setLayout('show_block_social');
	echo $this->loadTemplate();
?>
<!-- EO SOCIAL NETWORKS -->
<!-- RIGHT END EXTRA DATA -->
<?php if(!empty($this->element->extraData->rightEnd)) { echo implode("\r\n",$this->element->extraData->rightEnd); } ?>
<!-- EO RIGHT END EXTRA DATA -->
<span id="hikashop_product_id_main" class="hikashop_product_id_main">
	<input type="hidden" name="product_id" value="<?php echo (int)$this->element->product_id; ?>" form="hikashop_product_form" />
</span>
	</div>
</div>
<!-- END GRID -->
<div id="hikashop_product_bottom_part" class="hikashop_product_bottom_part hikashop_fullwidth_bottom">
<!-- BOTTOM BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->bottomBegin)) { echo implode("\r\n",$this->element->extraData->bottomBegin); } ?>
<!-- EO BOTTOM BEGIN EXTRA DATA -->
<!-- DESCRIPTION -->
<?php if(!empty($this->element->product_description)) { ?>
	<div class="hikashop_fullwidth_band hikashop_fullwidth_band_light">
		<div class="hikashop_fullwidth_band_inner">
			<h2 class="hikashop_fullwidth_band_title"><?php echo JText::_('PRODUCT_DESCRIPTION'); ?></h2>
			<div id="hikashop_product_description_main" class="hikashop_product_description_main"><?php
				echo JHTML::_('content.prepare',preg_replace('#<hr *id="system-readmore" */?>#i','',$this->element->product_description));
			?></div>
		</div>
	</div>
<?php } ?>
<!-- EO DESCRIPTION -->
<!-- CUSTOM PRODUCT FIELDS -->
<?php
	if(!empty($this->fields)) {
?>
	<div class="hikashop_fullwidth_band hikashop_fullwidth_band_dark">
		<div class="hikashop_fullwidth_band_inner">
			<h2 class="hikashop_fullwidth_band_title"><?php echo JText::_('SPECIFICATIONS'); ?></h2>
<?php
		$this->setLayout('show_block_custom_main');
		echo $this->loadTemplate();
?>
		</div>
	</div>
<?php } ?>
<!-- EO CUSTOM PRODUCT FIELDS -->
<!-- MANUFACTURER URL -->
<?php if(!empty($this->element->product_url)) { ?>
	<div class="hikashop_fullwidth_band hikashop_fullwidth_band_light">
		<div class="hikashop_fullwidth_band_inner">
			<span id="hikashop_product_url_main" class="hikashop_product_url_main"><?php
				echo JText::sprintf('MANUFACTURER_URL', '<a href="' . $this->element->product_url . '" target="_blank">' . $this->element->product_url . '</a>');
			?></span>
		</div>
	</div>
<?php } ?>
<!-- EO MANUFACTURER URL -->
<!-- FILES -->
<?php
	$this->setLayout('show_block_product_files');
	echo $this->loadTemplate();
?>
<!-- EO FILES -->
<!-- TAGS -->
<?php
	if(HIKASHOP_J30) {
		$this->setLayout('show_block_tags');
		echo $this->loadTemplate();
	}
?>
<!-- EO TAGS -->
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
.hikashop_fullwidth_hero {
	margin: 0 -15px 0 -15px;
	text-align: center;
}
.hikashop_fullwidth_image_wrap {
	max-width: 100%;
	margin: 0 auto;
}

.hikashop_fullwidth_hero .hikashop_main_image_div {
	width: 100%;
}
.hikashop_fullwidth_hero .hikashop_product_main_image_thumb {
	height: auto !important;
}
.hikashop_fullwidth_hero .hikashop_product_main_image {
	height: auto !important;
	width: 100%;
}
.hikashop_fullwidth_hero .hikashop_product_main_image img {
	max-width: 100%;
	width: auto;
	height: auto;
	min-height: 300px;
	max-height: 600px;
	object-fit: contain;
}
.hikashop_fullwidth_hero .hikashop_product_main_image_subdiv {
	min-height: 300px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.hikashop_fullwidth_hero .hikashop_small_image_div {
	display: flex;
	justify-content: center;
	gap: 10px;
	margin-top: 16px;
	padding: 0 20px;
	flex-wrap: wrap;
}
.hikashop_fullwidth_hero .hikashop_small_image_div a {
	display: inline-block;
}
.hikashop_fullwidth_hero .hikashop_small_image_div img {
	width: 80px;
	height: 80px;
	object-fit: cover;
	border: 2px solid transparent;
	border-radius: 4px;
	transition: border-color 0.2s;
}
.hikashop_fullwidth_hero .hikashop_small_image_div img.hikashop_child_image_active {
	border-color: #333;
}

.hikashop_fullwidth_purchase div#hikashop_social {
	text-align: center !important;
}
div#hikashop_product_right_part.hikashop_fullwidth_purchase {
	display: flex;
	justify-content: center;
	float: none;
	width: 100%;
	padding: 40px 20px;
	text-align: center;
}
.hikashop_fullwidth_purchase_inner {
	max-width: 500px;
	width: 100%;
}
.hikashop_fullwidth_title {
	font-size: 2em;
	font-weight: 300;
	margin: 0 0 10px 0;
}
.hikashop_fullwidth_code {
	display: block;
	font-size: 0.4em;
	color: #999;
	letter-spacing: 2px;
	text-transform: uppercase;
	font-weight: 400;
	margin-top: 6px;
}
.hikashop_fullwidth_price {
	display: block;
	font-size: 1.6em;
	margin: 16px 0;
}
.hikashop_fullwidth_cart {
	margin: 20px 0;
}
.hikashop_fullwidth_cart .hikabtn,
.hikashop_fullwidth_cart .hikabtn_checkout,
.hikashop_fullwidth_purchase .hikashop_product_contact_main .hikabtn {
	width: 100%;
	padding: 14px 24px;
	font-size: 1em;
	text-transform: uppercase;
	letter-spacing: 1px;
}

.hikashop_fullwidth_cart .hikashop_quantity_form .hikabtn {
	width: auto;
	padding: 2px 8px;
	font-size: inherit;
	text-transform: none;
	letter-spacing: 0;
}
.hikashop_fullwidth_bottom {
	margin-top: 20px;
}
.hikashop_fullwidth_band {
	padding: 50px 20px;
	margin: 0 -15px;
}
.hikashop_fullwidth_band_inner {
	max-width: 800px;
	margin: 0 auto;
	line-height: 1.8;
}
.hikashop_fullwidth_band_title {
	text-align: center;
	font-weight: 300;
	font-size: 1.5em;
	text-transform: uppercase;
	letter-spacing: 1px;
	margin-bottom: 24px;
}
.hikashop_fullwidth_band_light {
	background: #f8f8f8;
}
.hikashop_fullwidth_band_dark {
	background: #2d2d2d;
	color: #e0e0e0;
}
.hikashop_fullwidth_band_dark a {
	color: #8cb4ff;
}
.hikashop_fullwidth_band_dark .hikashop_fullwidth_band_title {
	color: #fff;
}
</style>
