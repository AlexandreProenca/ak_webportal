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
<?php echo JText::sprintf('THANK_YOU_FOR_REGISTERING',HIKASHOP_LIVE);?>
<!-- EO THANK YOU MESSAGE -->
<br/>
<?php if($data->active){ ?>
<!-- ACCOUNT ACTIVATION MESSAGE -->
	<?php echo JText::sprintf('ACCOUNT_MUST_BE_ACTIVATED'); ?>
<!-- EO ACCOUNT ACTIVATION MESSAGE -->
	<br/><br/>
<!-- ACTIVATION URL -->
	<a href="<?php echo $data->activation_url;?>"><?php echo $data->activation_url;?></a>
<!-- EO ACTIVATION URL -->
<?php } ?>
<br/><br/>
<!-- LOGIN MESSAGE -->
<?php echo JText::sprintf('YOU_CAN_LOG_IN_WITH');?>
<!-- EO LOGIN MESSAGE -->
<br/>
<!-- USERNAME -->
<?php echo JText::sprintf('HIKA_USERNAME').' : '.$data->username;?>
<!-- EO USERNAME -->
<br/>
<!-- PASSWORD -->
<?php echo JText::sprintf('HIKA_PASSWORD').' : '.$data->password;?>
<!-- EO PASSWORD -->
<br/>
<br/>
<!-- THANK YOU PARTNER -->
<?php if(!empty($data->user_partner_activated)){
	echo JText::sprintf('THANK_YOU_FOR_BECOMING_OUR_PARTNER',$data->id,$data->partner_url);
}?>
<!-- EO THANK YOU PARTNER -->
<br/>
<!-- BEST REGARDS -->
<?php echo JText::sprintf('BEST_REGARDS_CUSTOMER',$mail->from_name);?>
<!-- EO BEST REGARDS -->
