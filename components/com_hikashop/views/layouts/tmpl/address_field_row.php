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

$labelcolumnclass = 'hkc-sm-4';
$inputcolumnclass = 'hkc-sm-8';
if(!isset($this->address)) $this->address = null;

$field = null;
if(isset($this->params) && $this->params->get('field')) {
    $field = $this->params->get('field');
} elseif(isset($this->field)) {
    $field = $this->field;
}

$onChangeJs = $this->params->get('onChangeJs');
if(empty($onChangeJs)) {
	$onChangeJs = @$this->onChangeJs;
}

$fieldname = $field->field_namekey;
$onWhat = ($field->field_type == 'radio') ? 'onclick' : 'onchange';

$html = $this->fieldClass->display(
    $field,
    @$this->address->$fieldname,
    $this->inputPrefix . '[' . $fieldname . ']',
    false,
    ' class="' . HK_FORM_CONTROL_CLASS . '" ' . $onWhat . '="' . $onChangeJs . '"',
    false,
    $this->fields,
    $this->address,
    false
);


if($field->field_type == 'hidden') {
    echo $html;
    return;
}
?>
<div class="hkform-group control-group hikashop_address_<?php echo $fieldname; ?>" id="<?php echo $this->idPrefix . '_' . $fieldname; ?>">
    <?php echo $this->fieldClass->getFieldName($field, true, $labelcolumnclass.' hkcontrol-label'); ?>
    <div class="hikashop_address_field_input <?php echo $inputcolumnclass;?>">
        <?php echo $html; ?>
    </div>
</div>
