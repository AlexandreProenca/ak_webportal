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
?><style type="text/css">
.wishlist_button{
	color:#333333 !important;
	font-size:18px;
	font-weight:normal;
	font-weight:bold;
	margin-bottom:0px;
	padding:0px;
	background-color: #ffc435;
	border-radius: 5px;
	padding: 15px;
	display: block;
	text-align: center;
}
</style>
<table class="w600" border="0" cellspacing="0" cellpadding="0" width="600" style="margin:0px;font-family: Arial, Helvetica, sans-serif;font-size:12px;line-height:18px;">
	<tr>
		<td class="w20" width="20"></td>
		<td class="w560 pict" style="text-align:left; color:#575757" width="560">
			<div id="title" style="font-family: Arial, Helvetica, sans-serif;font-size:12px;line-height:18px;">
<!-- LOGO -->
<img src="{VAR:LIVE_SITE}media/com_hikashop/images/icons/icon-48-forum.png" border="0" alt="" style="float:left;margin-right:4px;"/>
<!-- EO LOGO -->
<!-- TITLE -->
<h1 style="font-size:16px;font-weight:bold; border-bottom:1px solid #ddd; padding-bottom:10px">
	{TXT:WISHLIST_TITLE}
</h1>
<!-- EO TITLE -->
<!-- BEGIN MESSAGE -->
<h2 style="font-size:12px;font-weight:bold; padding-bottom:10px">
	{TXT:WISHLIST_BEGIN_MESSAGE}
</h2>
<!-- EO BEGIN MESSAGE -->
			</div>
		</td>
		<td class="w20" width="20"></td>
	</tr>
	<tr>
		<td class="w20" width="20"></td>
		<td style="">
<!-- WISHLIST ACCESS BUTTON -->
			<h3>
				<a class="wishlist_button" href="{VAR:URL}">{TXT:DISPLAY_THE_WISHLIST_OF_USER}</a>
			</h3>
<!-- EO WISHLIST ACCESS BUTTON -->
		</td>
		<td class="w20" width="20"></td>
	</tr>
</table>
