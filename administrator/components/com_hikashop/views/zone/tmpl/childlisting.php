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
?><div style="float:right">
	<?php
		echo $this->popup->display(
			'<i class="fa fa-plus"></i> '.JText::_('ADD'),
			'ADD',
			hikashop_completeLink("zone&task=selectchildlisting&main_id=".$this->main_id."&main_namekey=".$this->main_namekey,true ),
			'subzones_add_button',
			760, 480, 'class="btn btn-primary"', '', 'link'
		);
		echo ' ';
		echo $this->popup->display(
			'<i class="fa fa-upload"></i> '.JText::_('IMPORT_CSV'),
			'IMPORT_SUBZONES',
			hikashop_completeLink("zone&task=importcsv&main_id=".$this->main_id,true ),
			'subzones_import_button',
			500, 450, 'class="btn btn-success"', '', 'link'
		);
	?>
</div>
<table id="hikashop_zone_child_listing" class="adminlist table table-striped table-hover" cellpadding="1" width="100%">
	<thead>
		<tr>
			<th class="title">
				<?php echo JText::_('ZONE_NAME_ENGLISH'); ?>
			</th>
			<th class="title">
				<?php echo JText::_('HIKA_NAME'); ?>
			</th>
			<th class="title">
				<?php echo JText::_('ZONE_CODE_2'); ?>
			</th>
			<th class="title">
				<?php echo JText::_('ZONE_CODE_3'); ?>
			</th>
			<th class="title">
				<?php echo JText::_('ZONE_TYPE'); ?>
			</th>
			<th class="title">
				<?php echo JText::_('HIKA_EDIT'); ?>
			</th>
			<th class="title titletoggle">
				<?php echo JText::_('HIKA_DELETE'); ?>
			</th>
			<th class="title">
				<?php echo JText::_('ID'); ?>
			</th>
		</tr>
	</thead>
	<tbody id="list_0_data">
		<?php
			$this->k = 0;
			$this->setLayout('child');
			for($i = 0,$a = count($this->list);$i<$a;$i++){
				$this->row =& $this->list[$i];
				echo $this->loadTemplate();
				$this->k = 1-$this->k;
			}
		?>
	</tbody>
</table>

