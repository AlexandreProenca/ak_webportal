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
$type = $this->plugin_type;
$upType = strtoupper($type);
$plugin_name = $type.'_name';
$plugin_alias = $type.'_alias';
$plugin_alias_input =$plugin_alias.'_input';
$plugin_published = $type.'_published';
$plugin_name_input =$plugin_name.'_input';
$plugin_description = $type.'_description';
$plugin_name_published = $plugin_name.'_published';
$plugin_name_id = $plugin_name.'_id';
$plugin_description_published = $plugin_description.'_published';
$plugin_description_id = $plugin_description.'_id';
?>
<table class="admintable" style="width:100%">
	<tr>
		<td class="key"><label for="hikashop_plugin_name_field"><?php
			echo JText::_( 'HIKA_NAME' );
		?></label></td>
		<td>
			<input id="hikashop_plugin_name_field" type="text" name="<?php echo $this->$plugin_name_input; ?>" value="<?php echo $this->escape(@$this->element->$plugin_name); ?>" />
<?php if(isset($this->$plugin_name_published)) {
	$publishedid = 'published-'.$this->$plugin_name_id;
?>
			<span id="<?php echo $publishedid; ?>" class="spanloading"><?php echo $this->toggle->toggle($publishedid,(int) $this->$plugin_name_published,'translation') ?></span>
<?php } ?>
		</td>
	</tr>
	<tr>
		<td class="key"><label for="hikashop_plugin_alias_field"><?php
			echo JText::_( 'HIKA_ALIAS' );
		?></label></td>
		<td>
			<input id="hikashop_plugin_alias_field" type="text" name="<?php echo $this->$plugin_alias_input; ?>" value="<?php echo $this->escape(@$this->element->$plugin_alias); ?>" />
		</td>
	</tr>
	<tr>
		<td class="key"  colspan="2" width="100%">
			<span style="float:left"><label for="jform_articletext"><?php echo JText::_('HIKA_DESCRIPTION'); ?></label></span>
<?php if(isset($this->$plugin_description_published)){
	$publishedid = 'published-'.$this->$plugin_description_id;
?>
			<span id="<?php echo $publishedid; ?>" class="spanloading"><?php echo $this->toggle->toggle($publishedid,(int) $this->$plugin_description_published,'translation') ?></span>
<?php } ?>
			<br/>
<?php
	$this->editor->content = @$this->element->$plugin_description;
	echo $this->editor->display();
?>
		</td>
	</tr>
<?php
if(!empty($this->translatableParams) && strpos($this->editor->name, 'translation_') === 0) {
	foreach($this->translatableParams as $paramKey => $paramInfo) {
		$paramField = $paramInfo['field'];
		$paramFieldPublished = $paramField . '_published';
		$paramFieldId = $paramField . '_id';
		$paramLabel = JText::_($paramInfo['label']);
		$paramType = $paramInfo['type'];
		$paramValue = @$this->element->$paramField;
?>
	<tr>
		<td class="key" <?php echo ($paramType == 'wysiwyg' || $paramType == 'textarea' || $paramType == 'big-textarea') ? 'colspan="2" width="100%"' : ''; ?>>
			<span style="float:left"><label><?php echo $paramLabel; ?></label></span>
<?php if(isset($this->$paramFieldPublished)) {
	$publishedid = 'published-'.$this->$paramFieldId;
?>
			<span id="<?php echo $publishedid; ?>" class="spanloading"><?php echo $this->toggle->toggle($publishedid,(int) $this->$paramFieldPublished,'translation') ?></span>
<?php } ?>
<?php
	$editorNameParts = explode('_', $this->editor->name);
	$langId = end($editorNameParts);
	if($paramType == 'wysiwyg') {
		$paramEditor = hikashop_get('helper.editor');
		$paramEditor->name = 'translation_'.$paramField.'_'.$langId;
		$paramEditor->content = $paramValue;
		$paramEditor->height = 200;
?>
			<br/>
<?php echo $paramEditor->display(); ?>
		</td>
	</tr>
<?php } elseif($paramType == 'textarea' || $paramType == 'big-textarea') { ?>
			<br/>
			<textarea name="translation[<?php echo $paramField; ?>][<?php echo $langId; ?>]" style="width:100%;min-height:<?php echo ($paramType == 'big-textarea') ? '200' : '100'; ?>px"><?php echo $this->escape($paramValue); ?></textarea>
		</td>
	</tr>
<?php } else { ?>
		</td>
		<td>
			<input type="text" name="translation[<?php echo $paramField; ?>][<?php echo $langId; ?>]" value="<?php echo $this->escape($paramValue); ?>" style="width:100%" />
		</td>
	</tr>
<?php } ?>
<?php
	}
}
?>
</table>
