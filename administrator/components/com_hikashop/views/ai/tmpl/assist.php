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
$editorId = $this->params->get('editorId', '');
$type = $this->params->get('type', 'description');
$instanceId = 'hikashop_ai_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $editorId);
$previewMode = ($type === 'view') ? 'diff' : 'preview';

$placeholder = JText::_('HIKA_AI_INSTRUCTIONS_PLACEHOLDER');
if($type === 'translation')
	$placeholder = JText::_('HIKA_AI_TRANSLATION_PLACEHOLDER');
elseif($type === 'view')
	$placeholder = JText::_('HIKA_AI_VIEW_PLACEHOLDER');

$buttonLabel = JText::_('HIKA_AI_GENERATE');
if($type === 'translation')
	$buttonLabel = JText::_('HIKA_AI_TRANSLATE');

$contextData = json_encode(array(
	'type' => $type,
	'product_id' => (int)$this->params->get('product_id', 0),
	'language_id' => (int)$this->params->get('language_id', 0),
	'field' => $this->params->get('field', ''),
	'view_id' => $this->params->get('view_id', ''),
));

$ajaxUrl = hikashop_completeLink('ai&task=generate&tmpl=raw&' . hikashop_getFormToken() . '=1', true);
?>
<div class="hikashop_ai_assist" id="<?php echo $instanceId; ?>">
	<div class="hikashop_ai_assist_header"><?php echo JText::_('HIKA_AI_ASSIST'); ?></div>
	<div class="hikashop_ai_assist_input">
		<textarea rows="3" placeholder="<?php echo htmlspecialchars($placeholder); ?>" class="hikashop_ai_instructions"></textarea>
		<button type="button" class="btn btn-primary hikashop_ai_generate_btn" onclick="return false;">
			<span class="hikashop_ai_btn_label"><?php echo $buttonLabel; ?></span>
			<span class="hikashop_ai_spinner"><i class="fa fa-spinner fa-spin"></i></span>
		</button>
	</div>
	<div class="hikashop_ai_result">
		<div class="hikashop_ai_preview"></div>
		<div class="hikashop_ai_actions">
			<button type="button" class="btn btn-success hikashop_ai_apply_btn"><?php echo JText::_('HIKA_AI_APPLY'); ?></button>
			<button type="button" class="btn hikashop_ai_discard_btn"><?php echo JText::_('HIKA_AI_DISCARD'); ?></button>
		</div>
	</div>
	<div class="hikashop_ai_error" style="display:none;"></div>
</div>
<script type="text/javascript">
window.hikashop.ready(function() {
	hikashopAi.init({
		containerId: <?php echo json_encode($instanceId); ?>,
		editorId: <?php echo json_encode($editorId); ?>,
		type: <?php echo json_encode($type); ?>,
		previewMode: <?php echo json_encode($previewMode); ?>,
		context: <?php echo $contextData; ?>,
		ajaxUrl: <?php echo json_encode($ajaxUrl); ?>
	});
});
</script>
