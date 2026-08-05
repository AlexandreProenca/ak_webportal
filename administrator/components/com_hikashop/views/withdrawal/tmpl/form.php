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
?><form action="<?php echo hikashop_completeLink('withdrawal'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
	<div class="hikashop_backend_tile_edition">
		<div class="hk-container-fluid">
			<div class="hkc-lg-6 hikashop_tile_block"><div>
				<div class="hikashop_tile_title"><?php echo JText::_('HIKA_WITHDRAWAL_MANAGE'); ?></div>
				<dl class="hika_options">
					<dt><label><?php echo JText::_('HIKA_WITHDRAWAL_ID'); ?></label></dt>
					<dd><?php echo $this->element->withdrawal_id; ?></dd>
				</dl>
				<dl class="hika_options">
					<dt><label for="withdrawal_status"><?php echo JText::_('HIKA_WITHDRAWAL_STATUS'); ?></label></dt>
					<dd>
						<?php echo hikashop_get('type.withdrawal_status')->display('data[withdrawal][withdrawal_status]', $this->element->withdrawal_status, 'id="withdrawal_status" class="inputbox custom-select"'); ?>
					</dd>
				</dl>
				<dl class="hika_options">
					<dt><label for="withdrawal_reason"><?php echo JText::_('HIKA_WITHDRAWAL_REASON'); ?></label></dt>
					<dd>
						<textarea name="data[withdrawal][withdrawal_reason]" id="withdrawal_reason" rows="5" class="inputbox" style="width: 90%;"><?php echo htmlspecialchars($this->element->withdrawal_reason); ?></textarea>
					</dd>
				</dl>
			</div></div>

			<div class="hkc-lg-6 hikashop_tile_block"><div>
				<div class="hikashop_tile_title"><?php echo JText::_('HIKASHOP_ORDER'); ?></div>
				<dl class="hika_options">
					<dt><label><?php echo JText::_('HIKASHOP_ORDER'); ?></label></dt>
					<dd>
						<a href="<?php echo hikashop_completeLink('order&task=edit&order_id=' . $this->element->withdrawal_order_id . '&cancel_redirect='.urlencode(base64_encode(hikashop_completeLink('withdrawal&task=edit&withdrawal_id=' . $this->element->withdrawal_id)))); ?>"><?php echo $this->element->order->order_number; ?></a>
					</dd>
				</dl>
				<dl class="hika_options">
					<dt><label><?php echo JText::_('CUSTOMER'); ?></label></dt>
					<dd>
						<a href="<?php echo hikashop_completeLink('user&task=edit&user_id=' . $this->element->order->order_user_id); ?>"><?php echo $this->element->order->customer->user_email; ?></a>
					</dd>
				</dl>
				<dl class="hika_options">
					<dt><label for="withdrawal_user_check"><?php echo JText::_('HIKA_WITHDRAWAL_USER_CHECKED'); ?></label></dt>
					<dd>
						<input type="checkbox" name="data[withdrawal][withdrawal_user_check]" id="withdrawal_user_check" value="1" <?php echo $this->element->withdrawal_user_check ? 'checked="checked"' : ''; ?> />
					</dd>
				</dl>
			</div></div>

			<div class="hkc-lg-12 hikashop_tile_block"><div>
				<div class="hikashop_tile_title"><?php echo JText::_('HIKA_WITHDRAWAL_PRODUCTS'); ?></div>
				<?php 
				if(!empty($this->element->order->products)) {
					$withdrawal_products = array();
					if(!empty($this->element->withdrawal_products)) {
						$prods = $this->element->withdrawal_products;
						if(is_string($prods)) $prods = json_decode($prods);
						foreach($prods as $p) {
							if(isset($p->order_product_id))
								$withdrawal_products[$p->order_product_id] = $p->quantity;
							elseif(isset($p->product_id))
								$withdrawal_products[$p->product_id] = $p->quantity;
						}
					}
					$fieldsClass = hikashop_get('class.field');
					$itemFields = $fieldsClass->getFields('backend', $this->element->order->products, 'item');
				?>
				<table class="adminlist table table-striped table-bordered">
					<thead>
						<tr>
							<th width="20"><input type="checkbox" name="toggle" value="" onclick="var c=this.checked;var e=document.querySelectorAll('.withdrawal_product_checkbox');for(var i=0;i<e.length;i++){e[i].checked=c;e[i].onclick();}" /></th>
							<th><?php echo JText::_('PRODUCT'); ?></th>
							<th><?php echo JText::_('HIKA_PURCHASED_QUANTITY'); ?></th>
							<th><?php echo JText::_('HIKA_WITHDRAWAL_PRODUCTS'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach($this->element->order->products as $product) {
							$checked = isset($withdrawal_products[$product->order_product_id]) ? 'checked="checked"' : '';
							$qty = isset($withdrawal_products[$product->order_product_id]) ? $withdrawal_products[$product->order_product_id] : $product->order_product_quantity;
							$prefix = '';
							if(!empty($product->order_product_option_parent_id))
								$prefix = '&nbsp;&nbsp;&nbsp; └ ';
							?>
							<tr>
								<td class="hk_center"><input type="checkbox" name="data[selected_products][<?php echo $product->order_product_id; ?>]" value="1" <?php echo $checked; ?> class="withdrawal_product_checkbox" onclick="document.getElementById('withdrawal_product_<?php echo $product->order_product_id; ?>').disabled = !this.checked;" /></td>
								<td>
									<?php 
										echo $prefix . $product->order_product_name;
										if(!empty($product->order_product_code))
											echo ' (' . $product->order_product_code . ')'; 

										if(!empty($itemFields)) {
											foreach($itemFields as $field) {
												$namekey = $field->field_namekey;
												if(!isset($product->$namekey) || (empty($product->$namekey) && !strlen((string)$product->$namekey))) continue;
												echo '<br/>' . $prefix . $fieldsClass->getFieldName($field).': '.$fieldsClass->show($field, $product->$namekey, 'backend');
											}
										}
									?>
								</td>
								<td class="hk_center"><?php echo $product->order_product_quantity; ?></td>
								<td class="hk_center"><input type="number" max="<?php echo $product->order_product_quantity; ?>" name="data[products][<?php echo $product->order_product_id; ?>]" id="withdrawal_product_<?php echo $product->order_product_id; ?>" value="<?php echo $qty; ?>" class="input-mini" style="text-align:center" <?php echo empty($checked) ? 'disabled="disabled"' : ''; ?> /></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<?php
				}
				?>
			</div></div>
		</div>
	</div>
    <input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
    <input type="hidden" name="ctrl" value="withdrawal" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="withdrawal_id" value="<?php echo $this->element->withdrawal_id; ?>" />
    <input type="hidden" name="data[withdrawal][withdrawal_id]" value="<?php echo $this->element->withdrawal_id; ?>" />
    <?php echo JHtml::_('form.token'); ?>
</form>
