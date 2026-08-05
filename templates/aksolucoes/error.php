<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.aksolucoes
 *
 * @copyright   (C) 2026 AK Soluções em Tecnologia
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\CMS\Document\ErrorDocument $this */

$app     = Factory::getApplication();
$debug   = (bool) $app->get('debug', false);
$code    = (int) ($this->error->getCode() ?: 500);
$tplPath = Uri::root(true) . '/templates/aksolucoes';

// Log the real error; never expose internals to visitors in production.
Log::add($code . ' ' . $this->error->getMessage(), Log::ERROR, 'tpl_aksolucoes');

$message = $code === 404
    ? Text::_('TPL_AKSOLUCOES_ERROR_404')
    : Text::_('TPL_AKSOLUCOES_ERROR_GENERIC');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#0e2a5c">
	<title><?php echo $code; ?> — <?php echo htmlspecialchars($app->get('sitename'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></title>
	<link rel="icon" href="<?php echo $tplPath; ?>/images/logos/favicon-32.png" type="image/png">
	<link rel="stylesheet" href="<?php echo $tplPath; ?>/fonts/fonts.css">
	<style>
		:root {
			--ak-navy: #0e2a5c;
			--ak-navy-dark: #06122f;
			--ak-cyan: #1fb6ee;
			--ak-green: #8dc63f;
		}
		* { box-sizing: border-box; }
		body {
			margin: 0;
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 24px;
			color: #fff;
			font: 400 16px/1.6 "Inter", system-ui, sans-serif;
			background:
				radial-gradient(120% 120% at 0% 0%, rgba(31, 182, 238, 0.28), transparent 55%),
				radial-gradient(120% 120% at 100% 100%, rgba(141, 198, 63, 0.22), transparent 55%),
				var(--ak-navy-dark);
		}
		.error-card {
			width: min(560px, 100%);
			padding: 48px 40px;
			text-align: center;
			border: 1px solid rgba(255, 255, 255, 0.18);
			border-radius: 20px;
			background: rgba(255, 255, 255, 0.06);
			backdrop-filter: blur(18px);
		}
		.error-code {
			font: 800 88px/1 "Montserrat", "Inter", sans-serif;
			background: linear-gradient(135deg, var(--ak-green), var(--ak-cyan));
			-webkit-background-clip: text;
			background-clip: text;
			color: transparent;
		}
		.error-card h1 {
			margin: 8px 0 12px;
			font: 700 26px/1.2 "Montserrat", "Inter", sans-serif;
		}
		.error-card p { margin: 0 0 28px; color: rgba(255, 255, 255, 0.82); }
		.error-btn {
			display: inline-block;
			padding: 14px 26px;
			border-radius: 999px;
			color: #fff;
			font-weight: 800;
			text-decoration: none;
			background: linear-gradient(135deg, var(--ak-green), var(--ak-cyan) 48%, #168bc7);
			box-shadow: 0 18px 48px rgba(31, 182, 238, 0.24);
		}
		.error-debug {
			margin-top: 28px;
			padding: 16px;
			text-align: left;
			font: 500 13px/1.5 "JetBrains Mono", monospace;
			color: rgba(255, 255, 255, 0.7);
			background: rgba(0, 0, 0, 0.28);
			border-radius: 12px;
			overflow: auto;
		}
	</style>
</head>

<body>
	<div class="error-card">
		<div class="error-code"><?php echo $code; ?></div>
		<h1><?php echo $message; ?></h1>
		<p><?php echo Text::_('TPL_AKSOLUCOES_ERROR_LEAD'); ?></p>
		<a class="error-btn" href="<?php echo Uri::root(); ?>"><?php echo Text::_('TPL_AKSOLUCOES_ERROR_HOME'); ?></a>

		<?php if ($debug) : ?>
			<pre class="error-debug"><?php echo htmlspecialchars($this->error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></pre>
		<?php endif; ?>
	</div>

	<jdoc:include type="modules" name="error-404" style="none" />
</body>

</html>
