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
?><div id="hikashop_withdrawal_form_page" class="hikashop_withdrawal_form_page">
    <form action="<?php echo $this->url; ?>" method="post" name="withdrawalForm" id="withdrawalForm" class="form-validate">
        <fieldset>
			<div class="title_area" style="float:left">
			    <h1><?php echo JText::_('HIKA_WITHDRAW_FROM_CONTRACT'); ?></h1>
			</div>
			<div class="toolbar" id="toolbar" style="float: right;">
				<button class="hikabtn hikabtn-success" type="submit"><?php echo JText::_('HIKA_SUBMIT'); ?></button>
			</div>
			<div style="clear:both"></div>
		</fieldset>

        <?php if(!empty($this->order)) { ?>
	        <dl>
	        	<dt class="hikashop_withdrawal_order_info">
		            <label><?php echo JText::_('HIKA_WITHDRAWAL_ORDER'); ?>:</label>
	            </dt>
	            <dd>
		            <?php echo $this->order->order_number; ?>
	            </dd>
	        </dl>

	        <div class="hikashop_withdrawal_products">
	            <h3><?php echo JText::_('HIKA_WITHDRAWAL_PRODUCT_SELECTION'); ?></h3>
	            <table class="table table-striped">
	                <thead>
	                    <tr>
	                    	<th class="hikashop_withdrawal_product_checkbox" style="width:25px;text-align:center;">
	                    		<input type="checkbox" onclick="var c=this.checked;var e=document.querySelectorAll('.hikashop_withdrawal_product_checkbox input');for(var i=0;i<e.length;i++){e[i].checked=c;e[i].onclick();}" checked="checked" />
	                    	</th>
	                        <th><?php echo JText::_('CART_PRODUCT_NAME'); ?></th>
	                        <th><?php echo JText::_('HIKA_PURCHASED_QUANTITY'); ?></th>
	                        <th><?php echo JText::_('HIKA_WITHDRAWAL_PRODUCTS'); ?></th>
	                    </tr>
	                </thead>
	                <tbody>
	                <?php
					$config = hikashop_config();
					$group_options = (int)$config->get('group_options', 0);
					foreach($this->order->products as $product) {
						if($group_options && !empty($product->order_product_option_parent_id)) continue;

						$checked = 'checked="checked"';
						$disabled = '';
						$quantity = $product->order_product_quantity;

						if(isset($this->formData['selected_products']) && !isset($this->formData['selected_products'][$product->order_product_id])) {
							$checked = '';
							$disabled = 'disabled="disabled"';
						}

						if(isset($this->formData['products'][$product->order_product_id])) {
							$quantity = $this->formData['products'][$product->order_product_id];
						}
					?>
	                    <tr>
	                    	<td style="text-align:center;">
	                    		<input type="checkbox" name="withdrawal[selected_products][<?php echo $product->order_product_id; ?>]" value="1" <?php echo $checked; ?> onclick="document.getElementById('withdrawal_product_<?php echo $product->order_product_id; ?>').disabled = !this.checked;" class="hikashop_withdrawal_product_checkbox" />
	                    	</td>
	                        <td style="width:70%"><?php 
	                        	echo $product->order_product_name; 

								if($group_options) {
									foreach($this->order->products as $j => $optionElement) {
										if($optionElement->order_product_option_parent_id != $product->order_product_id)
											continue;

										?>
										<p class="hikashop_order_option_name">
										<?php
										echo $optionElement->order_product_name;
										if($optionElement->order_product_price > 0) {
											if($config->get('price_with_tax')) {
												echo ' ( + '.$this->currencyHelper->format($optionElement->order_product_price + $optionElement->order_product_tax, $this->order->order_currency_id).' )';
											} else {
												echo ' ( + '.$this->currencyHelper->format($optionElement->order_product_price, $this->order->order_currency_id).' )';
											}
										}
										?>
										</p>
										<?php
									}
								}

								if(hikashop_level(2)){
									$item_type = 'display:front_order=1';
									$itemFields = $this->fieldsClass->getFields($item_type,$product,'item');
									if(!empty($itemFields)) {
										foreach($itemFields as $field) {
											$namekey = $field->field_namekey;
											if(!empty($product->$namekey) && strlen($product->$namekey)) {
												$field->currentElement = $product;
												echo '<p class="hikashop_order_item_'.$namekey.'">' .
													$this->fieldsClass->getFieldName($field) . ': <span>' .
													$this->fieldsClass->show($field,$product->$namekey,'hikashop_custom_file_link', $this->order->order_token).'</span>' .
													'</p>';
											}
										}
									}
								}
	                        ?></td>
	                        <td><?php echo $product->order_product_quantity; ?></td>
	                        <td>
	                            <input type="number" id="withdrawal_product_<?php echo $product->order_product_id; ?>" name="withdrawal[products][<?php echo $product->order_product_id; ?>]" value="<?php echo $quantity; ?>" min="0" max="<?php echo $product->order_product_quantity; ?>" class="input-mini <?php echo HK_FORM_CONTROL_CLASS; ?>" style="width: 100px;" <?php echo $disabled; ?> />
	                        </td>
	                    </tr>
	                <?php } ?>
	                </tbody>
	            </table>
	        </div>
	        <input type="hidden" name="withdrawal[withdrawal_order_id]" value="<?php echo $this->order->order_id; ?>" />
	        <dl>
        <?php } else { ?>
        	<dl>
                 <dt class="hikashop_withdrawal_item_name">
                     <label for="withdrawal_email"><?php echo JText::_('HIKA_EMAIL'); ?> <span class="hikashop_field_required_label">*</span></label>
                 </dt>
                 <dd class="hikashop_withdrawal_item_value">
                     <input type="text" name="withdrawal[email]" id="withdrawal_email" class="<?php echo HK_FORM_CONTROL_CLASS; ?> required validate-email" value="<?php echo $this->escape(@$this->formData['email']); ?>" required />
                 </dd>
                 <dt class="hikashop_withdrawal_item_name">
                     <label for="withdrawal_order_number"><?php echo JText::_('ORDER_NUMBER'); ?> <span class="hikashop_field_required_label">*</span></label>
                 </dt>
                 <dd class="hikashop_withdrawal_item_value">
                     <input type="text" name="withdrawal[order_number]" id="withdrawal_order_number" class="<?php echo HK_FORM_CONTROL_CLASS; ?> required" value="<?php echo $this->escape(@$this->formData['order_number']); ?>" required />
                 </dd>
        <?php } ?>

			<dt class="hikashop_withdrawal_reason">
				<label for="withdrawal_reason"><?php echo JText::_('HIKA_WITHDRAWAL_REASON'); ?></label>
			</dt>
			<dd class="hikashop_withdrawal_reason_value">
				<textarea name="withdrawal[withdrawal_reason]" id="withdrawal_reason" class="<?php echo HK_FORM_CONTROL_CLASS; ?>" rows="5"><?php echo $this->escape(@$this->formData['withdrawal_reason']); ?></textarea>
			</dd>
        </dl>

        <input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
        <input type="hidden" name="ctrl" value="withdrawal" />
        <input type="hidden" name="task" value="save" />
        <input type="hidden" name="Itemid" value="<?php echo hikaInput::get()->getInt('Itemid'); ?>" />
        <?php echo JHtml::_('form.token'); ?>
    </form>
</div>
