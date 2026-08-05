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
?>				<span id="result" >
					<?php echo @$this->rows[0]->product_id.' '.@$this->rows[0]->product_name; ?>
					<input type="hidden" name="data[waitlist][product_id]" value="<?php echo @$this->rows[0]->product_id; ?>" />
				</span>
