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

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;


$key = $displayData['key'];

$priceDisplayData = $displayData['priceDisplayData'];

$hasPrefix = !empty($priceDisplayData['prefix']);
$hasValue  = !empty($priceDisplayData['value']);
$hasSuffix = !empty($priceDisplayData['suffix']);

$wrapperClasses = trim('hs_price_element_wrapper ' . trim($priceDisplayData['wrapperClass'] ?? ''));

if ($hasPrefix || $hasValue || $hasSuffix) :
    ?>
    <div class="<?php echo $wrapperClasses; ?>">
        <?php
        if ($hasPrefix) :
            echo '<span class="hs_price_label hs_price_label_prefix">' . Text::_($priceDisplayData['prefix']) . '</span>';
        endif;

        if ($hasValue)  :
            echo '<span class="hs_price_value">' . $priceDisplayData['value'] . '</span>';
        endif;

        if ($hasSuffix) :
            echo '<span class="hs_price_label hs_price_label_suffix">' . Text::_($priceDisplayData['suffix']) . '</span>';
        endif;
        ?>
    </div>
<?php
endif;
