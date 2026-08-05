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
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\CMS\Document\HtmlDocument $this */

$app   = Factory::getApplication();
$input = $app->getInput();
$user  = $app->getIdentity();

// HikaShop refreshes product variants through tmpl=component requests. Keep
// the context classes used by index.php so scoped store styles still apply.
$option = $input->getCmd('option', '');
$view   = $input->getCmd('view', '');
$bodyClass = trim(
	'site tmpl-component'
	. ($option !== '' ? ' ' . $option : '')
	. ($view !== '' ? ' view-' . $view : '')
	. ($user !== null && !(bool) $user->guest ? ' is-logged-in' : ' is-guest')
);
$this->addStyleSheet(Uri::root(true) . '/templates/aksolucoes/fonts/fonts.css', ['version' => 'auto']);
$this->addStyleSheet(Uri::root(true) . '/templates/aksolucoes/css/template.css', ['version' => 'auto']);

$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
$tplPath = Uri::root(true) . '/templates/aksolucoes';
$this->addHeadLink($tplPath . '/images/logos/favicon-32.png', 'icon', 'rel', ['type' => 'image/png']);
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">

<head>
	<jdoc:include type="metas" />
	<jdoc:include type="styles" />
	<jdoc:include type="scripts" />
</head>

<body class="<?php echo htmlspecialchars($bodyClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
	<main id="top" class="subpage-main">
		<div class="container subpage-inner">
			<jdoc:include type="message" />
			<jdoc:include type="component" />
		</div>
	</main>
</body>

</html>
