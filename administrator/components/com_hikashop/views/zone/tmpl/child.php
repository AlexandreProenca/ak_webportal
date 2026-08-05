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
	$childNamekey = !empty($this->row->zone_namekey) ? $this->row->zone_namekey : @$this->row->zone_child_namekey;
	$orphan = empty($this->row->zone_id);
?>
<tr id="zone_namekey-<?php echo $childNamekey; ?>" class="row<?php echo $this->k; ?>">
	<td class="hk_center">
		<?php echo $orphan ? '<em>'.htmlspecialchars((string)$childNamekey).'</em>' : @$this->row->zone_name_english; ?>
	</td>
	<td class="hk_center">
		<?php echo @$this->row->zone_name; ?>
	</td>
	<td class="hk_center">
		<?php echo @$this->row->zone_code_2; ?>
	</td>
	<td class="hk_center">
		<?php echo @$this->row->zone_code_3; ?>
	</td>
	<td class="hk_center">
		<?php echo @$this->row->zone_type; ?>
	</td>
	<td>
		<?php if(!$orphan){ ?>
		<a href="<?php echo hikashop_completeLink('zone&task=edit&cid[]='.@$this->row->zone_id); ?>" target="_blank" title="<?php echo JText::_('HIKA_EDIT'); ?>">
			<i class="fa fa-pen fa-pencil"></i>
		</a>
		<?php } ?>
	</td>
	<td class="hk_center">
		<span class="spanloading">
			<?php echo $this->toggleClass->delete("zone_namekey-".$childNamekey,$this->main_namekey.'-'.$childNamekey,'zone',true) ?>
		</span>
	</td>
	<td class="hk_center">
		<?php echo @$this->row->zone_id; ?>
	</td>
</tr>
