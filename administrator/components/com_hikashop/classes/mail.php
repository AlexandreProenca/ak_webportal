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
class hikashopMailClass {
	public $mail_success = true;
	public $_force_embed = false;
	public $mail_folder = '';
	public $mailer = null;

	public function __construct() {
		$this->mailer = JFactory::getMailer();
	}

	public function get($name, &$data) {
		$app = JFactory::getApplication();

		$this->mailer = JFactory::getMailer();

		$mail = new stdClass();
		$mail->mail_name = $name;

		$this->loadInfos($mail, $name);

		$mail->body = $this->loadEmail($mail, $data);
		$mail->altbody = $this->loadEmail($mail, $data, 'text');
		if(hikashop_isClient('administrator'))
			$mail->preload = $this->loadEmail($mail, $data, 'preload');
		$mail->data =& $data;

		if($data !== true)
			$mail->body = hikashop_absoluteURL($mail->body);

		if(empty($mail->altbody) && $data !== true)
			$mail->altbody = $this->textVersion($mail->body);

		return $mail;
	}

	public function getFiles() {
		$files = array(
			'cron_report',
			'order_admin_notification',
			'order_creation_notification',
			'order_status_notification',
			'order_notification',
			'payment_notification',
			'user_account',
			'user_account_admin_notification',
			'out_of_stock',
			'order_cancel',
			'waitlist_notification',
			'waitlist_admin_notification',
			'new_comment',
			'contact_request',
			'subscription_eot',
			'massaction_notification',
			'wishlist_share',
			'withdrawal_admin_notification',
			'withdrawal_user_notification',
		);

		$plugin_files = array();
		JPluginHelper::importPlugin('hikashop');
		$app = JFactory::getApplication();
		$app->triggerEvent('onMailListing', array(&$plugin_files));
		if(!empty($plugin_files)) {
			$files = array_merge($files, $plugin_files);
		}

		return $files;
	}

	public function loadInfos(&$mail, $name) {
		$config =& hikashop_config();

		$mail->name_info = $name;

		$settings = array(
			'from_name' => true,
			'from_email' => true,
			'reply_name' => true,
			'reply_email' => true,

			'template' => array('config' => 'mail_default_template'),

			'bcc_email' => false,
			'cc_email' => false,
			'subject' => false,

			'html' => array('default' => 1),
			'published' => array('default' => 1),
			'email_log_published' => array('default' => 1),
			'payment_methods' => array('default' => ''),
		);
		foreach($settings as $k => $v) {
			$default = (is_array($v) && isset($v['default'])) ? $v['default'] : null;
			$mail->$k = $config->get($name . '.' . $k, $default);
			if(empty($mail->$k) && $v === true)
				$mail->$k = $config->get($k);
			if(empty($mail->$k) && is_array($v) && isset($v['config']))
				$mail->$k = $config->get( $v['config'] );
		}

		$attach = $config->get($name.'.attach');
		$mail->attach = array();
		if(!empty($attach)) {
			$mail->attach = hikashop_unserialize($attach);
		}
	}

	 public function getPreviewMaker($mail_name) {
	 	$class_name = $mail_name.'PreviewMaker';

		if(strpos($class_name, '.'))
			$class_name = str_replace('.', '_', $class_name);

		if(!class_exists($class_name)) {
		 	$path = $this->getMailPath($mail_name, 'preview');
			if(empty($path))
				return false;

			include_once($path);
		}
		if(!class_exists($class_name))
			return false;

		return new $class_name;
	 }

	public function saveForm() {
	}


	public function save(&$element) {
		if(empty($element->mail_name))
			return false;
	}

	public function saveEmail($name, $data, $type = 'html') {
	}

	public function delete(&$mail_name, $type = '') {
		return true;
	}

	public function loadEmail(&$mail, &$data, $type = 'html') {
		$path = $this->getMailPath($mail, $type);

		if(empty($path))
			return '';

		if($data === true) {
			jimport('joomla.filesystem.file');
			return file_get_contents($path);
		}

		$preload = $this->getMailPath($mail, 'preload');
		$postload = $this->getMailPath($mail, 'postload');

		if($mail->mail_name == 'order_status_notification' && !empty($data->order_status)) {
			$mail_status_name = $mail->mail_name.'.'.$data->order_status;
			jimport('joomla.filesystem.file');
			$mail_status_name = JFile::makeSafe($mail_status_name);
			$pathWithStatus = $this->getMailPath($mail_status_name, $type);
			if(!empty($pathWithStatus)) $path = $pathWithStatus;

			$preloadWithStatus = $this->getMailPath($mail_status_name, 'preload');
			if(!empty($preloadWithStatus)) $preload = $preloadWithStatus;

			$postloadWithStatus = $this->getMailPath($mail_status_name, 'postload');
			if(!empty($postloadWithStatus)) $postload = $postloadWithStatus;
		}

		$currencyClass = hikashop_get('class.currency');
		$currencyHelper = $currencyClass;

		ob_start();

		$useTemplating = !empty($mail->template);
		$vars = array();
		$texts = array();
		$templates = array();

		if(!empty($preload) && file_exists($preload))
			include $preload;

		if($useTemplating || !empty($vars) || !empty($texts) || !empty($templates))
			ob_start();

		require($path);

		if(!empty($postload) && file_exists($postload)) {
			include $postload;
		} else if($useTemplating || !empty($vars) || !empty($texts) || !empty($templates)) {

			$body = $this->processMailTemplate($mail, $data, $texts, $vars, $templates);
			if(!empty($mail->template) && $type == 'html') {
				$templateFromJoomla = false;
				if($mail->template == 'joomla' && HIKASHOP_J50) {
					$config = \Joomla\CMS\Component\ComponentHelper::getParams('com_mails');
					$style = $config->get('mail_style', '');
					$disable_layout = (bool)$config->get('disable_htmllayout', true);
					if(in_array($style, array('html', 'both')) && $disable_layout) {
						$layout = $config->get('mail_htmllayout', 'mailtemplate');
						$logo   = (string) $config->get('mail_logofile', '');

						$layoutParts = explode(':', $layout);
						if (count($layoutParts) === 2) {
							$layout = $layoutParts[1];
						}
						$layoutFile = new \Joomla\CMS\Layout\FileLayout('joomla.mail.' . $layout, null, ['client' => 'site']);
						if (count($layoutParts) === 2) {
							$layoutFile->addIncludePaths([
								JPATH_SITE . '/templates/' . $layoutParts[0] . '/html/layouts',
								JPATH_SITE . '/templates/' . $layoutParts[0] . '/html/layouts/com_mails',
							]);
						}

						$lang = JFactory::getLanguage();
						$app = JFactory::getApplication();
						$layoutTemplateData = array(
							'siteName' => $app->get('sitename'),
							'lang'     => substr($lang->getTag(), 0, 2)
						);

						if ($logo) {
							$logo = HIKASHOP_LIVE . \Joomla\CMS\HTML\HTMLHelper::_('cleanImageURL', $logo)->url;
							$layoutTemplateData['logo'] = 'site-logo';
						}

						$templateFromJoomla = true;
						$body = $layoutFile->render(['mail' => $body, 'extra' => $layoutTemplateData], null);

						if ($logo) {
							$body = str_replace('cid:site-logo', $logo, $body);
						}
					}
				}
				if(!$templateFromJoomla) {
					$tpl_path = $this->getTemplatePath($mail->template, $mail->mail_name, $type);
					if(empty($tpl_path))
						$tpl_path = $this->getTemplatePath('default', $mail->mail_name, $type);

					if(!empty($tpl_path)) {
						$vars['TPL_CONTENT'] = $body;
						ob_start();
						require($tpl_path);
						$body = $this->processMailTemplate($mail, $data, $texts, $vars, $templates);
					}
				}
			}

			echo $body;
		}

		unset($vars);
		unset($texts);
		unset($templates);

		$ret = ob_get_clean();
		return trim($ret);
	}

	public function processMailTemplate(&$mail, &$data, $texts, $vars, $templates = array()) {
		$content = ob_get_clean();

		JPluginHelper::importPlugin('hikashop');
		$app = JFactory::getApplication();
		$app->triggerEvent('onHkProcessMailTemplate', array(&$mail, &$data, &$content, &$vars, &$texts, &$templates));

		if(empty($content))
			return '';

		if(!empty($templates)) {
			foreach($templates as $key => $templateVariables) {
				$cursorStartLength = strlen('<!--{START:'.$key.'}-->');
				$cusorEndLength = strlen('<!--{END:'.$key.'}-->');

				$cursorStart = strpos($content, '<!--{START:'.$key.'}-->');
				$cursorEnd = strpos($content, '<!--{END:'.$key.'}-->');

				while($cursorStart !== false && $cursorEnd !== false) {
					$templateData = '';
					$templateContent = '';
					if($cursorStart !== false && $cursorEnd !== false) {
						$templateContent = substr($content, $cursorStart + $cursorStartLength, $cursorEnd - $cursorStart - $cursorStartLength);
						if(strpos($content, '{VAR:'.$key.'}') === false)
							$content = substr($content, 0, $cursorStart) . '{VAR:'.$key.'}' . substr($content, $cursorEnd + $cusorEndLength);
						else
							$content = substr($content, 0, $cursorStart) . substr($content, $cursorEnd + $cusorEndLength);
					}
					if(!empty($templateContent) && !empty($templateVariables)) {
						foreach($templateVariables as $c) {
							$d = $templateContent;
							foreach($c as $k => $v) {
								if(is_string($v) || is_int($v) || is_float($v)) {
									$d = str_replace('{LINEVAR:'.$k.'}', $v, $d);
								} else if(is_object($v) || is_array($v)) {
									foreach($v as $objK => $objV) {
										if(is_string($objV) || is_int($objV) || is_float($objV))
											$d = str_replace('{LINEVAR:'.$k.'.'.$objK.'}', $objV, $d);
									}
								}
							}
							$templateData .= $d;
						}
						$content = str_replace('{VAR:'.$key.'}', $templateData, $content);
					}
					$cursorStart = strpos($content, '<!--{START:'.$key.'}-->');
					$cursorEnd = strpos($content, '<!--{END:'.$key.'}-->');
				}
			}
		}

		foreach($texts as $k => $v) {
			if(is_null($v))
				$v = '';
			if(is_string($v) || is_int($v) || is_float($v))
				$content = str_replace('{TXT:'.$k.'}', $v, $content);
		}

		foreach($vars as $k => $v) {
			if(is_string($v) || is_int($v) || is_float($v))
				$content = str_replace('{VAR:'.$k.'}', $v, $content);
			else if(is_object($v) || is_array($v)) {
				foreach($v as $objK => $objV) {
					if(is_string($objV) || is_int($objV) || is_float($objV))
						$content = str_replace('{VAR:'.$k.'.'.$objK.'}', $objV, $content);
				}
			}

			$startIf = '<!--{IF:'.$k.'}-->';
			$endIf = '<!--{ENDIF:'.$k.'}-->';
			if(empty($v)) {
				$cursorStart = strpos($content, $startIf);
				$cursorEnd = strpos($content, $endIf);
				if($cursorStart !== false && $cursorEnd !== false) {
					$content = substr($content, 0, $cursorStart) . substr($content, $cursorEnd + strlen($endIf));
				} else {
					$content = str_replace(array($startIf, $endIf), '', $content);
				}
			} else {
				$content = str_replace(array($startIf, $endIf), '', $content);
			}
		}
		if(strpos($content, '<!--{IF:') !== false) {
			$out = array();
			if(preg_match_all('#<!--{IF:([- _A-Z0-9a-z]+)}-->#U', $content, $out)) {
				foreach($out[1] as $key) {
					$startIf = '<!--{IF:'.$key.'}-->';
					$endIf = '<!--{ENDIF:'.$key.'}-->';
					$cursorStart = strpos($content, $startIf);
					$cursorEnd = strpos($content, $endIf);
					if($cursorStart !== false && $cursorEnd !== false) {
						$content = substr($content, 0, $cursorStart) . substr($content, $cursorEnd + strlen($endIf));
					} else {
						$content = str_replace(array($startIf, $endIf), '', $content);
					}
				}
			}
		}

		if(strpos($content, '{TXT:') !== false) {
			$out = array();
			if(preg_match_all('#{TXT:([- _A-Z0-9a-z]+)}#U', $content, $out)) {
				foreach($out[1] as $key) {
					if(isset($texts[$key]))
						$content = str_replace('{TXT:'.$key.'}', $texts[$key], $content);
					else
						$content = str_replace('{TXT:'.$key.'}', JText::_($key), $content);
				}
			}
		}

		if(strpos($content, '{VAR:') !== false) {
			$out = array();
			if(preg_match_all('#{VAR:([-. _A-Z0-9a-z]+)}#U', $content, $out)) {
				foreach($out[1] as $key) {
					$content = str_replace('{VAR:'.$key.'}', '', $content);
				}
			}
		}

		if(strpos($content, '{LINEVAR:') !== false) {
			$out = array();
			if(preg_match_all('#{LINEVAR:([-. _A-Z0-9a-z]+)}#U', $content, $out)) {
				foreach($out[1] as $key) {
					$content = str_replace('{LINEVAR:'.$key.'}', '', $content);
				}
			}
		}

		return $content;
	}

	public function getMailPath(&$mail_name, $type = 'html', $getModifiedFile = false) {
		if(empty($this->mail_folder)) {
			$config = hikashop_config();
			$this->mail_folder = rtrim( str_replace( '{root}', JPATH_ROOT, $config->get('mail_folder', HIKASHOP_MEDIA.'mail'.DS)), '/\\');
			if(!empty($this->mail_folder))
				$this->mail_folder .= DS;
		}
		if(empty($this->mail_folder)) {
			$this->mail_folder = HIKASHOP_MEDIA.'mail'.DS;
		}

		if(is_object($mail_name)) {
			$name = $mail_name->mail_name;
		} else {
			$name = $mail_name;
		}

		if(strpos($name, '..') !== false)
			return false;

		$name = str_replace(array('\\', '/'), DS, $name);

		$override_path_name = $type.'_override_path';
		$path_name = $type.'_path';
		$override_name = $type.'_override';

		$override_path = $this->mail_folder . $name . '.' . $type . '.modified.php';
		$path = $this->mail_folder . $name . '.' . $type . '.php';
		if(is_object($mail_name)) {
			$mail_name->$override_path_name = $override_path;
			$mail_name->$path_name = $path;
		}

		if(!file_exists($path)) {

			$plugin_files = array();
			JPluginHelper::importPlugin('hikashop');
			$app = JFactory::getApplication();
			$app->triggerEvent('onMailListing', array(&$plugin_files));
			if(!empty($plugin_files)) {
				$mail_folder = '';
				$mail_file = '';
				foreach($plugin_files as $plugin_file) {
					if($plugin_file['file'] == $name || $plugin_file['filename'] == $name) {
						$mail_folder = @$plugin_file['folder'];
						$mail_file = $plugin_file['filename'];
						break;
					}
				}
				if(!empty($mail_file)) {
					if(empty($mail_folder))
						$mail_folder = $this->mail_folder;

					$override_path = $mail_folder . $mail_file . '.' . $type . '.modified.php';
					$path = $mail_folder . $mail_file . '.' . $type . '.php';
					if(is_object($mail_name)) {
						$mail_name->$override_path_name = $override_path;
						$mail_name->$path_name = $path;
					}
					if(file_exists($override_path)) {
						if(is_object($mail_name)) {
							$mail_name->$override_name = true;
						}
						return $override_path;
					}
					if(file_exists($path)) {
						if($getModifiedFile)
							return $override_path;
						return $path;
					}
					if($type == 'text' && $getModifiedFile && file_exists($mail_folder . $mail_file . '.html.php'))
						return $override_path;
					return '';
				}
			}
		}

		if(file_exists($override_path)) {
			if(is_object($mail_name)) {
				$mail_name->$override_name = true;
			}
			return $override_path;
		}

		if(!file_exists($path))
			return '';

		if($getModifiedFile)
			return $override_path;
		return $path;

	}

	public function getTemplatePath($template_name, $mail_name, $type = 'html') {
		if(empty($this->mail_folder)) {
			$config = hikashop_config();
			$this->mail_folder = rtrim( str_replace('{root}', JPATH_ROOT, $config->get('mail_folder', HIKASHOP_MEDIA.'mail'.DS)), '/\\');
			if(!empty($this->mail_folder))
				$this->mail_folder .= DS;
		}

		if(empty($this->mail_folder))
			$this->mail_folder = HIKASHOP_MEDIA.'mail'.DS;

		if(strpos($template_name, '..') !== false || (!empty($mail_name) && strpos($mail_name, '..') !== false))
			return false;

		$template_name = str_replace(array('\\', '/'), DS, $template_name);
		$mail_name = str_replace(array('\\', '/'), DS, $mail_name);

		$path = $this->mail_folder . 'template' . DS . $template_name . '.' . $type . '.modified.php';
		if(file_exists($path))
			return $path;
		$path = $this->mail_folder . 'template' . DS . $template_name . '.' . $type . '.php';
		if(file_exists($path))
			return $path;
		$path = $this->mail_folder . 'template' . DS . $template_name . '.php';
		if(file_exists($path))
			return $path;

		$external_template_files = array();
		JPluginHelper::importPlugin('hikashop');
		$app = JFactory::getApplication();
		$app->triggerEvent('onMailTemplateListing', array(&$external_template_files, $mail_name));
		if(empty($external_template_files))
			return false;

		$mail_folder = '';
		$mail_file = '';
		foreach($external_template_files as $k => $f) {
			if($f['file'] != $template_name)
				continue;

			$mail_folder = @$f['folder'];
			if(empty($mail_folder))
				$mail_folder = $this->mail_folder . 'template' . DS;

			$mail_file = $f['filename'];
			$path = $mail_folder . $mail_file . '.' . $type . '.modified.php';
			if(file_exists($path))
				return $path;
			$path = $mail_folder . $mail_file . '.' . $type . '.php';
			if(file_exists($path))
				return $path;
			$path = $mail_folder . $mail_file . '.php';
			if(file_exists($path))
				return $path;
		}
		return false;
	}

	public function preProcessMail(&$mail){

		$config =& hikashop_config();

		$this->mailer->CharSet = $config->get('charset');
		if(empty($this->mailer->CharSet)) $this->mailer->CharSet = 'utf-8';

		$this->mailer->Encoding = $config->get('encoding_format');
		if(empty($this->mailer->Encoding)) $this->mailer->Encoding = 'base64';

		$this->mailer->WordWrap = intval($config->get('word_wrapping',0));

		$this->mailer->Sender = $this->cleanText($config->get('bounce_email'));
		if(empty($this->mailer->Sender)) $this->mailer->Sender = '';

		if(!empty($mail->dst_email)){
			if(!is_array($mail->dst_email) && strpos($mail->dst_email,',')){
				$mail->dst_email = explode(',',$mail->dst_email);
			}
			if(is_array($mail->dst_email)){
				if(HIKASHOP_J30){
					foreach($mail->dst_email as $dst_mail){
						$this->mailer->addRecipient($dst_mail);
					}
				}else{
					$this->mailer->addRecipient($mail->dst_email);
				}
			}else{
				$addedName = $config->get('add_names',true) ? $this->cleanText(@$mail->dst_name) : '';
				$this->mailer->AddAddress($this->cleanText($mail->dst_email),(string)$addedName);
			}
		}
		if(!empty($mail->cc_email)) {
			if(!is_array($mail->cc_email))
				$mail->cc_email = explode(',', $mail->cc_email);
			if(HIKASHOP_J30){
				foreach($mail->cc_email as $cc_email){
					$this->mailer->addCC($cc_email);
				}
			}else{
				$this->mailer->addCC($mail->cc_email);
			}
		}
		if(!empty($mail->bcc_email)) {
			if(!is_array($mail->bcc_email))
				$mail->bcc_email = explode(',', $mail->bcc_email);
			if(HIKASHOP_J30){
				foreach($mail->bcc_email as $bcc_email){
					$this->mailer->addBCC($bcc_email);
				}
			}else{
				$this->mailer->addBCC($mail->bcc_email);
			}
		}
		$this->setFrom($mail->from_email,@$mail->from_name);
		if(!empty($mail->reply_email)) {
			$replyEmail = $this->cleanText($mail->reply_email);
			if($replyEmail !== '' && filter_var($replyEmail, FILTER_VALIDATE_EMAIL)) {
				$replyToName = $config->get('add_names', true) ? (string)$this->cleanText(@$mail->reply_name) : '';
				$this->mailer->addReplyTo($replyEmail, $replyToName);
			}
		}

		$this->processMailSubject($mail);

		$this->mailer->setSubject($mail->subject);
		$this->mailer->IsHTML(@$mail->html);
		if(!empty($mail->html)){
			if(empty($mail->full_body)) {
				$headers = array(
					'<meta http-equiv="Content-Type" content="text/html; charset='.$this->mailer->CharSet.'">',
					'<title>'.$mail->subject.'</title>',
				);
				if(isset($mail->htmlStyle)) {
					$headers[] = '<style type="text/css">'."\r\n".$mail->htmlStyle."\r\n".'</style>';
				}
				$htmlExtra = '';
				$lang = JFactory::getLanguage();
				if($lang->isRTL()) {
					$htmlExtra = ' dir="rtl"';
				}

				$mail->body = preg_replace_callback('#class="w([0-9]+)[a-z_ ]*"(.+?)(?=>)#i', function ($matches){
					if(strpos($matches[2], 'width') !== false)
						return $matches[0];
					return 'width="'.$matches[1].'" '.$matches[0];
				}, $mail->body);

				$favicon = $config->get('email_favicon');
				if(!empty($favicon)) {
					$headers[] = '<link rel="Shortcut Icon" href="'.$favicon.'" />';
				}

				$headers[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';

				if(!empty($mail->extraHeaders) && count($mail->extraHeaders)) {
					$headers = array_merge($headers, $mail->extraHeaders);
				}

				$this->mailer->Body = '<html><head>'.
					implode('', $headers).
					'</head><body class="hikashop_mail"'.$htmlExtra.'>'.hikashop_absoluteURL($mail->body).'</body></html>';
			}else{
				$this->mailer->Body = $mail->body;
			}
			if($config->get('multiple_part',false)){
				if(empty($mail->altbody)){
					$this->mailer->AltBody = $this->textVersion($mail->body);
				}else{
					$this->mailer->AltBody = $mail->altbody;
				}
			}
		}else{
			if(empty($mail->altbody)&&!empty($mail->body))
				$mail->altbody = $this->textVersion($mail->body);
			$this->mailer->Body = $mail->altbody;
		}

		if(empty($mail->attachments) && !empty($mail->mail_name)) {
			$mail->attachments = $this->loadAttachments($mail->mail_name);
		}

		if(!empty($mail->attachments)){
			if($config->get('embed_files') || $this->_force_embed){
				foreach($mail->attachments as $attachment){
					$this->mailer->AddAttachment($attachment->filename);
				}
			}else{
				$attachStringHTML = '<br/><fieldset><legend>'.JText::_( 'ATTACHMENTS' ).'</legend><table>';
				$attachStringText = "\n"."\n".'------- '.JText::_( 'ATTACHMENTS' ).' -------';
				foreach($mail->attachments as $attachment){
					$attachStringHTML .= '<tr><td><a href="'.$attachment->url.'" target="_blank">'.$attachment->name.'</a></td></tr>';
					$attachStringText .= "\n".'-- '.$attachment->name.' ( '.$attachment->url.' )';
				}
				$attachStringHTML .= '</table></fieldset>';
				if(@$mail->html){
					$this->mailer->Body = str_replace('</body></html>',$attachStringHTML.'</body></html>',$this->mailer->Body);
					if(!empty($this->mailer->AltBody))
						$this->mailer->AltBody .= "\n".$attachStringText;
				}else{
					$this->mailer->Body .= $attachStringText;
				}
			}
		}

		JPluginHelper::importPlugin('hikashop');
		JPluginHelper::importPlugin('hikashopshipping');
		JPluginHelper::importPlugin('hikashoppayment');
		$app = JFactory::getApplication();
		$app->triggerEvent('onBeforeMailSend', array(&$mail, &$this->mailer) );
	}

	public function sendMail(&$mail) {
		if(empty($mail))
			return false;
		if(isset($mail->published) && !$mail->published)
			return true;

		if(@$mail->payment_methods == 'all') $mail->payment_methods = '';
		if(!empty(($mail->payment_methods)) && isset($mail->data->cart->order_payment_id)) {
			$mail->payment_methods = explode(',',trim((string)$mail->payment_methods,','));
			if($mail->payment_methods && count($mail->payment_methods) && !in_array($mail->data->cart->order_payment_id, $mail->payment_methods)) {
				hikashop_writeToLog('Sending of email ' . $mail->mail_name.' skipped because the payment method (id: '.$mail->data->cart->order_payment_id.') of the order (id: '.$mail->data->cart->order_id.') did not match with the selected payment methods for this email.');
				return true;
			}
		}

		$do = true;
		JPluginHelper::importPlugin('hikashop');
		JPluginHelper::importPlugin('hikashopshipping');
		JPluginHelper::importPlugin('hikashoppayment');
		$app = JFactory::getApplication();
		$mail->mail_success = false;
		$app->triggerEvent('onBeforeMailPrepare', array(&$mail, &$this->mailer, &$do) );
		if(!$do) {
			if($mail->mail_success == true)
				$this->mail_success = true;
			else
				$this->mail_success = false;
			return false;
		}
		$config = hikashop_config();

		$result = true;
		try {
			$this->preProcessMail($mail);
			if((bool)$config->get('embed_images',0)){
				$this->embedImages();
			}

			if(strtoupper($this->mailer->CharSet) != 'UTF-8'){
				$encodingHelper = hikashop_get('helper.encoding');
				$this->mailer->Body = $encodingHelper->change($this->mailer->Body,'UTF-8',$this->mailer->CharSet);
				$this->mailer->Subject = $encodingHelper->change($this->mailer->Subject,'UTF-8',$this->mailer->CharSet);
				if(!empty($this->mailer->AltBody))
					$this->mailer->AltBody = $encodingHelper->change($this->mailer->AltBody,'UTF-8',$this->mailer->CharSet);
			}
			$this->mailer->Body = str_replace(" ",' ',$this->mailer->Body);

			$result = $this->mailer->Send();
		} catch( Joomla\CMS\Mail\Exception\MailDisabledException $e) {
			$result = false;
			$app->enqueueMessage($e->getMessage(), 'error');
			hikashop_writeToLog($e->getMessage());
		} catch( Exception $e) {
			$result = false;
			if(hikashop_isClient('administrator')) {
				$app->enqueueMessage($e->getMessage(), 'error');
			} else {
				hikashop_writeToLog($e->getMessage());
			}
		}
		if(!$result || !empty($result->message)) {
			$this->mail_success = false;
		}
		if(!empty($result->message)) {
		}

		$app->triggerEvent('onAfterMailSend', array(&$mail, &$this->mailer, $this->mail_success) );
		return $result;
	}

	public function processMailSubject(&$mail) {
		if(preg_match_all('#\{([-._A-Z0-9a-z]+)\}#', $mail->subject, $matches)) {
			foreach($matches[0] as $k => $match) {
				$var = $matches[1][$k];
				$val = '';
				$table = '';
				if(strpos($var, '.')) {
					list($table, $var) = explode('.', $var, 2);
					if(!empty($table) && !empty($var)){
						if(!empty($mail->data->cart->$table->$var) && is_string($mail->data->cart->$table->$var)){
							$val = $mail->data->cart->$table->$var;
						}elseif(!empty($mail->data->$table->$var) && is_string($mail->data->$table->$var)){
							$val = $mail->data->$table->$var;
						}
					}
				} elseif(!empty($var) && !empty($mail->data->cart->$var) && is_string($mail->data->cart->$var)) {
					$val = $mail->data->cart->$var;
				} elseif(!empty($var) && !empty($mail->data->$var) && is_string($mail->data->$var)) {
					$val = $mail->data->$var;
				}
				$mail->subject = str_replace($match, $val, $mail->subject);
			}
		}
	}

	public function loadAttachments($name) {
		$config =& hikashop_config();
		$attach = $config->get($name.'.attach');

		if(empty($attach)) {
			$attach = array();
			return $attach;
		}

		$attachData = hikashop_unserialize($attach);
		$uploadFolder = str_replace(array('/','\\'),DS,html_entity_decode($config->get('uploadsecurefolder')));
		if(preg_match('#^([A-Z]:)?/.*#',$uploadFolder)) {
			if(!$config->get('embed_files')) {
				$this->_force_embed = true;
			}
			$uploadPath = str_replace(array('/','\\'),DS,$uploadFolder);
		} else {
			$uploadFolder = trim($uploadFolder,DS.' ').DS;
			$uploadPath = str_replace(array('/','\\'),DS,HIKASHOP_ROOT.$uploadFolder);
		}
		$uploadURL = HIKASHOP_LIVE.str_replace(DS,'/',$uploadFolder);

		if(!$config->get('embed_files') && !$this->_force_embed) {
			$folder = $config->get('uploadfolder');
			if(preg_match('#^([A-Z]:)?/.*#',$folder)) {
				$path = str_replace(array('/','\\'),DS,$folder);
			} else {
				$folder = trim($folder,DS.' ').DS;
				$path = str_replace(array('/','\\'),DS,HIKASHOP_ROOT.$folder);
			}
			$uploadURL = HIKASHOP_LIVE.str_replace(DS,'/',$folder);
		}
		$attach = array();
		foreach($attachData as $oneAttach) {
			if(!$config->get('embed_files') && !$this->_force_embed) {
				jimport( 'joomla.filesystem.file' );
				try{
					JFile::copy($uploadPath.$oneAttach->filename, $folder.$oneAttach->filename);
				}catch(Exception $e) {
					hikashop_writeToLog($e->getMessage());
					continue;
				}
			}
			$attachObj = new stdClass();
			$attachObj->name = $oneAttach->filename;
			$attachObj->filename = $uploadPath.$oneAttach->filename;
			$attachObj->url = $uploadURL.$oneAttach->filename;
			$attach[] = $attachObj;
		}
		return $attach;
	}

	public function cleanText($text) {
		return trim( preg_replace('/(%0A|%0D|\n+|\r+)/i', '', (string) $text) );
	}

	public function setFrom($email, $name = '') {
		if(!empty($email)) {
			$this->mailer->From = $this->cleanText($email);
		}

		$config =& hikashop_config();
		if(!empty($name) && $config->get('add_names', true)) {
			$this->mailer->FromName = $this->cleanText($name);
		}
	}

	public function textVersion($html) {
		$html = hikashop_absoluteURL($html);
		$html = preg_replace('# +#', ' ', $html);
		$html = str_replace(array("\n","\r","\t"), '', $html);

		$removeScript = "#< *script(?:(?!< */ *script *>).)*< */ *script *>#isU";
		$removeStyle = "#< *style(?:(?!< */ *style *>).)*< */ *style *>#isU";
		$removeStrikeTags =  '#< *strike(?:(?!< */ *strike *>).)*< */ *strike *>#iU';
		$replaceByTwoReturnChar = '#< *(h1|h2)[^>]*>#Ui';
		$replaceByStars = '#< *li[^>]*>#Ui';
		$replaceByReturnChar1 = '#< */ *(li|td|tr|div|p)[^>]*> *< *(li|td|tr|div|p)[^>]*>#Ui';
		$replaceByReturnChar = '#< */? *(br|p|h1|h2|h3|li|ul|h4|h5|h6|tr|td|div)[^>]*>#Ui';
		$replaceLinks = '/< *a[^>]*href *= *"([^"]*)"[^>]*>(.*)< *\/ *a *>/Uis';

		$text = preg_replace(array($removeScript,$removeStyle,$removeStrikeTags,$replaceByTwoReturnChar,$replaceByStars,$replaceByReturnChar1,$replaceByReturnChar,$replaceLinks),array('','','',"\n\n","\n* ","\n","\n",'${2} ( ${1} )'),$html);
		$text = str_replace(array(" ","&nbsp;"),' ',strip_tags($text));
		$text = trim(@html_entity_decode($text,ENT_QUOTES,'UTF-8'));
		$text = preg_replace('# +#',' ',$text);
		$text = preg_replace('#\n *\n\s+#',"\n\n",$text);

		return $text;
	}

	public function embedImages() {
		preg_match_all('/(src|background)="([^"]*)"/Ui', $this->mailer->Body, $images);
		$result = true;
		if(empty($images[2]))
			return $result;

		$mimetypes = array(
			'bmp'  => 'image/bmp',
			'gif'  => 'image/gif',
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'jpe'  => 'image/jpeg',
			'png'  => 'image/png',
			'tiff' => 'image/tiff',
			'tif'  => 'image/tiff'
		);
		$live = rtrim(HIKASHOP_LIVE, '/');
		$liveVariants = array($live);
		$parsed = parse_url($live);
		if(!empty($parsed['host'])) {
			$otherScheme = (!empty($parsed['scheme']) && $parsed['scheme'] === 'https') ? 'http' : 'https';
			$liveVariants[] = $otherScheme . '://' . $parsed['host'] . (isset($parsed['path']) ? $parsed['path'] : '');

			if(strpos($parsed['host'], 'www.') === 0) {
				$hostNoWww = substr($parsed['host'], 4);
				$liveVariants[] = $parsed['scheme'] . '://' . $hostNoWww . (isset($parsed['path']) ? $parsed['path'] : '');
				$liveVariants[] = $otherScheme . '://' . $hostNoWww . (isset($parsed['path']) ? $parsed['path'] : '');
			} else {
				$hostWww = 'www.' . $parsed['host'];
				$liveVariants[] = $parsed['scheme'] . '://' . $hostWww . (isset($parsed['path']) ? $parsed['path'] : '');
				$liveVariants[] = $otherScheme . '://' . $hostWww . (isset($parsed['path']) ? $parsed['path'] : '');
			}
		}
		foreach($liveVariants as $k => $v) {
			$liveVariants[$k] = rtrim($v, '/') . '/';
		}
		usort($liveVariants, function($a, $b) { return strlen($b) - strlen($a); });

		$allimages = array();
		foreach($images[2] as $i => $url) {
			if(isset($allimages[$url]))
				continue;

			$allimages[$url] = 1;
			$path = $url;
			foreach($liveVariants as $variant) {
				if(stripos($url, $variant) === 0) {
					$path = HIKASHOP_ROOT . str_replace('/', DS, substr($url, strlen($variant)));
					break;
				}
			}

			if($path === $url && strpos($url, '://') === false && strpos($url, 'cid:') !== 0) {
				$relativePath = ltrim(str_replace('/', DS, $url), DS);
				$absolutePath = HIKASHOP_ROOT . $relativePath;
				if(file_exists($absolutePath))
					$path = $absolutePath;
			}

			$filename = basename($url);
			$md5 = md5($filename);
			$cid = 'cid:' . $md5;
			$fileParts = explode('.', $filename);
			$ext = strtolower($fileParts[1]);

			if(!isset($mimetypes[$ext]))
				continue;

			$mimeType  = $mimetypes[$ext];
			if($this->mailer->AddEmbeddedImage($path, $md5, $filename, 'base64', $mimeType)){
				 $this->mailer->Body = preg_replace("/".$images[1][$i]."=\"".preg_quote($url, '/')."\"/Ui", $images[1][$i]."=\"".$cid."\"", $this->mailer->Body);
			} else {
				$result = false;
			}
		}
		return $result;
	}

	public function prepareForViewBuilder(&$mail) {
		if(empty($mail->mail_name))
			return false;
		$path = $this->getMailPath($mail, 'html', true);
		if(empty($path))
			return false;
		$mail->file = new stdClass();
		$mail->file->overriden = !empty($mail->html_override);
		$mail->file->edit = $mail->html_path;
		if($mail->file->overriden)
			$mail->file->edit = $mail->html_override_path;
		$mail->file->content = file_get_contents($mail->file->edit);

		$viewClass = hikashop_get('class.view');
		$viewClass->initStructure($mail->file);
	}
}
