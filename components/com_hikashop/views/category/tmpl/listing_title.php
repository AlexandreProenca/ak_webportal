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
?><span class="hikashop_category_name">
	<a href="<?php echo $this->row->link;?>">
		<?php
		echo $this->row->category_name;
		if($this->params->get('number_of_products',0))
			echo ' ('.$this->row->number_of_products.')';
		?>
	</a>
</span>
