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
	if (!$this->config->get('ajax_add_to_cart', 1)) {
		$form = ',\'hikashop_product_form\'';
	}
?>
<div id="hikashop_product_top_part" class="hikashop_product_top_part">
<!-- TOP BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->topBegin)) { echo implode("\r\n",$this->element->extraData->topBegin); } ?>
<!-- EO TOP BEGIN EXTRA DATA -->
	<h1>
<!-- NAME -->
		<span id="hikashop_product_name_main" class="hikashop_product_name_main">
			<?php
			if (hikashop_getCID('product_id')!=$this->element->product_id && isset ($this->element->main->product_name))
				echo $this->element->main->product_name;
			else
				echo $this->element->product_name;

		?></span>
<!-- EO NAME -->
<!-- CODE -->
	<?php if ($this->config->get('show_code')) { ?>
		<span id="hikashop_product_code_main" class="hikashop_product_code_main">
			<?php
			echo $this->element->product_code;
			?>
		</span>
		<?php } ?>
<!-- EO CODE -->
	</h1>
<!-- TOP END EXTRA DATA -->
<?php if(!empty($this->element->extraData->topEnd)) { echo implode("\r\n",$this->element->extraData->topEnd); } ?>
<!-- EO TOP END EXTRA DATA -->
<!-- SOCIAL NETWORKS -->
<?php
	$this->setLayout('show_block_social');
	echo $this->loadTemplate();
?>
<!-- EO SOCIAL NETWORKS -->
</div>

<div class="hk-row-fluid">
	<div id="hikashop_product_left_part" class="hikashop_product_left_part hkc-md-6">
<!-- RIGHT BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->rightBegin)) { echo implode("\r\n",$this->element->extraData->rightBegin); } ?>
<!-- EO RIGHT BEGIN EXTRA DATA -->
<!-- VOTE -->
		<div id="hikashop_product_vote_mini" class="hikashop_product_vote_mini">
			<?php
			if($this->params->get('show_vote_product') == '-1'){
				$this->params->set('show_vote_product',$config->get('show_vote_product'));
			}
			if($this->params->get('show_vote_product')){
				$js = '';
				$this->params->set('vote_type','product');
				if(isset($this->element->main)){
					$product_id = $this->element->main->product_id;
				}else{
					$product_id = $this->element->product_id;
				}
				$this->params->set('vote_ref_id',$product_id);
				echo hikashop_getLayout('vote', 'mini', $this->params, $js);
			}
			?>
		</div>
<!-- EO VOTE -->
<!-- PRICE -->
<?php
?>
	<span id="hikashop_product_price_main" class="hikashop_product_price_main">
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
	<br />
<!-- BUNDLED PRODUCTS -->
<?php
	$this->setLayout('show_block_bundled');
	echo $this->loadTemplate();
?>
<!-- EO BUNDLED PRODUCTS -->
<!-- CHARACTERISTICS -->
<?php
	if($this->params->get('characteristic_display')!='list'){
		$this->setFormProcessing('hikashop_product_form');
		$this->setLayout('show_block_characteristic');
		echo $this->loadTemplate();
		$this->setFormProcessing('', false);
?>
	<br />
<?php }  ?>
<!-- EO CHARACTERISTICS -->
<!-- OPTIONS -->
<?php
	if (hikashop_level(1) && !empty ($this->element->options)) {
		$this->setFormProcessing('hikashop_product_form');
	?>
	<div id="hikashop_product_options" class="hikashop_product_options">
		<?php
		$this->setLayout('option');
		echo $this->loadTemplate();
		?>
	</div>
	<br />
<?php
		$form = ',\'hikashop_product_form\'';
		if ($this->config->get('redirect_url_after_add_cart', 'stay_if_cart') == 'ask_user') {
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
	if (!empty ($this->itemFields)) {
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
	$this->formName = $form;
	if($this->params->get('show_price')) { ?>
		<span id="hikashop_product_price_with_options_main" class="hikashop_product_price_with_options_main">
		</span>
<?php } ?>
<!-- EO PRICE WITH OPTIONS -->
<!-- ADD TO CART BUTTON -->
<?php if(empty ($this->element->characteristics) || $this->params->get('characteristic_display')!='list'){ ?>
		<div id="hikashop_product_quantity_main" class="hikashop_product_quantity_main">
			<?php
			$this->row = & $this->element;
			$rawFormName = !empty($this->currentFormName) ? $this->currentFormName : 'hikashop_product_form';
			$this->ajax = 'if(hikashopCheckChangeForm(\'item\',\'' . $rawFormName . '\')){ return hikashopModifyQuantity(\'' . $this->row->product_id . '\',field,1' . $form . ',\'cart\'); } else { return false; }';
			$this->setLayout('quantity');
			echo $this->loadTemplate();
			?>
		</div>
		<div id="hikashop_product_quantity_alt" class="hikashop_product_quantity_main_alt hikashop_alt_hide">
			<?php echo JText::_('ADD_TO_CART_AVAILABLE_AFTER_CHARACTERISTIC_SELECTION'); ?>
		</div>
	<?php } 
	?>
<!-- EO ADD TO CART BUTTON -->
<!-- CONTACT US BUTTON -->
	<div id="hikashop_product_contact_main" class="hikashop_product_contact_main">
<?php
	$contact = $this->config->get('product_contact',0);
	if(hikashop_level(1) && ($contact == 2 || ($contact == 1 && !empty ($this->element->product_contact)))) {
		$css_button = $this->config->get('css_button', 'hikabtn');
		$attributes = 'class="'.$css_button.'"';
		$fallback_url = hikashop_completeLink('product&task=contact&cid=' . (int)$this->row->product_id  . $this->url_itemid);
		$content = JText::_('CONTACT_US_FOR_INFO');

		echo $this->loadHkLayout('button', array( 'attributes' => $attributes, 'content' => $content, 'fallback_url' => $fallback_url));
	}
?>
	</div>
<!-- EO CONTACT US BUTTON -->
<!-- CUSTOM PRODUCT FIELDS -->
<?php
	if(!empty($this->fields)) {
		$this->setLayout('show_block_custom_main');
		echo $this->loadTemplate();
	}
?>
<!-- EO CUSTOM PRODUCT FIELDS -->
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
		<input type="hidden" name="product_id" value="<?php echo $this->element->product_id; ?>" form="hikashop_product_form" />
	</span>
</div>
<div id="hikashop_product_right_part" class="hikashop_product_right_part hkc-md-6">
<!-- RIGHT END EXTRA DATA -->
<?php if(!empty($this->element->extraData->rightBegin)) { echo implode("\r\n",$this->element->extraData->leftBegin); } ?>
<!-- EO RIGHT END EXTRA DATA -->
<!-- IMAGE -->
<?php
	$this->row =& $this->element;
	$this->setLayout('show_block_img');
	echo $this->loadTemplate();
?>
<!-- EO IMAGE -->
<!-- RIGHT END EXTRA DATA -->
<?php if(!empty($this->element->extraData->rightEnd)) { echo implode("\r\n",$this->element->extraData->leftEnd); } ?>
<!-- EO RIGHT END EXTRA DATA -->
</div>
</div>
<!-- END GRID -->
<div id="hikashop_product_bottom_part" class="hikashop_product_bottom_part">
<!-- BOTTOM BEGIN EXTRA DATA -->
<?php if(!empty($this->element->extraData->bottomBegin)) { echo implode("\r\n",$this->element->extraData->bottomBegin); } ?>
<!-- EO BOTTOM BEGIN EXTRA DATA -->
<!-- DESCRIPTION -->
	<div id="hikashop_product_description_main" class="hikashop_product_description_main">
		<?php
		echo JHTML::_('content.prepare',preg_replace('#<hr *id="system-readmore" */?>#i','',$this->element->product_description));
		?>
	</div>
<!-- EO DESCRIPTION -->
<!-- MANUFACTURER URL -->
	<span id="hikashop_product_url_main" class="hikashop_product_url_main">
		<?php
		if (!empty ($this->element->product_url)) {
			echo JText::sprintf('MANUFACTURER_URL', '<a href="' . $this->element->product_url . '" target="_blank">' . $this->element->product_url . '</a>');
		}
		?>
	</span>
<!-- EO MANUFACTURER URL -->
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
