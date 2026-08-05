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
?><style>html, body { height:100%; margin:0; padding:0; overflow:hidden; }</style>
<div class="hikaGalleryPopup">
<div class="hikaGalleryHeader">
	<h1><?php echo $this->popupTitle; ?></h1>
	<button class="btn btn-success" type="button" onclick="window.hikashop.submitform('galleryselect','adminForm');"><i class="fa fa-save"></i> <?php echo JText::_('OK'); ?></button>
</div>
<form action="<?php echo hikashop_completeLink('upload&task=galleryimage', true); ?>" method="post" name="adminForm" id="adminForm">
	<div class="hikaGallerySearch">
		<?php echo $this->loadHkLayout('search', array()); ?>
	</div>
	<div class="hikaGalleryBody">
		<div class="hikaGallerySidebar">
<?php
echo $this->treeContent;
?>
<script type="text/javascript">
hikashopGallery.callbackSelection = function(tree,id) {
	var d = document, node = tree.get(id);
	if( node.value && node.name ) {
		var url = "<?php
			$params = '';
			if(!empty($this->uploadConfig['extra'])) {
				foreach($this->uploadConfig['extra'] as $uploadField => $uploadFieldValue) {
					$params .= '&' . urlencode($uploadField) . '=' . urlencode((string)$uploadFieldValue);
				}
			}
			echo hikashop_completeLink('upload&task=galleryimage&folder={FOLDER}&uploader='.$this->uploader.'&field='.$this->field.$params, true, true) ;
		?>";
		if(node.value != '<?php echo rtrim($this->destFolder, '/'); ?>') {
			document.location = url.replace('{FOLDER}', node.value.replace('/', '|'));
		}
	}
}
</script>
		</div>
		<div class="hikaGalleryMain">
			<ul class="hikaGallery">
<?php
if(!empty($this->dirContent)) {
	hikashop_loadJsLib('tooltip');
	foreach($this->dirContent as $k => $content) {
		$chk_uid = 'hikaGalleryChk_' . $k . '_' . uniqid();

		$tooltip = '';
		if(strlen($content->filename) > 15)
			$tooltip = ' data-toggle="hk-tooltip" data-title="'.htmlentities($content->filename).'"';
?>
	<li class="hikaGalleryItem">
		<a class="hikaGalleryPhoto" href="#" onclick="return window.hikagallery.select(this, '<?php echo $chk_uid; ?>');"<?php echo $tooltip; ?>>
			<?php if(empty($content->thumbnail)) { ?>
				<span class="fa fa-file" style="font-size:60px;line-height:75px;"></span>
				<span class="file_name"><?php echo substr($content->filename,0,12); ?></span>
			<?php } else { ?>
				<img src="<?php echo $content->thumbnail->url; ?>" alt="<?php echo $content->filename; ?>"/>
			<?php } ?>
			<span style="display:none;" class="hikaGalleryChk"><input type="checkbox" id="<?php echo $chk_uid ;?>" name="files[]" value="<?php echo $content->path; ?>"/></span>
			<div class="hikaGalleryCommand">
				<?php if(!empty($content->width) && !empty($content->height)) { ?>
					<span class="photo_name"><?php echo $content->filename; ?></span>
					<span><?php echo $content->width . 'x' . $content->height; ?></span>
				<?php } ?>
				<span style="float:right"><?php echo $content->size; ?></span>
			</div>
		</a>
	</li>
<?php
	}
}
?>
			</ul>
		</div>
	</div>
	<div class="hikaGalleryFooter">
		<?php echo $this->pagination->getListFooter(); ?>
	</div>
<script type="text/javascript">
window.hikagallery = {};
window.hikagallery.select = function(el, id) {
	var d = document, w = window, o = w.Oby, chk = d.getElementById(id);
	if(chk) {
		if(chk.checked) {
			o.removeClass(el.parentNode, 'selected');
		} else {
			o.addClass(el.parentNode, 'selected');
		}
		chk.checked = !chk.checked;
	}
	return false;
}
</script>
	<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
	<input type="hidden" name="tmpl" value="component" />
	<input type="hidden" name="task" value="galleryimage" />
	<input type="hidden" name="ctrl" value="upload" />
	<input type="hidden" name="folder" value="<?php echo $this->destFolder; ?>" />
	<input type="hidden" name="uploader" value="<?php echo $this->uploader; ?>" />
	<input type="hidden" name="field" value="<?php echo $this->field; ?>" />
<?php
	if(!empty($this->uploadConfig['extra'])) {
		foreach($this->uploadConfig['extra'] as $uploadField => $uploadFieldValue) {
?>
	<input type="hidden" name="<?php echo $uploadField; ?>" value="<?php echo $uploadFieldValue; ?>" />
<?php
		}
	}
?>
	<?php echo JHTML::_('form.token'); ?>
</form>
</div>
