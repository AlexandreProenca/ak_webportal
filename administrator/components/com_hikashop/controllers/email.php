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
class emailController extends hikashopController {
	var $type = 'mail';

	function __construct($config = array())
	{
		parent::__construct($config);
		$this->modify_views[]='emailtemplate';
		$this->display[]='preview';
		$this->display[]='diff';
		$this->modify[]='saveemailtemplate';
		$this->modify_views[]='orderstatus';
		$this->modify[]='saveorderstatus';
	}
	public function test() {
	}

	public function edit() {
		return false;
	}

	public function remove() {
		return $this->listing();
	}

	public function getUploadSetting($upload_key, $caller = '') {
	}

	public function preview() {
	}

	public function manageUpload($upload_key, &$ret, $uploadConfig, $caller = '') {
		if(empty($ret))
			return;
	}

	function emailtemplate() {
	}


	function diff() {
	}

	function orderstatus() {
	}

	public function saveemailtemplate() {
	}

	public function saveorderstatus() {
	}
}
