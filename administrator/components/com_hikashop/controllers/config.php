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
class ConfigController extends hikashopController {
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->registerDefaultTask('config');

		$this->modify_views = array_merge($this->modify_views, array(
			'latest', 'share', 'send', 'css', 'language', 'cleanreport', 'config', 'export_fields', 'addresslayout'
		));
		$this->display = array_merge($this->display, array(
			'seepaymentreport', 'seereport', 'checkout_newblock', 'checkout_newstep',
			'check_update',
		));
		$this->modify = array_merge($this->modify, array(
			'savelanguage', 'savecss', 'wizard', 'checkdb', 'resetAddressFormat', 'save_export_fields', 'saveaddresslayout',
			'install_update',
		));
	}

	public function save() {
		$this->store();
		return $this->cancel();
	}

	public function apply() {
		$this->store();
		return $this->display();
	}

	public function display($cachable = false, $urlparams = array()) {
		hikaInput::get()->set('layout', 'config');
		return parent::display();
	}

	public function cancel() {
		$this->setRedirect( hikashop_completeLink('dashboard',false,true) );
	}

	public function store($new = false) {
		$app = JFactory::getApplication();
		JSession::checkToken() || die('Invalid Token');
		$fileClass = hikashop_get('class.file');
		$source = is_array($_POST['config'])?'post':'request'; //to avoid strange bugs on some web servers where the config array might be only in one of the two global variable :/
		$formData = hikaInput::get()->{$source}->get('config', array(), 'array');
		$aclcats = hikaInput::get()->get('aclcat', array(), 'array' );
		if(!empty($aclcats)) {
			if(hikaInput::get()->getString('acl_config','all') != 'all' && !hikashop_isAllowed($formData['acl_config_manage'])) {
				$app->enqueueMessage(JText::_( 'ACL_WRONG_CONFIG' ), 'notice');
				unset($formData['acl_config_manage']);
			}
			$deleteAclCats = array();
			$unsetVars = array('manage','delete','view');
			foreach($aclcats as $oneCat) {
				if(hikaInput::get()->getString('acl_'.$oneCat) == 'all') {
					foreach($unsetVars as $oneVar) {
						unset($formData['acl_'.$oneCat.'_'.$oneVar]);
					}
					$deleteAclCats[] = $oneCat;
				}
			}
		}
		$config =& hikashop_config();

		$nameboxes = array('simplified_registration', 'product_show_modules','affiliation_category_restriction');
		foreach($nameboxes as $namebox) {
			if(!isset($formData[$namebox])) {
				$formData[$namebox] = '';
			} elseif(is_array($formData[$namebox])) {
				$formData[$namebox] = implode(',', $formData[$namebox]);
			}
		}

		if(!isset($this->wizard)){
			if($formData['uploadsecurefolder'] == $formData['uploadfolder']){
				$app->enqueueMessage('The "Upload folder" and "Upload secure folder" settings cannot use the same path.', "error");
				return false;
			}
		}

		$status = $config->save($formData);
	 	if(!empty($deleteAclCats)) {
			$db = JFactory::getDBO();
	 		$escapedCats = array();
	 		foreach($deleteAclCats as $cat) {
	 			$escapedCats[] = "`config_namekey` LIKE 'acl_".$db->escape($cat)."%'";
	 		}
	 		$db->setQuery("DELETE FROM `#__hikashop_config` WHERE ".implode(" OR ", $escapedCats));
	 		$db->execute();
	 	}
		$ids = $fileClass->storeFiles('default_image',0);
		if(!empty($ids)) {
			$data = $fileClass->get($ids[0]);
			$formData['default_image']=$data->file_path;
		}
		if(hikashop_level(2)) {
			$ids = $fileClass->storeFiles('watermark',0,'watermark');
			if(!empty($ids)){
				$data = $fileClass->get($ids[0]);
				$formData['watermark']=$data->file_path;
			}
		}
		$formData['store_address']=hikaInput::get()->getRaw( 'config_store_address','');

		$this->_saveAddressFormat();

		if(!empty($formData['cart_item_limit']) && !is_numeric($formData['cart_item_limit'])){
			$formData['cart_item_limit']=0;
		}
		if(!isset($this->wizard) && !$this->_checkWorkflow($formData)){
			$app->enqueueMessage('Checkout workflow invalid. The modification is ignored. See <a style="font-size:1.2em;text-decoration:underline" href="http://www.hikashop.com/support/documentation/integrated-documentation/54-hikashop-config.html#main" target="_blank" >the documentation</a> for more information on how to configure that option.', 'error');
			unset($formData['checkout']);
			return false;
		}

		if(!isset($this->wizard)){
			if(empty($formData['category_sef_name']) && empty($formData['product_sef_name'])){
				$app->enqueueMessage('No SEF category and product names entered. Please complete at least one of these two fields. The system put back the default values');
				$formData['category_sef_name']='category';
				$formData['product_sef_name']='product';
			}
		}


		if(!empty($formData['weight_symbols'])){
			$symbols = explode(',',$formData['weight_symbols']);
			$weightHelper = hikashop_get('helper.weight');
			$possibleSymbols = array_keys($weightHelper->conversion);
			$possibleSymbols[]='l';
			$possibleSymbols[]='ml';
			$possibleSymbols[]='cl';
			$okSymbols = array();
			foreach($symbols as $k => $symbol){
				if(!in_array($symbol,$possibleSymbols)){
					$app->enqueueMessage('The weight unit "'.$symbol.'" is not in the list of possible units : '.implode(',',$possibleSymbols));
					return false;
				}else{
					$okSymbols[]=$symbol;
				}
			}
			$formData['weight_symbols'] = implode(',',$okSymbols);
		}
		if(!isset($this->wizard) && empty($formData['weight_symbols'])){
			$app->enqueueMessage('No valid weight unit entered. The system put back the default units.');
			$formData['weight_symbols']='kg,g,mg,lb,oz,ozt';
		}
		if(!empty($formData['volume_symbols'])){
			$symbols = explode(',',$formData['volume_symbols']);
			$weightHelper = hikashop_get('helper.volume');
			$possibleSymbols = array_keys($weightHelper->conversion);
			$okSymbols = array();
			foreach($symbols as $k => $symbol){
				if(!in_array($symbol,$possibleSymbols)){
					$app->enqueueMessage('The dimension unit "'.$symbol.'" is not in the list of possible units : '.implode(',',$possibleSymbols));
					return false;
				}else{
					$okSymbols[]=$symbol;
				}
			}
			$formData['volume_symbols'] = implode(',',$okSymbols);
		}
		if(!isset($this->wizard) && empty($formData['volume_symbols'])){
			$app->enqueueMessage('No valid dimension unit entered. The system put back the default units.');
			$formData['volume_symbols']='m,dm,cm,mm,in,ft,yd';
		}
		if(!isset($this->wizard) && $formData['force_ssl']=='url' && empty($formData['force_ssl_url'])){
			$formData['force_ssl']='no';
			$app->enqueueMessage('No ssl url specified, force ssl parameter setted to no');
		}

		if(!empty($formData['order_number_format']))
			$formData['order_number_format']=str_replace('&quot;}"','"}',$formData['order_number_format']);

		$config =& hikashop_config();
		$status = $config->save($formData);
		if($status){
			$app->enqueueMessage(JText::_( 'HIKASHOP_SUCC_SAVED' ));
		}else{
			$app->enqueueMessage(JText::_( 'ERROR_SAVING' ), 'error');
		}

		$pluginsClass = hikashop_get('class.plugins');

		$paramsPlugins = hikaInput::get()->get('params', array(), 'array');
		foreach($paramsPlugins as $group => $paramsPluginsOneGroup){
			foreach($paramsPluginsOneGroup as $name => $paramsPlugin){
				$plugin = $pluginsClass->getByName($group,$name,false);
				if(!empty($plugin)){
					if(empty($plugin->params))
						$plugin->params = $paramsPlugin;
					else {
						$plugin->params = array_merge($plugin->params, $paramsPlugin);
					}
					$pluginsClass->save($plugin);
				}
			}
		}
		$js = '
function setVisible(value) {
	value = parseInt(value);
	var d = document, disp = (value == 1) ? "" : "none";
	d.getElementById("sef_cat_name").style.display = disp;
	d.getElementById("sef_prod_name").style.display = disp;
}';
		$doc = JFactory::getDocument();
	 	$doc->addScriptDeclaration($js);

		$config->load();
		return $status;

	}

	protected function _checkWorkflow(&$formData) {
		if(empty($formData['checkout']) && !empty($formData['checkout_workflow']))
			return true;

		if(empty($formData['checkout'])) {
			$app = JFactory::getApplication();
			$app->enqueueMessage('Your checkout workflow is empty.');
			return false;
		}
		$formData['checkout'] = trim($formData['checkout']);
		$steps = explode(',',$formData['checkout']);
		$login = false;
		$address = false;
		$ok = array('login','address','shipping','payment','confirm','coupon','cart','cartstatus','status','fields','terms','end');

		JPluginHelper::importPlugin('hikashop');
		JPluginHelper::importPlugin('hikashopshipping');
		JPluginHelper::importPlugin('hikashoppayment');
		$app = JFactory::getApplication();
		$list = array();
		$app->triggerEvent('onCheckoutStepList', array(&$list));
		if(!empty($list)) {
			foreach($list as $k => $v) {
				if(!in_array($k, $ok))
					$ok[] = $k;
			}
		}

		foreach($steps as $step){
			if(empty($step)){
				$app = JFactory::getApplication();
				$app->enqueueMessage('You have an empty step in your checkout workflow.');
				return false;
			}
			$views = explode('_',$step);
			foreach($views as $view){
				if(!in_array($view,$ok)){
					$app = JFactory::getApplication();
					$app->enqueueMessage('You have a view name which is not possible in your checkout workflow. You can only use the views: '.implode(',',$ok));
					return false;
				}
				if($view=='login') $login = true;
				if($view=='address') $address = true;
			}
		}
		if($address && !$login){
			$app = JFactory::getApplication();
			$app->enqueueMessage('You cannot have the Address view without the Login view on your checkout workflow.', 'error');
			return false;
		}
		return true;
	}

	protected function _saveAddressFormat() {
		$format = trim(hikaInput::get()->getRaw( 'config_address_format',''));
		if(empty($format))
			return;
		$config = hikashop_config();

		$originalFormat = $config->getAddressFormat(true);
		if($originalFormat == $format)
			return;

		jimport('joomla.filesystem.file');

		$header = "<?php
 defined('_JEXEC') or die('Restricted access');
?>
";

		$format = $header . $format;

		if(defined('HIKASHOP_WORDPRESS')) {
			$themeDir = get_stylesheet_directory();
			$paths = array(
				$themeDir . DS . 'hikashop' . DS . 'address' . DS . 'address_template.php',
				$themeDir . DS . 'hikashop' . DS . 'administrator' . DS . 'order' . DS . 'address_template.php',
			);
			foreach($paths as $path) {
				JFile::write($path, $format);
			}
		} else {
			$db = JFactory::getDBO();
			$query = "SELECT * FROM #__template_styles";
			$db->setQuery($query);
			$templates = $db->loadObjectList();
			if(empty($templates))
				return;

			foreach($templates as $template){
				$path = HIKASHOP_ROOT;
				$view = 'address';
				if($template->client_id){
					$path .= 'administrator' . DS;
					$view = 'order';
				}
				$path .= 'templates' . DS . $template->template . DS . 'html' . DS . 'com_hikashop' . DS . $view . DS . 'address_template.php';
				JFile::write($path, $format);
			}
		}
	}

	public function resetAddressFormat() {
		jimport('joomla.filesystem.file');
		$ret = 	array(
			'error' => '',
			'result' => true,
		);

		if(defined('HIKASHOP_WORDPRESS')) {
			$themeDir = get_stylesheet_directory();
			$paths = array(
				$themeDir . DS . 'hikashop' . DS . 'address' . DS . 'address_template.php',
				$themeDir . DS . 'hikashop' . DS . 'administrator' . DS . 'order' . DS . 'address_template.php',
			);
			foreach($paths as $path) {
				if(file_exists($path) && !JFile::delete($path)){
					$ret['error'] .= JText::sprintf('COULD_NOT_DELETE_THE_FILE', $path);
					$ret['result'] = false;
				}
			}
		} else {
			$db = JFactory::getDBO();
			$query = "SELECT * FROM #__template_styles";
			$db->setQuery($query);
			$templates = $db->loadObjectList();
			if(empty($templates))
				return;

			foreach($templates as $template){
				$path = HIKASHOP_ROOT;
				if($template->client_id){
					$path .= 'administrator' . DS;
				}
				$path .= 'templates' . DS . $template->template . DS . 'html' . DS . 'com_hikashop' . DS . 'address' . DS . 'address_template.php';
				if(!JFile::delete($path)){
					$ret['error'] .= JText::sprintf('COULD_NOT_DELETE_THE_FILE', $path);
					$ret['result'] = false;
				}
			}
		}

		if($ret['result']){
			$ret['message'] = hikashop_display(JText::_('ADDRESS_FORMAT_RESET_SUCCESSFULLY'),'success',true);
			$config = hikashop_config();
			$ret['defaultFormat'] = trim($config->getAddressFormat(true));
		}

		ob_clean();
		echo json_encode($ret);
		exit;
	}

	public function test() {
		$app = JFactory::getApplication();
		$this->store();

		$config =& hikashop_config();
		$user = hikashop_loadUser(true);
		$mailClass = hikashop_get('class.mail');
		$addedName = $config->get('add_names',true) ? $mailClass->cleanText(@$user->name) : '';
		$true = true;
		$mail = $mailClass->get('test',$true);
		$mailClass->mailer->AddAddress($user->user_email,$addedName);
		$mail->subject = 'Test e-mail from '.HIKASHOP_LIVE;
		$mail->altbody = 'This test email confirms that your configuration enables HikaShop to send emails normally.';
		$mail->html=0;
		$mail->debug = 1;
		$result = $mailClass->sendMail($mail);
		if(!$result){
			$bounce = $config->get('bounce_email');
			if(!empty($bounce)){
				$app->enqueueMessage(JText::sprintf('ADVICE_BOUNCE',$bounce),'notice');
			}
		}
		return $this->display();
	}

	public function seepaymentreport() {
		$config =& hikashop_config();
		$path = trim(html_entity_decode((string)$config->get('payment_log_file')));
		if(!preg_match('#\.log$#i', $path) || strpos($path, '..') !== false) {
			hikashop_display('The log file must only contain alphanumeric characters and end with .log','error');
			return false;
		}
		$reportPath = JPath::clean(HIKASHOP_ROOT.$path);
		if(!JPath::check($reportPath)) {
			hikashop_display(JText::_('EXIST_LOG'),'info');
			return false;
		}

		$logFile = @file_get_contents((string)$reportPath);
		if(empty($logFile)){
			hikashop_display(JText::_('EMPTY_LOG').' '.$reportPath,'info');
		} else {
			$logFile = trim($logFile);

			if(strpos($logFile, '<pre') !== false) {
				$tmp = str_replace(array("\r\n","\r","\n"), "\x02", $logFile);
				$pre = array();
				if(preg_match_all('#<pre\s?(.*)>(.*)</pre>#Ui', $tmp, $pre)) {
					foreach($pre[0] as $p) {
						$n = str_replace("\x02", "\r\n", $p);
						$tmp = str_replace($p, $n, $tmp);
						unset($n);
					}
					unset($p);
					unset($pre);
				}
				$ret = str_replace("\x02", "<br/>\r\n", $tmp);
			} else
				$ret = nl2br($logFile);

			echo $ret;
		}
	}

	public function seereport() {
		$config =& hikashop_config();
		$path = trim(html_entity_decode((string)$config->get('cron_savepath')));
		if(!preg_match('#\.log$#i',$path) || strpos($path, '..') !== false){
			hikashop_display('The log file must only contain alphanumeric characters and end with .log','error');
			return false;
		}
		$reportPath = JPath::clean(HIKASHOP_ROOT.$path);

		if(!JPath::check($reportPath)) {
			hikashop_display(JText::_('EXIST_LOG'),'info');
			return false;
		}
		$logFile = @file_get_contents((string)$reportPath);
		if(empty($logFile)){
			hikashop_display(JText::_('EMPTY_LOG'),'info');
		}else{
			echo nl2br($logFile);
		}
	}

	public function cleanreport() {
		jimport('joomla.filesystem.file');
		$config =& hikashop_config();

		$path = trim(html_entity_decode((string)$config->get('cron_savepath')));
		if(!preg_match('#\.log$#i',$path) || strpos($path, '..') !== false){
			hikashop_display('The log file must only contain alphanumeric characters and end with .log','error');
			return false;
		}
		$reportPath = JPath::clean(HIKASHOP_ROOT.$path);

		if(!JPath::check($reportPath)) {
			hikashop_display(JText::_('EXIST_LOG'),'info');
			return false;
		}

		if(is_file((string)$reportPath)){
			$result = JFile::delete($reportPath);
			if($result){
				hikashop_display(JText::_('SUCC_DELETE_LOG'),'success');
			}else{
				hikashop_display(JText::_('ERROR_DELETE_LOG'),'error');
			}
		}else{
			hikashop_display(JText::_('EXIST_LOG'),'info');
		}
	}

	public function language() {
		hikaInput::get()->set('layout', 'language');
		return parent::display();
	}

	public function savelanguage() {
		JSession::checkToken() || die( 'Invalid Token' );
		$this->_savelanguage();
		return $this->language();
	}

	public function latest() {
		return $this->language();
	}

	public function savecss() {
		JSession::checkToken() || die('Invalid Token');

		$file = hikaInput::get()->getCmd('file');
		if(!preg_match('#^([-_a-z0-9]*)_([-_a-z0-9]*)$#i',$file,$result)){
			hikashop_display('Could not load the file '.htmlentities($file).' properly');
			exit;
		}
		$type = $result[1];
		$fileName = JFile::makeSafe($result[2]);
		jimport('joomla.filesystem.file');
		$path = HIKASHOP_MEDIA.'css'.DS.$type.'_'.$fileName.'.css';
		if(!JPath::check($path)) {
			hikashop_display(JText::sprintf('FAIL_SAVE','invalid filename'),'error');
			return $this->css();
		}
		$csscontent = hikaInput::get()->getString('csscontent');
		$alreadyExists = file_exists($path);
		if(JFile::write($path, $csscontent)){
			$varName = hikaInput::get()->getCmd('var');
			$configName = 'css_'.$varName;
			$config =& hikashop_config();
			$newConfig = new stdClass();
			$newConfig->$configName = $fileName;
 			$config->save($newConfig);
			hikashop_display(JText::_('HIKASHOP_SUCC_SAVED'),'success');
			if(!$alreadyExists){
				$js = "var optn = document.createElement(\"OPTION\");
						optn.text = '$fileName'; optn.value = '$fileName';
						mydrop = window.top.document.getElementById('".$varName."_choice');
						mydrop.options.add(optn);
						lastid = 0; while(mydrop.options[lastid+1]){lastid = lastid+1;} mydrop.selectedIndex = lastid;";
				$doc = JFactory::getDocument();
				$doc->addScriptDeclaration( $js );
			}
		}else{
			hikashop_display(JText::sprintf('FAIL_SAVE',$path),'error');
		}
		return $this->css();
	}

	public function css() {
		hikaInput::get()->set('layout', 'css');
		return parent::display();
	}


	public function send() {
		JSession::checkToken() || die( 'Invalid Token' );
		$bodyEmail = hikaInput::get()->getString('mailbody');
		$code = hikaInput::get()->getString('code');
		hikaInput::get()->set('code',$code);
		if(empty($code)) return;
		$config =& hikashop_config();
		$user = hikashop_loadUser(true);
		$mailClass = hikashop_get('class.mail');
		$addedName = $config->get('add_names',true) ? $mailClass->cleanText(@$user->name) : '';
		$true = true;
		$mail = $mailClass->get('language',$true);
		$mailClass->mailer->AddAddress($user->user_email,$addedName);
		$mailClass->mailer->AddAddress('translate@hikashop.com','Hikashop Translation Team');
		$mail->subject = '[HIKASHOP LANGUAGE FILE] '.$code;
		$mail->altbody = 'The website '.HIKASHOP_LIVE.' using HikaShop '.$config->get('level').$config->get('version').' sent a language file : '.$code;
		$mail->altbody .= "\n"."\n"."\n".$bodyEmail;
		$mail->html=0;
		jimport('joomla.filesystem.file');
		$path = JPath::clean(hikashop_getLanguageFile($code));
		$result = parse_ini_file((string)$path);
		if(!$result){
			hikashop_display('There is an parsing error in your language file. So the file could not be sent. Please activate the "debug language" setting of the Joomla configuration and look at the debug information at the bottom of the page. It will tell you on which line of the file you have a problem.', 'success');
			return;
		}
		$mailClass->mailer->AddAttachment($path);
		$result = $mailClass->sendMail($mail);

		if($result){
			hikashop_display(JText::_('THANK_YOU_SHARING'),'success');
		}
		$this->display();
	}

	public function share() {
		JSession::checkToken() || die('Invalid Token');

		if($this->_savelanguage()) {
			hikaInput::get()->set( 'layout', 'share' );
			return parent::display();
		}
		return $this->language();
	}

	protected function _savelanguage() {
		$code = hikaInput::get()->getString('code');
		hikaInput::get()->set('code', $code);
		if(empty($code))
			return;

		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');

		$content = hikaInput::get()->getRaw('content', '');
		$content_override = hikaInput::get()->getRaw('content_override', '');

		$folder = hikashop_getLanguagePath(JPATH_ROOT).DS.'overrides';
		jimport('joomla.filesystem.folder');
		if(!is_dir($folder)) {
			JFolder::create($folder);
		}
		if(is_dir($folder)) {
			$path = $folder.DS.$code.'.override.ini';
			if(!JPath::check($path)) {
				hikashop_display(JText::sprintf('FAIL_SAVE','invalid filename'),'error');
				return false;
			}
			$result = JFile::write($path, $content_override);
			if(!$result) {
				hikashop_display(JText::sprintf('FAIL_SAVE',$path),'error');
			}
		}

		if(empty($content))
			return;

		$path = hikashop_getLanguageFile($code);
		if(!JPath::check($path)) {
			hikashop_display(JText::sprintf('FAIL_SAVE','invalid filename'),'error');
			return false;
		}
		$result = JFile::write($path, $content);
		if($result) {
			hikashop_display(JText::_('HIKASHOP_SUCC_SAVED'),'success');
			$updateHelper = hikashop_get('helper.update');
			$updateHelper->installMenu($code);
		} else {
			hikashop_display(JText::sprintf('FAIL_SAVE',$path),'error');
		}

		if(defined('HIKASHOP_WORDPRESS')) {
			$content_wordpress = hikaInput::get()->getRaw('content_wordpress', '');
			if(strlen($content_wordpress)) {
				$wp_path = hikashop_getLanguagePath(JPATH_ROOT).DS.$code.'.wordpress.ini';
				if(JPath::check($wp_path))
					JFile::write($wp_path, $content_wordpress);
			}
		}

		return $result;
	}

	public function getUploadSetting($upload_key, $caller = '') {
		if(empty($upload_key))
			return false;

		$upload_value = null;
		$upload_keys = array(
			'default_image' => array(
				'type' => 'image',
				'field' => 'config[default_image]'
			),
			'watermark' => array(
				'type' => 'image',
				'field' => 'config[watermark]',
				'delete' => true,
				'uploader_id' => 'hikashop_config_watermark_image'
			)
		);

		if(empty($upload_keys[$upload_key]))
			return false;
		$upload_value = $upload_keys[$upload_key];

		return array(
			'limit' => 1,
			'type' => $upload_value['type'],
			'options' => array(),
			'extra' => array(
				'delete' => !empty($upload_value['delete']),
				'uploader_id' => (!empty($upload_value['uploader_id']) ? $upload_value['uploader_id'] : null),
				'field_name' => $upload_value['field']
			)
		);
	}

	public function manageUpload($upload_key, &$ret, $uploadConfig, $caller = '') {
		if(empty($ret) || empty($ret->name))
			return;

		$upload_keys = array(
			'default_image' => true,
			'watermark' => true
		);
		if(empty($upload_keys[$upload_key]))
			return;

		$data = array(
			$upload_key => $ret->name
		);
		$config = hikashop_config();
		$config->save($data);
	}

	public function export_fields() {
		hikaInput::get()->set('layout', 'export_fields');
		return parent::display();
	}

	public 	function save_export_fields() {
		JSession::checkToken() or jexit('Access forbidden');
		$type = hikaInput::get()->getWord('type');
		$columns = hikaInput::get()->get('columns', array(), 'array');

		if($type) {
			$classType = 'class.'.$type;
			$class = hikashop_get($classType);
			if(method_exists($class, 'getExportColumns')) {
				$allowed_columns = $class->getExportColumns(false);
				$columns = array_intersect($columns, array_keys($allowed_columns));
			} else {
				$columns = array();
			}

			$config = hikashop_config();
			$save = array(
				$type.'_export_columns' => implode(',', $columns)
			);
			if($type == 'order') {
				$save['order_export_one_product_per_row'] = hikaInput::get()->getInt('order_export_one_product_per_row', 0);
			}
			$config->save($save);
		}

		$app = JFactory::getApplication();
		$app->enqueueMessage(JText::_('HIKA_SAVED'));
		echo '<script>window.parent.hikashop.closeBox();</script>';
		$app->close();
	}

	public function checkdb() {
		$databaseHelper = hikashop_get('helper.database');
		$html = $databaseHelper->checkdb();

		hikaInput::get()->set('layout', 'checkdb');
		return parent::display();
	}

	public function checkout_newblock() {
		$name = hikaInput::get()->get('name', '');
		if(empty($name))
			exit;
		hikashop_cleanBuffers();
		$checkout_workflowType = hikashop_get('type.checkout_workflow');
		echo $checkout_workflowType->newBlock($name);
		exit;
	}

	public function checkout_newstep() {
		$num = hikaInput::get()->getInt('num', -1);
		if($num < 0)
			exit;
		hikashop_cleanBuffers();
		$checkout_workflowType = hikashop_get('type.checkout_workflow');
		echo $checkout_workflowType->newStep($num);
		exit;
	}

	public function addresslayout() {
		hikaInput::get()->set('layout', 'address_layout');
		return parent::display();
	}

	public function check_update() {
		JSession::checkToken('get') || die('Invalid Token');

		$config = hikashop_config();
		$version = $config->get('version', '');
		$level = $config->get('level', 'Starter');

		$upgradeLevel = hikaInput::get()->getCmd('upgrade_level', '');
		$isUpgrade = !empty($upgradeLevel);
		$targetLevel = $isUpgrade ? $upgradeLevel : $level;

		if(!in_array($targetLevel, array('Starter', 'Essential', 'Business'))) {
			return $this->_jsonResponse(array('error' => JText::_('HIKA_ERROR_PARSING_RESPONSE')));
		}

		$isPaid = ($targetLevel !== 'Starter');
		if($isPaid) {
			$url = 'https://www.hikashop.com/component/updateme/updatexml/component-hikashop/version-' . $version . '/level-' . $targetLevel . '/li-' . urlencode(base64_encode(HIKASHOP_LIVE)) . '/file-extension.xml';
		} else {
			$url = 'https://www.hikashop.com/component/updateme/updatexml/component-hikashop/level-' . $targetLevel . '/file-extension.xml';
		}

		$response = $this->_fetchUrl($url);
		if($response === false) {
			return $this->_jsonResponse(array('error' => JText::_('HIKA_ERROR_CONNECTING')));
		}

		$xml = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NONET);
		if($xml === false) {
			return $this->_jsonResponse(array('error' => JText::_('HIKA_ERROR_PARSING_RESPONSE')));
		}

		$newVersion = '';
		$packageUrl = '';
		if(isset($xml->update)) {
			$update = $xml->update;
			if(isset($update->version))
				$newVersion = (string)$update->version;
			if(isset($update->downloads->downloadurl))
				$packageUrl = (string)$update->downloads->downloadurl;
		}

		if(empty($newVersion) || empty($packageUrl)) {
			if($isUpgrade) {
				return $this->_jsonResponse(array('domain_not_found' => true));
			}
			return $this->_jsonResponse(array('update_available' => false));
		}

		if(!$isUpgrade && version_compare($newVersion, $version, '<=')) {
			return $this->_jsonResponse(array('update_available' => false));
		}

		return $this->_jsonResponse(array(
			'update_available' => true,
			'new_version' => $newVersion,
			'package_url' => $packageUrl,
		));
	}

	public function install_update() {
		JSession::checkToken() || die('Invalid Token');

		$packageUrl = hikaInput::get()->getString('package_url', '');
		$parsedUrl = !empty($packageUrl) ? parse_url($packageUrl) : false;
		$host = !empty($parsedUrl['host']) ? strtolower($parsedUrl['host']) : '';
		if(empty($packageUrl) || !in_array($parsedUrl['scheme'], array('http', 'https')) || ($host !== 'www.hikashop.com' && $host !== 'hikashop.com')) {
			return $this->_jsonResponse(array('success' => false, 'error' => JText::_('HIKA_INVALID_PACKAGE_URL')));
		}

		if(defined('ABSPATH')) {
			require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
			require_once(ABSPATH . 'wp-admin/includes/file.php');
			require_once(ABSPATH . 'wp-admin/includes/misc.php');
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');

			$skin = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Plugin_Upgrader($skin);

			$tmpFile = download_url($packageUrl);
			if(is_wp_error($tmpFile)) {
				return $this->_jsonResponse(array('success' => false, 'error' => $tmpFile->get_error_message()));
			}

			$pluginBasename = plugin_basename(HIKASHOP_WP_PLUGIN_FILE);
			$result = $upgrader->install($tmpFile, array('overwrite_package' => true));
			@unlink($tmpFile);

			if(is_wp_error($result)) {
				return $this->_jsonResponse(array('success' => false, 'error' => $result->get_error_message()));
			}
			if($result === false) {
				return $this->_jsonResponse(array('success' => false, 'error' => JText::_('HIKA_ERROR_INSTALLING')));
			}

			delete_transient('hikashop_update_info');

			return $this->_jsonResponse(array('success' => true));
		} else {
			jimport('joomla.installer.helper');
			jimport('joomla.installer.installer');

			$tmpFile = JInstallerHelper::downloadPackage($packageUrl);
			if(!$tmpFile) {
				return $this->_jsonResponse(array('success' => false, 'error' => JText::_('HIKA_ERROR_CONNECTING')));
			}

			$config = JFactory::getConfig();
			$tmpPath = $config->get('tmp_path', JPATH_ROOT . '/tmp');
			$package = JInstallerHelper::unpack($tmpPath . '/' . $tmpFile);
			if(!$package || empty($package['dir'])) {
				return $this->_jsonResponse(array('success' => false, 'error' => JText::_('HIKA_ERROR_INSTALLING')));
			}

			$installer = JInstaller::getInstance();
			$result = $installer->install($package['dir']);

			JInstallerHelper::cleanupInstall($tmpPath . '/' . $tmpFile, $package['dir']);

			if(!$result) {
				return $this->_jsonResponse(array('success' => false, 'error' => JText::_('HIKA_ERROR_INSTALLING')));
			}

			return $this->_jsonResponse(array('success' => true));
		}
	}

	protected function _fetchUrl($url) {
		if(defined('ABSPATH') && function_exists('wp_remote_get')) {
			$response = wp_remote_get($url, array('timeout' => 30));
			if(is_wp_error($response))
				return false;
			return wp_remote_retrieve_body($response);
		}

		if(function_exists('curl_init')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$result = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if($httpCode >= 200 && $httpCode < 400 && $result !== false)
				return $result;
			return false;
		}

		$result = @file_get_contents($url);
		return ($result !== false) ? $result : false;
	}

	protected function _jsonResponse($data) {
		hikashop_cleanBuffers();
		header('Content-Type: application/json');
		echo json_encode($data);
		exit;
	}

	public function saveaddresslayout() {
		$layout = hikaInput::get()->getString('address_form_layout', '');


		$decoded = json_decode($layout, true);
		if($layout !== '' && $layout !== 'null' && $decoded === null) {
			echo json_encode(array('success' => false, 'error' => 'Invalid JSON'));
			exit;
		}

		$clean = array();
		if(is_array($decoded)) {
			$clean['enabled'] = !empty($decoded['enabled']);
			$clean['columns'] = isset($decoded['columns']) ? (int)$decoded['columns'] : 4;

			$validLabelPositions = array('above', 'yes', 'no');
			$clean['label_position'] = (isset($decoded['label_position']) && in_array($decoded['label_position'], $validLabelPositions)) 
				? $decoded['label_position'] : 'above';

			$clean['rows'] = array();
			if(!empty($decoded['rows']) && is_array($decoded['rows'])) {
				foreach($decoded['rows'] as $row) {
					$cleanRow = array('fields' => array());
					if(!empty($row['fields']) && is_array($row['fields'])) {
						foreach($row['fields'] as $field) {
							if(empty($field['namekey'])) continue;

							$cleanField = array(
								'namekey' => preg_replace('/[^a-zA-Z0-9_\-]/', '', $field['namekey']),
								'width' => isset($field['width']) ? (int)$field['width'] : 4
							);

							if(isset($field['label_position']) && in_array($field['label_position'], $validLabelPositions)) {
								$cleanField['label_position'] = $field['label_position'];
							}

							$cleanRow['fields'][] = $cleanField;
						}
					}
					if(!empty($cleanRow['fields'])) {
						$clean['rows'][] = $cleanRow;
					}
				}
			}
		}

		$layout = json_encode($clean);

		$config = hikashop_config();
		$saveData = array('address_form_layout' => $layout);
		$status = $config->save($saveData);

		echo json_encode(array('success' => $status));
		exit;
	}
}
