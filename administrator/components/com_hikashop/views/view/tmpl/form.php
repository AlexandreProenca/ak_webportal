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
<?php if(hikaInput::get()->getCmd('tmpl', '') === 'component') { ?>
<div class="hikashop_popup_buttons" style="text-align:right; padding:10px; background:#f5f5f5; border-bottom:1px solid #ddd; margin:-10px -10px 10px -10px;">
	<button type="button" class="btn" onclick="if(window.parent && window.parent.hikashop) { window.parent.hikashop.closeBox(); } else { window.close(); }">
		<i class="fas fa-times"></i> <?php echo JText::_('HIKA_CANCEL'); ?>
	</button>
	<button type="button" class="btn btn-primary" onclick="hikashopSaveViewFile();" style="margin-left:5px;">
		<i class="fas fa-save"></i> <?php echo JText::_('HIKA_SAVE'); ?>
	</button>
</div>
<script>
function hikashopSaveViewFile() {
	var ta = document.getElementById('jform_articletext');
	if(ta && ta.hikaCM) {
		ta.value = ta.hikaCM.getValue();
	} else if(typeof Joomla !== 'undefined' && Joomla.editors && Joomla.editors.instances) {
		for(var id in Joomla.editors.instances) {
			if(Joomla.editors.instances.hasOwnProperty(id)) {
				var editor = Joomla.editors.instances[id];
				if(editor && typeof editor.getValue === 'function') {
					var textarea = document.getElementById(id);
					if(textarea) {
						textarea.value = editor.getValue();
					}
				}
			}
		}
	}
	try { sessionStorage.setItem('hikashop_view_saved', '1'); } catch(e) {}
	hikashop.submitform('apply', 'adminForm');
}
document.addEventListener('DOMContentLoaded', function() {
	try {
		if(sessionStorage.getItem('hikashop_view_saved') === '1') {
			sessionStorage.removeItem('hikashop_view_saved');
			if(window.parent && window.parent !== window) {
				window.parent.location.reload();
			}
		}
	} catch(e) {}
});
</script>
<?php }
$tmpl = hikaInput::get()->getCmd('tmpl', '');
$formAction = hikashop_completeLink('view' . (!empty($tmpl) ? '&tmpl=' . $tmpl : ''));
?>
<form action="<?php echo $formAction; ?>" method="post"  name="adminForm" id="adminForm">

	<?php if($this->ftp){ ?>
	<fieldset title="<?php echo JText::_('DESCFTPTITLE'); ?>">
		<legend><?php echo JText::_('DESCFTPTITLE'); ?></legend>

		<?php echo JText::_('DESCFTP'); ?>

		<?php
			if(!empty($this->ftp) && method_exists($this->ftp, '__toString'))
				$msg = $this->ftp->__toString();
			else
				$msg = @$this->ftp->message;
		?>

		<?php if(!empty($msg)){ ?>
			<p>
			<?php
				echo JText::_( $msg );
			?></p>
		<?php } ?>

		<table class="adminform nospace">
		<tbody>
		<tr>
			<td width="120">
				<label for="username"><?php echo JText::_('HIKA_USERNAME'); ?>:</label>
			</td>
			<td>
				<input type="text" id="username" name="username" class="input_box" size="70" value="" />
			</td>
		</tr>
		<tr>
			<td width="120">
				<label for="password"><?php echo JText::_('HIKA_PASSWORD'); ?>:</label>
			</td>
			<td>
				<input type="password" id="password" name="password" class="input_box" size="70" value="" />
			</td>
		</tr>
		</tbody>
		</table>
	</fieldset>
	<?php } ?>

	<table id="hikashop_edit_view" class="adminform table">
	<tr>
		<th><?php
			if($this->element->type_name != HIKASHOP_COMPONENT && !empty($this->element->type_pretty_name)) {
				echo $this->element->type_pretty_name . ' - ';
			}
			$allowDuplicate = false;
			if(substr($this->element->filename,-4) == '.php') {
				if( $this->element->view == 'product' ) {
					if( substr($this->element->filename,0,8) == 'listing_' || substr($this->element->filename,0,5) == 'show_')
						$allowDuplicate = true;
				} else if( $this->element->view == 'category' && substr($this->element->filename,0,8) == 'listing_' ) {
					$allowDuplicate = true;
				}
			}

			if($allowDuplicate) {
				$name = explode('_', substr($this->element->filename, 0, -4), 2);
				echo $this->element->view .' / '.$name[0].'_<input type="text" name="duplicate" id="duplicate" value="'.$name[1].'"/>.php';
			} else {
				echo $this->element->view .' / '. $this->element->filename;
			}
		?></th>
	</tr>
<?php
			$display = '';
			if(!empty( $this->element->structure)) {
?>
	<tr>
		<td>
			<?php
				$this->setLayout('builder');
				echo $this->loadTemplate();
			?>
		</td>
	</tr>
<?php
			}
?>
	<tr <?php echo $display; ?>>
		<td>
			<?php // CodeMirror 6 (Joomla 5+) loads the language as @codemirror/lang-{mode}, so it needs 'php'. CodeMirror 5 (Joomla 3/4 and WordPress) uses the MIME mode.
			$hkCodeSyntax = (HIKASHOP_J50 && !defined('HIKASHOP_WORDPRESS')) ? 'php' : 'application/x-httpd-php';
			echo $this->editor->displayCode('filecontent',$this->element->content, array('syntax' => $hkCodeSyntax)); ?>
<?php
			if($this->aiHelper->isAvailable()) {
				$this->aiHelper->renderAssist('filecontent', 'view', array(
					'view_id' => $this->element->id
				));
			}
?>
		</td>
	</tr>
	</table>

	<div class="clr"></div>

	<input type="hidden" name="id" value="<?php echo $this->element->id; ?>" />
	<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT;?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="ctrl" value="<?php echo hikaInput::get()->getCmd('ctrl', '');?>" />
	<?php if(!empty($tmpl)) { ?><input type="hidden" name="tmpl" value="<?php echo $tmpl; ?>" /><?php } ?>
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
