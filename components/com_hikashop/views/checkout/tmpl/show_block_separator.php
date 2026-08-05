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
$grid = $this->getGrid();
if(empty($grid))
	return;

switch($this->options['type']) {
	case 'start':
		$class = 'hk-row-fluid';
		if($grid[0] != 12)
			$class.= ' hk-row-' . $grid[0];
?>
<div class="<?php echo $class; ?>">
	<div class="hkc-md-<?php echo $grid[1]; ?>">
<?php
		break;
	case 'vertical':
?>
	</div>
	<div class="hkc-md-<?php echo $grid[1]; ?>">
<?php
		$this->emptyBlocksCount++;
		break;
	case 'horizontal':
		$class = 'hk-row-fluid';
		if($grid[0] != 12)
			$class.= ' hk-row-' . $grid[0];
?>
	</div>
</div>
<div class="<?php echo $class; ?>">
	<div class="hkc-md-<?php echo $grid[1]; ?>">
<?php
		$this->emptyBlocksCount++;
		break;
	case 'end':
?>
	</div>
</div>
<?php
		break;
	default:
		break;
}
