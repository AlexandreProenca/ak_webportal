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
?><form action="<?php echo JRoute::_('index.php?option=com_hikashop'); ?>" method="post" name="adminForm" id="adminForm">
<div class="hikashop_dashboard_config" id="hikashop_dashboard_config">
	<div class="hk-row-fluid">
		<!-- Left Sidebar: Available Widgets -->
		<div class="hkc-md-3 hkc-sm-12">
			<div class="hikashop_dashboard_sidebar">
				<div class="hk-form-group">
					<label><?php echo JText::_('HIKA_DASHBOARD_PRESET'); ?></label>
					<?php echo $this->presets; ?>
				</div>
				<hr/>
				<h4><?php echo JText::_('HIKA_DASHBOARD_WIDGETS'); ?></h4>
				<div id="available-widgets" class="connectedSortable">
					<?php foreach($this->available_widgets as $key => $widget): ?>
						<div class="dashboard-widget-item" data-id="<?php echo $key; ?>" data-width="<?php echo isset($widget['class']) ? $widget['class'] : 'hkc-sm-12'; ?>">
							<div class="widget-header">
								<span class="widget-title"><?php echo $widget['label']; ?></span>
								<div class="widget-actions">
									<i class="fas fa-arrows-alt handle" title="<?php echo JText::_('HIKA_MOVE'); ?>"></i>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<!-- Right Content: Dashboard Layout -->
		<div class="hkc-md-9 hkc-sm-12">
			<div class="hikashop_dashboard_actions mb-3" style="display:flex; align-items:center;">
				<button type="button" id="hikashop_dashboard_reset_btn" class="btn btn-danger" onclick="hikashop_dashboard.reset();" style="display:none;">
					<i class="fas fa-undo"></i> <?php echo JText::_('HIKA_DASHBOARD_RESET'); ?>
				</button>
				<button type="button" class="btn btn-primary" onclick="hikashop_dashboard.addRow();" style="margin-left: auto;">
					<i class="fas fa-plus"></i> <?php echo JText::_('HIKA_DASHBOARD_ADD_ROW'); ?>
				</button>
			</div>

			<div id="dashboard-layout">
				<!-- Rows will be generated here by JS -->
			</div>
		</div>
	</div>
</div>
	<input type="hidden" name="task" value="" />
    <input type="hidden" name="layout" id="hikashop_dashboard_layout_input" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>

<div id="widget-settings-modal" style="display:none;"></div>

<script>
	if(typeof window.hikashop_dashboard_data === 'undefined') {
		window.hikashop_dashboard_data = {};
	}
	window.hikashop_dashboard_data.layout = <?php echo json_encode($this->current_layout); ?>;
	window.hikashop_dashboard_data.settingsUrl = '<?php echo hikashop_completeLink('dashboard&task=settings&tmpl=component', false, false, true); ?>';
	window.hikashop_dashboard_data.lang = {
		move: '<?php echo JText::_("HIKA_MOVE"); ?>',
		delete: '<?php echo JText::_("HIKA_DELETE"); ?>',
		settings: '<?php echo JText::_("HIKA_SETTINGS"); ?>',
		resize: '<?php echo JText::_("HIKA_RESIZE"); ?>',
		move_up: '<?php echo JText::_("HIKA_MOVE_UP"); ?>',
		move_down: '<?php echo JText::_("HIKA_MOVE_DOWN"); ?>',
		move_prev: '<?php echo JText::_("HIKA_MOVE_PREV_POSITION"); ?>',
		move_next: '<?php echo JText::_("HIKA_MOVE_NEXT_POSITION"); ?>'
	};

	<?php
	?>
</script>

