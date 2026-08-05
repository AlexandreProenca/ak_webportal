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
?><form action="index.php?option=<?php echo HIKASHOP_COMPONENT ?>" method="post"  name="adminForm" id="adminForm" >
	<input type="hidden" name="code" value="<?php echo $this->file->name; ?>" />
	<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="ctrl" value="config" />
	<?php echo JHTML::_( 'form.token' ); ?>
	<div class="clearfix"></div>

	<?php echo hikashop_display(JText::_( 'OVERRIDE_WITH_EXPLANATION_TRANSLATION'),'info'); ?>
	<div class="hikashop_backend_tile_edition">
		<div class="hk-container-fluid">
			<div class="hkc-lg-6 hikashop_tile_block hikashop_language_edit_main">
				<div>
<?php if(defined('HIKASHOP_WORDPRESS') && isset($this->wp_content)) { ?>
					<div class="hikashop_language_tabs" style="margin-bottom:5px;">
						<a href="#" id="hk_lang_tab_main" class="hikabtn hikabtn-primary" onclick="return hikashopLangTab('main');"><?php echo JText::_('HIKA_FILE'); ?></a>
						<a href="#" id="hk_lang_tab_wp" class="hikabtn" onclick="return hikashopLangTab('wp');">WordPress</a>
					</div>
					<div id="hk_lang_panel_main">
						<div class="hikashop_tile_title">
							<?php echo JText::_( 'HIKA_FILE').' : '.$this->file->name; ?>
						</div>
						<textarea style="width:98%;" rows="32" name="content" id="translation" ><?php echo str_replace('</textarea>', '&lt;/textarea&gt;', @$this->file->content);?></textarea>
					</div>
					<div id="hk_lang_panel_wp" style="display:none;">
						<div class="hikashop_tile_title">
							WordPress : <?php echo $this->file->name; ?>
						</div>
						<textarea style="width:98%;" rows="32" name="content_wordpress" id="translation_wordpress" ><?php echo str_replace('</textarea>', '&lt;/textarea&gt;', $this->wp_content);?></textarea>
					</div>
					<script type="text/javascript">
					function hikashopLangTab(tab) {
						document.getElementById('hk_lang_panel_main').style.display = (tab === 'main') ? '' : 'none';
						document.getElementById('hk_lang_panel_wp').style.display = (tab === 'wp') ? '' : 'none';
						document.getElementById('hk_lang_tab_main').className = 'hikabtn' + (tab === 'main' ? ' hikabtn-primary' : '');
						document.getElementById('hk_lang_tab_wp').className = 'hikabtn' + (tab === 'wp' ? ' hikabtn-primary' : '');
						return false;
					}
					</script>
<?php } else { ?>
					<div class="hikashop_tile_title">
						<?php echo JText::_( 'HIKA_FILE').' : '.$this->file->name; ?>
					</div>
					<textarea style="width:98%;" rows="32" name="content" id="translation" ><?php echo str_replace('</textarea>', '&lt;/textarea&gt;', @$this->file->content);?></textarea>
<?php } ?>
				</div>
			</div>
			<div class="hkc-lg-6 hikashop_tile_block hikashop_language_edit_override">
				<div>
					<div class="hikashop_tile_title">
						<?php echo JText::_( 'OVERRIDE').' : '; ?>
					</div>
					<textarea style="width:98%;" rows="32" name="content_override" id="translation_override" ><?php echo str_replace('</textarea>', '&lt;/textarea&gt;', $this->override_content);?></textarea>
				</div>
			</div>
		</div>
	</div>
	<div class="clr"></div>
</form>
