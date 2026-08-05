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
<?php if(!empty($this->filters)) { ?>
	<div style="float:right"><?php echo implode(' ', $this->filters); ?></div>
	<div style="clear:right"></div>
<?php } ?>
<form action="<?php echo hikashop_completeLink('plugins&plugin_type='.$this->plugin_type); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">

<div class="hk-row">
	<div class="hkc-md-12 hika_j4_search">
		<?php echo $this->loadHkLayout('search', array()); ?>
	</div>
	<div id="hikashop_listing_filters_id" class="hkc-md-12 hikashop_listing_filters <?php echo $this->openfeatures_class; ?>"><?php
		if(!empty($this->extrafilters)) {
			foreach($this->extrafilters as $name => $filterObj) {
				echo $filterObj->displayFilter($name, $this->pageInfo->filter);
			}
		}
		echo JHTML::_('select.genericlist', $this->pluginValues, 'filter_plugin', 'class="custom-select" onchange="this.form.submit();"', 'value', 'text', $this->pageInfo->filter->plugin);
		echo $this->pulbishedType->display('filter_published',$this->pageInfo->filter->published);
	?></div>
</div>
<?php 
	$cols = 7; 
	echo $this->loadHkLayout('columns', array()); 
?>
<table id="hikashop_plugins_listing" class="adminlist table table-striped" cellpadding="1">
	<thead>
		<tr>
			<th class="title titlenum"><?php echo JText::_('HIKA_NUM');?></th>
			<th class="title titlebox">
				<input type="checkbox" name="toggle" value="" onclick="hikashop.checkAll(this);" />
			</th>
			<th class="title"><?php echo JText::_('HIKA_NAME');?></th>
			<th class="title default" data-alias="alias"><?php echo JText::_('HIKA_ALIAS');?></th>
<?php
	if(!empty($this->listing_columns)) {
		foreach($this->listing_columns as $key => $column) {
			$cols++;
?>			<th class="title"><?php echo JText::_($column['name']);?></th>
<?php
		}
	}
?>
			<th class="title"><?php echo JText::_('HIKA_TYPE');?></th>
			<th class="title titleorder" style="width:10%;"><?php
				if ($this->ordering->ordering) {
					$keys = array_keys($this->rows);  
					$rows_nb = end($keys);
					$href = "javascript:saveorder(".$rows_nb.", 'saveorder')";
					?><a href="<?php echo $href; ?>" rel="tooltip" class="saveorder btn btn-sm btn-secondary float-end" title="Save Order">
						<button class="button-apply btn btn-success" type="button">
<!--						<span class="icon-apply" aria-hidden="true"></span> -->
							<i class="fas fa-save"></i>
						</button>
					</a><?php
				}
				echo JText::_('HIKA_ORDER');
			?></th>
			<th class="title" style="width:2%;"><?php echo JText::_('HIKA_DELETE');?></th>
			<th class="title" style="width:2%;"><?php echo JText::_('HIKA_PUBLISHED');?></th>
		</tr>
	</thead>
	<tbody>
<?php
$p_id = $this->plugin_type.'_id';
$p_name = $this->plugin_type.'_name';
$p_alias = $this->plugin_type.'_alias';
$p_order = $this->plugin_type.'_ordering';
$p_published = $this->plugin_type.'_published';
$p_type = $this->plugin_type.'_type';

if(empty($this->rows))
	$this->rows = array();

$k = 0;
$i = 0;
$count = count($this->rows);
if(!empty($this->rows)) {
	foreach($this->rows as $plugin_id => $plugin){
		$currentPlugin = null;
		if(isset($this->plugins[ $plugin->$p_type ])) {
			$currentPlugin = $this->plugins[ $plugin->$p_type ];
		}

		if(!empty($plugin->_single_instance)) {
?>
		<tr class="row<?php echo $k;?>" id="single_<?php echo $plugin->$p_type;?>">
			<td class="hk_center"><?php echo $i+1; ?></td>
			<td class="hk_center"><!-- single-instance --></td>
			<td>
				<a href="<?php echo hikashop_completeLink('plugins&task=edit&name=' . $plugin->$p_type . '&plugin_type=' . $this->plugin_type . '&subtask=edit'); ?>"><?php
					echo $plugin->$p_name;
				?></a>
			</td>
			<td><?php echo $plugin->$p_alias; ?></td>
<?php
		if(!empty($this->listing_columns)) {
			foreach($this->listing_columns as $key => $column) {
?>			<td><?php if(isset($column['col'])) { $col = $column['col']; echo @$plugin->$col; } ?></td>
<?php
			}
		}
?>
			<td><?php
				if(!empty($currentPlugin))
					echo JText::_($currentPlugin->name);
				else
					echo $plugin->$p_type;
			?></td>
			<td class="order"><!-- no ordering for single-instance --></td>
			<td class="hk_center"><!-- no delete for single-instance --></td>
			<td class="hk_center">
				<span id="enabled-<?php echo $plugin->_extension_id; ?>" class="spanloading"><?php
					echo $this->toggleClass->toggle('enabled-' . $plugin->_extension_id, (int)$plugin->$p_published, 'plugins');
				?></span>
			</td>
		</tr>
<?php
		} else {
			$published_id = $this->plugin_type.'_published-' . $plugin->$p_id;
			$id = $this->plugin_type.'_' . $plugin->$p_id;

			if(!empty($currentPlugin)) {
				$plugin->$p_published = $plugin->$p_published && $currentPlugin->published;
			}
?>
		<tr class="row<?php echo $k;?>" id="<?php echo $id;?>">
			<td class="hk_center"><?php
				echo $i+1;
			?></td>
			<td class="hk_center"><?php
				echo JHTML::_('grid.id', $i, $plugin->$p_id );
			?></td>
			<td>
				<a href="<?php echo hikashop_completeLink('plugins&plugin_type='.$this->plugin_type.'&task=edit&name='. $plugin->$p_type .'&subtask='.$this->plugin_type.'_edit&'.$p_id.'='.$plugin->$p_id);?>"><?php
					echo $plugin->$p_name;
					if(empty($plugin->$p_name))
						echo '<em>' . JText::_('NO_NAME') . '</em>';
				?></a>
			</td>
			<td>
				<a href="<?php echo hikashop_completeLink('plugins&plugin_type='.$this->plugin_type.'&task=edit&name='. $plugin->$p_type .'&subtask='.$this->plugin_type.'_edit&'.$p_id.'='.$plugin->$p_id);?>"><?php
					echo $plugin->$p_alias;
				?></a>
			</td>
<?php
		if(!empty($this->listing_columns)) {
			foreach($this->listing_columns as $key => $column) {
				$cols++;
?>			<td><?php
				if(isset($column['col'])) {
					$col = $column['col'];
					echo @$plugin->$col;
				}
			?></td>
<?php
		}
	}
?>
			<td><?php
				if(!empty($currentPlugin))
					echo JText::_($currentPlugin->name);
				else
					echo $plugin->$p_type;
			?></td>
			<td class="order column_move">
<?php if($this->ordering->ordering) { ?>
	<span class="move_icon"><img src="../media/com_hikashop/images/move.png" alt=""></span>
<?php } ?>
				<input type="text" name="order[]" size="5" <?php if(!$this->ordering->ordering) echo 'disabled="disabled"'; ?> value="<?php echo $plugin->$p_order; ?>" class="text_area" style="text-align: center" />
			</td>
			<td class="hk_center">
				<span class=""><?php
					echo $this->toggleClass->delete($id, $plugin->$p_type.'-'.$plugin->$p_id, $this->plugin_type, true);
				?></span>
			</td>
			<td class="hk_center">
				<span id="<?php echo $published_id;?>" class="spanloading"><?php echo $this->toggleClass->toggle($published_id, (int)$plugin->$p_published, $this->plugin_type);?></span>
			</td>
		</tr>
<?php
		} // end if single_instance else
		$k = 1-$k;
		$i++;
	}
} else if(in_array($this->plugin_type, array('payment','shipping'))) {
?>
		<tr>
			<td class="empty_list" colspan="<?php echo $cols; ?>"><?php
				echo JText::_('NO_METHOD_CLICK_NEW');
			?></td>
		</tr>
<?php
}
?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="<?php echo $cols; ?>">
				<?php echo $this->pagination->getListFooter(); ?>
			</td>
		</tr>
	</tfoot>
</table>
<?php
$marketplace_cookie = 'hikashop_marketplace_' . $this->plugin_type;
$marketplace_dismissed = !empty($_COOKIE[$marketplace_cookie]);
if(!$marketplace_dismissed) {
	switch($this->plugin_type) {
		case 'payment':
			$mp_icon = 'fa-credit-card';
			$mp_headline = 'MARKETPLACE_PAYMENT_HEADLINE';
			$mp_desc = 'MARKETPLACE_PAYMENT_DESC';
			$mp_cta = 'MARKETPLACE_PAYMENT_CTA';
			$mp_url = 'https://www.hikashop.com/marketplace/category/33-payment.html';
			break;
		case 'shipping':
			$mp_icon = 'fa-truck';
			$mp_headline = 'MARKETPLACE_SHIPPING_HEADLINE';
			$mp_desc = 'MARKETPLACE_SHIPPING_DESC';
			$mp_cta = 'MARKETPLACE_SHIPPING_CTA';
			$mp_url = 'https://www.hikashop.com/marketplace/category/34-shipping.html';
			break;
		default:
			$mp_icon = 'fa-puzzle-piece';
			$mp_headline = 'MARKETPLACE_PLUGINS_HEADLINE';
			$mp_desc = 'MARKETPLACE_PLUGINS_DESC';
			$mp_cta = 'MARKETPLACE_PLUGINS_CTA';
			$mp_url = 'https://www.hikashop.com/marketplace.html';
			break;
	}
?>
<div id="hikashop_marketplace_card" class="hikashop_marketplace_card">
	<button type="button" class="hikashop_marketplace_card_close" onclick="this.parentNode.style.display='none';document.cookie='<?php echo $marketplace_cookie; ?>=1;path=/;max-age=31536000';" title="<?php echo JText::_('HIKA_CLOSE'); ?>">&times;</button>
	<div class="hikashop_marketplace_card_icon">
		<i class="fas <?php echo $mp_icon; ?>"></i>
	</div>
	<div>
		<div class="hikashop_marketplace_card_headline"><?php echo JText::_($mp_headline); ?></div>
		<div class="hikashop_marketplace_card_desc"><?php echo JText::_($mp_desc); ?></div>
		<div class="hikashop_marketplace_card_actions">
			<a href="<?php echo $mp_url; ?>" target="_blank" class="btn btn-primary btn-sm"><?php echo JText::_($mp_cta); ?></a>
			<a href="https://www.hikashop.com/support/contact-us.html" target="_blank" class="btn btn-outline-secondary btn-sm"><?php echo JText::_('MARKETPLACE_REQUEST_PLUGIN'); ?></a>
		</div>
	</div>
</div>
<?php } ?>
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT;?>" />
<input type="hidden" name="task" value="listing"/>
<?php echo JHTML::_( 'form.token' ); ?>
</form>
