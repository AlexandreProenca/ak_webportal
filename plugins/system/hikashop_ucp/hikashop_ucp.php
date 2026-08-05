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
defined('_JEXEC') or die;

if(!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);
if(!defined('HIKASHOP_WORDPRESS'))
	include_once(JPATH_ROOT.'/administrator/components/com_hikashop/pluginCompat.php');
if(!class_exists('hikashopJoomlaPlugin')) return;

class plgSystemHikashop_ucp extends hikashopJoomlaPlugin {

	function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
		if(!isset($this->params)) {
			if(HIKASHOP_J50 && !class_exists('JPluginHelper'))
				class_alias('Joomla\CMS\Plugin\PluginHelper', 'JPluginHelper');
			$plugin = JPluginHelper::getPlugin('system', 'hikashop_ucp');

			if(HIKASHOP_J50 && !class_exists('JRegistry'))
				class_alias('Joomla\Registry\Registry', 'JRegistry');
			$this->params = new JRegistry(@$plugin->params);
		}
	}

	private function _loadHikaShop() {
		if(!defined('DS'))
			define('DS', DIRECTORY_SEPARATOR);
		if(!include_once(rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php'))
			return true;
		return false;
	}

	public function onAfterInitialise() {
		$pathInfo = $this->_getPathInfo();
		if(empty($pathInfo))
			return;

		if(HIKASHOP_J50 && !class_exists('JFactory'))
			class_alias('Joomla\CMS\Factory', 'JFactory');

		$app = JFactory::getApplication();
		$site = false;
		if(version_compare(JVERSION, '4.0', '>=') && $app->isClient('site'))
			$site = true;
		if(version_compare(JVERSION, '4.0', '<') && $app->isSite())
			$site = true;
		if(!$site)
			return;

		$path = trim($pathInfo, '/');

		$mcp_enabled = $this->params->get('mcp_enabled', 0);
		if($mcp_enabled) {
			$mcp_slug = trim($this->params->get('mcp_slug', 'ucp-mcp'), '/');
			if(substr($path, -strlen($mcp_slug)) === $mcp_slug) {
				if($this->_loadHikaShop())
					return;
				$this->_handleMcp();
				return;
			}
		}

		if($path === '.well-known/ucp') {
			if($this->_loadHikaShop())
				return;
			$this->_handleDiscovery();
			return;
		}

		$basePath = trim($this->params->get('base_path', '/ucp/v1'), '/');
		if(strpos($path, $basePath) !== 0)
			return;

		if($this->_loadHikaShop())
			return;

		require_once __DIR__.'/includes/UcpRouter.php';
		$router = new HikashopUcpRouter($this->params);
		$router->dispatch($path, $basePath);
	}

	private function _handleDiscovery() {
		require_once __DIR__.'/includes/UcpDiscovery.php';
		$discovery = new HikashopUcpDiscovery($this->params);
		$discovery->getProfile();
	}

	private function _handleMcp() {
		require_once __DIR__.'/includes/UcpAuth.php';
		$auth = new HikashopUcpAuth($this->params);
		if(!$auth->validate())
			return;

		require_once __DIR__.'/includes/UcpMcp.php';
		$mcp = new HikashopUcpMcp($this->params);
		$mcp->handle();
	}

	public function onBeforeCompileHead() {
		if(!$this->params->get('webmcp_enabled', 0))
			return;

		$app = JFactory::getApplication();
		$site = false;
		if(version_compare(JVERSION, '4.0', '>=') && $app->isClient('site'))
			$site = true;
		if(version_compare(JVERSION, '4.0', '<') && $app->isSite())
			$site = true;
		if(!$site)
			return;

		if($this->_loadHikaShop())
			return;

		$doc = JFactory::getDocument();
		if($doc->getType() !== 'html')
			return;

		$basePath = trim($this->params->get('base_path', '/ucp/v1'), '/');
		$siteUrl = rtrim(HIKASHOP_LIVE, '/');
		$config = array(
			'ucpBase' => '/' . $basePath,
			'siteUrl' => $siteUrl,
		);
		$doc->addScriptDeclaration('window.hikashopWebMCPConfig = ' . json_encode($config) . ';');

		$doc->addScript(HIKASHOP_LIVE . 'media/plg_system_hikashop_ucp/js/hikashop_webmcp.js');
	}

	private function _getPathInfo() {
		if(!empty($_SERVER['PATH_INFO']))
			return $_SERVER['PATH_INFO'];

		if(empty($_SERVER['REQUEST_URI']))
			return '';

		$requestUri = $_SERVER['REQUEST_URI'];

		$pos = strpos($requestUri, '?');
		if($pos !== false)
			$requestUri = substr($requestUri, 0, $pos);

		$ucpPaths = array('/.well-known/ucp');

		$basePath = trim($this->params->get('base_path', '/ucp/v1'), '/');
		if(!empty($basePath))
			$ucpPaths[] = '/' . $basePath;

		$mcp_slug = trim($this->params->get('mcp_slug', 'ucp-mcp'), '/');
		if(!empty($mcp_slug))
			$ucpPaths[] = '/' . $mcp_slug;

		foreach($ucpPaths as $pattern) {
			$matchPos = strpos($requestUri, $pattern);
			if($matchPos !== false)
				return substr($requestUri, $matchPos);
		}

		return '';
	}
}
