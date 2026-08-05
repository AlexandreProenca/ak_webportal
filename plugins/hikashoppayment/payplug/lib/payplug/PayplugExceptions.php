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

class PayplugException extends Exception
{
}

class InvalidCredentialsException extends PayplugException
{
}

class InvalidSignatureException extends PayplugException
{
}

class MalformedURLException extends PayplugException
{
}

class NetworkException extends PayplugException
{
}

class ParametersNotSetException extends PayplugException
{
}

class MissingRequiredParameterException extends PayplugException
{
}

