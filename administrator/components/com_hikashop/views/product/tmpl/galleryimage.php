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
	<div></div>
	<button class="btn" type="button" onclick="submitbutton('galleryselect');"><img style="vertical-align: middle" src="<?php echo HIKASHOP_IMAGES; ?>save.png"/><?php echo JText::_('OK'); ?></button>
</div>
<form action="index.php?option=<?php echo HIKASHOP_COMPONENT; ?>&amp;ctrl=product" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
	<div class="hikaGallerySearch">
		<?php echo JText::_('FILTER');?>:
		<input type="text" name="search" id="search" value="<?php echo $this->escape($this->pageInfo->search);?>" class="text_area" onchange="document.adminForm.submit();" />
		<button class="btn" onclick="document.adminForm.limitstart.value=0;this.form.submit();"><?php echo JText::_( 'GO' ); ?></button>
		<button class="btn" onclick="document.adminForm.limitstart.value=0;document.getElementById('search').value='';this.form.submit();"><?php echo JText::_( 'RESET' ); ?></button>
	</div>
	<div class="hikaGalleryBody">
		<div class="hikaGallerySidebar">
<?php
echo $this->treeContent;
?>
<script type="text/javascript">
hikashopGallery.callbackSelection = function(tree,id) {
	var d = document, node = tree.get(id);
	if( node.value && node.name && node.value != '<?php echo rtrim($this->destFolder, '/'); ?>') {
		document.location = "<?php echo hikashop_completeLink('product&task=galleryimage&id='.hikaInput::get()->getInt('id').'&cid='.@$this->cid.'&product_id='.hikaInput::get()->getInt('product_id'), true, true) ;?>&folder=" + node.value;
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
		if(strlen($content->filename) > 5)
			$tooltip = ' data-toggle="hk-tooltip" data-title="'.htmlentities($content->filename).'"';
?>
	<li class="hikaGalleryItem">
		<a class="hikaGalleryPhoto" href="#" onclick="return window.hikagallery.select(this, '<?php echo $chk_uid; ?>');"<?php echo $tooltip; ?>>
			<img src="<?php echo $content->thumbnail->url; ?>" alt="<?php echo $content->filename; ?>"/>
			<span style="display:none;" class="hikaGalleryChk"><input type="checkbox" id="<?php echo $chk_uid ;?>" name="files[]" value="<?php echo $content->path; ?>"/></span>
			<div class="hikaGalleryCommand">
				<span class="photo_name"><?php echo $content->filename; ?></span>
				<span><?php echo $content->width . 'x' . $content->height; ?></span>
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
window.hikagallery = {
	selection: null
};
window.hikagallery.select = function(el, id) {
	var d = document, w = window, o = w.Oby, chk = d.getElementById(id);
	if(chk) {
		if(chk.checked) {
			o.removeClass(el.parentNode, 'selected');
			chk.checked = false;
			window.hikagallery.selection = null;
		} else {
			if(window.hikagallery.selection) {
				try {
					window.hikagallery.selection.chk.checked = false;
					o.removeClass(window.hikagallery.selection.obj, 'selected');
				} catch(e) {}
			}
			o.addClass(el.parentNode, 'selected');
			chk.checked = true;
			window.hikagallery.selection = {obj: el.parentNode, chk: chk};
		}
	}
	return false;
}
</script>
	<input type="hidden" name="data[file][file_type]" value="product" />
	<input type="hidden" name="data[file][file_ref_id]" value="<?php echo hikaInput::get()->getInt('product_id'); ?>" />
	<input type="hidden" name="id" value="<?php echo hikaInput::get()->getInt('id');?>" />
	<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
	<input type="hidden" name="tmpl" value="component" />
	<input type="hidden" name="task" value="galleryimage" />
	<input type="hidden" name="ctrl" value="product" />
	<?php echo JHTML::_('form.token'); ?>
</form>
</div>
