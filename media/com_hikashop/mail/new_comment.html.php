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
?><!-- NEW COMMENT NOTIFICATION MESSAGE -->
<?php echo JText::sprintf('NEW_COMMENT_NOTIFICATION_SUBJECT',HIKASHOP_LIVE);?><br/>
<!-- EO NEW COMMENT NOTIFICATION MESSAGE -->
<!-- COMMENT ELEMENT NAME -->
<?php
if(isset($data->result->vote_type) && $data->result->vote_type == 'vendor'){
	echo JText::_('COMMENT_ITEM_NAME').": ".$data->type->vendor_name;
}else{
	echo JText::_('COMMENT_ITEM_NAME').": ".$data->type->product_name;
}
?>
<!-- EO COMMENT ELEMENT NAME -->
	<br/>
<!-- COMMENT USERNAME -->
<?php echo JText::_('HIKA_USERNAME').": ".$data->result->username_comment; ?>
<!-- EO COMMENT USERNAME -->
	<br/>
<!-- COMMENT EMAIL -->
<?php echo JText::_('HIKA_EMAIL').": ".$data->result->email_comment; ?>
<!-- EO COMMENT EMAIL -->
	<br/><br/>
<!-- COMMENT CONTENT -->
<?php echo JText::_('COMMENT_CONTENT').": ".$data->result->comment; ?>
<!-- EO COMMENT CONTENT -->
	<br/><br/>
<!-- COMMENT URL -->
<?php echo JText::_('SEE_COMMENT').": "; ?>
	<br/>
	<a href="<?php echo JRoute::_('administrator/index.php?option=com_hikashop&ctrl=vote&task=edit&cid[]='.$data->result->vote_id,false,true);?>"><?php echo JRoute::_('administrator/index.php?option=com_hikashop&ctrl=vote&task=edit&cid[]='.$data->result->vote_id,false,true);?></a>
<!-- EO COMMENT URL -->
