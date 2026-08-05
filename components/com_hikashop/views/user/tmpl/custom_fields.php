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
$type = $this->type;
$after = array();
if($type == 'address') {
	$this->fields = $this->extraFields[$type];
	$this->fieldClass = $this->fieldsClass;
	$this->inputPrefix = 'data['.$type.']';
	$this->idPrefix = 'hikashop_'.$type;
	$this->onChangeJs = "window.hikashop.toggleField(this,'{FIELD}','".$type."',0,'hikashop_');";

	echo $this->loadHkLayout('address_form_layout');
} else {
	foreach($this->extraFields[$type] as $fieldName => $oneExtraField) {
		$onWhat='onchange';
		if($oneExtraField->field_type=='radio')
			$onWhat='onclick';
		$html = $this->fieldsClass->display(
			$oneExtraField,
			@$this->$type->$fieldName,
			'data['.$type.']['.$fieldName.']',
			false,
			' class="'.HK_FORM_CONTROL_CLASS.'" '.$onWhat.'="window.hikashop.toggleField(this,\''.$fieldName.'\',\''.$type.'\',0);"',
			false,
			$this->extraFields[$type],
			$this->$type,
			false
		);
		if($oneExtraField->field_type=='hidden') {
			$after[] = $html;
			continue;
		}
	?>
			<div class="hkform-group control-group hikashop_registration_<?php echo $fieldName;?>_line" id="hikashop_<?php echo $type.'_'.$oneExtraField->field_namekey; ?>">
				<?php echo $this->fieldsClass->getFieldName($oneExtraField,true,'hkc-sm-4 hkcontrol-label');?>
				<div class="hkc-sm-8">
					<?php 
					echo $html; ?>
				</div>
			</div>
	<?php 
	}
}

if(count($after)) {
	echo implode("\r\n", $after);
}
?>
