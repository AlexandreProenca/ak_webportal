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
$config = hikashop_config();
?>
<form action="<?php echo hikashop_completeLink('withdrawal'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="hk-row-fluid">
		<div class="hkc-md-10 hika_j4_search">
			<?php echo $this->loadHkLayout('search', array()); ?>
		</div>
		<div id="hikashop_listing_filters_id" class="hkc-md-2 hikashop_listing_filters">
            <?php echo hikashop_get('type.withdrawal_status')->displayFilter('status', $this->pageInfo->filter->status); ?>
		</div>
	</div>
<?php 
	echo $this->loadHkLayout('columns', array()); 
?>
    <table class="adminlist table table-striped" id="withdrawal_grid">
        <thead>
            <tr>
                <th width="1%" class="title"><input type="checkbox" name="toggle" value="" onclick="checkAll(this);" /></th>
                <th class="title"><?php echo JHtml::_('grid.sort', 'HIKA_WITHDRAWAL_ID', 'a.withdrawal_id', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value); ?></th>
                <th class="title"><?php echo JHtml::_('grid.sort', 'HIKA_WITHDRAWAL_ORDER', 'o.order_number', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value); ?></th>
                <th class="title"><?php echo JHtml::_('grid.sort', 'HIKA_USER', 'u.user_email', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value); ?></th>
                <th class="title"><?php echo JHtml::_('grid.sort', 'HIKA_WITHDRAWAL_USER_CHECKED', 'a.withdrawal_user_check', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value); ?></th>
                <th class="title"><?php echo JHtml::_('grid.sort', 'HIKA_WITHDRAWAL_STATUS', 'a.withdrawal_status', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value); ?></th>
                <th class="title"><?php echo JHtml::_('grid.sort', 'HIKA_WITHDRAWAL_CREATED', 'a.withdrawal_created', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach($this->rows as $i => $row) {
            $link = hikashop_completeLink('withdrawal&task=edit&withdrawal_id=' . $row->withdrawal_id);
			$color = $config->get('withdrawal_color_' . $row->withdrawal_status);
			if(empty($color)) {
				switch($row->withdrawal_status) {
					case 'approved':
						$color = '#e4e590ff';
						break;
					case 'rejected':
						$color = '#ecadadff';
						break;
					case 'processed':
						$color = '#bde3f6ff';
						break;
					case 'created':
						$color = '#b5d8b8ff';
						break;
				}
			}
			$style = '';
			if(!empty($color)) $style = ' style="background-color:'.$color.'"';
            ?>
            <tr class="row<?php echo $i % 2; ?>">
                <td<?php echo $style; ?>><?php echo JHtml::_('grid.id', $i, $row->withdrawal_id); ?></td>
                <td<?php echo $style; ?>><a href="<?php echo $link; ?>"><?php echo $row->withdrawal_id; ?></a></td>
                <td<?php echo $style; ?>><a href="<?php echo hikashop_completeLink('order&task=edit&order_id=' . $row->withdrawal_order_id . '&cancel_redirect='.urlencode(base64_encode(hikashop_completeLink('withdrawal')))); ?>"><?php echo $row->order_number; ?></a></td>
                <td<?php echo $style; ?>><a href="<?php echo hikashop_completeLink('user&task=edit&user_id=' . $row->user_id); ?>"><?php echo $row->user_email; ?></a></td>
                <td align="center"<?php echo $style; ?>><?php echo $row->withdrawal_user_check ? '<i class="icon-checkmark"></i>' : '<i class="icon-remove"></i>'; ?></td>
                <td<?php echo $style; ?>><?php echo JText::_('HIKA_WITHDRAWAL_STATUS_' . strtoupper($row->withdrawal_status)); ?></td>
                <td<?php echo $style; ?>><?php echo hikashop_getDate($row->withdrawal_created); ?></td>
            </tr>
            <?php
        }
        ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7">
                    <?php echo $this->pagination->getListFooter(); ?>
                </td>
            </tr>
        </tfoot>
    </table>
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="filter_order" value="<?php echo $this->pageInfo->filter->order->value; ?>" />
    <input type="hidden" name="filter_order_Dir" value="<?php echo $this->pageInfo->filter->order->dir; ?>" />
    <?php echo JHtml::_('form.token'); ?>
</form>
