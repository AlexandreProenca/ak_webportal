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
if(!$this->config->get('display_bundled_products', 0))
	return;
$variant_name = '';
$variant_main = '_main';
$display_mode = '';
if(!empty($this->variant_name)) {
	$variant_name = $this->variant_name;
	if(substr($variant_name, 0, 1) != '_')
		$variant_name = '_' . $variant_name;
	$variant_main = $variant_name;
	$display_mode = 'display:none;';
}

ob_start();
$productClass = hikashop_get('class.product');
if(!empty($this->element->bundles)) {
	foreach ($this->element->bundles as $bundle) {
		if(!empty($bundle->product_name)) {
			$bundle->product_name = hikashop_translate($bundle->product_name);
		}
		if($bundle->product_type == 'variant') {
			$db = JFactory::getDBO();
			$query = 'SELECT * FROM '.hikashop_table('variant').' AS v '.
				' LEFT JOIN '.hikashop_table('characteristic') .' AS c ON v.variant_characteristic_id = c.characteristic_id '.
				' WHERE v.variant_product_id = '.(int)$bundle->product_id.' ORDER BY v.ordering';
			$db->setQuery($query);
			$bundle->characteristics = $db->loadObjectList();
			$parentProduct = $productClass->get((int)$bundle->product_parent_id);
			$productClass->checkVariant($bundle, $parentProduct);
		}
		?>
		<div class="hikashop_product_bundled_item">
			<span class="hikashop_product_bundled_item_name">
				<?php echo $bundle->product_name; ?>
			</span>
			<span class="hikashop_product_bundled_item_separator">
				<?php echo JText::_('HIKA_BUNDLE_SEPARATOR'); ?>
			</span>
			<span class="hikashop_product_bundled_item_quantity">
				<?php echo $bundle->product_related_quantity; ?>
			</span>
		</div>
		<?php
	}
}
$bundled_products = ob_get_clean();
?>

<div id="hikashop_product_bundled<?php echo $variant_main;?>" class="hikashop_product_bundled_main" style="<?php echo $display_mode;?>">
<?php if(!empty($bundled_products)) { ?>
	<h4><?php echo JText::_('BUNDLED_PRODUCTS');?></h4>
	<div class="hikashop_product_bundled<?php echo $variant_name;?>">
		<?php echo $bundled_products; ?>
	</div>
<?php } ?>
</div>
