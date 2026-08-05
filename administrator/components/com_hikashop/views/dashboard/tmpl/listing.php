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
<?php
if(!empty($this->statistics)) {
?>
<div class="hk-container-fluid">
<?php
	foreach($this->statistics_slots as $slot) {
		$slot_stats = array();
		foreach($this->statistics as $key => $stat) {
			if(isset($stat['published']) && !$stat['published']) continue;
			if($stat['slot'] == $slot) {
				$slot_stats[] = array('key' => $key, 'stat' => $stat, 'class' => isset($stat['class']) ? $stat['class'] : 'hkc-sm-12');
			}
		}

		if(empty($slot_stats)) continue;

		$rows = array();
		$current_idx = 0;
		$total_stats = count($slot_stats);

		while($current_idx < $total_stats) {
			$visual_row = array();
			$row_width = 0;
			$check_idx = $current_idx;

			while($check_idx < $total_stats) {
				$item = $slot_stats[$check_idx];
				$width = 12;
				if(preg_match('/hkc-lg-(\d+)/', $item['class'], $matches)) {
					$width = (int)$matches[1];
				}

				if($row_width + $width > 12) break; // Row full

				$item['width'] = $width;
				$visual_row[] = $item;
				$row_width += $width;
				$check_idx++;
			}

			if(empty($visual_row)) {
				$current_idx++; 
				continue; // Should not happen
			}

			$dominant_idx = -1;
			foreach($visual_row as $i => $v_item) {
				if($v_item['width'] >= 8) {
					$dominant_idx = $i;
					break;
				}
			}

			$final_row_items = array();

			if($dominant_idx !== -1) {
				$dom_item = $visual_row[$dominant_idx];
				$sidecar_width = 12 - $dom_item['width'];

				$sidecar_items = array();
				foreach($visual_row as $i => $v_item) {
					if($i !== $dominant_idx) {
						$sidecar_items[] = $v_item;
					}
				}

				$next_idx = $check_idx; // Start after visual row
				while($next_idx < $total_stats) {
					$next_item = $slot_stats[$next_idx];
					$next_w = 12;
					if(preg_match('/hkc-lg-(\d+)/', $next_item['class'], $matches)) {
						$next_w = (int)$matches[1];
					}

					if($sidecar_width > 0 && $next_w <= $sidecar_width) {
						$next_item['width'] = $next_w;
						$sidecar_items[] = $next_item;
						$next_idx++;
					} else {
						break; // Stop at first item that doesn't fit sidecar
					}
				}

				$current_idx = $next_idx;

				$wrapper = null;
				if(!empty($sidecar_items)) {
					$wrapper = array(
						'type' => 'wrapper',
						'width' => $sidecar_width,
						'class' => 'hkc-lg-' . $sidecar_width, // e.g. hkc-lg-3
						'items' => $sidecar_items
					);
				}

				if($dominant_idx == 0) {
					$final_row_items[] = $dom_item;
					if($wrapper) $final_row_items[] = $wrapper;
				} else {
					if($wrapper) $final_row_items[] = $wrapper;
					$final_row_items[] = $dom_item;
				}

			} else {
				$wrapper = array(
					'type' => 'wrapper',
					'width' => 12,
					'class' => 'hkc-lg-12',
					'items' => $visual_row
				);
				$final_row_items[] = $wrapper;
				$current_idx = $check_idx;
			}

			$rows[] = $final_row_items;
		}
?>
	<div class="hk-row">
<?php
		foreach($rows as $row_items) {
			foreach($row_items as $item) {
				if(isset($item['type']) && $item['type'] == 'wrapper') {
?>
		<div class="<?php echo $item['class']; ?> hk-dashboard-wrapper">
			<div class="hk-row">
<?php
					foreach($item['items'] as $sub_item) {
						$wrapper_w = $item['width'];
						$old_w = $sub_item['width'];
						$new_w = ($wrapper_w > 0) ? ($old_w / $wrapper_w * 12) : 12;

						$new_class = preg_replace('/hkc-lg-\d+/', 'hkc-lg-'.(int)$new_w, $sub_item['class']);
						if(strpos($new_class, 'hkc-lg-') === false) $new_class .= ' hkc-lg-'.(int)$new_w;
?>
				<div id="hikashop_dashboard_stat_<?php echo $sub_item['key']; ?>" class="<?php echo $new_class; ?>"><?php
					echo $this->statisticsClass->display($sub_item['stat']);
				?></div>
<?php
					}
?>
			</div>
		</div>
<?php
				} else {
?>
		<div id="hikashop_dashboard_stat_<?php echo $item['key']; ?>" class="<?php echo $item['class']; ?>"><?php
					echo $this->statisticsClass->display($item['stat']);
		?></div>
<?php
				}
			}
		}
?>
	</div>
	<?php
	}
	?>
</div>
<?php
}

if(!empty($this->widgets)) {
	$itemOnARow = 0;
	if(!HIKASHOP_BACK_RESPONSIVE) {
?>
<table class="adminform" style="width:100%;border-collapse:separate;border-spacing:5px">
<?php
	} else {
		echo '<div class="cpanel-widgets">';
	}

	foreach($this->widgets as $widget) {
		if(empty($widget->widget_params->display))
			continue;

		if(!hikashop_level(2)){
			if(isset($widget->widget_params->content) && in_array($widget->widget_params->content, array('partners', 'map')))
				continue;
			if(!hikashop_level(1) && in_array($widget->widget_params->display,array('gauge','pie')))
				continue;
		}
		if($itemOnARow == 0) {
			echo (!HIKASHOP_BACK_RESPONSIVE) ? '<tr>' :'<div class="row-fluid">';
		}
		$val = preg_replace('#[^a-z0-9]#i', '_', strtoupper($widget->widget_name));
		$trans = JText::_($val);
		if($val != $trans) {
			$widget->widget_name = $trans;
		}
		if(hikashop_level(1) && $this->manage) {
			$widget->widget_name.= '
			<a href="'.hikashop_completeLink('report&task=edit&cid[]='.$widget->widget_id.'&dashboard=true').'">
				<img src="'.HIKASHOP_IMAGES.'edit.png" alt="edit"/>
			</a>';
		}
		if(!HIKASHOP_BACK_RESPONSIVE) {
			echo '<td valign="top" style="border: 1px solid #CCCCCC"><fieldset style="border:0px" class="adminform"><legend>'.$widget->widget_name.'</legend>';
		} else {
			echo '<div class="span4" style="border: 1px solid #CCCCCC;min-height:280px"><fieldset style="border:0px" class="adminform"><legend>'.$widget->widget_name.'</legend>';
		}

		$this->widget =& $widget;
		if($widget->widget_params->display == 'listing') {
			if(empty($widget->widget_params->content_view))
				continue;
			$this->setLayout($widget->widget_params->content_view);
		} elseif(in_array($widget->widget_params->display, array('column', 'line', 'area'))) {
			$this->setLayout('chart');
		} else {
			$this->setLayout($widget->widget_params->display);
		}

		echo $this->loadTemplate();
		echo (!HIKASHOP_BACK_RESPONSIVE) ? '</fieldset></td>' : '</fieldset></div>';

		$itemOnARow++;

		if($itemOnARow == 3) {
			echo (!HIKASHOP_BACK_RESPONSIVE) ? '</tr>' : '</div>';
			$itemOnARow = 0;
		}
	}
	if(!HIKASHOP_BACK_RESPONSIVE) {
?>
</table>
<?php
	} else {
		echo '</div>';
	}
}

$this->setLayout('cpanel');
echo $this->loadTemplate();
