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
?>{address_company}
{address_title} {address_firstname} {address_lastname}
{address_street}
{address_post_code} {address_city} {address_state}
{address_country}
<?php if(!empty($this->address->address_telephone)) echo JText::sprintf('TELEPHONE_IN_ADDRESS','{address_telephone}'); ?>

<?php if(!empty($this->address->address_vat)) echo JText::sprintf('VAT_IN_ADDRESS','{address_vat}'); ?>
