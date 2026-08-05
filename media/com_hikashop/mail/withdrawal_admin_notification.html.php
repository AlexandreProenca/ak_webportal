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
<img src="{VAR:LIVE_SITE}media/com_hikashop/images/icons/icon-48-order.png" border="0" alt="" style="float:left;margin-right:4px;"/>
<!-- EO LOGO -->
<!-- TITLE -->
<h1 class="hika_template_color" style="font-size:16px;font-weight:bold; border-bottom:1px solid #ddd; padding-bottom:10px">
	{TXT:WITHDRAWAL_TITLE}
</h1>
<!-- EO TITLE -->
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
	<h3 style="color:#393939 !important; font-size:14px; font-weight:normal; font-weight:bold;margin-bottom:0px;padding:0px;">{TXT:HI_ADMIN}</h3>
<!-- EO HELLO -->
<!-- MAIN MESSAGE -->
	{TXT:WITHDRAWAL_INTRO}
<!-- EO MAIN MESSAGE -->
</p>

<!-- REASON -->
<h3 class="hika_template_color" style="font-size:14px;font-weight:bold;border-bottom:1px solid #ddd;padding-top:10px;padding-bottom:10px;">
    {TXT:WITHDRAWAL_REASON_TITLE}
</h3>
<p>{VAR:WITHDRAWAL_REASON}</p>
<!-- EO REASON -->

<table class="w550" border="0" cellspacing="0" cellpadding="0" width="550" style="margin-top:10px;font-family: Arial, Helvetica, sans-serif;font-size:12px;line-height:18px;">
		<tr>
<!-- BILLING ADDRESS TITLE -->
		<!--{IF:BILLING_ADDRESS}--><td class="hika_template_color" style="font-size:12px;font-weight:bold;">{TXT:BILLING_ADDRESS}</td><!--{ENDIF:BILLING_ADDRESS}-->
<!-- EO BILLING ADDRESS TITLE -->
<!-- SHIPPING ADDRESS TITLE -->
		<!--{IF:SHIPPING}--><!--{IF:SHIPPING_ADDRESS}--><td class="hika_template_color" style="font-size:12px;font-weight:bold;">{TXT:SHIPPING_ADDRESS}</td><!--{ENDIF:SHIPPING_ADDRESS}--><!--{ENDIF:SHIPPING}-->
<!-- EO SHIPPING ADDRESS TITLE -->
	</tr>
	<tr>
<!-- BILLING ADDRESS -->
		<!--{IF:BILLING_ADDRESS}--><td>{VAR:BILLING_ADDRESS}</td><!--{ENDIF:BILLING_ADDRESS}-->
<!-- EO BILLING ADDRESS -->
<!-- SHIPPING ADDRESS -->
		<!--{IF:SHIPPING}--><!--{IF:SHIPPING_ADDRESS}--><td>{VAR:SHIPPING_ADDRESS}</td><!--{ENDIF:SHIPPING_ADDRESS}--><!--{ENDIF:SHIPPING}-->
<!-- EO SHIPPING ADDRESS -->
	</tr>
</table>
<!-- PRODUCTS LIST TITLE -->
<h1 class="hika_template_color" style="font-size:16px;font-weight:bold;border-bottom:1px solid #ddd;padding-top:10px;padding-bottom:10px;">
	{TXT:SUMMARY_OF_WITHDRAWAL}
</h1>
<!-- EO PRODUCTS LIST TITLE -->
<!--{START:VENDOR_LINE}-->
<!-- VENDOR NAME -->
<!--{IF:VENDOR_CONTENT}-->{VAR:VENDOR_CONTENT}<!--{ENDIF:VENDOR_CONTENT}-->
<!-- EO VENDOR NAME -->
<table class="w550" border="0" cellspacing="0" cellpadding="0" width="550" style="margin-top:10px;margin-bottom:10px;font-family: Arial, Helvetica, sans-serif;font-size:12px;line-height:18px;">
	<tr>
<!-- PRODUCT NAME TITLE -->
		<td class="hika_template_color" style="border-bottom:1px solid #ddd;padding-bottom:3px;text-align:left;font-size:12px;font-weight:bold;">{TXT:PRODUCT_NAME}</td>
<!-- EO PRODUCT NAME TITLE -->
<!-- PRODUCT CUSTOM FIELDS TITLE -->
		{TXT:CUSTOMFIELD_NAME}
<!-- EO PRODUCT CUSTOM FIELDS TITLE -->
<!-- PRODUCT PRICE TITLE -->
		<td class="hika_template_color" style="border-bottom:1px solid #ddd;padding-bottom:3px;text-align:right;font-size:12px;font-weight:bold;">{TXT:PRODUCT_PRICE}</td>
<!-- EO PRODUCT PRICE TITLE -->
<!-- PRODUCT QUANTITY TITLE -->
		<td class="hika_template_color" style="border-bottom:1px solid #ddd;padding-bottom:3px;text-align:right;font-size:12px;font-weight:bold;">{TXT:PRODUCT_QUANTITY}</td>
<!-- EO PRODUCT QUANTITY TITLE -->
<!-- PRODUCT TOTAL TITLE -->
		<td class="hika_template_color" style="border-bottom:1px solid #ddd;padding-bottom:3px;text-align:right;font-size:12px;font-weight:bold;">{TXT:PRODUCT_TOTAL}</td>
<!-- EO PRODUCT TOTAL TITLE -->
	</tr>
<!--{START:PRODUCT_LINE}-->
	<tr>
<!-- PRODUCT NAME VALUE -->
		<td style="border-bottom:1px solid #ddd;padding-bottom:3px;">
			{LINEVAR:PRODUCT_IMG}
			{LINEVAR:PRODUCT_NAME}<!--{IF:ORDER_PRODUCT_CODE}--> {LINEVAR:PRODUCT_CODE}<!--{ENDIF:ORDER_PRODUCT_CODE}-->
			{LINEVAR:PRODUCT_DOWNLOAD}
			{LINEVAR:PRODUCT_DETAILS}
		</td>
<!-- EO PRODUCT NAME VALUE -->
<!-- PRODUCT CUSTOM FIELDS VALUE -->
		{LINEVAR:CUSTOMFIELD_VALUE}
<!-- EO PRODUCT CUSTOM FIELDS VALUE -->
<!-- PRODUCT PRICE VALUE -->
		<td style="border-bottom:1px solid #ddd;padding-bottom:3px;text-align:right;white-space:nowrap">{LINEVAR:PRODUCT_PRICE}</td>
<!-- EO PRODUCT PRICE VALUE -->
<!-- PRODUCT QUANTITY VALUE -->
		<td style="border-bottom:1px solid #ddd;padding-bottom:3px;text-align:right">{LINEVAR:PRODUCT_QUANTITY}</td>
<!-- EO PRODUCT QUANTITY VALUE -->
<!-- PRODUCT TOTAL VALUE -->
		<td style="border-bottom:1px solid #ddd;padding-bottom:3px;text-align:right;white-space:nowrap">{LINEVAR:PRODUCT_TOTAL}</td>
<!-- EO PRODUCT TOTAL VALUE -->
	</tr>
<!--{END:PRODUCT_LINE}-->
</table>
<!--{END:VENDOR_LINE}-->
<!-- WITHDRAWAL MANAGE LINK -->
<p>
    <a href="{VAR:MANAGE_URL}" class="cart_button hika_template_color">{TXT:MANAGE_WITHDRAWAL}</a>
</p>
<!-- EO WITHDRAWAL MANAGE LINK -->
			</div>
		</td>
		<td class="w20" width="20"></td>
	</tr>
</table>
