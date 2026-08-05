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
class plgHikashoppaymentPaypalcheckout extends hikashopPaymentPlugin
{

	var $bncode = 'HIKARISOFTWARE_Cart_PPCP';
	var $liveMerchantId = 'SXL9FKNKGAEM8';
	var $livePartnerClientId = 'ATMLcPXNkey-vCSk3rNDmQ84adkXQKf1bWNXK01UOT2gW8XbgMRMvLXnPjqoU6KjzDcuaGQ1QydJ6Yhn';
	var $sandboxMerchantId = 'NZFJZCZ2WRXPN';
	var $sandboxPartnerClientId = 'AcTqgpujxMEGpk8rKeEu9LOG6EgskJyK-AtCMdQBtZjN4zt51HLetLv3Y9plTUGrFCont2uzaZVi4aqe';

	var $accepted_currencies = array(
		'AUD','BRL','CAD','EUR','GBP','JPY','USD','NZD','CHF','HKD','SGD','SEK',
		'DKK','PLN','NOK','HUF','CZK','MXN','MYR','PHP','TWD','THB','ILS','TRY',
	);

	var $accepted_locales = array(
		'en_AL','ar_DZ','en_DZ','fr_DZ','es_DZ','zh_DZ','en_AD','fr_AD','es_AD',
		'zh_AD','en_AO','fr_AO','es_AO','zh_AO','en_AI','fr_AI','es_AI','zh_AI',
		'en_AG','fr_AG','es_AG','zh_AG','es_AR','en_AR','en_AM','fr_AM','es_AM',
		'zh_AM','en_AW','fr_AW','es_AW','zh_AW','en_AU','de_AT','en_AT','en_AZ',
		'fr_AZ','es_AZ','zh_AZ','en_BS','fr_BS','es_BS','zh_BS','ar_BH','en_BH',
		'fr_BH','es_BH','zh_BH','en_BB','fr_BB','es_BB','zh_BB','en_BY','en_BE',
		'nl_BE','fr_BE','en_BZ','es_BZ','fr_BZ','zh_BZ','fr_BJ','en_BJ','es_BJ',
		'zh_BJ','en_BM','fr_BM','es_BM','zh_BM','en_BT','es_BO','en_BO','fr_BO',
		'zh_BO','en_BA','en_BW','fr_BW','es_BW','zh_BW','pt_BR','en_BR','en_VG',
		'fr_VG','es_VG','zh_VG','en_BN','en_BG','fr_BF','en_BF','es_BF','zh_BF',
		'fr_BI','en_BI','es_BI','zh_BI','en_KH','fr_CM','en_CM','en_CA','fr_CA',
		'en_CV','fr_CV','es_CV','zh_CV','en_KY','fr_KY','es_KY','zh_KY','fr_TD',
		'en_TD','es_TD','zh_TD','es_CL','en_CL','fr_CL','zh_CL','zh_CN','es_CO',
		'en_CO','fr_CO','zh_CO','fr_KM','en_KM','es_KM','zh_KM','en_CG','fr_CG',
		'es_CG','zh_CG','fr_CD','en_CD','es_CD','zh_CD','en_CK','fr_CK','es_CK',
		'zh_CK','es_CR','en_CR','fr_CR','zh_CR','fr_CI','en_CI','en_HR','en_CY',
		'cs_CZ','en_CZ','fr_CZ','es_CZ','zh_CZ','da_DK','en_DK','fr_DJ','en_DJ',
		'es_DJ','zh_DJ','en_DM','fr_DM','es_DM','zh_DM','es_DO','en_DO','fr_DO',
		'zh_DO','es_EC','en_EC','fr_EC','zh_EC','ar_EG','en_EG','fr_EG','es_EG',
		'zh_EG','es_SV','en_SV','fr_SV','zh_SV','en_ER','fr_ER','es_ER','zh_ER',
		'en_EE','ru_EE','fr_EE','es_EE','zh_EE','en_ET','fr_ET','es_ET','zh_ET',
		'en_FK','fr_FK','es_FK','zh_FK','da_FO','en_FO','fr_FO','es_FO','zh_FO',
		'en_FJ','fr_FJ','es_FJ','zh_FJ','fi_FI','en_FI','fr_FI','es_FI','zh_FI',
		'fr_FR','en_FR','en_GF','fr_GF','es_GF','zh_GF','en_PF','fr_PF','es_PF',
		'zh_PF','fr_GA','en_GA','es_GA','zh_GA','en_GM','fr_GM','es_GM','zh_GM',
		'en_GE','fr_GE','es_GE','zh_GE','de_DE','en_DE','en_GI','fr_GI','es_GI',
		'zh_GI','el_GR','en_GR','fr_GR','es_GR','zh_GR','da_GL','en_GL','fr_GL',
		'es_GL','zh_GL','en_GD','fr_GD','es_GD','zh_GD','en_GP','fr_GP','es_GP',
		'zh_GP','es_GT','en_GT','fr_GT','zh_GT','fr_GN','en_GN','es_GN','zh_GN',
		'en_GW','fr_GW','es_GW','zh_GW','en_GY','fr_GY','es_GY','zh_GY','es_HN',
		'en_HN','fr_HN','zh_HN','en_HK','zh_HK','hu_HU','en_HU','fr_HU','es_HU',
		'zh_HU','en_IS','en_IN','id_ID','en_ID','en_IE','fr_IE','es_IE','zh_IE',
		'he_IL','en_IL','it_IT','en_IT','en_JM','es_JM','fr_JM','zh_JM','ja_JP',
		'en_JP','ar_JO','en_JO','fr_JO','es_JO','zh_JO','en_KZ','fr_KZ','es_KZ',
		'zh_KZ','en_KE','fr_KE','es_KE','zh_KE','en_KI','fr_KI','es_KI','zh_KI',
		'ar_KW','en_KW','fr_KW','es_KW','zh_KW','en_KG','fr_KG','es_KG','zh_KG',
		'en_LA','en_LV','ru_LV','fr_LV','es_LV','zh_LV','en_LS','fr_LS','es_LS',
		'zh_LS','en_LI','fr_LI','es_LI','zh_LI','en_LT','ru_LT','fr_LT','es_LT',
		'zh_LT','en_LU','de_LU','fr_LU','es_LU','zh_LU','en_MK','en_MG','fr_MG',
		'es_MG','zh_MG','en_MW','fr_MW','es_MW','zh_MW','en_MY','en_MV','fr_ML',
		'en_ML','es_ML','zh_ML','en_MT','en_MH','fr_MH','es_MH','zh_MH','en_MQ',
		'fr_MQ','es_MQ','zh_MQ','en_MR','fr_MR','es_MR','zh_MR','en_MU','fr_MU',
		'es_MU','zh_MU','en_YT','fr_YT','es_YT','zh_YT','es_MX','en_MX','en_FM',
		'en_MD','fr_MC','en_MC','en_MN','en_ME','en_MS','fr_MS','es_MS','zh_MS',
		'ar_MA','en_MA','fr_MA','es_MA','zh_MA','en_MZ','fr_MZ','es_MZ','zh_MZ',
		'en_NA','fr_NA','es_NA','zh_NA','en_NR','fr_NR','es_NR','zh_NR','en_NP',
		'nl_NL','en_NL','en_AN','fr_AN','es_AN','zh_AN','en_NC','fr_NC','es_NC',
		'zh_NC','en_NZ','fr_NZ','es_NZ','zh_NZ','es_NI','en_NI','fr_NI','zh_NI',
		'fr_NE','en_NE','es_NE','zh_NE','en_NG','en_NU','fr_NU','es_NU','zh_NU',
		'en_NF','fr_NF','es_NF','zh_NF','no_NO','en_NO','ar_OM','en_OM','fr_OM',
		'es_OM','zh_OM','en_PW','fr_PW','es_PW','zh_PW','es_PA','en_PA','fr_PA',
		'zh_PA','en_PG','fr_PG','es_PG','zh_PG','es_PY','en_PY','es_PE','en_PE',
		'fr_PE','zh_PE','en_PH','en_PN','fr_PN','es_PN','zh_PN','pl_PL','en_PL',
		'pt_PT','en_PT','en_QA','fr_QA','es_QA','zh_QA','ar_QA','en_RE','fr_RE',
		'es_RE','zh_RE','en_RO','fr_RO','es_RO','zh_RO','ru_RU','en_RU','fr_RW',
		'en_RW','es_RW','zh_RW','en_WS','en_SM','fr_SM','es_SM','zh_SM','en_ST',
		'fr_ST','es_ST','zh_ST','ar_SA','en_SA','fr_SA','es_SA','zh_SA','fr_SN',
		'en_SN','es_SN','zh_SN','en_RS','fr_RS','es_RS','zh_RS','fr_SC','en_SC',
		'es_SC','zh_SC','en_SL','fr_SL','es_SL','zh_SL','en_SG','sk_SK','en_SK',
		'fr_SK','es_SK','zh_SK','en_SI','fr_SI','es_SI','zh_SI','en_SB','fr_SB',
		'es_SB','zh_SB','en_SO','fr_SO','es_SO','zh_SO','en_ZA','fr_ZA','es_ZA',
		'zh_ZA','ko_KR','en_KR','es_ES','en_ES','en_LK','en_SH','fr_SH','es_SH',
		'zh_SH','en_KN','fr_KN','es_KN','zh_KN','en_LC','fr_LC','es_LC','zh_LC',
		'en_PM','fr_PM','es_PM','zh_PM','en_VC','fr_VC','es_VC','zh_VC','en_SR',
		'fr_SR','es_SR','zh_SR','en_SJ','fr_SJ','es_SJ','zh_SJ','en_SZ','fr_SZ',
		'es_SZ','zh_SZ','sv_SE','en_SE','de_CH','fr_CH','en_CH','zh_TW','en_TW',
		'en_TJ','fr_TJ','es_TJ','zh_TJ','en_TZ','fr_TZ','es_TZ','zh_TZ','th_TH',
		'en_TH','fr_TG','en_TG','es_TG','zh_TG','en_TO','en_TT','fr_TT','es_TT',
		'zh_TT','ar_TN','en_TN','fr_TN','es_TN','zh_TN','en_TM','fr_TM','es_TM',
		'zh_TM','en_TC','fr_TC','es_TC','zh_TC','en_TV','fr_TV','es_TV','zh_TV',
		'tr_TR','en_TR','en_UG','fr_UG','es_UG','zh_UG','en_UA','ru_UA','fr_UA',
		'es_UA','zh_UA','en_AE','fr_AE','es_AE','zh_AE','ar_AE','en_GB','en_US',
		'fr_US','es_US','zh_US','es_UY','en_UY','fr_UY','zh_UY','en_VU','fr_VU',
		'es_VU','zh_VU','en_VA','fr_VA','es_VA','zh_VA','es_VE','en_VE','fr_VE',
		'zh_VE','en_VN','en_WF','fr_WF','es_WF','zh_WF','ar_YE','en_YE','fr_YE',
		'es_YE','zh_YE','en_ZM','fr_ZM','es_ZM','zh_ZM','en_ZW'
	);
	var $accepted_applepay_locales = array(
		'ar','ca','cs','da','de','el','en','es','fi','fr','he','hi','hr','hu',
		'id','it','ja','ko','ms','nb','nl','pl','pt','ro','ru','sk','sv','th',
		'tr','uk','vi','zh'
	);

	var $rounding = array('TWD' => 0, 'MYR' => 0, 'JPY' => 0, 'HUF' => 0);

	var $multiple = true;
	var $name = 'paypalcheckout';
	var $doc_form = 'paypalcheckout';
	var $extraParams = '';
	var $countries = array();

	var $features = array(
		'authorize_capture' => true,
		'recurring' => true,
		'refund' => true,
	);


	private $_apiClient = null;

	var $pluginConfig = array(
		'paypal_account_connection' => array('PAYPAL_ACCOUNT_CONNECTION', 'title'),
		'sandbox' => array('HIKA_SANDBOX', 'boolean', 0),
		'connect' => array('PAYPAL_CHECKOUT_CONNECT', 'html'),
		'client_id' => array('PAYPAL_CHECKOUT_CLIENT_ID', 'input'),
		'client_secret' => array('PAYPAL_CHECKOUT_CLIENT_SECRET', 'input'),
		'merchant_id' => array('PAYPAL_CHECKOUT_MERCHANT_ID', 'input'),

		'accept_credit_card_payments' => array('ACCEPT_CREDIT_CARD_PAYMENTS', 'title'),
		'enable_credit_card' => array('ENABLE_CREDIT_CARDS', 'boolean', 1),
		'credit_card_limits' => array('', 'content'),
		'enable_3dsecure' => array('ENABLE_3DSECURE', 'list', array(
				'SCA_WHEN_REQUIRED' =>'SCA_WHEN_REQUIRED',
				'SCA_ALWAYS' => 'SCA_ALWAYS',
				'NO' => 'HIKASHOP_NO',
			),
		),
		'info_3dsecure' => array('', 'content'),
		'payments_with_paypal_buttons_options' => array('PAYMENTS_WITH_PAYPAL_BUTTONS_OPTIONS', 'title'),
		'brand_name' => array('PAYPAL_CHECKOUT_MERCHANT_NAME', 'input'),
		'capture' => array('INSTANTCAPTURE', 'boolean','1'),
		'landing_page' => array('PAYPAL_CHECKOUT_LANDING_PAGE', 'list', array(
				'LOGIN' =>'PAYPAL_CHECKOUT_LOGIN_PAGE',
				'BILLING' => 'PAYPAL_CHECKOUT_CREDIT_CARD_PAGE',
				'NO_PREFERENCE' => 'PAYPAL_CHECKOUT_NO_PREFERENCE',
			),
		),
		'disable_funding' => array(
			'PAYPAL_CHECKOUT_DISABLE_FUNDING',
			'checkbox',
			array(
				'card' =>'Credit or debit cards',
				'credit' => 'PayPal Credit (US, UK)',
				'paylater' => 'Pay Later (US, UK), Pay in 4 (AU), 4X PayPal (France), Paga en 3 plazos (Spain), Paga in 3 rate (Italy), Später Bezahlen (Germany)',
				'venmo' => 'Venmo',
				'bancontact' => 'Bancontact',
				'blik' => 'BLIK',
				'eps' => 'eps',
				'ideal' => 'iDEAL',
				'mercadopago' => 'Mercado Pago',
				'mybank' => 'MyBank',
				'p24' => 'Przelewy24',
				'sepa' => 'SEPA-Lastschrift',
				'multibanco' => 'Multibanco',
				'trustly' => 'Trustly',
			),
			'tooltip' => 'PAYPAL_CHECKOUT_DISABLE_FUNDING_TOOLTIP',
		),
		'funding' => array(
			'PAYPAL_CHECKOUT_ENABLE_FUNDING',
			'checkbox',
			array(
				'card' =>'Credit or debit cards',
				'credit' => 'PayPal Credit (US, UK)',
				'paylater' => 'Pay Later (US, UK), Pay in 4 (AU), 4X PayPal (France), Paga en 3 plazos (Spain), Paga in 3 rate (Italy), Später Bezahlen (Germany)',
				'venmo' => 'Venmo',
				'bancontact' => 'Bancontact',
				'blik' => 'BLIK',
				'eps' => 'eps',
				'ideal' => 'iDEAL (requires onboarding, contact PayPal)',
				'mercadopago' => 'Mercado Pago',
				'mybank' => 'MyBank',
				'p24' => 'Przelewy24',
				'sepa' => 'SEPA-Lastschrift',
				'multibanco' => 'Multibanco',
				'trustly' => 'Trustly',
			),
			'tooltip' => 'PAYPAL_CHECKOUT_ENABLE_FUNDING_TOOLTIP',
		),
		'enable_applepay' => array('ENABLE_APPLE_PAY', 'boolean', 0),
		'enable_googlepay' => array('ENABLE_GOOGLE_PAY', 'boolean', 0),
		'country_code' => array('COUNTRY_CODE_FOR_GOOGLE_PAY', 'input', 'US'),
		'layout' => array('PAYPAL_CHECKOUT_BUTTON_LAYOUT', 'list', array(
				'vertical' => 'VERTICAL',
				'horizontal' => 'HORIZONTAL',
			),
		),
		'color' => array('PAYPAL_CHECKOUT_BUTTON_COLOR', 'list', array(
				'gold' => 'PAYPAL_CHECKOUT_GOLD',
				'blue' => 'PAYPAL_CHECKOUT_BLUE',
				'silver' => 'PAYPAL_CHECKOUT_SILVER',
				'white' => 'PAYPAL_CHECKOUT_WHITE',
				'black' => 'PAYPAL_CHECKOUT_BLACK',
			),
		),
		'shape' => array('PAYPAL_CHECKOUT_BUTTON_SHAPE', 'list', array(
				'rect' => 'PAYPAL_CHECKOUT_RECTANGLE',
				'pill' => 'PAYPAL_CHECKOUT_PILL',
			),
		),
		'label' => array('PAYPAL_CHECKOUT_BUTTON_LABEL', 'list', array(
				'paypal' => 'PayPal',
				'checkout' => 'PayPal Checkout',
				'buynow' => 'PayPal Buy Now',
				'pay' => 'Pay with PayPal',
			),
		),
		'tagline' => array('PAYPAL_CHECKOUT_BUTTON_TAGLINE', 'boolean', 1),
		'listing_position' => array('PAYPAL_CHECKOUT_PAY_LATER_MESSAGING_ON_PRODUCT_LISTINGS', 'list', array(
				'' => 'HIKASHOP_NO',
				'top' => 'HIKA_TOP',
				'middle' => 'HIKA_MIDDLE',
				'bottom' => 'HIKA_BOTTOM',
			),
		),
		'product_page_position' => array('PAYPAL_CHECKOUT_PAY_LATER_MESSAGING_ON_PRODUCT_PAGE', 'list', array(
				'' => 'HIKASHOP_NO',
				'topBegin' => 'TOP_BEGIN',
				'topEnd' => 'TOP_END',
				'leftBegin' => 'LEFT_BEGIN',
				'leftEnd' => 'LEFT_END',
				'rightBegin' => 'RIGHT_BEGIN',
				'rightMiddle' => 'RIGHT_MIDDLE',
				'rightEnd' => 'RIGHT_END',
				'bottomBegin' => 'BOTTOM_BEGIN',
				'bottomMiddle' => 'BOTTOM_MIDDLE',
				'bottomEnd' => 'BOTTOM_END',
			),
		),
		'cart_page_position' => array('PAYPAL_CHECKOUT_PAY_LATER_MESSAGING_ON_CART_PAGE', 'list', array(
				'' => 'HIKASHOP_NO',
				'bottom' => 'HIKA_BOTTOM',
			),
		),
		'checkout_page_position' => array('PAYPAL_CHECKOUT_PAY_LATER_MESSAGING_ON_CHECKOUT_CART', 'list', array(
				'' => 'HIKASHOP_NO',
				'top' => 'HIKA_TOP',
				'bottom' => 'HIKA_BOTTOM',
			),
		),
		'paylater_messaging_color' => array('PAYPAL_CHECKOUT_PAY_LATER_MESSAGING_COLOR', 'list', array(
				'black' => 'PAYPAL_CHECKOUT_BLACK',
				'white' => 'PAYPAL_CHECKOUT_WHITE',
				'monochrome' => 'PAYPAL_CHECKOUT_MONOCHROME',
				'grayscale' => 'PAYPAL_CHECKOUT_GRAYSCALE',
			),
		),
		'standard_options' => array('STANDARD_OPTIONS', 'title'),
		'details' => array('SEND_DETAILS_OF_ORDER', 'boolean', 1),

		'language' => array('PAYPAL_LANGUAGE', 'list', array(
				'auto' => 'HIKA_AUTOMATIC',
				'website' => 'USE_WEBSITE_LANGUAGE',
			),
		),
		'debug' => array('DEBUG', 'boolean', 0),
		'cancel_url' => array('CANCEL_URL', 'input'),
		'return_url' => array('RETURN_URL', 'input'),
		'invalid_status' => array('INVALID_STATUS', 'orderstatus'),
		'pending_status' => array('PENDING_STATUS', 'orderstatus'),
		'verified_status' => array('VERIFIED_STATUS', 'orderstatus'),
	);

	function __construct(&$subject, $config) {
		return parent::__construct($subject, $config);
	}

	private function getApiClient() {
		if(!empty($this->_apiClient))
			return $this->_apiClient;
		require_once __DIR__ . '/paypalcheckout_api.php';
		$this->_apiClient = new PaypalCheckoutAPI(
			$this->payment_params->client_id,
			$this->payment_params->client_secret,
			!empty($this->payment_params->sandbox),
			$this->bncode,
			!empty($this->payment_params->debug)
		);
		return $this->_apiClient;
	}

	public function onHikashopBeforeDisplayView(&$view) {
		$app = JFactory::getApplication();
		if(version_compare(JVERSION,'4.0','<'))
			$admin = $app->isAdmin();
		else
			$admin = $app->isClient('administrator');
		if($admin)
			return;
		$viewName = $view->getName();
		$layout = $view->getLayout();

		if($viewName == 'product' && $layout == 'listing' && hikaInput::get()->getVar('hikashop_front_end_main', 0) && hikaInput::get()->getVar('task') == 'listing') {
			$this->processListing($view);
		}
		if($viewName == 'product' && $layout == 'show') {
			$this->processDetailsPage($view);
		}
	}

	public function onHikashopAfterDisplayView(&$view) {
		$app = JFactory::getApplication();
		if(version_compare(JVERSION,'4.0','<'))
			$admin = $app->isAdmin();
		else
			$admin = $app->isClient('administrator');
		if($admin)
			return;
		$viewName = $view->getName();
		$layout = $view->getLayout();

		if($viewName == 'cart' && $layout == 'show') {
			$this->processCart($view);
		}
	}
	public function onBeforeCheckoutViewDisplay($layout, &$view) {
		if($layout != 'cart')
			return;

		$method = $this->getPaymentMethod();
		if(!$method) {
			return;
		}
		$position = $this->getPosition($method, 'listing');
		if(empty($position))
			return;

		$data = $this->getMessagingHTML(0, 'payment', $method);

		if(!isset($view->extraData))
			$view->extraData = array();

		if(!isset($view->extraData[$view->module_position]))
			$view->extraData[$view->module_position] = new stdClass();

		if(!isset($view->extraData[$view->module_position]->$position))
			$view->extraData[$view->module_position]->$position = array();
		array_push($view->extraData[$view->module_position]->$position, $data);
	}

	private function processListing(&$view) {

		$method = $this->getPaymentMethod();
		if(!$method) {
			return;
		}
		$position = $this->getPosition($method, 'listing');
		if(empty($position))
			return;

		$data = $this->getMessagingHTML(0, 'category', $method);

		if(!isset($view->element->extraData))
			$view->element->extraData = new stdClass();

		if(!isset($view->element->extraData->$position))
			$view->element->extraData->$position = array();
		array_push($view->element->extraData->$position, $data);
	}

	private function processCart(&$view) {
		if(empty($view->cart->total->prices)) {
			return;
		}

		$method = $this->getPaymentMethod();
		if(!$method) {
			return;
		}
		$position = $this->getPosition($method, 'cart');
		if(empty($position))
			return;

		$price_value = 'price_value';
		if($view->config->get('price_with_tax')) {
			$price_value = 'price_value_with_tax';
		}
		$mainPrice = reset($view->cart->total->prices);

		if(empty($mainPrice->$price_value) || $mainPrice->$price_value <= 0) {
			return;
		}

		$data = $this->getMessagingHTML($mainPrice->$price_value, 'cart', $method);

		echo $data;
	}

	private function processDetailsPage(&$view) {
		if(empty($view->element->prices)) {
			return;
		}
		$method = $this->getPaymentMethod();
		if(!$method) {
			return;
		}
		$position = $this->getPosition($method, 'product_page');
		if(empty($position))
			return;

		$price_value = 'price_value';
		if($view->params->get('price_with_tax')) {
			$price_value = 'price_value_with_tax';
		}
		$mainPrice = reset($view->element->prices);

		if(empty($mainPrice->$price_value) || $mainPrice->$price_value <= 0) {
			return;
		}

		$data = $this->getMessagingHTML($mainPrice->$price_value, 'product', $method);

		if(!isset($view->element->extraData))
			$view->element->extraData = new stdClass();

		if(!isset($view->element->extraData->$position))
			$view->element->extraData->$position = array();
		array_push($view->element->extraData->$position, $data);
	}

	private function getMessagingHTML($amount, $type, &$method) {
		static $currency = null;
		if($currency == null) {
			$currency_id = hikashop_getCurrency();
			$currencyClass = hikashop_get('class.currency');
			$currencyObj = $currencyClass->get($currency_id);
			$currency = $currencyObj->currency_code;
		}

		$extra = '';
		if($type == 'product') {
			$extra = '
				<script>
					window.Oby.registerAjax(["hkContentChanged"], function(params){
						var form  = document.querySelector(\'form[name="hikashop_product_form"]\');
						if(!form)
							return;
						var formData = window.Oby.getFormData(form, false);

						var price = null;
						var priceInput = document.getElementById("hikashop_price_product_" + formData.product_id);
						if(priceInput) {
							price = priceInput.value;
						}
						var priceWithOptionsInput = document.getElementById("hikashop_price_product_with_options_" + formData.product_id);
						if(priceWithOptionsInput) {
							price = priceWithOptionsInput.value;
						}
						if(price) {
							price = (Math.round(price * 100) / 100).toFixed(2);
							var paypalDiv  = document.querySelector("div[data-pp-message]");
							paypalDiv.setAttribute("data-pp-amount", price);
						}
					});
				</script>
			';
		}

		$attribs = '';
		if(!empty($method->payment_params->paylater_messaging_color)) {
			$attribs.=' data-pp-style-text-color="'.$method->payment_params->paylater_messaging_color.'"';
		}
		$url = 'https://www.paypal.com';
		$amount = number_format(round((float)$amount,2), 2, '.', '');
		return '<div data-pp-message
		data-pp-placement="'.$type.'" 
		data-pp-amount="'.$amount.'" 
		data-pp-currency="'.$currency.'" 
		'.$attribs.'
		></div>'.$extra.'
		<script src="'.$url.'/sdk/js?client-id='.$method->payment_params->client_id.'&components=messages&currency='.$currency.'" data-partner-attribution-id="'.$this->bncode.'"></script>
';
	}

	private function getPosition(&$method, $type) {
		if(!empty($method->payment_params->funding)) {
			if(is_string($method->payment_params->funding)) {
				$fundings = explode(',', $method->payment_params->funding);
			} else {
				$fundings = $method->payment_params->funding;
			}
			if(!in_array('paylater', $fundings))
				return '';
		}
		$var = $type.'_position';
		if(!empty($method->payment_params->$var)) {
			return $method->payment_params->$var;
		}
		return '';
	}

	private function getPaymentMethod() {
		static $method = null;
		if(is_null($method)) {
			$class = hikashop_get('class.cart');
			$cart = $class->getFullCart();
			if(!empty($cart->usable_methods->payment)) {
				foreach($cart->usable_methods->payment as $payment) {
					if($payment->payment_type == $this->name) {
						$method = $payment;
						break;
					}
				}
			}
			if(is_null($method))
				$method = $this->loadFirstPaymentMethodFound();
		}

		if(empty($method->payment_params->client_id))
			$method = false;

		return $method;
	}

	private function loadFirstPaymentMethodFound() {
		static $result = null;
		if(!is_null($result))
			return $result;

		$db = JFactory::getDBO();
		$where = array('payment_type = '.$db->Quote($this->name),'payment_published=\'1\'');
		$currency = hikashop_getCurrency();
		if(!empty($currency)){
			$where[] = "(payment_currency IN ('','_','all') OR payment_currency LIKE '%,".intval($currency).",%')";
		}
		hikashop_addACLFilters($where,'payment_access');
		$db->setQuery('SELECT * FROM `#__hikashop_payment` WHERE '.implode(' AND ',$where).' ORDER BY payment_ordering ASC');
		$result = $db->loadObject();

		if(empty($result))
			$result = false;

		if(!empty($result->payment_params)) {
			$result->payment_params = hikashop_unserialize($result->payment_params);
		}
		if(!empty($result->payment_name))
			$result->payment_name = hikashop_translate($result->payment_name);
		if(!empty($result->payment_description))
			$result->payment_description = hikashop_translate($result->payment_description);
		return $result;
	}

	public function onBeforeOrderCreate(&$order,&$do){
		if(parent::onBeforeOrderCreate($order, $do) === true)
			return true;

		if((empty($this->payment_params->client_id) || empty($this->payment_params->client_secret)) && $this->plugin_data->payment_id == $order->order_payment_id) {
			$this->app->enqueueMessage('Please check your &quot;PayPal Checkout&quot; payment method configuration');
			$do = false;
		}
	}

	public function onAfterOrderUpdate(&$order,&$do){
		if(empty($order->order_status))
			return;

		$payment_method = $order->old->order_payment_method;
		if(!empty($order->order_payment_method)) {
			$payment_method = $order->order_payment_method;
		}
		if($payment_method != $this->name)
			return true;

		$order_type = $order->old->order_type;
		if(!empty($order->order_type)) {
			$order_type = $order->order_type;
		}

		if($order_type != 'sale')
			return true;

		$order_payment_id = $order->old->order_payment_id;
		if(!empty($order->order_payment_id)) {
			$order_payment_id = $order->order_payment_id;
		}

		$this->payment_params = null;
		if(!empty($order_payment_id) && $this->pluginParams($order_payment_id))
			$this->payment_params =& $this->plugin_params;

		if(empty($this->payment_params)) {
			return;
		}
		if(!empty($this->payment_params->capture)) {
			return;
		}

		if($order->order_status == $this->payment_params->verified_status && $order->old->order_status == $this->payment_params->pending_status) {
			$this->processCaptureorder($order);
		}
	}


	public function onAfterOrderConfirm(&$order, &$methods, $method_id) {
		parent::onAfterOrderConfirm($order, $methods, $method_id);

		$this->loadJS();

		$this->notify_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment='.$this->name.'&order_id='.$order->order_id.'&tmpl=component&lang='.$this->locale . $this->url_itemid;
		$this->cancel_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id='.$order->order_id . $this->url_itemid;
		$this->orderData = $this->getOrderData($order);

		$this->currencyClass = hikashop_get('class.currency');
		$this->total_amount = $this->currencyClass->format($order->order_full_price, $order->order_currency_id);

		if(!empty($this->payment_params->debug)) {
			hikashop_writeToLog($this->orderData);
			hikashop_writeToLog($this->params);
		}

		if(!empty($this->payment_params->enable_credit_card)) {
			$this->_loadCountries();
		}

		return $this->showPage('end');

	}

	private function _loadCountries() {
		$db = JFactory::getDBO();
		$query = 'SELECT zone_code_2, zone_name, zone_name_english FROM `#__hikashop_zone` WHERE zone_type = \'country\' AND zone_published = 1 ORDER BY zone_ordering ASC, zone_name_english ASC';
		$db->setQuery($query);
		$countries = $db->loadObjectList();
		$this->countries = array();
		foreach($countries as $country) {
			$name = $country->zone_name_english;
			if($country->zone_name != $country->zone_name_english) {
				$name .= ' ('.$country->zone_name.')';
			}
			$this->countries[$country->zone_code_2] = $name;
		}
	}

	public function onPaymentConfiguration(&$element) {
		parent::onPaymentConfiguration($element);

		if(empty($element->payment_params->client_id) || empty($element->payment_params->client_secret)) {
			if(empty($element->payment_params->sandbox)) {
				$element->payment_params->sandbox = 0;
			}			
			$this->pluginConfig['connect'][2] = $this->getConnectButtonHTML($element->payment_params->sandbox);
		} else {
			$this->pluginConfig['connect'][2] = $this->checkMerchantStatus($element);
			$this->pluginConfig['connect'][2] .= $this->getChangeLinkedAccountHTML();
		}
		$config = hikashop_config();
		$round_calculations = $config->get('round_calculations');
		if(!isset($element->payment_params->details)) {
			$element->payment_params->details = 1;
		}
		if(empty($round_calculations) && !empty($element->payment_params->details)){
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('PAYPAL_CHECKOUT_ROUND_PRICES_WARNING'));
		}

		if(empty($element->payment_params->client_id)) {
			$app = JFactory::getApplication();
			$lang = JFactory::getLanguage();
			$locale = strtolower(substr($lang->get('tag'), 0, 2));
			$app->enqueueMessage(JText::sprintf('ENTER_INFO_REGISTER_IF_NEEDED', 'PayPal Checkout', JText::_('PAYPAL_CHECKOUT_CLIENT_ID'), 'PayPal', 'https://www.paypal.com/' . $locale . '/webapps/mpp/account-selection'));
		}
		if(empty($element->payment_params->client_secret)) {
			$app = JFactory::getApplication();
			$lang = JFactory::getLanguage();
			$locale = strtolower(substr($lang->get('tag'), 0, 2));
			$app->enqueueMessage(JText::sprintf('ENTER_INFO_REGISTER_IF_NEEDED', 'PayPal Checkout', JText::_('PAYPAL_CHECKOUT_CLIENT_SECRET'), 'PayPal', 'https://www.paypal.com/' . $locale . '/webapps/mpp/account-selection'));
		}

	}

	public function checkMerchantStatus(&$element) {
		if(empty($element->payment_params->merchant_id)) {
			return '';
		}
		$merchantId = !empty($element->payment_params->sandbox) ? $this->sandboxMerchantId : $this->liveMerchantId;

		try {
			require_once __DIR__ . '/paypalcheckout_api.php';
			$api = new PaypalCheckoutAPI(
				$element->payment_params->client_id,
				$element->payment_params->client_secret,
				!empty($element->payment_params->sandbox),
				$this->bncode
			);
			$response = $api->request('GET', '/v1/customer/partners/' . $merchantId . '/merchant-integrations/' . $element->payment_params->merchant_id);
		} catch(Exception $e) {
			return hikashop_display($e->getMessage(), 'error', true);
		}

		if(empty($response))
			return hikashop_display('Returned data from merchant status request not valid', 'error', true);

		$array = json_decode(json_encode($response), true);

		$prod_ok_for_ACDC = false;
		$cap_ok_for_ACDC = false;
		$limit = false;
		$withdraw_ok = false;
		$send_ok = false;
		$in_review = false;
		$need_more_data = false;
		$denied = false;
		if(!empty($array['products'])) {
			foreach($array['products'] as $product) {
				if($product['name'] == 'PPCP_CUSTOM' && $product['vetting_status'] == 'SUBSCRIBED') {
					$prod_ok_for_ACDC = true;
				}
				if($product['name'] == 'PPCP_CUSTOM' && $product['vetting_status'] == 'NEED_MORE_DATA') {
					$need_more_data = true;
				}
				if($product['name'] == 'PPCP_CUSTOM' && $product['vetting_status'] == 'IN_REVIEW') {
					$in_review = true;
				}
				if($product['name'] == 'PPCP_CUSTOM' && $product['vetting_status'] == 'DENIED') {
					$denied = true;
				}
			}
		}
		if(!empty($array['capabilities'])) {
			foreach($array['capabilities'] as $capability) {
				if($capability['name'] == 'CUSTOM_CARD_PROCESSING' && $capability['status'] == 'ACTIVE') {
					$cap_ok_for_ACDC = true;
					if(!empty($capability['limits']) && !empty($capability['limits'][0]['type']) && $capability['limits'][0]['type'] == 'GENERAL') {
						$limit = true;
					}
				}
				if($capability['name'] == 'WITHDRAW_MONEY' && !empty($capability['limits'])) {
					$withdraw_ok = true;
				}
				if($capability['name'] == 'SEND_MONEY' && !empty($capability['limits'])) {
					$send_ok = true;
				}
			}
		}
		if(!$prod_ok_for_ACDC || !$cap_ok_for_ACDC) {
			$this->pluginConfig['enable_credit_card'] = array('', 'content');
			$current_3ds = !empty($element->payment_params->enable_3dsecure) ? $element->payment_params->enable_3dsecure : '';
			$this->pluginConfig['enable_3dsecure'] = array('<input type="hidden" name="data[payment][payment_params][enable_3dsecure]" value="'.htmlspecialchars($current_3ds, ENT_COMPAT, 'UTF-8').'" />', 'content');
		} else {
			if(empty($element->payment_params->enable_credit_card)) {
				$current_3ds = !empty($element->payment_params->enable_3dsecure) ? $element->payment_params->enable_3dsecure : '';
				$this->pluginConfig['enable_3dsecure'] = array('<input type="hidden" name="data[payment][payment_params][enable_3dsecure]" value="'.htmlspecialchars($current_3ds, ENT_COMPAT, 'UTF-8').'" />', 'content');
			}
			if(!empty($element->payment_params->enable_3dsecure) && $element->payment_params->enable_3dsecure != 'NO') {
				$this->pluginConfig['info_3dsecure'] = array(JText::_('INFO_3DSSECURE'), 'content');
			}
			if($limit && !$withdraw_ok && !$send_ok) {
				$this->pluginConfig['credit_card_limits'][0] .= JText::_('MORE_INFO_FOR_ACDC_WITH_LIMIT');
			}
			if($limit && $withdraw_ok && $send_ok) {
				$this->pluginConfig['credit_card_limits'][0] .= JText::_('MORE_INFO_FOR_ACDC_OVER_LIMIT');
			}
		}
		if($need_more_data) {
			$this->pluginConfig['credit_card_limits'][0] .= JText::_('MORE_INFO_FOR_ACDC_OVER_LIMIT');
		}
		if($in_review) {
			$this->pluginConfig['credit_card_limits'][0] .= JText::_('IN_REVIEW_FOR_ACDC');
		}
		if($denied) {
			$this->pluginConfig['credit_card_limits'][0] .= JText::_('DENIED_FOR_ACDC');
		}

		if(empty($array['primary_email_confirmed'])) {
			$msg = JText::_('PAYPAL_CHECKOUT_PRIMARY_EMAIL_NOT_CONFIRMED');
			if(!empty($array['message'])) {
				$msg = $array['message'] .'<br/>'. $msg;
			}
			return hikashop_display($msg,'error',true);
		}

		if(empty($array['payments_receivable'])) {
			$msg = JText::_('PAYPAL_CHECKOUT_PAYMENTS_NOT_RECEIVABLE');
			if(!empty($array['message'])) {
				$msg = $array['message'] .'<br/>'. $msg;
			}
			return hikashop_display($msg,'error',true);
		}
		return hikashop_display(JText::_('PAYPAL_CHECKOUT_SUCCESSFULLY_CONNECTED'),'success',true);
	}

	private function getChangeLinkedAccountHTML() {
		return '
		<script>
			window.hikashop.changeLinkedAccount = function() {
				var clientId = document.getElementById(\'data_payment_payment_params_client_id\');
				var clientSecret = document.getElementById(\'data_payment_payment_params_client_secret\');
				var merchantId = document.getElementById(\'data_payment_payment_params_merchant_id\');
				clientId.value = \'\';
				clientSecret.value = \'\';
				merchantId.value = \'\';

				Joomla.submitbutton(\'apply\');
			}
		</script>
		<a href="#" onclick="window.hikashop.changeLinkedAccount();return false;" class="hikabtn hikabtn-primary">'.JText::_('PAYPAL_CHECKOUT_CHANGE_LINKED_ACCOUNT').'</a>
		';
	}

	public function getConnectButtonHTML($sandbox = false, $callback = '') {
		hikashop_loadJslib('notify');
		$nonce = substr(hash('sha512',mt_rand()),17,70);
		$AJAX_URL = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment='.$this->name.'&onboarding=1&nonce='.urlencode($nonce).'&tmpl=component';
		$partnerClientId = $this->livePartnerClientId;
		$merchantId = $this->liveMerchantId;
		$url = 'https://www.paypal.com';
		if(empty($sandbox)) {
			$sandbox = 0;
		}
		if(!empty($sandbox)) {
			$partnerClientId = $this->sandboxPartnerClientId;
			$merchantId = $this->sandboxMerchantId;
			$url = 'https://www.sandbox.paypal.com';
		}

		$closeURL = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment='.$this->name.'&closepopup=1&tmpl=component';
		$params = array(
			'partnerId' => $merchantId,
			'product' => 'ppcp',
			'integrationType' => 'FO',
			'features' => 'PAYMENT',
			'partnerClientId' => $partnerClientId,
			'partnerLogoUrl' => 'https://www.hikashop.com/images/branding/hikashop_logo1.png',
			'displayMode' => 'minibrowser',
			'sellerNonce' => $nonce,
		);
		if(empty($callback)) {
			$js = 'Joomla.submitbutton(\'apply\');';
			$selector = '#data_payment_payment_params_sandbox input';
		} else {
			$js = $callback.'();';
			$selector = '#sanbox input';
		}

		$doc = JFactory::getDocument();
		$doc->addScript('https://www.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js');
		$html = '
		<a
			id="paypal_connect"
			class="direct"
			target="_blank"
			data-paypal-onboard-complete="onboardedCallback"
			href="'.$url.'/bizsignup/partner/entry?'.http_build_query($params).'"
			data-paypal-button="PPLtBlue"
			>
			'.JText::_('PAYPAL_CHECKOUT_CONNECT_TO_PAYPAL_CHECKOUT').'
		</a>
		<script>
			function onboardedCallback(authCode, sharedId) {
				var callbackURL = \''.$AJAX_URL.'\';
				if(window.hikashop.isPayPalSandbox==1) {
					callbackURL += \'&sandbox=1\';
				}
				callbackURL += \'&authCode=\' + authCode + \'&sharedId=\' + sharedId;
				window.Oby.xRequest(callbackURL,  {mode:\'POST\'}, function(xhr){
					var resp = window.Oby.evalJSON(xhr.responseText);

					console.log(callbackURL);
					console.log(resp);

					if(resp && resp.error) {
						jQuery(document.getElementById(\'paypal_connect\')).notify({title:resp.errorTitle, text:resp.errorMessage, image:\'<i class=\"fas fa-3x fa-file-invoice\"></i>\', globalPosition:\'top right\'},{style:"metro",className:\'error\', autoHide: false, arrowShow:true});
					} else {
						var clientId = document.getElementById(\'data_payment_payment_params_client_id\');
						if(!clientId)
							clientId =  document.querySelector(\'input[name="data[payment][payment_params][client_id]"]\');
						var clientSecret = document.getElementById(\'data_payment_payment_params_client_secret\');
						if(!clientSecret)
							clientSecret =  document.querySelector(\'input[name="data[payment][payment_params][client_secret]"]\');
						var merchantId = document.getElementById(\'data_payment_payment_params_merchant_id\');
						if(!merchantId)
							merchantId =  document.querySelector(\'input[name="data[payment][payment_params][merchant_id]"]\');
						clientId.value = resp.clientId;
						clientSecret.value = resp.clientSecret;
						merchantId.value = resp.merchantId;

						'.$js.'
					}
				});
			}
			window.hikashop.changeSandbox = function(sandbox) {
				window.hikashop.isPayPalSandbox = sandbox;

				var src = \'https://www.paypal\';
				var dst = \'https://www.paypal\';
				if(sandbox==1) {
					dst = \'https://www.sandbox.paypal\';
				} else {
					src = \'https://www.sandbox.paypal\';
				}
				document.getElementById(\'paypal_connect\').href = document.getElementById(\'paypal_connect\').href.replace(src, dst);

				if(sandbox==1) {
					src = \''.$this->livePartnerClientId.'\';
					dst = \''.$this->sandboxPartnerClientId.'\';
				} else {
					src = \''.$this->sandboxPartnerClientId.'\';
					dst = \''.$this->livePartnerClientId.'\';
				}
				document.getElementById(\'paypal_connect\').href = document.getElementById(\'paypal_connect\').href.replace(src, dst);

				if(sandbox==1) {
					src = \''.$this->liveMerchantId.'\';
					dst = \''.$this->sandboxMerchantId.'\';
				} else {
					src = \''.$this->sandboxMerchantId.'\';
					dst = \''.$this->liveMerchantId.'\';
				}
				document.getElementById(\'paypal_connect\').href = document.getElementById(\'paypal_connect\').href.replace(src, dst);


			}
			window.hikashop.setSandboxFlag = function() {
				document.querySelectorAll(\''.$selector.'\').forEach((elem) => {
					if(elem.checked) {
						window.hikashop.changeSandbox(elem.value);
					}
				});
			}

			window.hikashop.ready( function() {
				window.hikashop.isPayPalSandbox = '.(int)empty($sandbox).';
				window.hikashop.setSandboxFlag();
				document.querySelectorAll(\''.$selector.'\').forEach((elem) => {
					elem.addEventListener("click", function(event) {
						window.hikashop.changeSandbox(event.target.value);
					});
				});
				(function(d, s, id) {
					var js, ref = d.getElementsByTagName(s)[0];
					if (!d.getElementById(id)) {
						js = d.createElement(s);
						js.id = id;
						js.async = true;
						js.src = "https://www.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js";
						ref.parentNode.insertBefore(js, ref);
					}
				}(document, "script", "paypal-js"));
			});
		</script>
		';
		return $html;
	}

	private function processCreateorder() {
		$result = new stdClass();
		$result->errorTitle = JText::_('PAYPAL_CHECKOUT_ERROR_OCCURRED');
		$result->errorMessage = '';
		$result->error = false;

		$order_id = hikaInput::get()->getInt('order_id');
		if(empty($order_id)) {
			$result->error = true;
			$result->errorMessage = 'Order id is missing';
			return $result;
		}

		$dbOrder = $this->getOrder($order_id);
		$this->loadPaymentParams($dbOrder);
		if(empty($this->payment_params)) {
			$result->error = true;
			$result->errorMessage = 'Params could not be loaded for payment method';
			return $result;
		}

		$orderClass = hikashop_get('class.order');
		$order = $orderClass->loadFullOrder($order_id);
		if(empty($order)) {
			$result->error = true;
			$result->errorMessage = 'Order could not be loaded';
			return $result;
		}
		$this->loadOrderData($order);

		$userClass = hikashop_get('class.user');
		$order->customer = $userClass->get($order->order_user_id);
		$order->cart =& $order;

		$order_taxes = $order->order_tax_info;
		if(is_string($order_taxes))
			$order_taxes = hikashop_unserialize($order_taxes);
		$order_full_tax = 0;
		if(!empty($order_taxes)){
			foreach($order_taxes as $tax) {
				$order_full_tax += $tax->tax_amount;
			}
		}
		$order_full_tax += $order->order_shipping_tax + $order->order_payment_tax - $order->order_discount_tax;

		$price = new stdClass();
		$price->price_value_with_tax = $order->order_full_price;
		$price->price_value = $order->order_full_price-$order_full_tax;
		$order->cart->full_total = new stdClass();
		$order->cart->full_total->prices = array($price);
		$order->cart->total = new stdClass();

		$config = hikashop_config();
		if($config->get('group_options',0)){
			foreach($order->cart->products as $k => $product){
				if(!empty($product->order_product_option_parent_id)){
					foreach($order->cart->products as $k2 => $product2){
						if($product->order_product_option_parent_id == $product2->order_product_id){
							$product2->order_product_price += $product->order_product_price;
							$product2->order_product_tax += $product->order_product_tax;
							$product2->order_product_total_price_no_vat += $product->order_product_total_price_no_vat;
							$product2->order_product_total_price += $product->order_product_total_price;
						}
					}
				}
			}
		}

		$currencyClass = hikashop_get('class.currency');
		$currencyClass->calculateTotal($order->cart->products, $order->cart->total, $order->order_currency_id);
		if(bccomp(sprintf('%F',$order->order_discount_price), 0, 5) !== 0) {
			$order->cart->coupon = new stdClass();
			$order->cart->coupon->discount_value =& $order->order_discount_price;
		}

		try {
			$api = $this->getApiClient();
			$response = $api->createOrder($this->getOrderData($order));
			if(empty($response->id)) {
				$result->error = true;
				hikashop_writeToLog('order creation failed.');
				if(!empty($response->status)) {
					$result->errorMessage = $response->status;
				} else {
					$result->errorMessage = 'Unknown error when creating order';
				}
				hikashop_writeToLog($response);
				return $result;
			}
			$result->id = $response->id;

			if(!empty($response->status) && $response->status === 'PAYER_ACTION_REQUIRED' && !empty($response->links)) {
				foreach($response->links as $link) {
					if($link->rel === 'payer-action') {
						$result->redirect = $link->href;
						break;
					}
				}
				$orderClass = hikashop_get('class.order');
				$updateOrder = new stdClass();
				$updateOrder->order_id = $order->order_id;
				$updateOrder->order_payment_params = new stdClass();
				$updateOrder->order_payment_params->paypal_checkout_id = $response->id;
				$orderClass->save($updateOrder);
			}

			return $result;
		} catch(Exception $e) {
			$result->error = true;
			$result->errorMessage = $e->getMessage();
			hikashop_writeToLog('order creation failed: ' . $result->errorMessage);
			return $result;
		}
	}
	private function processCaptureorder(&$order) {
		if(empty($order->old->order_payment_params->paypal_checkout_id)) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('PAYPAL_CHECKOUT_ERROR_ORDER_ID_MISSING'), 'error');

			if($this->payment_params->debug) {
				hikashop_writeToLog('PayPal checkout error order id missing for order '.$order->order_id);
			}
			return false;
		}

		if($this->payment_params->debug) {
			hikashop_writeToLog('capture order with paypal id  '.$order->old->order_payment_params->paypal_checkout_id);
		}

		try {
			$api = $this->getApiClient();

			$response = $api->getOrder($order->old->order_payment_params->paypal_checkout_id);
			if(empty($response->id)) {
				$app = JFactory::getApplication();
				$app->enqueueMessage(!empty($response->status) ? $response->status : 'Unknown error when capturing order', 'error');
				hikashop_writeToLog($response);
				return false;
			}

			$authorization_id = $response->purchase_units[0]->payments->authorizations[0]->id;

			if(!empty($response->purchase_units[0]->payments->authorizations[0]->expiration_time)) {
				$d = DateTime::createFromFormat('c', $response->purchase_units[0]->payments->authorizations[0]->expiration_time);
				if($d) {
					if($d->getTimestamp() <= time()) {
						$reauth = $api->reauthorize($authorization_id);
						if(empty($reauth->id)) {
							$app = JFactory::getApplication();
							$app->enqueueMessage(!empty($reauth->status) ? $reauth->status : 'Unknown error when reauthorizing', 'error');
							hikashop_writeToLog($reauth);
							return false;
						}
						$authorization_id = $reauth->id;
					}
				} else {
					$app = JFactory::getApplication();
					$app->enqueueMessage('Authorization date format unknown', 'error');
					hikashop_writeToLog($response);
				}
			}

			$captureResponse = $api->captureAuthorization($authorization_id);
			if(empty($captureResponse->id)) {
				$app = JFactory::getApplication();
				$app->enqueueMessage(!empty($captureResponse->status) ? $captureResponse->status : 'Unknown error when capturing order', 'error');
				hikashop_writeToLog($captureResponse);
				return false;
			}
			return true;
		} catch(Exception $e) {
			$app = JFactory::getApplication();
			$app->enqueueMessage($e->getMessage(), 'error');
			hikashop_writeToLog($e->getMessage());
			return false;
		}
	}

	private function processOnapprove() {
		$result = new stdClass();
		$result->errorTitle = JText::_('PAYPAL_CHECKOUT_ERROR_OCCURRED');
		$result->errorMessage = '';
		$result->errorCode = null;
		$result->error = false;

		$order_id = hikaInput::get()->getInt('order_id');
		if(empty($order_id)) {
			$result->error = true;
			$result->errorMessage = 'Order id is missing';
			return $result;
		}

		$paypal_id = hikaInput::get()->getString('orderID');
		if(empty($paypal_id)) {
			$result->error = true;
			$result->errorMessage = 'PayPal Order id is missing';
			return $result;
		}

		$dbOrder = $this->getOrder($order_id);
		$this->loadPaymentParams($dbOrder);
		if(empty($this->payment_params)) {
			$result->error = true;
			$result->errorMessage = 'Params could not be loaded for payment method';
			return $result;
		}

		$orderClass = hikashop_get('class.order');
		$order = $orderClass->loadFullOrder($order_id);
		if(empty($order)) {
			$result->error = true;
			$result->errorMessage = 'Order could not be loaded';
			return $result;
		}

		$updateOrder = new stdClass();
		$updateOrder->order_id = $order_id;
		$updateOrder->order_payment_params = new stdClass();
		$updateOrder->order_payment_params->paypal_checkout_id = $paypal_id;
		$orderClass->save($updateOrder);

		if($this->payment_params->debug) {
			hikashop_writeToLog('on approve for '.$paypal_id);
		}

		try {
			$api = $this->getApiClient();
			$action = 'proceed';

			$response = $api->getOrder($paypal_id);
			if(empty($response->id)) {
				$result->error = true;
				$result->errorMessage = !empty($response->status) ? $response->status : 'Unknown error when reading order';
				hikashop_writeToLog($response);
				return $result;
			}

			$paypalStatus = $response->status;

			if($paypalStatus == 'COMPLETED') {
				$result->id = $response->id;
				return $result;
			}

			if(in_array($paypalStatus, array('PAYER_ACTION_REQUIRED', 'PENDING_APPROVAL'))) {
				$result->id = $response->id;
				$result->pending = true;
				return $result;
			}

			$source = null;
			if(!empty($response->payment_source->card)) {
				if(!empty($this->payment_params->enable_3dsecure) && $this->payment_params->enable_3dsecure != 'NO') {
					$source = $response->payment_source->card;
				}
			}
			if(!empty($response->payment_source->apple_pay))
				$source = $response->payment_source->apple_pay->card;
			if(!empty($response->payment_source->google_pay))
				$source = $response->payment_source->google_pay->card;

			if($source && !empty($source->authentication_result)) {
				$liability_shift = @$source->authentication_result->liability_shift;
				$enrollment_status = @$source->authentication_result->three_d_secure->enrollment_status;
				$authentication_status = @$source->authentication_result->three_d_secure->authentication_status;

				$action = 'stop_and_retry';
				if($enrollment_status == 'Y') {
					if($authentication_status == 'Y' && in_array($liability_shift, array('POSSIBLE','YES')))
						$action = 'proceed';
					elseif(in_array($authentication_status, array('N','R')) && $liability_shift == 'NO')
						$action = 'stop_and_retry';
					elseif($authentication_status == 'A' && $liability_shift == 'POSSIBLE')
						$action = 'proceed';
					elseif($authentication_status == 'N' && $liability_shift == 'NO')
						$action = 'proceed';
				} elseif(in_array($enrollment_status, array('N','U','B')) && $liability_shift == 'NO') {
					$action = 'proceed';
				}
				if(!empty($response->payment_source->card->authentication_result)) {
					$updateOrder->order_payment_params = new stdClass();
					$updateOrder->order_payment_params->paypal_checkout_id = $paypal_id;
					$updateOrder->order_payment_params->authentication_result = $response->payment_source->card->authentication_result;
					$updateOrder->order_payment_params->authentication_action = $action;
					$orderClass->save($updateOrder);
				}
			}

			if($action != 'proceed') {
				$result->error = true;
				if($action == 'stop')
					$result->errorMessage = JText::_('PAYPAL_CHECKOUT_3DSECURE_STOP');
				elseif($action == 'stop_and_retry')
					$result->errorMessage = JText::_('PAYPAL_CHECKOUT_3DSECURE_STOP_AND_RETRY');
				$result->errorCode = $action;
				return $result;
			}

			if(!empty($this->payment_params->capture))
				$response = $api->captureOrder($paypal_id);
			else
				$response = $api->authorizeOrder($paypal_id);

			if(empty($response->id)) {
				$result->error = true;
				$result->errorMessage = !empty($response->status) ? $response->status : 'Unknown error when processing order';
				hikashop_writeToLog($response);
				return $result;
			}
			$result->id = $response->id;

			$this->_extractVaultToken($response, $order);

			return $result;
		} catch(Exception $e) {
			$result->error = true;
			$result->errorMessage = $e->getMessage();
			hikashop_writeToLog($result->errorMessage);
			return $result;
		}
	}

	private function processOnboarding() {
		$result = new stdClass();
		$result->errorTitle = JText::_('PAYPAL_CHECKOUT_ERROR_OCCURRED');
		$result->errorMessage = '';
		$result->error = false;

		$nonce = hikaInput::get()->getString('nonce');
		if(empty($nonce)) {
			$result->error = true;
			$result->errorMessage = 'Nonce is missing';
			return $result;
		}
		$authCode = hikaInput::get()->getString('authCode');
		if(empty($authCode)) {
			$result->error = true;
			$result->errorMessage = 'authCode is missing';
			return $result;
		}
		$sharedId = hikaInput::get()->getString('sharedId');
		if(empty($sharedId)) {
			$result->error = true;
			$result->errorMessage = 'sharedId is missing';
			return $result;
		}
		$sharedId = base64_encode($sharedId.':');

		$sandbox = hikaInput::get()->getInt('sandbox');
		$url = 'https://api-m.paypal.com';
		$merchantId = $this->liveMerchantId;
		if($sandbox) {
			$url = 'https://api-m.sandbox.paypal.com';
			$merchantId = $this->sandboxMerchantId;
		}

		require_once __DIR__ . '/paypalcheckout_api.php';
		$api = new PaypalCheckoutAPI('', '', $sandbox, $this->bncode);

		$post = 'grant_type=authorization_code&code=' . urlencode($authCode) . '&code_verifier=' . urlencode($nonce);
		$tokenResponse = $api->requestRaw('POST', $url . '/v1/oauth2/token', $post, array(
			'Content-Type: application/x-www-form-urlencoded',
			'Authorization: Basic ' . $sharedId,
		));
		if(empty($tokenResponse->access_token)) {
			$result->error = true;
			$result->errorMessage = 'Returned data from Acquire Seller Access does not contain access token';
			hikashop_writeToLog($tokenResponse);
			return $result;
		}
		$token = $tokenResponse->access_token;

		$credResponse = $api->requestRaw('GET', $url . '/v1/customer/partners/' . $merchantId . '/merchant-integrations/credentials', null, array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $token,
		));
		if(empty($credResponse->client_id)) {
			$result->error = true;
			$result->errorMessage = 'Returned data from Get Seller Credentials does not contain client ID';
			hikashop_writeToLog($credResponse);
			return $result;
		}
		if(empty($credResponse->client_secret)) {
			$result->error = true;
			$result->errorMessage = 'Returned data from Get Seller Credentials does not contain client secret';
			hikashop_writeToLog($credResponse);
			return $result;
		}
		if(empty($credResponse->payer_id)) {
			$result->error = true;
			$result->errorMessage = 'Returned data from Get Seller Credentials does not contain payer ID';
			hikashop_writeToLog($credResponse);
			return $result;
		}

		$result->clientId = $credResponse->client_id;
		$result->clientSecret = $credResponse->client_secret;
		$result->merchantId = $credResponse->payer_id;

		return $result;
	}

	private function runProcess($name) {
		ob_start();
		$result = $this->$name();
		$output = ob_get_clean();
		if(!empty($ouput)) {
			hikashop_writeToLog($output);
		}
		echo json_encode($result);
	}

	public function onPaymentNotification(&$statuses) {
		$createorder = hikaInput::get()->getInt('createorder');
		if($createorder) {
			$this->runProcess('processCreateorder');
			exit;
		}
		$onapprove = hikaInput::get()->getInt('onapprove');
		if($onapprove) {
			$this->runProcess('processOnapprove');
			exit;
		}
		$onboarding = hikaInput::get()->getInt('onboarding');
		if($onboarding) {
			$this->runProcess('processOnboarding');
			exit;
		}
		$closepopup = hikaInput::get()->getInt('closepopup');
		if($closepopup) {
			echo "
			<script>
			window.close();
			</script>
			";
			exit;
		}

		$order_id = hikaInput::get()->getInt('order_id');
		$paypal_id = hikaInput::get()->getString('paypal_id');

		if(empty($paypal_id))
			$paypal_id = hikaInput::get()->getString('token');

		if(empty($paypal_id)) {
			hikashop_writeToLog('paypal_id missing !');
			hikashop_writeToLog($order_id);
			return false;
		}

		$dbOrder = $this->getOrder($order_id);
		$this->loadPaymentParams($dbOrder);
		if(empty($this->payment_params))
			return false;

		$this->loadOrderData($dbOrder);

		if($this->payment_params->debug) {
			hikashop_writeToLog($dbOrder);
			hikashop_writeToLog($paypal_id);
		}

		try {
			$api = $this->getApiClient();
			$response = $api->getOrder($paypal_id);

			if(!empty($response->status) && $response->status === 'APPROVED' && !empty($this->payment_params->capture)) {
				$captureResponse = $api->captureOrder($paypal_id);
				if(!empty($captureResponse->id)) {
					$this->_extractVaultToken($captureResponse, $dbOrder);
					$response = $api->getOrder($paypal_id);
				}
			}

			$ok = $this->checkResponse($response, $dbOrder);
		} catch(Exception $e) {
			hikashop_writeToLog($e->getMessage());
		}

		$pending = hikaInput::get()->getInt('pending');

		if(!empty($ok)) {
			$history = new stdClass();
			$history->notified = 1;
			$history->amount = @$ok->amount->value.@$ok->amount->currency_code;
			$history->data = 'PayPal transaction id:'.$paypal_id;
			$status = $this->payment_params->verified_status;
			if($response->intent == 'AUTHORIZE' && in_array($response->status, array('APPROVED', 'COMPLETED'))) {
				$status = $this->payment_params->pending_status;
				$history->notified = 0;
			}

			if($this->payment_params->debug) {
				hikashop_writeToLog($response);
			}

			$this->modifyOrder($order_id, $status, $history, true);
		} elseif($pending && in_array(@$response->status, array('PENDING_APPROVAL', 'APPROVED', 'PAYER_ACTION_REQUIRED'))) {
			$history = new stdClass();
			$history->notified = 0;
			$history->data = 'PayPal transaction id:'.$paypal_id.' - Waiting for bank confirmation';
			if($this->payment_params->debug) {
				hikashop_writeToLog('APM pending: '.$response->status);
				hikashop_writeToLog($response);
			}
			$this->modifyOrder($order_id, $this->payment_params->pending_status, $history, false);
		} else {
			hikashop_writeToLog($paypal_id);
			hikashop_writeToLog($response);
			$this->modifyOrder($order_id, $this->payment_params->invalid_status, false, true);
		}

		$return_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id='.$dbOrder->order_id . $this->url_itemid;
		$app = JFactory::getApplication();
		$app->redirect($return_url);
	}

	private function checkResponse(&$response, &$dbOrder) {
		$completed = $response->status == 'COMPLETED';
		if(!$completed && empty($this->payment_params->capture)) {
			$completed = $response->status == 'APPROVED';
		}
		if(!$completed) {
			return false;
		}

		if(!isset($response->purchase_units) || !count($response->purchase_units))
			return false;
		$purchaseUnit = reset($response->purchase_units);

		if($response->status == 'COMPLETED') {
			if(!empty($response->purchase_units->payments)) {
				foreach($response->purchase_units->payments as $payment) {
					if(!empty($payment->status) && $payment->status == 'PENDING') {
						$response->intent = 'AUTHORIZE';
					}
					if(!empty($payment->captures)) {
						foreach($payment->captures as $capture) {
							if(!empty($capture->status) && $capture->status == 'PENDING') {
								$response->intent = 'AUTHORIZE';
							}
						}
					}
				}
			}
		}

		if($this->currency->currency_locale['int_frac_digits'] > 2)
			$this->currency->currency_locale['int_frac_digits'] = 2;
		$rounding = 2;
		if(isset($this->rounding[$this->currency->currency_code]))
			$rounding = $this->rounding[$this->currency->currency_code];
		if($purchaseUnit->amount->value < round($dbOrder->order_full_price, $rounding)) {
			return false;
		}

		if($purchaseUnit->amount->currency_code != $this->currency->currency_code) {
			return false;
		}

		return $purchaseUnit;
	}

	private function _extractVaultToken(&$response, &$order) {
		$vaultId = null;
		$vaultCustomerId = null;

		if(!empty($response->payment_source->paypal->attributes->vault->id)) {
			$vaultId = $response->payment_source->paypal->attributes->vault->id;
			$vaultCustomerId = @$response->payment_source->paypal->attributes->vault->customer->id;
		}
		if(empty($vaultId) && !empty($response->payment_source->card->attributes->vault->id)) {
			$vaultId = $response->payment_source->card->attributes->vault->id;
			$vaultCustomerId = @$response->payment_source->card->attributes->vault->customer->id;
		}

		if(empty($vaultId))
			return;

		$userClass = hikashop_get('class.user');
		$user = $userClass->get(@$order->order_user_id);
		if(empty($user))
			return;

		$userParams = @$user->user_params;
		if(empty($userParams) || !is_object($userParams))
			$userParams = new stdClass();

		$userParams->paypal_vault_id = $vaultId;
		if(!empty($vaultCustomerId))
			$userParams->paypal_vault_customer_id = $vaultCustomerId;

		$updateUser = new stdClass();
		$updateUser->user_id = $user->user_id;
		$updateUser->user_params = $userParams;
		$userClass->save($updateUser);

		if($this->payment_params->debug)
			hikashop_writeToLog('PayPal Vault token saved for user ' . $user->user_id . ': ' . $vaultId);
	}

	public function onOrderAuthorizationRenew(&$order) {
		$this->pluginParams((int)$order->order_payment_id);
		if(empty($this->plugin_params))
			return false;
		$this->payment_params =& $this->plugin_params;

		$userClass = hikashop_get('class.user');
		$user = $userClass->get($order->order_user_id);
		if(empty($user->user_params->paypal_vault_id))
			return false;

		$vaultId = $user->user_params->paypal_vault_id;

		$currencyClass = hikashop_get('class.currency');
		$currency = $currencyClass->get($order->order_currency_id);
		$currencyCode = strtoupper($currency->currency_code);
		$rounding = 2;
		if(isset($this->rounding[$currencyCode]))
			$rounding = $this->rounding[$currencyCode];

		$shipping = null;
		if(!empty($order->order_shipping_address_id)) {
			$addressClass = hikashop_get('class.address');
			$address = $addressClass->get($order->order_shipping_address_id);
			if(!empty($address)) {
				$zoneClass = hikashop_get('class.zone');
				$country = $zoneClass->get(@$address->address_country);
				$countryCode = !empty($country->zone_code_2) ? $country->zone_code_2 : '';
				$shipping = array(
					'name' => array('full_name' => trim(@$address->address_firstname . ' ' . @$address->address_lastname)),
					'address' => array(
						'address_line_1' => @$address->address_street,
						'admin_area_2' => @$address->address_city,
						'postal_code' => @$address->address_post_code,
						'country_code' => $countryCode,
					),
				);
			}
		}

		$orderData = array(
			'intent' => 'CAPTURE',
			'payment_source' => array(
				'paypal' => array(
					'vault_id' => $vaultId,
					'stored_credential' => array(
						'payment_initiator' => 'MERCHANT',
						'usage_pattern' => 'RECURRING_POSTPAID',
						'usage' => 'SUBSEQUENT',
					),
				),
			),
			'purchase_units' => array(array(
				'reference_id' => 'order_' . $order->order_id,
				'description' => 'Recurring order #' . $order->order_id,
				'amount' => array(
					'currency_code' => $currencyCode,
					'value' => sprintf('%.' . $rounding . 'f', $order->order_full_price),
				),
			)),
		);
		if(!empty($shipping))
			$orderData['purchase_units'][0]['shipping'] = $shipping;

		try {
			$api = $this->getApiClient();

			$response = $api->createOrder($orderData);
			if(empty($response->id)) {
				hikashop_writeToLog('PayPal Vault recurring charge failed for order #' . $order->order_id . ': ' . json_encode($response));
				return false;
			}

			if($response->status === 'COMPLETED') {
				$orderUpdate = new stdClass();
				$orderUpdate->order_id = $order->order_id;
				$params = @$order->order_payment_params;
				if(is_string($params)) $params = hikashop_unserialize($params);
				if(empty($params) || !is_object($params)) $params = new stdClass();
				$params->paypal_checkout_id = $response->id;
				if(!empty($response->purchase_units[0]->payments->captures[0]->id))
					$params->paypal_capture_id = $response->purchase_units[0]->payments->captures[0]->id;
				$orderUpdate->order_payment_params = serialize($params);
				$orderClass = hikashop_get('class.order');
				$orderClass->save($orderUpdate);

				if($this->payment_params->debug)
					hikashop_writeToLog('PayPal Vault recurring charge successful for order #' . $order->order_id);
				return true;
			}

			if(in_array($response->status, array('CREATED', 'APPROVED'))) {
				$captureResponse = $api->captureOrder($response->id);
				if(!empty($captureResponse->id) && $captureResponse->status === 'COMPLETED') {
					$orderUpdate = new stdClass();
					$orderUpdate->order_id = $order->order_id;
					$params = new stdClass();
					$params->paypal_checkout_id = $response->id;
					if(!empty($captureResponse->purchase_units[0]->payments->captures[0]->id))
						$params->paypal_capture_id = $captureResponse->purchase_units[0]->payments->captures[0]->id;
					$orderUpdate->order_payment_params = serialize($params);
					$orderClass = hikashop_get('class.order');
					$orderClass->save($orderUpdate);
					return true;
				}
			}

			hikashop_writeToLog('PayPal Vault recurring charge failed for order #' . $order->order_id . ': status=' . @$response->status);
			return false;
		} catch(Exception $e) {
			hikashop_writeToLog('PayPal Vault recurring charge exception for order #' . $order->order_id . ': ' . $e->getMessage());
			return false;
		}
	}

	public function getPaymentDefaultValues(&$element) {
		$element->payment_name='PayPal Checkout';
		$element->payment_description='You can pay with PayPal Checkout using this payment method';
		$element->payment_images='PayPal';

		$element->payment_params->instant_capture = 1;
		$element->payment_params->landing_page='NO_PREFERENCE';
		$element->payment_params->invalid_status='cancelled';
		$element->payment_params->verified_status='confirmed';
		$element->payment_params->funding = array_keys($this->pluginConfig['funding'][2]);
		$element->payment_params->layout = 'vertical';
		$element->payment_params->color = 'gold';
		$element->payment_params->label = 'paypal';
		$element->payment_params->shape = 'rect';
		$element->payment_params->tagline = '1';
		$element->payment_params->listing_position = 'bottom';
		$element->payment_params->product_page_position = 'rightMiddle';
		$element->payment_params->cart_page_position = 'bottom';
		$element->payment_params->checkout_page_position = 'bottom';
		$element->payment_params->paylater_messaging_color = 'black';
	}

	private function getOrderData(&$order) {
		if($this->currency->currency_locale['int_frac_digits'] > 2)
			$this->currency->currency_locale['int_frac_digits'] = 2;

		$rounding = 2;
		if(isset($this->rounding[$this->currency->currency_code]))
			$rounding = $this->rounding[$this->currency->currency_code];

		$orderData = new stdClass();
		if(!empty($this->payment_params->capture)) {
			$orderData->intent = 'CAPTURE';
			$orderData->processing_instruction = 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL';
		} else {
			$orderData->intent = 'AUTHORIZE';
		}
		$orderData->application_context = new stdClass();
		if(empty($order->order_shipping_id)) {
			$orderData->application_context->shipping_preference = 'NO_SHIPPING';
		}elseif(!empty($order->cart->billing_address)) {
			$orderData->application_context->shipping_preference = 'SET_PROVIDED_ADDRESS';
		}
		if(!empty($this->payment_params->brand_name))
			$orderData->application_context->brand_name = mb_substr($this->payment_params->brand_name, 0, 127);
		$orderData->application_context->return_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment='.$this->name.'&order_id='.$order->order_id.'&tmpl=component&lang='.$this->locale . $this->url_itemid . '&closepopup=1';
		$orderData->application_context->cancel_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id='.$order->order_id . $this->url_itemid;
		if(!empty($this->payment_params->landing_page)) {
			$orderData->application_context->landing_page = $this->payment_params->landing_page;
		}

		$orderData->payer = new stdClass();
		$orderData->payer->email_address = $this->user->user_email;

		$this->billing_country_code = null;
		if(empty($order->cart->billing_address->address_country_code_2)) {
			if(!empty($order->cart->billing_address->address_country->zone_code_2)) {
				$this->billing_country_code = $order->cart->billing_address->address_country->zone_code_2;
			}
		} else {
			$this->billing_country_code = $order->cart->billing_address->address_country_code_2;
		}
		$this->billing_state = null;
		if(!empty($order->cart->billing_address->address_state_name)) {
			$this->billing_state = $order->cart->billing_address->address_state_name;
		}elseif(!empty($order->cart->billing_address->address_state->zone_name)) {
			$this->billing_state = $order->cart->billing_address->address_state->zone_name;
		}

		if(
			!empty($order->cart->billing_address->address_firstname) && 
			!empty($order->cart->billing_address->address_lastname) && 
			!empty($order->cart->billing_address->address_street) && 
			!empty($order->cart->billing_address->address_city) && 
			!empty($order->cart->billing_address->address_post_code) && 
			!empty($this->billing_country_code)
		) {
			$orderData->payer->name = new stdClass();
			$orderData->payer->name->given_name = $order->cart->billing_address->address_firstname;
			$orderData->payer->name->surname = $order->cart->billing_address->address_lastname;
			$orderData->payer->address = new stdClass();
			$orderData->payer->address->address_line_1 = $order->cart->billing_address->address_street;
			if(!empty($order->cart->billing_address->address_street2)) {
				$orderData->payer->address->address_line_2 = $order->cart->billing_address->address_street2;
			}
			$orderData->payer->address->admin_area_2 = $order->cart->billing_address->address_city;
			$orderData->payer->address->postal_code = $order->cart->billing_address->address_post_code;
			$orderData->payer->address->admin_area_1 = $this->billing_state;
			$orderData->payer->address->country_code = $this->billing_country_code;
		}
		$purchaseUnit = new stdClass();
		$purchaseUnit->invoice_id = $order->order_id;
		$purchaseUnit->description = mb_substr(JText::_('ORDER_NUMBER').' '.$order->order_number,0,127);
		$purchaseUnit->items = [];

		$shipping_country_code = null;
		if(empty($order->cart->shipping_address->address_country_code_2)) {
			if(!empty($order->cart->shipping_address->address_country->zone_code_2)) {
				$shipping_country_code = $order->cart->shipping_address->address_country->zone_code_2;
			}
		} else {
			$shipping_country_code = $order->cart->shipping_address->address_country_code_2;
		}

		if(
			!empty($order->cart->shipping_address->address_firstname) && 
			!empty($order->cart->shipping_address->address_lastname) && 
			!empty($order->cart->shipping_address->address_street) && 
			!empty($order->cart->shipping_address->address_city) && 
			!empty($order->cart->shipping_address->address_post_code) && 
			!empty($shipping_country_code)
		) {
			$purchaseUnit->shipping = new stdClass();
			$purchaseUnit->shipping->name = new stdClass();
			$purchaseUnit->shipping->name->full_name = $order->cart->shipping_address->address_firstname. ' ' . $order->cart->shipping_address->address_lastname;
			$purchaseUnit->shipping->address = new stdClass();
			$purchaseUnit->shipping->address->address_line_1 = $order->cart->shipping_address->address_street;
			if(!empty($order->cart->shipping_address->address_street2)) {
				$purchaseUnit->shipping->address->address_line_2 = $order->cart->shipping_address->address_street2;
			}
			$purchaseUnit->shipping->address->admin_area_2 = $order->cart->shipping_address->address_city;
			$purchaseUnit->shipping->address->postal_code = $order->cart->shipping_address->address_post_code;
			if(!empty($order->cart->shipping_address->address_state_name)) {
				$purchaseUnit->shipping->address->admin_area_1 = $order->cart->shipping_address->address_state_name;
			}elseif(!empty($order->cart->shipping_address->address_state->zone_name)) {
				$purchaseUnit->shipping->address->admin_area_1 = $order->cart->shipping_address->address_state->zone_name;
			}
			$purchaseUnit->shipping->address->country_code = $shipping_country_code;
		} else {
			$orderData->application_context->shipping_preference = 'NO_SHIPPING';
		}

		$config = hikashop_config();
		$group = $config->get('group_options',0);
		$item_total = 0;
		$tax_total = 0;
		foreach($order->cart->products as $product) {
			if($group && $product->order_product_option_parent_id) continue;
			if(empty($product->order_product_quantity)) continue;

			$item = new stdClass();
			$item->name = mb_substr(strip_tags($product->order_product_name),0,127);
			$item->quantity = $product->order_product_quantity;
			$item->sku = mb_substr($product->order_product_code,0,127);
			$item->unit_amount = new stdClass();
			$item->unit_amount->value = number_format(round($product->order_product_price, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
			$item->unit_amount->currency_code = $this->currency->currency_code;
			$item->tax = new stdClass();
			$item->tax->value = number_format(round($product->order_product_tax, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
			$item->tax->currency_code = $this->currency->currency_code;
			$purchaseUnit->items[] = $item;
			$item_total += round($product->order_product_price, $rounding) * $product->order_product_quantity;
			$tax_total += round($product->order_product_tax, $rounding) * $product->order_product_quantity;
		}
		$discount = new stdClass();
		$discount->currency_code = $this->currency->currency_code;
		$discount->value = 0;

		if(!empty($order->cart->additional)){
			foreach($order->cart->additional as $product) {
				if(empty($product->order_product_price) || $product->order_product_price == 0) continue;
				if($product->order_product_price < 0) {
					$discount->value += $product->order_product_price+$product->order_product_tax;
					continue;
				}
				$item = new stdClass();
				$item->name =  mb_substr(JText::_(strip_tags($product->order_product_name)),0,127);
				$item->quantity = 1;
				$item->sku = mb_substr($product->order_product_code,0,127);
				$item->unit_amount = new stdClass();
				$item->unit_amount->value = number_format(round($product->order_product_price, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
				$item->unit_amount->currency_code = $this->currency->currency_code;
				$item->tax = new stdClass();
				$item->tax->value = number_format(round($product->order_product_tax, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
				$item->tax->currency_code = $this->currency->currency_code;
				$purchaseUnit->items[] = $item;
				$item_total += round($product->order_product_price, $rounding);
				$tax_total += round($product->order_product_tax, $rounding);
			}
		}
		if(!empty($order->order_payment_price) && bccomp(sprintf('%F',$order->order_payment_price), 0, 5)) {
			$item = new stdClass();
			$item->name = mb_substr(JText::_('HIKASHOP_PAYMENT'),0,127);
			$item->quantity = 1;
			$item->sku = 'payment_fee';
			$item->unit_amount = new stdClass();
			$item->unit_amount->value = number_format(round($order->order_payment_price-$order->order_payment_tax, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
			$item->unit_amount->currency_code = $this->currency->currency_code;
			$item->tax = new stdClass();
			$item->tax->value = number_format(round($order->order_payment_tax, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
			$item->tax->currency_code = $this->currency->currency_code;
			$purchaseUnit->items[] = $item;
			$item_total += round($order->order_payment_price-$order->order_payment_tax, $rounding);
			$tax_total += round($order->order_payment_tax, $rounding);
		}

		if(!isset($this->payment_params->details))
			$this->payment_params->details = 1;

		if(empty($this->payment_params->details)) {
			$item_total_with_taxes = round($order->cart->full_total->prices[0]->price_value_with_tax, (int)$this->currency->currency_locale['int_frac_digits']);
			$item_total = round($order->cart->full_total->prices[0]->price_value, (int)$this->currency->currency_locale['int_frac_digits']);
			$tax_total = $item_total_with_taxes - $item_total;
			$item = new stdClass();
			$item->name = $purchaseUnit->description;
			$item->quantity = 1;
			$item->sku = 'full_order';
			$item->unit_amount = new stdClass();
			$item->unit_amount->value = number_format($item_total, $rounding, '.', '');
			$item->unit_amount->currency_code = $this->currency->currency_code;
			$item->tax = new stdClass();
			$item->tax->value = number_format($tax_total, $rounding, '.', '');
			$item->tax->currency_code = $this->currency->currency_code; 
			$purchaseUnit->items = array($item);
		}

		$purchaseUnit->amount = new stdClass();
		$purchaseUnit->amount->value = number_format(round($order->cart->full_total->prices[0]->price_value_with_tax, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
		$purchaseUnit->amount->currency_code = $this->currency->currency_code;
		$purchaseUnit->amount->breakdown = new stdClass();
		$purchaseUnit->amount->breakdown->item_total = new stdClass();
		$purchaseUnit->amount->breakdown->item_total->value = number_format($item_total, $rounding, '.', '');
		$purchaseUnit->amount->breakdown->item_total->currency_code = $this->currency->currency_code;
		$purchaseUnit->amount->breakdown->tax_total = new stdClass();
		$purchaseUnit->amount->breakdown->tax_total->value = number_format($tax_total, $rounding, '.', '');
		$purchaseUnit->amount->breakdown->tax_total->currency_code = $this->currency->currency_code;

		if(!empty($this->payment_params->details)) {
			$order_total_rounded = (float)$purchaseUnit->amount->value;
			$shipping_value = 0;
			if(!empty($order->order_shipping_price) && bccomp(sprintf('%F',$order->order_shipping_price), 0, 5))
				$shipping_value = round($order->order_shipping_price, $rounding);
			$computed_discount = round($item_total + $tax_total + $shipping_value - $order_total_rounded, $rounding);
			if(bccomp(sprintf('%F', $computed_discount), '0', $rounding) > 0) {
				$discount->value = number_format($computed_discount, $rounding, '.', '');
				$purchaseUnit->amount->breakdown->discount = $discount;
			}

			if(!empty($order->order_shipping_price) && bccomp(sprintf('%F',$order->order_shipping_price), 0, 5)) {
				$purchaseUnit->amount->breakdown->shipping = new stdClass();
				$purchaseUnit->amount->breakdown->shipping->value = number_format(round($order->order_shipping_price, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
				$purchaseUnit->amount->breakdown->shipping->currency_code = $this->currency->currency_code;
			}
		}
		$orderData->purchase_units = [$purchaseUnit];

		if(!empty($order->order_payment_params->recurring)) {
			$paypalSource = new stdClass();
			$paypalSource->experience_context = clone $orderData->application_context;
			$paypalSource->experience_context->return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=' . $this->name . '&order_id=' . $order->order_id . '&lang=' . $this->locale . $this->url_itemid;
			$paypalSource->attributes = new stdClass();
			$paypalSource->attributes->vault = new stdClass();
			$paypalSource->attributes->vault->store_in_vault = 'ON_SUCCESS';
			$paypalSource->attributes->vault->usage_type = 'MERCHANT';
			$paypalSource->attributes->vault->customer_type = 'CONSUMER';
			$paypalSource->attributes->vault->usage_pattern = 'RECURRING_POSTPAID';

			$orderData->payment_source = new stdClass();
			$orderData->payment_source->paypal = $paypalSource;
			unset($orderData->application_context);
			unset($orderData->processing_instruction);
		}

		return $orderData;
	}

	private function _generateClientToken($id) {
		try {
			$api = $this->getApiClient();
			$response = $api->generateClientToken($id);
			if(!empty($response->client_token))
				return $response->client_token;
			hikashop_writeToLog('Client token could not be generated by PayPal Checkout for ACDC');
			hikashop_writeToLog($response);
			return false;
		} catch(Exception $e) {
			hikashop_writeToLog('Client token generation failed: ' . $e->getMessage());
			return false;
		}
	}

	private function loadJS() {
		$this->params = [
			'client-id' => $this->payment_params->client_id,
			'integration-date' => '2024-05-19',
			'currency' => $this->currency->currency_code,
			'components' => 'buttons,funding-eligibility,messages',
		];

		if(!empty($this->payment_params->enable_credit_card)) {

			$token = $this->_generateClientToken($this->user->user_id);
			if(empty($token)) {
				$this->payment_params->enable_credit_card = false;
			} else {
				$this->params['components'].=',hosted-fields';
				$this->extraParams = 'data-client-token="'.$token.'"';
				$_SESSION['hikashop_paypalcheckout_client_token'] = $token;
			}
			if(!empty($this->payment_params->enable_3dsecure) && $this->payment_params->enable_3dsecure != 'NO') {
				$this->enable_3dsecure = $this->payment_params->enable_3dsecure;
			}
		}

		if(!empty($this->payment_params->enable_googlepay)) {
			if(empty($this->payment_params->country_code)) {
				$this->payment_params->enable_googlepay = false;
			}
			$this->params['components'].=',googlepay';
			if($this->payment_params->sandbox) {
				$this->payment_params->googlepay_environment = 'TEST';
			} else {
				$this->payment_params->googlepay_environment = 'PRODUCTION';
			}
		}
		if(!empty($this->payment_params->enable_applepay)) {
			$this->params['components'].=',applepay';
			$this->payment_params->applepay_color = 'black';
			if(!empty($this->payment_params->color) && $this->payment_params->color == 'white') {
				$this->payment_params->applepay_color = 'white';
			}
			$lang = JFactory::getLanguage();
			$parts = explode('-',$lang->get('tag'));
			$locale = array_shift($parts);
			if(in_array($locale, $this->accepted_applepay_locales)) {
				$this->payment_params->applepay_locale = $locale;
			}

			$this->payment_params->store_name = str_replace(array("\n", "\r", '"'), '', (string)$this->payment_params->brand_name);
		}


		if(!empty($this->payment_params->disable_funding)) {
			if(is_string($this->payment_params->disable_funding)) {
				$this->payment_params->disable_funding = explode(',', $this->payment_params->disable_funding);
			}
			$key = array_search('sofort', $this->payment_params->disable_funding);
			if ($key !== false) {
				unset($this->payment_params->disable_funding[$key]);
			}
			$this->payment_params->disable_funding = implode(',', $this->payment_params->disable_funding);

			$this->params['disable-funding'] = $this->payment_params->disable_funding;
		}
		if(!empty($this->payment_params->funding)) {
			if(is_string($this->payment_params->funding)) {
				$this->payment_params->funding = explode(',', $this->payment_params->funding);
			}
			$key = array_search('sofort', $this->payment_params->funding);
			if ($key !== false) {
				unset($this->payment_params->funding[$key]);
			}
			$this->payment_params->funding = implode(',', $this->payment_params->funding);
			$this->params['enable-funding'] = $this->payment_params->funding;
		}

		if(!empty($this->payment_params->capture)) {
			$this->params['intent'] = 'capture';
		} else {
			$this->params['intent'] = 'authorize';
		}
		if(!empty($this->payment_params->debug)) {
			$this->params['debug'] = 'true';
		}

		if(empty($this->payment_params->layout)) {
			$this->payment_params->layout = 'vertical';
		}
		if(empty($this->payment_params->color)) {
			$this->payment_params->color = 'gold';
		}
		if(empty($this->payment_params->label)) {
			$this->payment_params->label = 'paypal';
		}
		if(empty($this->payment_params->shape)) {
			$this->payment_params->shape = 'rect';
		}
		if($this->payment_params->layout == 'horizontal') {
			if(!isset($this->payment_params->tagline)) {
				$this->payment_params->tagline = '1';
			}
		} else {
			$this->payment_params->tagline = false;
		}
		if(!empty($this->payment_params->language) && $this->payment_params->language == 'website') {
			$lang = JFactory::getLanguage();
			$locale = str_replace('-','_', $lang->get('tag'));
			if(in_array($locale, $this->accepted_locales)) {
				$this->params['locale'] = $locale;
			}
		}
	}

	public function onHikashopCronTrigger(&$messages) {
		$pluginsClass = hikashop_get('class.plugins');
		$plugin = $pluginsClass->getByName('hikashoppayment', 'paypalcheckout');
		if(empty($plugin))
			return true;

		if(!empty($plugin->params['last_apm_check']) && $plugin->params['last_apm_check'] + 600 > time())
			return true;

		$plugin->params['last_apm_check'] = time();
		$pluginsClass->save($plugin);

		$this->payment_params = $plugin->params;
		if(empty($this->payment_params['pending_status']))
			return true;

		$db = JFactory::getDBO();

		$query = 'SELECT o.order_id, o.order_payment_id, o.order_payment_params, o.order_full_price, o.order_currency_id, o.order_created '.
			' FROM '.hikashop_table('order').' AS o '.
			' WHERE o.order_payment_method = '.$db->Quote('paypalcheckout').
			' AND o.order_status = '.$db->Quote($this->payment_params['pending_status']).
			' AND o.order_created > '.(int)(time() - 86400); // only check orders from the last 24 hours
		$db->setQuery($query);
		$pendingOrders = $db->loadObjectList();

		if(empty($pendingOrders))
			return true;

		$count = 0;
		foreach($pendingOrders as $pendingOrder) {
			$params = hikashop_unserialize($pendingOrder->order_payment_params);
			if(empty($params->paypal_checkout_id))
				continue;

			$this->pluginParams($pendingOrder->order_payment_id);
			if(empty($this->plugin_params))
				continue;
			$this->payment_params = $this->plugin_params;

			$paypal_id = $params->paypal_checkout_id;

			try {
				$api = $this->getApiClient();
				$response = $api->getOrder($paypal_id);

				if(empty($response->id))
					continue;

				$status = $response->status;

				if($this->payment_params->debug) {
					hikashop_writeToLog('Cron APM check: order '.$pendingOrder->order_id.' paypal_id '.$paypal_id.' status '.$status);
				}

				if($status == 'COMPLETED') {
					$history = new stdClass();
					$history->notified = 1;
					$history->amount = '';
					if(!empty($response->purchase_units)) {
						$pu = reset($response->purchase_units);
						$history->amount = @$pu->amount->value . @$pu->amount->currency_code;
					}
					$history->data = 'PayPal transaction id:'.$paypal_id.' - Confirmed by cron (APM)';
					$this->modifyOrder($pendingOrder->order_id, $this->payment_params->verified_status, $history, true);
					$count++;
				} elseif($status == 'VOIDED') {
					$history = new stdClass();
					$history->notified = 0;
					$history->data = 'PayPal transaction id:'.$paypal_id.' - Payment voided by PayPal';
					$this->modifyOrder($pendingOrder->order_id, $this->payment_params->invalid_status, $history, false);
				}
			} catch(Exception $e) {
				hikashop_writeToLog('Cron APM check error for order '.$pendingOrder->order_id.': '.$e->getMessage());
			}
		}

		if($count > 0) {
			$messages[] = $count.' PayPal APM order(s) confirmed';
		}

		return true;
	}
}
