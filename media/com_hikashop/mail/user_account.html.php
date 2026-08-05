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
?><!-- HELLO -->
<?php echo JText::sprintf('HI_CUSTOMER',@$data->name);?>
<!-- EO HELLO -->
<br/>
<!-- THANK YOU MESSAGE -->
<?php echo JText::sprintf('THANK_YOU_FOR_REGISTERING',HIKASHOP_LIVE);?><br/>
<!-- EO THANK YOU MESSAGE -->
<?php if($data->active){ ?>
<!-- ACCOUNT ACTIVATION MESSAGE -->
<?php echo JText::_('ACCOUNT_MUST_BE_ACTIVATED'); ?>
<!-- EO ACCOUNT ACTIVATION MESSAGE -->
	<br/><br/>
<!-- ACTIVATION URL -->
	<div style="text-align:center">
		<a class="cart_button hika_template_color" href="<?php echo $data->activation_url;?>"><?php echo JText::_('ACTIVATE_MY_ACCOUNT');?></a>
	</div>
<!-- EO ACTIVATION URL -->
<?php } ?>
<br/><br/>
<?php
$password = false;
jimport('joomla.application.component.helper');
$usersConfig = JComponentHelper::getParams( 'com_users' );
if ($usersConfig->get('sendpassword')) {
	$password = true;
}
?>
<!-- LOGIN MESSAGE -->
<?php if($password)	echo JText::sprintf('YOU_CAN_LOG_IN_WITH');?><br/>
<!-- EO LOGIN MESSAGE -->
<!-- USERNAME -->
<?php echo JText::sprintf('HIKA_USERNAME').' : '.$data->username;?><br/>
<!-- EO USERNAME -->
<!-- PASSWORD -->
<?php if($password)	echo JText::sprintf('HIKA_PASSWORD').' : '.$data->password.'<br/>'; ?>
<!-- EO PASSWORD -->
<!-- THANK YOU PARTNER -->
<br/>
<?php if(!empty($data->user_partner_activated)){
	echo JText::sprintf('THANK_YOU_FOR_BECOMING_OUR_PARTNER',$data->user_id,$data->partner_url);
}?>
<!-- EO THANK YOU PARTNER -->
<br/>
<!-- BEST REGARDS -->
<?php echo JText::sprintf('BEST_REGARDS_CUSTOMER',$mail->from_name);?>
<!-- EO BEST REGARDS -->
