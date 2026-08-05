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
?><table class="w600" border="0" cellspacing="0" cellpadding="0" width="600" style="margin:0px;font-family: Arial, Helvetica, sans-serif;font-size:12px;line-height:18px;">
	<tr>
		<td class="w20" width="20"></td>
		<td class="w560 pict" style="text-align:left; color:#575757" width="560">
			<div id="title" style="font-family: Arial, Helvetica, sans-serif;font-size:12px;line-height:18px;">
<!-- LOGO -->
<img src="{VAR:LIVE_SITE}media/com_hikashop/images/icons/icon-48-forum.png" border="0" alt="" style="float:left;margin-right:4px;"/>
<!-- EO LOGO -->
<!-- TITLE -->
<h1 style="color:#1c8faf !important;font-size:16px;font-weight:bold; border-bottom:1px solid #ddd; padding-bottom:10px">
	{TXT:WAITLIST_TITLE}
</h1>
<!-- EO TITLE -->
<!-- FOR PRODUCT -->
<h2 style="color:#1c8faf !important;font-size:12px;font-weight:bold; padding-bottom:10px">
	{TXT:FOR_PRODUCT}
</h2>
<!-- EO FOR PRODUCT -->
			</div>
		</td>
		<td class="w20" width="20"></td>
	</tr>
	<tr>
		<td class="w20" width="20"></td>
		<td style="border:1px solid #adadad;background-color:#ffffff;">
			<div class="w550" width="550" id="content" style="font-family: Arial, Helvetica, sans-serif;font-size:12px;line-height:18px;margin-left:5px;margin-right:5px;">
<p>
<!-- HELLO -->
	<h3 style="color:#393939 !important; font-size:14px; font-weight:normal; font-weight:bold;margin-bottom:0px;padding:0px;">{TXT:HI_USER}</h3>
<!-- EO HELLO -->
<!-- BEGIN MESSAGE -->
	{TXT:WAITLIST_BEGIN_MESSAGE}
<!-- EO BEGIN MESSAGE -->
</p>

<table class="w550" border="0" cellspacing="0" cellpadding="0" width="550" style="margin-top:10px;font-family: Arial, Helvetica, sans-serif;font-size:12px;line-height:18px;">
	<tr>
<!-- USER TITLE -->
		<td style="color:#1c8faf !important;font-size:12px;font-weight:bold;">{TXT:USER}</td>
<!-- EO USER TITLE -->
<!-- USER VALUE -->
		<td>{VAR:USER_DETAILS}</td>
<!-- EO USER VALUE -->
	</tr>
	<tr>
<!-- PRODUCT TITLE -->
		<td style="color:#1c8faf !important;font-size:12px;font-weight:bold;">{TXT:PRODUCT}</td>
<!-- EO PRODUCT TITLE -->
<!-- PRODUCT VALUE -->
		<td>{VAR:PRODUCT_DETAILS}</td>
<!-- EO PRODUCT VALUE -->
	</tr>
</table>
			</div>
		</td>
		<td class="w20" width="20"></td>
	</tr>
</table>
