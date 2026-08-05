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
?><div class="iframedoc" id="iframedoc"></div>
<form action="<?php echo hikashop_completeLink('zone'); ?>" method="post"  name="adminForm" id="adminForm">
	<div class="hk-row-fluid">
		<div class="hkc-md-12 hika_j4_search">
<?php
	echo $this->loadHkLayout('search', array());
?>
		</div>
		<div id="hikashop_listing_filters_id" class="hkc-md-12 hikashop_listing_filters <?php echo $this->openfeatures_class; ?>">
<?php
	echo $this->filters->country;
	echo $this->filters->type;
?>
		</div>
	</div>
<?php 
	echo $this->loadHkLayout('columns', array()); 
?>
	<table id="hikashop_zone_listing" class="adminlist table table-striped table-hover" cellpadding="1">
		<thead>
			<tr>
				<th class="title titlenum">
					<?php echo JText::_( 'HIKA_NUM' );?>
				</th>
				<th class="title titlebox">
					<input type="checkbox" name="toggle" value="" onclick="hikashop.checkAll(this);" />
				</th>
				<th class="title">
					<?php echo JHTML::_('grid.sort', JText::_('ZONE_NAME_ENGLISH'), 'a.zone_name_english', $this->pageInfo->filter->order->dir,$this->pageInfo->filter->order->value ); ?>
				</th>
				<th class="title">
					<?php echo JHTML::_('grid.sort', JText::_('HIKA_NAME'), 'a.zone_name', $this->pageInfo->filter->order->dir,$this->pageInfo->filter->order->value ); ?>
				</th>
				<th class="title">
					<?php echo JHTML::_('grid.sort', JText::_('ZONE_CODE_2'), 'a.zone_code_2', $this->pageInfo->filter->order->dir,$this->pageInfo->filter->order->value ); ?>
				</th>
				<th class="title">
					<?php echo JHTML::_('grid.sort', JText::_('ZONE_CODE_3'), 'a.zone_code_3', $this->pageInfo->filter->order->dir,$this->pageInfo->filter->order->value ); ?>
				</th>
				<th class="title">
					<?php echo JHTML::_('grid.sort', JText::_('ZONE_TYPE'), 'a.zone_type', $this->pageInfo->filter->order->dir,$this->pageInfo->filter->order->value ); ?>
				</th>
				<th class="title titletoggle">
					<?php echo JHTML::_('grid.sort',   JText::_('HIKA_PUBLISHED'), 'a.zone_published', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value ); ?>
				</th>
				<th class="title titleorder"><?php
					if ($this->order->ordering) {
						$keys = array_keys($this->rows);
						$rows_nb = end($keys);
						$href = "javascript:saveorder(".$rows_nb.", 'saveorder')";
						?><a href="<?php echo $href; ?>" rel="tooltip" class="saveorder btn btn-sm btn-secondary float-end" title="Save Order">
							<button class="button-apply btn btn-success" type="button">
								<i class="fas fa-save"></i>
							</button>
						</a><?php
					}
					echo JHTML::_('grid.sort', JText::_('HIKA_ORDER'), 'a.zone_ordering', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value);
				?></th>
				<th class="title">
					<?php echo JHTML::_('grid.sort',   JText::_( 'ID' ), 'a.zone_id', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value ); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="10">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php
				$k = 0;
				for($i = 0,$a = count($this->rows);$i<$a;$i++){
					$row =& $this->rows[$i];
					$publishedid = 'zone_published-'.$row->zone_id;
			?>
				<tr class="<?php echo "row$k"; ?>">
					<td class="hk_center">
					<?php echo $this->pagination->getRowOffset($i); ?>
					</td>
					<td class="hk_center">
						<?php echo JHTML::_('grid.id', $i, $row->zone_id ); ?>
					</td>
					<td>
						<?php if($this->manage){ ?>
							<a href="<?php echo hikashop_completeLink('zone&task=edit&cid[]='.$row->zone_id); ?>">
						<?php } ?>
								<?php echo $row->zone_name_english; ?>
						<?php if($this->manage){ ?>
							</a>
						<?php } ?>
					</td>
					<td>
						<?php if($this->manage){ ?>
							<a href="<?php echo hikashop_completeLink('zone&task=edit&cid[]='.$row->zone_id); ?>">
						<?php } ?>
								<?php echo $row->zone_name; ?>
						<?php if($this->manage){ ?>
							</a>
						<?php } ?>
					</td>
					<td>
						<?php echo $row->zone_code_2; ?>
					</td>
					<td>
						<?php echo $row->zone_code_3; ?>
					</td>
					<td>
						<?php echo $row->zone_type; ?>
					</td>
					<td class="hk_center">
						<?php if($this->manage){ ?>
							<span id="<?php echo $publishedid ?>" class="spanloading"><?php echo $this->toggleClass->toggle($publishedid,(int) $row->zone_published,'zone') ?></span>
						<?php }else{ echo $this->toggleClass->display('activate',$row->zone_published); } ?>
					</td>
					<td class="order column_move">
<?php if($this->manage){ ?>
						<span class="move_icon"><img src="../media/com_hikashop/images/move.png" alt=""></span>
						<input type="text" name="order[]" size="5" <?php if(!$this->order->ordering) echo 'disabled="disabled"'?> value="<?php echo (int)@$row->zone_ordering; ?>" class="text_area" style="text-align: center" />
<?php }else{ echo (int)@$row->zone_ordering; } ?>
					</td>
					<td width="1%" class="hk_center">
						<?php echo $row->zone_id; ?>
					</td>
				</tr>
			<?php
					$k = 1-$k;
				}
			?>
		</tbody>
	</table>
	<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="ctrl" value="<?php echo hikaInput::get()->getCmd('ctrl'); ?>" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->pageInfo->filter->order->value; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->pageInfo->filter->order->dir; ?>" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
