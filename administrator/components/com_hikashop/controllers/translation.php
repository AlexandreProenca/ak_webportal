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
class TranslationController extends hikashopController{
	var $toggle = array('published'=>'id');
	var $display = array('listing','show','');
	var $modify_views = array();
	var $add = array();
	var $modify = array('toggle');
	var $delete = array();
}
