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
hikashop_loadJsLib('sortable');
$doc = JFactory::getDocument();
$doc->addStyleSheet(HIKASHOP_CSS . 'address_layout_builder.css?v='.HIKASHOP_RESSOURCE_VERSION);
$doc->addScript(HIKASHOP_JS . 'address_layout_builder.js?v='.HIKASHOP_RESSOURCE_VERSION);
?>
<div id="hikashop_address_layout_popup">
    <!-- Top toolbar -->
    <div class="address_layout_toolbar">
        <div class="address_layout_toolbar_left">
            <div class="address_layout_control_group">
                <label for="address_layout_global_label_position"><?php echo JText::_('LABEL_POSITION'); ?>:</label>
                <select id="address_layout_global_label_position" class="form-select"
                        onchange="hikashopAddressLayoutBuilder.setGlobalLabelPosition(this.value);">
                    <option value="above" <?php echo (@$this->layoutConfig['label_position'] == 'above' ? 'selected' : ''); ?>><?php echo JText::_('HIKASHOP_ABOVE'); ?></option>
                    <option value="yes" <?php echo (@$this->layoutConfig['label_position'] == 'yes' ? 'selected' : ''); ?>><?php echo JText::_('HIKASHOP_NEXT_TO'); ?></option>
                    <option value="no" <?php echo (@$this->layoutConfig['label_position'] == 'no' ? 'selected' : ''); ?>><?php echo JText::_('HIKASHOP_HIDDEN'); ?></option>
                </select>
            </div>
        </div>
        <div class="address_layout_toolbar_right">
            <button type="button" class="btn" onclick="hikashopAddressLayoutBuilder.reset();">
                <i class="fas fa-undo"></i> <?php echo JText::_('HIKASHOP_RESET'); ?>
            </button>
            <button type="button" class="btn" onclick="if(window.parent && window.parent.hikashop) { window.parent.hikashop.closeBox(); }">
                <i class="fas fa-times"></i> <?php echo JText::_('HIKA_CANCEL'); ?>
            </button>
            <button type="button" class="btn btn-primary" onclick="hikashopAddressLayoutBuilder.save();">
                <i class="fas fa-save"></i> <?php echo JText::_('HIKA_SAVE'); ?>
            </button>
        </div>
    </div>

    <!-- Live preview builder area -->
    <div class="address_layout_builder_wrapper">
        <!-- Column guides -->
        <div class="address_layout_column_guides">
            <div class="address_layout_column_guide"></div>
            <div class="address_layout_column_guide"></div>
            <div class="address_layout_column_guide"></div>
            <div class="address_layout_column_guide"></div>
        </div>

        <!-- Rows container - fields will be rendered here -->
        <div id="address_layout_rows" class="address_layout_rows">
<?php
$renderedFields = array();
if(!empty($this->layoutConfig['rows'])) {
    foreach($this->layoutConfig['rows'] as $rowIndex => $row) {
        echo '<div class="address_layout_row" data-row="'.$rowIndex.'">';
        foreach($row['fields'] as $fieldConfig) {
            $namekey = $fieldConfig['namekey'];
            if(!isset($this->fields[$namekey])) continue;
            $field = $this->fields[$namekey];
            $renderedFields[$namekey] = true;

            $this->blockField = $field;
            $this->blockWidth = (int)$fieldConfig['width'];
            $this->blockLabelPosition = isset($fieldConfig['label_position']) ? $fieldConfig['label_position'] : null;
            $this->blockUnassigned = false;

            echo $this->loadTemplate('field');
        }
        echo '</div>';
    }
}
foreach($this->fields as $field) {
    if(isset($renderedFields[$field->field_namekey])) continue;
    if($field->field_type == 'hidden') continue;

    echo '<div class="address_layout_row" data-row="new">';

    $this->blockField = $field;
    $this->blockWidth = 4;
    $this->blockLabelPosition = null;
    $this->blockUnassigned = false;

    echo $this->loadTemplate('field');
    echo '</div>';
}
?>
            <!-- Drop zone for new row -->
            <div class="address_layout_drop_zone" data-row="new">
                <i class="fas fa-plus"></i> <?php echo JText::_('DROP_HERE_FOR_NEW_ROW'); ?>
            </div>
        </div>

        <!-- Unassigned fields container (hidden but used for logic if needed, or removed as requested) -->
        <div id="address_layout_unassigned_fields" style="display:none;"></div>
    </div>
</div>

<script>
window.hikashop.ready(function() {
    hikashopAddressLayoutBuilder.init({
        containerId: 'address_layout_rows',
        unassignedId: 'address_layout_unassigned_fields',
        config: <?php echo $this->layoutConfigJson ?: 'null'; ?>,
        saveUrl: '<?php echo hikashop_completeLink('config&task=saveaddresslayout&'.hikashop_getFormToken().'=1', true, false, true); ?>'
    });
});
</script>
