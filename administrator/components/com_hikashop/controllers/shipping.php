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
class ShippingController extends hikashopController{
	var $type='shipping';
	function __construct($config = array()){
		parent::__construct($config);
		$this->modify_views[] = 'unpublish';
		$this->modify_views[] = 'publish';
	}

}
