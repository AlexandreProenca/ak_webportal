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
class AiController extends hikashopController {

	public function __construct($config = array(), $skip = false) {
		parent::__construct($config, $skip);
		$this->display = array();
		$this->modify = array('generate');
	}

	public function generate() {
		JSession::checkToken('request') || die('Invalid Token');

		$aiHelper = hikashop_get('helper.ai');
		if(!$aiHelper->isAvailable()) {
			$this->sendJson(array('error' => 'AI not available'));
			return;
		}

		$type = hikaInput::get()->getCmd('type', '');
		$instructions = hikaInput::get()->getString('instructions', '');
		$currentContent = hikaInput::get()->getRaw('current_content', '');
		$context = array(
			'product_id' => hikaInput::get()->getInt('product_id', 0),
			'language_id' => hikaInput::get()->getInt('language_id', 0),
			'field' => hikaInput::get()->getCmd('field', ''),
			'view_id' => hikaInput::get()->getString('view_id', ''),
		);

		if(empty($type) || !in_array($type, array('description', 'translation', 'view'))) {
			$this->sendJson(array('error' => 'Invalid type'));
			return;
		}

		$result = $aiHelper->generate($type, $instructions, $currentContent, $context);

		if($result === false) {
			$this->sendJson(array('error' => 'AI generation failed'));
			return;
		}

		$this->sendJson($result);
	}

	protected function sendJson($data) {
		hikashop_cleanBuffers();
		header('Content-Type: application/json');
		echo json_encode($data);
		exit;
	}
}
