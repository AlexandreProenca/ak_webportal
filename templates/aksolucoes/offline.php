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
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\CMS\Document\HtmlDocument $this */

$app     = Factory::getApplication();
$tplPath = Uri::root(true) . '/templates/aksolucoes';
$sitename = htmlspecialchars($app->get('sitename'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#0e2a5c">
	<title><?php echo $sitename; ?></title>
	<link rel="icon" href="<?php echo $tplPath; ?>/images/logos/favicon-32.png" type="image/png">
	<link rel="stylesheet" href="<?php echo $tplPath; ?>/fonts/fonts.css">
	<jdoc:include type="head" />
	<style>
		:root { --ak-navy-dark: #06122f; --ak-cyan: #1fb6ee; --ak-green: #8dc63f; }
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
		.offline-card {
			width: min(460px, 100%);
			padding: 48px 40px;
			text-align: center;
			border: 1px solid rgba(255, 255, 255, 0.18);
			border-radius: 20px;
			background: rgba(255, 255, 255, 0.06);
			backdrop-filter: blur(18px);
		}
		.offline-card img { width: 64px; height: auto; margin: 0 auto 22px; display: block; }
		.offline-card h1 { margin: 0 0 12px; font: 700 24px/1.2 "Montserrat", "Inter", sans-serif; }
		.offline-card p { margin: 0 0 24px; color: rgba(255, 255, 255, 0.82); }
		.offline-card form { display: grid; gap: 12px; text-align: left; }
		.offline-card label { font-size: 14px; font-weight: 700; display: grid; gap: 6px; }
		.offline-card input {
			min-height: 46px; padding: 12px 14px; border-radius: 12px; border: 0;
			font: 500 15px/1.4 "Inter", sans-serif;
		}
		.offline-card .btn {
			min-height: 48px; border: 0; border-radius: 999px; cursor: pointer;
			color: #fff; font-weight: 800;
			background: linear-gradient(135deg, var(--ak-green), var(--ak-cyan) 48%, #168bc7);
		}
	</style>
</head>

<body>
	<div class="offline-card">
		<img src="<?php echo $tplPath; ?>/images/logos/logo-mark.png" alt="<?php echo $sitename; ?>">
		<h1><?php echo $sitename; ?></h1>
		<p><?php echo $app->get('offline_message') ? htmlspecialchars($app->get('offline_message'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : Text::_('TPL_AKSOLUCOES_OFFLINE_MESSAGE'); ?></p>

		<jdoc:include type="message" />

		<form action="<?php echo Uri::root(); ?>index.php" method="post">
			<label for="username"><?php echo Text::_('JGLOBAL_USERNAME'); ?>
				<input id="username" name="username" type="text" autocomplete="username">
			</label>
			<label for="passwd"><?php echo Text::_('JGLOBAL_PASSWORD'); ?>
				<input id="passwd" name="passwd" type="password" autocomplete="current-password">
			</label>
			<button class="btn" type="submit"><?php echo Text::_('JLOGIN'); ?></button>
			<input type="hidden" name="option" value="com_users">
			<input type="hidden" name="task" value="user.login">
			<input type="hidden" name="return" value="<?php echo base64_encode(Uri::base()); ?>">
			<?php echo \Joomla\CMS\HTML\HTMLHelper::_('form.token'); ?>
		</form>
	</div>
</body>

</html>
