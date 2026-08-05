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
class hikashopAiHelper {

	public function isAvailable() {
		static $available = null;
		if($available !== null)
			return $available;
		$available = false;
		if(!defined('HIKASHOP_WORDPRESS') || !function_exists('wp_ai_client_prompt'))
			return $available;
		if(function_exists('wp_supports_ai') && !wp_supports_ai())
			return $available;
		try {
			$registry = \WordPress\AiClient\AiClient::defaultRegistry();
			foreach($registry->getRegisteredProviderIds() as $providerId) {
				if($registry->isProviderConfigured($providerId)) {
					$available = true;
					break;
				}
			}
		} catch(\Throwable $e) {
			$available = false;
		}
		return $available;
	}

	public function renderAssist($editorId, $type, $options = array()) {
		if(!$this->isAvailable())
			return;

		static $jsLoaded = false;
		if(!$jsLoaded) {
			$jsLoaded = true;
			$doc = JFactory::getDocument();
			$doc->addScript(HIKASHOP_JS . 'hikashop_ai.js');
		}

		$params = new HikaParameter('');
		$params->set('editorId', $editorId);
		$params->set('type', $type);
		$params->set('product_id', isset($options['product_id']) ? (int)$options['product_id'] : 0);
		$params->set('language_id', isset($options['language_id']) ? (int)$options['language_id'] : 0);
		$params->set('field', isset($options['field']) ? $options['field'] : '');
		$params->set('view_id', isset($options['view_id']) ? $options['view_id'] : '');

		$js = '';
		echo hikashop_getLayout('ai', 'assist', $params, $js, true);
	}

	public function generate($type, $instructions, $currentContent, $context = array()) {
		if(!$this->isAvailable())
			return false;

		$systemPrompt = '';
		$userPrompt = '';

		switch($type) {
			case 'description':
				$product_id = isset($context['product_id']) ? (int)$context['product_id'] : 0;
				list($systemPrompt, $userPrompt) = $this->buildProductPrompt($product_id, $instructions, $currentContent);
				break;

			case 'translation':
				$product_id = isset($context['product_id']) ? (int)$context['product_id'] : 0;
				$language_id = isset($context['language_id']) ? (int)$context['language_id'] : 0;
				$field = isset($context['field']) ? $context['field'] : 'product_description';
				if(!preg_match('/^[a-z_]+$/', $field))
					$field = 'product_description';
				list($systemPrompt, $userPrompt) = $this->buildTranslationPrompt($product_id, $language_id, $field, $instructions, $currentContent);
				break;

			case 'view':
				$view_id = isset($context['view_id']) ? $context['view_id'] : '';
				list($systemPrompt, $userPrompt) = $this->buildViewPrompt($view_id, $instructions, $currentContent);
				break;

			default:
				return false;
		}

		if(empty($userPrompt))
			return false;

		try {
			$builder = wp_ai_client_prompt($userPrompt)
				->using_temperature(0.4);

			if(!empty($systemPrompt))
				$builder = $builder->using_system_instruction($systemPrompt);

			$result = $builder->generate_text();
		} catch(\Throwable $e) {
			return false;
		}

		$result = $this->cleanCodeFences($result);

		$response = array(
			'result' => $result,
		);

		if($type === 'view') {
			hikashop_get('inc.diff');
			$diff = HikashopDiffInc::compare($currentContent, $result);
			$response['diff_html'] = HikashopDiffInc::toTable($diff);
		}

		return $response;
	}

	protected function buildProductPrompt($product_id, $instructions, $currentContent) {
		$system = "You are an e-commerce copywriter assistant for HikaShop.\n"
			. "Your task is to write or improve product descriptions.\n"
			. "Return only the HTML content for the description.\n"
			. "Do not wrap the output in code fences or add any commentary.";

		$user = '';

		if(!empty($product_id)) {
			$productClass = hikashop_get('class.product');
			$product = $productClass->get($product_id);

			if(!empty($product)) {
				$user .= 'Product name: ' . $product->product_name . "\n";

				$categories = $productClass->getCategories($product_id);
				if(!empty($categories)) {
					$categoryClass = hikashop_get('class.category');
					$catNames = array();
					foreach($categories as $catId) {
						$cat = $categoryClass->get($catId);
						if(!empty($cat->category_name))
							$catNames[] = hikashop_translate($cat->category_name);
					}
					if(!empty($catNames))
						$user .= 'Categories: ' . implode(', ', $catNames) . "\n";
				}

				if(!empty($product->product_code))
					$user .= 'SKU: ' . $product->product_code . "\n";

				$db = JFactory::getDBO();
				$query = 'SELECT c.characteristic_value FROM ' . hikashop_table('variant') . ' AS v '
					. ' INNER JOIN ' . hikashop_table('characteristic') . ' AS c ON v.variant_characteristic_id = c.characteristic_id '
					. ' WHERE v.variant_product_id = ' . (int)$product_id;
				$db->setQuery($query);
				$charValues = $db->loadColumn();
				if(!empty($charValues))
					$user .= 'Characteristics: ' . implode(', ', $charValues) . "\n";
			}
		}

		if(!empty($currentContent))
			$user .= "\nCurrent description:\n" . $currentContent . "\n";

		if(!empty($instructions))
			$user .= "\nInstructions: " . $instructions . "\n";
		else
			$user .= "\nWrite a compelling product description based on the information above.\n";

		return array($system, $user);
	}

	protected function buildTranslationPrompt($product_id, $language_id, $field, $instructions, $currentTranslation) {
		$sourceContent = '';

		if(!empty($product_id)) {
			$productClass = hikashop_get('class.product');
			$product = $productClass->get($product_id);

			if(!empty($product) && !empty($product->$field))
				$sourceContent = $product->$field;
		}

		$sourceLang = $this->getDefaultLanguageName();

		$translationHelper = hikashop_get('helper.translation');
		$translationHelper->loadLanguages();
		$targetLang = 'the target language';
		if(!empty($translationHelper->languages[$language_id]))
			$targetLang = $this->languageCodeToName($translationHelper->languages[$language_id]->code);

		$system = "You are a professional translator for e-commerce content.\n"
			. "Translate from " . $sourceLang . " to " . $targetLang . ".\n"
			. "Preserve all HTML formatting. Keep brand names and technical terms as-is.\n"
			. "Return only the translated HTML content.\n"
			. "Do not wrap the output in code fences or add any commentary.";

		$user = "Content to translate:\n" . $sourceContent . "\n";

		if(!empty($currentTranslation))
			$user .= "\nCurrent translation (to improve):\n" . $currentTranslation . "\n";

		if(!empty($instructions))
			$user .= "\nAdditional instructions: " . $instructions . "\n";

		return array($system, $user);
	}

	protected function buildViewPrompt($view_id, $instructions, $currentCode) {
		$originalCode = '';
		$viewInfo = '';

		if(!empty($view_id)) {
			$viewClass = hikashop_get('class.view');
			$element = $viewClass->get($view_id);

			if(!empty($element) && !empty($element->path) && is_file($element->path)) {
				$originalCode = file_get_contents($element->path);
				$viewInfo = $element->view . ' / ' . $element->filename;
			}
		}

		$system = "You are a PHP/HTML developer working with HikaShop (a Joomla/WordPress e-commerce component).\n"
			. "Modify the template file according to the user's instructions.\n"
			. "Preserve all existing HikaShop variables and PHP logic patterns.\n"
			. "Return only the complete modified PHP template code.\n"
			. "Do not wrap the output in code fences or add any commentary.";

		$user = '';
		if(!empty($viewInfo))
			$user .= "Template: " . $viewInfo . "\n\n";

		if(!empty($originalCode))
			$user .= "Original template:\n" . $originalCode . "\n\n";

		if(!empty($currentCode) && $currentCode !== $originalCode)
			$user .= "Current code being edited:\n" . $currentCode . "\n\n";

		$user .= "Instructions: " . $instructions . "\n";

		return array($system, $user);
	}

	protected function cleanCodeFences($text) {
		$text = preg_replace('/^```(?:php|html|)\s*\n?/i', '', $text);
		$text = preg_replace('/\n?```\s*$/', '', $text);
		return $text;
	}

	protected function getDefaultLanguageName() {
		if(defined('HIKASHOP_WORDPRESS')) {
			$locale = get_locale();
			return $this->languageCodeToName(str_replace('_', '-', $locale));
		}
		$lang = JFactory::getLanguage();
		return $this->languageCodeToName($lang->getTag());
	}

	protected function languageCodeToName($code) {
		$map = array(
			'en' => 'English', 'fr' => 'French', 'de' => 'German', 'es' => 'Spanish',
			'it' => 'Italian', 'pt' => 'Portuguese', 'nl' => 'Dutch', 'pl' => 'Polish',
			'ru' => 'Russian', 'ja' => 'Japanese', 'zh' => 'Chinese', 'ko' => 'Korean',
			'ar' => 'Arabic', 'tr' => 'Turkish', 'sv' => 'Swedish', 'da' => 'Danish',
			'fi' => 'Finnish', 'no' => 'Norwegian', 'cs' => 'Czech', 'el' => 'Greek',
			'hu' => 'Hungarian', 'ro' => 'Romanian', 'bg' => 'Bulgarian', 'hr' => 'Croatian',
			'sk' => 'Slovak', 'sl' => 'Slovenian', 'lt' => 'Lithuanian', 'lv' => 'Latvian',
			'et' => 'Estonian', 'th' => 'Thai', 'vi' => 'Vietnamese', 'id' => 'Indonesian',
			'ms' => 'Malay', 'hi' => 'Hindi', 'uk' => 'Ukrainian', 'he' => 'Hebrew',
			'ca' => 'Catalan', 'eu' => 'Basque', 'gl' => 'Galician',
		);
		$short = strtolower(substr($code, 0, 2));
		return isset($map[$short]) ? $map[$short] : $code;
	}
}
