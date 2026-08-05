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
?><legend><?php echo JText::_('HISTORY'); ?></legend>
<div class="hikashop_history_container">
<!-- HISTORY TOP EXTRA DATA -->
<?php if(!empty($this->order->extraData->historyTop)) { echo implode("\r\n", $this->order->extraData->historyTop); } ?>
<!-- EO HISTORY TOP EXTRA DATA -->
<table id="hikashop_order_history_listing" class="hika_listing hika_table table table-striped table-hover">
	<thead>
		<tr>
<!-- TYPE HEADER -->
			<th class="title"><?php
				echo JText::_('HIKA_TYPE');
			?></th>
<!-- EO TYPE HEADER -->
<!-- STATUS HEADER -->
			<th class="title"><?php
				echo JText::_('ORDER_STATUS');
			?></th>
<!-- EO STATUS HEADER -->
<!-- REASON HEADER -->
			<th class="title"><?php
				echo JText::_('REASON');
			?></th>
<!-- EO REASON HEADER -->
<!-- USER HEADER -->
			<th class="title"><?php
				echo JText::_('HIKA_USER').' / '.JText::_('IP');
			?></th>
<!-- EO USER HEADER -->
<!-- DATE HEADER -->
			<th class="title"><?php
				echo JText::_('DATE');
			?></th>
<!-- EO DATE HEADER -->
<!-- INFORMATION HEADER -->
			<th class="title"><?php
				echo JText::_('INFORMATION');
			?></th>
<!-- EO INFORMATION HEADER -->
		</tr>
	</thead>
	<tbody>
<?php
$userClass = hikashop_get('class.user');
foreach($this->order->history as $k => $history) {
?>
		<tr>
<!-- TYPE -->
			<td><?php
				$val = preg_replace('#[^a-z0-9]#i','_',strtoupper($history->history_type));
				$trans = JText::_($val);
				if($val != $trans)
					$history->history_type = $trans;
				echo $history->history_type;
			?></td>
<!-- EO TYPE -->
<!-- STATUS -->
			<td><?php
				echo hikashop_orderStatus($history->history_new_status);
			?></td>
<!-- EO STATUS -->
<!-- REASON -->
			<td><?php
				echo $history->history_reason;
			?></td>
<!-- EO REASON -->
<!-- USER -->
			<td><?php
				$elements = array();
				if(!empty($history->history_user_id)){
					$user = $userClass->get($history->history_user_id);
					if(!empty($user->username))
						$elements[] = $user->username;
					elseif(!empty($user->user_email))
						$elements[] = $user->user_email;
				}
				if(!empty($history->history_ip))
					$elements[] = $history->history_ip;
				echo implode(' / ', $elements);
			?></td>
<!-- EO USER -->
<!-- DATE -->
			<td><?php
				echo hikashop_getDate($history->history_created,'%Y-%m-%d %H:%M');
			?></td>
<!-- EO DATE -->
<!-- INFORMATION -->
			<td><?php
				echo $history->history_data;
			?></td>
<!-- EO INFORMATION -->
		</tr>
<?php
}
?>
	</tbody>
</table>
<!-- HISTORY BOTTOM EXTRA DATA -->
<?php if(!empty($this->order->extraData->historyBottom)) { echo implode("\r\n", $this->order->extraData->historyBottom); } ?>
<!-- EO HISTORY BOTTOM EXTRA DATA -->
</div>
<script type="text/javascript">
window.orderMgr.updateHistory = function() {
	window.hikashop.xRequest('<?php echo hikashop_completeLink('order&task=show&subtask=history&cid='.$this->order->order_id, true, false, true); ?>',{update:'hikashop_order_field_history'});
}
</script>
