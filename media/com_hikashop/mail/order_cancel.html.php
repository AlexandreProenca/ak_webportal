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
$url = HIKASHOP_LIVE.'administrator/index.php?option=com_hikashop&ctrl=order&task=edit&order_id='.$data->order_id;
?>
<!-- CANCEL MESSAGE -->
<?php echo JText::_('ORDER_CANCEL_BY_USER');?>
<!-- EO CANCEL MESSAGE -->
<br/><br/>
<!-- ORDER NUMBER INFO -->
<?php echo JText::sprintf('NOTIFICATION_OF_ORDER_ON_WEBSITE', $data->order_number, HIKASHOP_LIVE);?><br/>
<!-- EO ORDER NUMBER INFO -->
<!-- ORDER URL -->
<?php echo JText::sprintf('ACCESS_ORDER_WITH_LINK', $url, $url); ?>
<!-- EO ORDER URL -->
<br/>
<!-- ORDER REFUND MESSAGE -->
<?php echo JText::_('MAKE_A_REFUND_IF_POSSIBLE'); ?>
<!-- EO ORDER REFUND MESSAGE -->
