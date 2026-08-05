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
<div id="hikashop_product_top_part" class="hikashop_product_top_part hikashop_product_sticky_layout">
<!-- TOP BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->topBegin)) { echo implode("\r\n",$this->element->extraData->topBegin); } ?>
<!-- EO TOP BEGIN EXTRA DATA -->
	<h1>
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
		<span id="hikashop_product_code_main" class="hikashop_product_code_main"><?php
			echo $this->element->product_code;
		?></span>
<?php } ?>
<!-- EO CODE -->
	</h1>
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

<div class="hk-row-fluid hikashop_sticky_row">
	<div id="hikashop_product_left_part" class="hikashop_product_left_part hkc-md-7 hikashop_sticky_content">
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
<!-- DESCRIPTION -->
	<div id="hikashop_product_description_main" class="hikashop_product_description_main hikashop_sticky_description"><?php
		echo JHTML::_('content.prepare',preg_replace('#<hr *id="system-readmore" */?>#i','',$this->element->product_description));
	?></div>
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
<!-- LEFT END EXTRA DATA -->
<?php if(!empty($this->element->extraData->leftEnd)) { echo implode("\r\n",$this->element->extraData->leftEnd); } ?>
<!-- EO LEFT END EXTRA DATA -->
	</div>

	<div id="hikashop_product_right_part" class="hikashop_product_right_part hkc-md-5 hikashop_sticky_sidebar">
		<div class="hikashop_sticky_sidebar_inner">
<!-- RIGHT BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->rightBegin)) { echo implode("\r\n",$this->element->extraData->rightBegin); } ?>
<!-- EO RIGHT BEGIN EXTRA DATA -->
<!-- PRICE -->
<?php
?>
		<span id="hikashop_product_price_main" class="hikashop_product_price_main hikashop_sticky_price">
<?php
	if($this->params->get('show_price') && (empty($this->displayVariants['prices']) || $this->params->get('characteristic_display') != 'list')) {
		$this->row =& $this->element;
		$this->setLayout('listing_price');
		echo $this->loadTemplate();
	}
?>
		</span>
<!-- EO PRICE -->
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
		<div id="hikashop_product_quantity_main" class="hikashop_product_quantity_main hikashop_sticky_cart"><?php
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
</div>
<!-- END GRID -->
<div id="hikashop_product_bottom_part" class="hikashop_product_bottom_part">
<!-- BOTTOM BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->bottomBegin)) { echo implode("\r\n",$this->element->extraData->bottomBegin); } ?>
<!-- EO BOTTOM BEGIN EXTRA DATA -->
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
.hikashop_sticky_row {
	display: flex;
	flex-wrap: wrap;
	gap: 30px;
	align-items: flex-start;
}
.hikashop_sticky_row .hikashop_sticky_content {
	flex: 0 0 58%;
	max-width: 58%;
}
.hikashop_sticky_row .hikashop_sticky_sidebar {
	flex: 1;
	min-width: 0;
	position: sticky;
	top: 20px;
	align-self: flex-start;
}
.hikashop_sticky_sidebar_inner {
	background: #fff;
	border: 1px solid #e0e0e0;
	border-radius: 8px;
	padding: 24px;
	box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.hikashop_sticky_price {
	display: block;
	font-size: 1.5em;
	font-weight: bold;
	margin-bottom: 10px;
	padding-bottom: 10px;
	border-bottom: 1px solid #eee;
}
.hikashop_sticky_cart {
	margin: 16px 0;
	padding: 12px 0;
	border-top: 1px solid #eee;
}

.hikashop_sticky_cart .hikashop_quantity_form .hikabtn {
	width: auto;
	padding: 2px 8px;
}
.hikashop_sticky_description {
	margin-top: 30px;
	padding-top: 20px;
	border-top: 1px solid #eee;
	line-height: 1.7;
}

.hikashop_sticky_content .hikashop_global_image_div {
	display: flex;
	gap: 12px;
}
.hikashop_sticky_content .hikashop_main_image_div {
	flex: 1;
	min-width: 0;
}
.hikashop_sticky_content .hikashop_product_main_image_thumb {
	height: auto !important;
}
.hikashop_sticky_content .hikashop_product_main_image {
	height: auto !important;
	width: 100%;
}
.hikashop_sticky_content .hikashop_product_main_image img {
	max-width: 100%;
	height: auto;
	width: 100%;
	object-fit: contain;
}
.hikashop_sticky_content .hikashop_small_image_div {
	display: flex;
	flex-direction: column;
	gap: 8px;
	flex-shrink: 0;
	width: 72px;
}
.hikashop_sticky_content .hikashop_small_image_div a {
	display: block;
}
.hikashop_sticky_content .hikashop_small_image_div img {
	width: 72px;
	height: 72px;
	object-fit: cover;
	border: 2px solid #eee;
	border-radius: 4px;
	transition: border-color 0.2s;
}
.hikashop_sticky_content .hikashop_small_image_div img.hikashop_child_image_active {
	border-color: #333;
}
@media (max-width: 768px) {
	.hikashop_sticky_row .hikashop_sticky_content,
	.hikashop_sticky_row .hikashop_sticky_sidebar {
		flex: 0 0 100%;
		max-width: 100%;
		position: relative;
		top: 0;
	}
	.hikashop_sticky_content .hikashop_global_image_div {
		flex-direction: column;
	}
	.hikashop_sticky_content .hikashop_small_image_div {
		flex-direction: row;
		width: auto;
		overflow-x: auto;
	}
}
</style>
