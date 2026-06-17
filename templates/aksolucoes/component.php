<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.aksolucoes
 *
 * @copyright   (C) 2026 AK Soluções em Tecnologia
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

/** @var \Joomla\CMS\Document\HtmlDocument $this */

$wa = $this->getWebAssetManager();
$wa->useStyle('template.aksolucoes.base');

$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
$tplPath = Uri::root(true) . '/templates/aksolucoes';
$this->addHeadLink($tplPath . '/images/logos/logo-mark.svg', 'icon', 'rel', ['type' => 'image/svg+xml']);
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">

<head>
	<jdoc:include type="metas" />
	<jdoc:include type="styles" />
	<jdoc:include type="scripts" />
</head>

<body class="site tmpl-component">
	<main id="top" class="subpage-main">
		<div class="container subpage-inner">
			<jdoc:include type="message" />
			<jdoc:include type="component" />
		</div>
	</main>
</body>

</html>
