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

$app   = Factory::getApplication();
$input = $app->getInput();
$wa    = $this->getWebAssetManager();

$tplPath  = Uri::root(true) . '/templates/aksolucoes';
$sitename = htmlspecialchars($app->get('sitename'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// ---------------------------------------------------------------------------
// Active page detection
// ---------------------------------------------------------------------------
$option = $input->getCmd('option', '');
$view   = $input->getCmd('view', '');
$menu   = $app->getMenu()->getActive();
$isHome = $menu !== null && (int) $menu->home === 1;
$pageclass = $menu !== null ? $menu->getParams()->get('pageclass_sfx', '') : '';

// ---------------------------------------------------------------------------
// Helper: keep only a valid hex colour before injecting it into inline CSS
// ---------------------------------------------------------------------------
$safeColor = static function (string $value, string $fallback): string {
    $value = trim($value);
    return preg_match('/^#[0-9a-f]{3,8}$/i', $value) ? $value : $fallback;
};

// ---------------------------------------------------------------------------
// Template parameters
// ---------------------------------------------------------------------------
$brandColor   = $safeColor((string) $this->params->get('brandColor', '#0e2a5c'), '#0e2a5c');
$accentColor  = $safeColor((string) $this->params->get('accentColor', '#1fb6ee'), '#1fb6ee');
$accentColor2 = $safeColor((string) $this->params->get('accentColor2', '#8dc63f'), '#8dc63f');

// Build a safe, root-relative URL from a media parameter (drops #joomlaImage metadata).
$mediaUrl = static function ($raw, string $default): string {
    $path = (string) ($raw !== '' && $raw !== null ? $raw : $default);
    $path = explode('#', $path)[0];
    $path = ltrim($path, '/');
    return htmlspecialchars(Uri::root(true) . '/' . $path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$logoMark = $mediaUrl($this->params->get('logoMark'), 'templates/aksolucoes/images/logos/logo-mark.png');
$logoWord = $mediaUrl($this->params->get('logoWord'), 'templates/aksolucoes/images/logos/logo-wordmark.svg');

$whatsappRaw = preg_replace('/\D+/', '', (string) $this->params->get('whatsapp', '5541991387368'));
$whatsappUrl = $whatsappRaw !== '' ? 'https://wa.me/' . $whatsappRaw : '';

$phoneComercial = htmlspecialchars((string) $this->params->get('phoneComercial', '+55 41 99156-9730'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$phoneTecnico   = htmlspecialchars((string) $this->params->get('phoneTecnico', '+55 41 99155-6440'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$email          = htmlspecialchars((string) $this->params->get('email', 'contato@aksolucoes.com.br'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$phoneComercialTel = preg_replace('/[^\d+]/', '', $phoneComercial);
$phoneTecnicoTel   = preg_replace('/[^\d+]/', '', $phoneTecnico);

$showTopbar     = (int) $this->params->get('showTopbar', 0) === 1;
$showLangToggle = (int) $this->params->get('showLangToggle', 0) === 1;
$showFab        = (int) $this->params->get('showFab', 1) === 1 && $whatsappUrl !== '';

// ---------------------------------------------------------------------------
// Assets
// ---------------------------------------------------------------------------
$wa->useStyle('template.aksolucoes.base')
   ->useScript('template.aksolucoes.main');

$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
$this->setMetaData('theme-color', $brandColor);
$this->addHeadLink($tplPath . '/images/logos/logo-mark.svg', 'icon', 'rel', ['type' => 'image/svg+xml']);

// Brand tokens overridable from template options
$this->addStyleDeclaration(sprintf(
    ':root{--ak-navy:%s;--ak-cyan:%s;--ak-green:%s;}',
    $brandColor,
    $accentColor,
    $accentColor2
));

$bodyClass = trim(
    'site'
    . ($option ? ' ' . $option : '')
    . ($view ? ' view-' . $view : '')
    . ($isHome ? ' is-home' : ' subpage')
    . ($pageclass ? ' ' . $pageclass : '')
);
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">

<head>
	<jdoc:include type="metas" />
	<jdoc:include type="styles" />
	<jdoc:include type="scripts" />
</head>

<body class="<?php echo htmlspecialchars($bodyClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

	<?php if ($showTopbar && $this->countModules('topbar', true)) : ?>
		<div class="site-topbar">
			<div class="container">
				<jdoc:include type="modules" name="topbar" style="none" />
			</div>
		</div>
	<?php endif; ?>

	<header class="site-header" id="siteHeader">
		<div class="header-inner">
			<a class="brand" href="<?php echo $this->baseurl; ?>/" aria-label="<?php echo $sitename; ?>">
				<img class="brand-mark" src="<?php echo $logoMark; ?>" alt="" width="741" height="606" decoding="async">
				<img class="brand-word" src="<?php echo $logoWord; ?>" alt="<?php echo $sitename; ?>" width="406" height="75" decoding="async">
			</a>

			<?php if ($this->countModules('menu', true)) : ?>
				<nav class="nav" aria-label="<?php echo Text::_('TPL_AKSOLUCOES_NAV_PRIMARY'); ?>">
					<jdoc:include type="modules" name="menu" style="none" />
				</nav>
			<?php endif; ?>

			<div class="header-right">
				<?php if ($showLangToggle) : ?>
					<div class="lang-toggle" aria-label="<?php echo Text::_('TPL_AKSOLUCOES_LANGUAGE'); ?>">
						<button type="button" class="active" data-lang="pt">PT</button>
						<button type="button" data-lang="en">EN</button>
						<button type="button" data-lang="es">ES</button>
					</div>
				<?php endif; ?>

				<?php if ($whatsappUrl !== '') : ?>
					<a class="btn btn-primary" href="<?php echo $whatsappUrl; ?>" target="_blank" rel="noopener">
						<?php echo Text::_('TPL_AKSOLUCOES_TALK_NOW'); ?>
					</a>
				<?php endif; ?>

				<button class="burger" id="menuButton" type="button" aria-label="<?php echo Text::_('TPL_AKSOLUCOES_MENU_OPEN'); ?>" aria-controls="mobileDrawer" aria-expanded="false">
					<i data-lucide="menu"></i>
				</button>
			</div>
		</div>
	</header>

	<div class="mobile-drawer" id="mobileDrawer" aria-hidden="true">
		<?php if ($this->countModules('menu', true)) : ?>
			<jdoc:include type="modules" name="menu" style="none" />
		<?php endif; ?>
		<?php if ($whatsappUrl !== '') : ?>
			<a class="btn btn-primary" href="<?php echo $whatsappUrl; ?>" target="_blank" rel="noopener">
				<?php echo Text::_('TPL_AKSOLUCOES_TALK_NOW'); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php if ($isHome) : ?>
		<main id="top">
			<jdoc:include type="message" />
			<?php if ($this->countModules('hero', true)) : ?>
				<jdoc:include type="modules" name="hero" style="none" />
			<?php endif; ?>
			<jdoc:include type="component" />
			<?php if ($this->countModules('main-bottom', true)) : ?>
				<jdoc:include type="modules" name="main-bottom" style="none" />
			<?php endif; ?>
		</main>
	<?php else : ?>
		<main id="top" class="subpage-main">
			<div class="container subpage-inner">
				<jdoc:include type="modules" name="main-top" style="card" />
				<jdoc:include type="message" />
				<div class="subpage-layout<?php echo $this->countModules('sidebar-right', true) ? ' has-sidebar' : ''; ?>">
					<div class="subpage-content">
						<jdoc:include type="component" />
						<jdoc:include type="modules" name="main-bottom" style="card" />
					</div>
					<?php if ($this->countModules('sidebar-right', true)) : ?>
						<aside class="subpage-sidebar">
							<jdoc:include type="modules" name="sidebar-right" style="card" />
						</aside>
					<?php endif; ?>
				</div>
			</div>
			<?php if ($this->countModules('bottom-a', true) || $this->countModules('bottom-b', true)) : ?>
				<div class="container subpage-bottom">
					<jdoc:include type="modules" name="bottom-a" style="card" />
					<jdoc:include type="modules" name="bottom-b" style="card" />
				</div>
			<?php endif; ?>
		</main>
	<?php endif; ?>

	<footer class="footer" id="contact">
		<div class="container footer-grid">
			<div class="footer-brand">
				<img src="<?php echo $logoWord; ?>" alt="<?php echo $sitename; ?>" width="406" height="75" decoding="async">
				<p><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_TAGLINE'); ?></p>
				<div class="social-links">
					<a href="https://www.instagram.com/aksolucoesbr/" target="_blank" rel="noopener" aria-label="Instagram"><i data-lucide="camera"></i></a>
					<a href="https://www.aksolucoes.com.br" target="_blank" rel="noopener" aria-label="<?php echo $sitename; ?>"><i data-lucide="external-link"></i></a>
				</div>
			</div>

			<?php if ($this->countModules('footer', true)) : ?>
				<jdoc:include type="modules" name="footer" style="none" />
			<?php else : ?>
				<div>
					<h4><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_SOLUTIONS'); ?></h4>
					<ul>
						<li><a href="<?php echo $this->baseurl; ?>/#solutions"><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_CONTACT_CENTER'); ?></a></li>
						<li><a href="<?php echo $this->baseurl; ?>/#solutions"><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_DATACENTER'); ?></a></li>
						<li><a href="<?php echo $this->baseurl; ?>/#solutions"><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_COLLAB'); ?></a></li>
						<li><a href="<?php echo $this->baseurl; ?>/#solutions"><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_TEM'); ?></a></li>
					</ul>
				</div>

				<div>
					<h4><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_CONTACT'); ?></h4>
					<ul>
						<li><a href="tel:<?php echo $phoneComercialTel; ?>"><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_COMMERCIAL'); ?>: <?php echo $phoneComercial; ?></a></li>
						<li><a href="tel:<?php echo $phoneTecnicoTel; ?>"><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_TECHNICAL'); ?>: <?php echo $phoneTecnico; ?></a></li>
						<?php if ($whatsappUrl !== '') : ?>
							<li><a href="<?php echo $whatsappUrl; ?>" target="_blank" rel="noopener">WhatsApp</a></li>
						<?php endif; ?>
						<li><a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a></li>
					</ul>
				</div>

				<div>
					<h4><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_ADDRESS'); ?></h4>
					<p><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_ADDRESS_LINE'); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<div class="container footer-bottom">
			<span><?php echo Text::sprintf('TPL_AKSOLUCOES_FOOTER_COPYRIGHT', date('Y')); ?></span>
			<span><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_SLOGAN'); ?></span>
		</div>
		<jdoc:include type="modules" name="footer-bottom" style="none" />
	</footer>

	<?php if ($showFab) : ?>
		<a class="fab" href="<?php echo $whatsappUrl; ?>" target="_blank" rel="noopener">
			<i data-lucide="message-circle"></i>
			<span><?php echo Text::_('TPL_AKSOLUCOES_TALK_NOW'); ?></span>
		</a>
	<?php endif; ?>

	<jdoc:include type="modules" name="debug" style="none" />
</body>

</html>
