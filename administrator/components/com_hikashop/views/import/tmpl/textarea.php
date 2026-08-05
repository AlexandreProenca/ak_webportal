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
?><textarea style="width:100%" rows="20" name="textareaentries">
<?php $text = hikaInput::get()->getString("textareaentries");
if(empty($text)){ ?>
product_name,price_value,price_currency_id
Bread,1.20,EUR
Coffee,2,USD
<?php }else echo $text?>
</textarea>
<table class="admintable table" cellspacing="1">
	<tr>
		<td class="key">
			<?php echo JText::_('COLUMN_MATCHING'); ?>
		</td>
		<td>
			<select name="textarea_column_mapping" id="textarea_column_mapping" class="custom-select" onchange="hikaImport.updateMappingUI('textarea', this.value);">
				<option value="none"><?php echo JText::_('NO_MATCHING'); ?></option>
				<option value="new"><?php echo JText::_('CREATE_NEW_MATCHING'); ?></option>
			</select>
			<input type="hidden" name="textarea_mapping" id="textarea_mapping" value="" />
			<span id="textarea_mapping_buttons" style="display:none;">
				<button type="button" class="btn btn-small" onclick="hikaImport.openColumnMatching('textarea');"><?php echo JText::_('CONFIGURE_COLUMNS'); ?></button>
				<button type="button" id="textarea_delete_btn" class="btn btn-small btn-danger" onclick="hikaImport.deleteMapping('textarea');" style="display:none; margin-left:5px;"><i class="icon-trash"></i> <?php echo JText::_('HIKA_DELETE'); ?></button>
			</span>
		</td>
	</tr>
	<tr>
		<td class="key" >
			<?php echo JText::_('UPDATE_PRODUCTS'); ?>
		</td>
		<td>
			<?php echo JHTML::_('hikaselect.booleanlist', 'textarea_update_products','',hikaInput::get()->getInt('textarea_update_products','1'));?>
		</td>
	</tr>
	<tr>
		<td class="key" >
			<?php echo JText::_('CREATE_CATEGORIES'); ?>
		</td>
		<td>
			<?php echo JHTML::_('hikaselect.booleanlist', 'textarea_create_categories','',hikaInput::get()->getInt('textarea_create_categories','1'));?>
		</td>
	</tr>
	<tr>
		<td class="key" >
			<?php echo JText::_('FORCE_PUBLISH'); ?>
		</td>
		<td>
			<?php echo JHTML::_('hikaselect.booleanlist', 'textarea_force_publish','',hikaInput::get()->getInt('textarea_force_publish','1'));?>
		</td>
	</tr>
	<tr>
		<td class="key" >
			<?php echo JText::_('UPDATE_PRODUCT_QUANTITY'); ?>
		</td>
		<td>
			<?php echo JHTML::_('hikaselect.booleanlist', 'textarea_update_product_quantity','',hikaInput::get()->getInt('textarea_update_product_quantity','0'));?>
		</td>
	</tr>
	<tr>
		<td class="key" >
			<?php echo JText::_('STORE_IMAGES_LOCALLY'); ?>
		</td>
		<td>
			<?php echo JHTML::_('hikaselect.booleanlist', 'textarea_store_images_locally','',hikaInput::get()->getInt('textarea_store_images_locally','1'));?>
		</td>
	</tr>
	<tr>
		<td class="key" >
			<?php echo JText::_('STORE_FILES_LOCALLY'); ?>
		</td>
		<td>
			<?php echo JHTML::_('hikaselect.booleanlist', 'textarea_store_files_locally','',hikaInput::get()->getInt('textarea_store_files_locally','1'));?>
		</td>
	</tr>
	<tr>
		<td class="key" >
			<?php echo JText::_('KEEP_OTHER_VARIANTS'); ?>
		</td>
		<td>
			<?php echo JHTML::_('hikaselect.booleanlist', 'keep_other_variants','',hikaInput::get()->getInt('keep_other_variants','1'));?>
		</td>
	</tr>
</table>
<?php echo $this->popupLink; ?>
