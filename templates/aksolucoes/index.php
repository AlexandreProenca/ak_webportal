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
$user  = $app->getIdentity();
$isLoggedIn = $user !== null && !(bool) $user->guest;

$tplPath  = Uri::root(true) . '/templates/aksolucoes';
$sitename = htmlspecialchars($app->get('sitename'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// ---------------------------------------------------------------------------
// Active page detection
// ---------------------------------------------------------------------------
$option = $input->getCmd('option', '');
$view   = $input->getCmd('view', '');
$menu   = $app->getMenu()->getActive();
// Home layout only when the active (home) menu item actually renders its own
// component — otherwise component pages without a dedicated menu item (e.g. the
// HikaShop cart/checkout) fall back to the home menu and wrongly inherit the
// transparent-header home layout. Compare the request's component to the menu's.
$menuOption = ($menu !== null && !empty($menu->query['option'])) ? $menu->query['option'] : '';
$isHome = $menu !== null && (int) $menu->home === 1 && ($menuOption === '' || $menuOption === $option);
$pageclass = $menu !== null ? $menu->getParams()->get('pageclass_sfx', '') : '';
$isProductsListing = $menu !== null
	&& (int) $menu->id === 104
	&& $option === 'com_hikashop'
	&& $view === 'product'
	&& $input->getInt('cid', 0) === 0;

// When previewing module positions (?tp=1) render every editable home area, so
// the empty positions are visible/outlined like a standard Joomla preview.
$tpPreview = $input->getInt('tp', 0) === 1;
$akDoc     = $this;
$showPos   = function (string $name) use ($tpPreview, $akDoc) {
	return $tpPreview || $akDoc->countModules($name, true);
};

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
$whatsappDisplay = strlen($whatsappRaw) === 13
    ? '+' . substr($whatsappRaw, 0, 2) . ' ' . substr($whatsappRaw, 2, 2) . ' ' . substr($whatsappRaw, 4, 5) . '-' . substr($whatsappRaw, 9)
    : $whatsappRaw;

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
// Joomla 5 serves template assets from media/templates/site/<tpl>/, so the Web Asset
// Manager will not find files kept inside templates/aksolucoes/. This template is
// self-contained, so emit its CSS/JS with explicit web-root paths instead.
// Version by the stylesheet's modification time so edits bust browser caches automatically.
$akAssetVer = (string) (@filemtime(JPATH_ROOT . '/templates/aksolucoes/css/template.css') ?: '1');
$this->addStyleSheet($tplPath . '/fonts/fonts.css', ['version' => $akAssetVer]);
$this->addStyleSheet($tplPath . '/css/template.css', ['version' => $akAssetVer]);
// The registered vendor asset contains Font Awesome's core only. Use Joomla's
// complete bundle so the matching @font-face declarations are available on
// every page, including HikaShop's cart and checkout views.
$this->addStyleSheet(Uri::root(true) . '/media/system/css/joomla-fontawesome.min.css', ['version' => 'auto']);
$this->addScript($tplPath . '/js/lucide.min.js', ['version' => $akAssetVer], ['defer' => true]);
$this->addScript($tplPath . '/js/template.js', ['version' => $akAssetVer], ['defer' => true]);

$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
$this->setMetaData('theme-color', $brandColor);
$this->addHeadLink($tplPath . '/images/logos/favicon-32.png?v=' . $akAssetVer, 'icon', 'rel', ['type' => 'image/png']);
$this->addHeadLink($tplPath . '/images/logos/apple-touch-icon.png?v=' . $akAssetVer, 'apple-touch-icon');

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
    . ($isLoggedIn ? ' is-logged-in' : ' is-guest')
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
				<img class="brand-logo brand-logo--light" src="<?php echo $tplPath; ?>/images/logos/logo-vertical-white.png?v=<?php echo $akAssetVer; ?>" alt="<?php echo $sitename; ?>" decoding="async">
				<img class="brand-logo brand-logo--dark" src="<?php echo $tplPath; ?>/images/logos/logo-vertical.png?v=<?php echo $akAssetVer; ?>" alt="<?php echo $sitename; ?>" decoding="async" aria-hidden="true">
			</a>

			<?php if ($this->countModules('menu', true)) : ?>
				<nav class="nav" aria-label="<?php echo Text::_('TPL_AKSOLUCOES_NAV_PRIMARY'); ?>">
					<jdoc:include type="modules" name="menu" style="none" />
				</nav>
			<?php endif; ?>

			<div class="header-right">
				<?php if ($showPos('member-area')) : ?>
					<div class="header-member">
						<jdoc:include type="modules" name="member-area" style="none" />
					</div>
				<?php endif; ?>

				<?php if ($showLangToggle) : ?>
					<div class="lang-toggle" aria-label="<?php echo Text::_('TPL_AKSOLUCOES_LANGUAGE'); ?>">
						<button type="button" class="active" data-lang="pt">PT</button>
						<button type="button" data-lang="en">EN</button>
						<button type="button" data-lang="es">ES</button>
					</div>
				<?php endif; ?>

					<div class="btn-cart">
						<a class="btn-cart__link" href="<?php echo $this->baseurl; ?>/index.php?option=com_hikashop&amp;ctrl=cart&amp;task=show" aria-label="Carrinho de compras">
							<svg class="btn-cart__icon" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="currentColor" d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zM7.16 14h9.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A.996.996 0 0 0 21.08 5H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7.16l1.1-2z"/></svg>
						</a>
						<?php if ($this->countModules('cart', true)) : ?><span class="btn-cart__count"><jdoc:include type="modules" name="cart" style="none" /></span><?php endif; ?>
					</div>

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

			<?php // Optional hero module slot (raw, above the article hero) ?>
			<?php if ($this->countModules('hero', true)) : ?>
				<jdoc:include type="modules" name="hero" style="none" />
			<?php endif; ?>

			<?php // Optional announcement slot above the article ?>
			<?php if ($this->countModules('main-top', true)) : ?>
				<jdoc:include type="modules" name="main-top" style="none" />
			<?php endif; ?>

			<?php // Home article — kept as the hero section only ?>
			<jdoc:include type="component" />

			<?php
			// Each home section is an editable module rendered full-bleed (raw),
			// keeping its original section markup/design. Order = the page flow.
			$akHomeSections = [
				'about', 'overview', 'contact-center', 'solutions', 'products',
				'collaboration', 'specialized', 'gallery', 'process', 'partners',
				'coverage', 'cta', 'main-bottom',
			];
			foreach ($akHomeSections as $akPos) :
				if ($showPos($akPos)) : ?>
					<jdoc:include type="modules" name="<?php echo $akPos; ?>" style="none" />
				<?php endif;
			endforeach; ?>
		</main>
	<?php else : ?>
		<main id="top" class="subpage-main">
			<div class="container subpage-inner">
				<jdoc:include type="modules" name="main-top" style="card" />
				<jdoc:include type="message" />
				<div class="subpage-layout<?php echo $isLoggedIn && $this->countModules('sidebar-right', true) ? ' has-sidebar' : ''; ?>">
					<div class="subpage-content">
						<?php if ($isProductsListing) : ?>
							<header class="ak-products-hero">
								<p class="ak-products-hero__eyebrow">Catálogo AK Soluções</p>
								<p class="ak-products-hero__subtitle">Equipamentos selecionados para a sua infraestrutura.</p>
								<span class="ak-products-hero__rule" aria-hidden="true"></span>
							</header>
						<?php endif; ?>
						<jdoc:include type="component" />
						<jdoc:include type="modules" name="main-bottom" style="card" />
					</div>
					<?php if ($isLoggedIn && $this->countModules('sidebar-right', true)) : ?>
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
		<?php
		$hasFooterModules = $this->countModules('footer-brand', true)
			|| $this->countModules('footer-a', true)
			|| $this->countModules('footer-b', true)
			|| $this->countModules('footer-c', true)
			|| $this->countModules('footer-payments', true);
		?>
		<div class="container footer-grid<?php echo $hasFooterModules ? ' footer-grid--modules' : ''; ?>">
			<?php if ($this->countModules('footer-brand', true)) : ?>
				<div class="footer-col footer-col--brand"><jdoc:include type="modules" name="footer-brand" style="akmodule" /></div>
			<?php else : ?>
				<div class="footer-brand">
					<img src="<?php echo $tplPath; ?>/images/logos/logo-mark-white.png?v=<?php echo $akAssetVer; ?>" alt="<?php echo $sitename; ?>" decoding="async">
					<p><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_TAGLINE'); ?></p>
					<div class="social-links">
						<a href="https://www.instagram.com/aksolucoesbr" target="_blank" rel="noopener" aria-label="Instagram">
							<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92-.06-1.27-.07-1.64-.07-4.85s.01-3.58.07-4.85c.15-3.23 1.66-4.77 4.92-4.92C8.42 2.17 8.8 2.16 12 2.16M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.69.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.2-4.35-2.62-6.78-6.98-6.98C15.67.01 15.26 0 12 0m0 5.84A6.16 6.16 0 1 0 12 18.16 6.16 6.16 0 0 0 12 5.84M12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8m6.41-11.85a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88"/></svg>
						</a>
						<a href="https://www.facebook.com/AKSolucoes" target="_blank" rel="noopener" aria-label="Facebook">
							<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M24 12.07C24 5.44 18.63.07 12 .07S0 5.44 0 12.07c0 5.99 4.39 10.95 10.13 11.85v-8.38H7.08v-3.47h3.05V9.43c0-3.01 1.79-4.67 4.53-4.67 1.31 0 2.69.24 2.69.24v2.95h-1.51c-1.49 0-1.96.93-1.96 1.87v2.25h3.33l-.53 3.47h-2.8v8.38C19.61 23.02 24 18.06 24 12.07"/></svg>
						</a>
						<a href="https://www.linkedin.com/company/ak-solu%C3%A7%C3%B5es/" target="_blank" rel="noopener" aria-label="LinkedIn">
							<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13m1.78 13.02H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0"/></svg>
						</a>
					</div>
				</div>
			<?php endif; ?>

			<?php if ($hasFooterModules) : ?>
				<?php if ($this->countModules('footer-a', true)) : ?><div class="footer-col"><jdoc:include type="modules" name="footer-a" style="akmodule" /></div><?php endif; ?>
				<?php if ($this->countModules('footer-b', true)) : ?><div class="footer-col"><jdoc:include type="modules" name="footer-b" style="akmodule" /></div><?php endif; ?>
				<?php if ($this->countModules('footer-c', true)) : ?><div class="footer-col"><jdoc:include type="modules" name="footer-c" style="akmodule" /></div><?php endif; ?>
				<?php if ($this->countModules('footer-payments', true)) : ?><div class="footer-col footer-col--pay"><jdoc:include type="modules" name="footer-payments" style="akmodule" /></div><?php endif; ?>
			<?php elseif ($this->countModules('footer', true)) : ?>
				<jdoc:include type="modules" name="footer" style="none" />
			<?php else : ?>
				<div>
					<h4><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_SOLUTIONS'); ?></h4>
					<ul>
						<li><a href="<?php echo $this->baseurl; ?>/#solutions"><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_COLLAB'); ?></a></li>
						<li><a href="<?php echo $this->baseurl; ?>/#solutions"><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_CONTACT_CENTER'); ?></a></li>
						<li><a href="<?php echo $this->baseurl; ?>/#solutions"><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_DATACENTER'); ?></a></li>
						<li><a href="<?php echo $this->baseurl; ?>/#solutions"><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_TEM'); ?></a></li>
					</ul>
				</div>

				<div>
					<h4><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_CONTACT'); ?></h4>
					<ul>
						<li><a href="tel:<?php echo $phoneComercialTel; ?>"><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_COMMERCIAL'); ?>: <?php echo $phoneComercial; ?></a></li>
						<li><a href="tel:<?php echo $phoneTecnicoTel; ?>"><?php echo Text::_('TPL_AKSOLUCOES_FOOTER_TECHNICAL'); ?>: <?php echo $phoneTecnico; ?></a></li>
						<?php if ($whatsappUrl !== '') : ?>
							<li><a href="<?php echo $whatsappUrl; ?>" target="_blank" rel="noopener">WhatsApp: <?php echo $whatsappDisplay; ?></a></li>
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
