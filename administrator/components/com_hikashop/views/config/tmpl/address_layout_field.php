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
$field = $this->blockField;
$width = $this->blockWidth;
$labelPosition = $this->blockLabelPosition;
$unassigned = $this->blockUnassigned;

$classes = 'address_layout_field';
if($unassigned) {
    $classes .= ' address_layout_field_unassigned';
    $width = 4; // Unassigned fields default to full width
}

if(empty($field->field_published)) {
     $classes .= ' address_layout_field_unpublished';
}

$classes .= ' hkc-sm-' . ($width * 3);
?>
<div class="<?php echo $classes; ?>" data-field="<?php echo $field->field_namekey; ?>" data-width="<?php echo $width; ?>" data-label-position="<?php echo ($labelPosition ?: 'inherit'); ?>">

    <!-- Drag handle -->
    <div class="address_layout_field_handle"><i class="fas fa-grip-vertical"></i></div>

    <!-- Field controls (shown on hover) -->
    <div class="address_layout_field_controls">
        <button type="button" class="hk-move-btn" onclick="hikashopAddressLayoutBuilder.moveField(this, 'left');" title="<?php echo JText::_('HIKA_MOVE_PREV_POSITION'); ?>"><i class="fas fa-arrow-left" aria-hidden="true"></i><span class="element-invisible"><?php echo JText::_('HIKA_MOVE_PREV_POSITION'); ?></span></button>
        <button type="button" class="hk-move-btn" onclick="hikashopAddressLayoutBuilder.moveField(this, 'right');" title="<?php echo JText::_('HIKA_MOVE_NEXT_POSITION'); ?>"><i class="fas fa-arrow-right" aria-hidden="true"></i><span class="element-invisible"><?php echo JText::_('HIKA_MOVE_NEXT_POSITION'); ?></span></button>
        <button type="button" class="hk-move-btn" onclick="hikashopAddressLayoutBuilder.moveField(this, 'up');" title="<?php echo JText::_('HIKA_MOVE_UP'); ?>"><i class="fas fa-arrow-up" aria-hidden="true"></i><span class="element-invisible"><?php echo JText::_('HIKA_MOVE_UP'); ?></span></button>
        <button type="button" class="hk-move-btn" onclick="hikashopAddressLayoutBuilder.moveField(this, 'down');" title="<?php echo JText::_('HIKA_MOVE_DOWN'); ?>"><i class="fas fa-arrow-down" aria-hidden="true"></i><span class="element-invisible"><?php echo JText::_('HIKA_MOVE_DOWN'); ?></span></button>
        <button type="button" class="address_layout_width_btn address_layout_resize_handle" onmousedown="hikashopAddressLayoutBuilder.startResize(event, this)" onkeydown="if(event.keyCode===13||event.keyCode===32){event.preventDefault();hikashopAddressLayoutBuilder.cycleWidth(this);}" title="<?php echo JText::_('HIKA_RESIZE'); ?> (<?php echo $width; ?>/4)"><i class="fas fa-arrows-alt-h" aria-hidden="true"></i><span class="element-invisible"><?php echo JText::_('HIKA_RESIZE'); ?> (<?php echo $width; ?>/4)</span></button>
        <button type="button" class="address_layout_label_btn" onclick="hikashopAddressLayoutBuilder.cycleLabelPosition(this)" title="<?php echo JText::_('LABEL_POSITION'); ?>"><i class="fas fa-tag" aria-hidden="true"></i><span class="element-invisible"><?php echo JText::_('LABEL_POSITION'); ?></span></button>
        <a href="index.php?option=com_hikashop&ctrl=field&task=edit&cid[]='<?php echo $field->field_id; ?>'" target="_blank" class="address_layout_label_btn" title="<?php echo JText::_('HIKA_SETTINGS'); ?>"><i class="fas fa-cog" aria-hidden="true"></i><span class="element-invisible"><?php echo JText::_('HIKA_SETTINGS'); ?></span></a>
    </div>

    <!-- Actual field content -->
    <div class="address_layout_field_content">

        <?php
        $effectiveLabel = $labelPosition ?: 'inherit';
        $fieldname = $field->field_namekey;

        $labelClass = '';
        if($effectiveLabel == 'above') $labelClass = 'hk-label-above';
        else if($effectiveLabel == 'no') $labelClass = 'hk-label-hidden';
        else if($effectiveLabel == 'yes') $labelClass = 'hk-label-inline';
        ?>

        <div class="address_layout_field_name <?php echo $labelClass; ?>"><?php echo $field->field_realname; ?></div>

        <?php
        $field->field_options['data-prevent-change'] = 'true';
        $inputClass = 'hikashop_field_input';
        if(in_array($field->field_type, array('zone', 'singledropdown', 'multipledropdown'))) {
            $inputClass .= ' form-select';
        }

        echo $this->fieldClass->display($field, @$this->sampleAddress->$fieldname, 'preview['.$fieldname.']', false, 'class="'.$inputClass.'"', false, $this->allAddressFields, $this->sampleAddress, false);
        unset($field->field_options['data-prevent-change']);
        ?>

    </div> 
</div>
