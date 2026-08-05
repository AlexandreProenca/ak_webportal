<?php

/**
 * AK Soluções — module chrome.
 * Wraps every module published into a template position with a consistent
 * shell (.ak-mod + optional title) so the home-page section grids and the
 * multi-column footer can lay modules out predictably.
 *
 * @package     Joomla.Site
 * @subpackage  Templates.aksolucoes
 * @copyright   (C) 2026 AK Soluções em Tecnologia
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

/** @var array $displayData */
$module  = $displayData['module'];
$params  = $displayData['params'];

if (!trim((string) $module->content)) {
    return;
}

$moduleClass = trim((string) $params->get('moduleclass_sfx', ''));
// moduleclass_sfx may start with a space-prefixed token; normalise it.
$moduleClass = $moduleClass !== '' ? ' ' . ltrim($moduleClass) : '';
$esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<div class="ak-mod<?php echo $esc($moduleClass); ?>" id="mod-<?php echo (int) $module->id; ?>">
	<?php if ($module->showtitle) : ?>
		<h3 class="ak-mod-title"><?php echo $esc($module->title); ?></h3>
	<?php endif; ?>
	<div class="ak-mod-body"><?php echo $module->content; ?></div>
</div>
