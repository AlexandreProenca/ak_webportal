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
defined('_JEXEC') or die('Restricted access');

if(!empty($this->uploaded)) {
	$filename = $this->uploaded;
	$parts = explode('.', $filename);
	$ext = array_pop($parts);
	$id = implode($parts);
	$image_name = str_replace('_', ' ', $id);
	$image_url = HIKASHOP_IMAGES . $this->type . '/' . $filename;
?>
<script type="text/javascript">
(function() {
	var nbId = 'data_<?php echo $this->type; ?>_<?php echo $this->type; ?>_images';
	var nb = window.parent.oNameboxes[nbId];
	if(nb) {
		var entry = {
			id: <?php echo json_encode($id); ?>,
			image_name: <?php echo json_encode($image_name); ?>,
			image_file: <?php echo json_encode($filename); ?>,
			image_url: <?php echo json_encode('<img src="' . $image_url . '" />'); ?>
		};
		nb.data[entry.id] = entry;
		nb.content.load(nb.data);
		nb.content.render(true);
	}
	window.parent.hikashop.closeBox();
})();
</script>
<?php
	return;
}
?>
<div style="padding:10px;">
	<h3><?php echo JText::_('HIKA_UPLOAD_IMAGE'); ?></h3>
	<form action="<?php echo hikashop_completeLink('plugins&task=saveimage&type='.$this->type); ?>" method="post" enctype="multipart/form-data">
		<div style="margin:10px 0;">
			<input type="file" name="plugin_image" accept="image