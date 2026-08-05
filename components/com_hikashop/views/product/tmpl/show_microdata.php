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
$pluginsClass = hikashop_get('class.plugins');
$google_products = $pluginsClass->getByName('hikashop','google_products');
$gp = (!empty($google_products->params)) ? $google_products->params : array();

$addCode = (empty($gp['add_code'])) ? '0' : $gp['add_code'];
$inStockOnly = (empty($gp['in_stock_only'])) ? '0' : $gp['in_stock_only'];
if(empty($gp))
	$taxedPrice = ((int)hikashop_config()->get('price_with_tax', 0)) ? '1' : '0';
else
	$taxedPrice = (empty($gp['taxed_price'])) ? '0' : $gp['taxed_price'];
$noDiscount = (empty($gp['no_discount'])) ? '0' : $gp['no_discount'];
$priceDisplayed = (empty($gp['price_displayed'])) ? 'cheapest' : $gp['price_displayed'];
$includeVariants = (empty($gp['include_variants'])) ? '0' : $gp['include_variants'];


$google_products_params = array();
$google_products_params['age_group'] = (empty($gp['age_group'])) ? '' : $gp['age_group'];
$google_products_params['gender'] = (empty($gp['gender'])) ? '' : $gp['gender'];
$google_products_params['size'] = (empty($gp['size'])) ? '' : $gp['size'];
$google_products_params['color'] = (empty($gp['color'])) ? '' : $gp['color'];
$google_products_params['mpn'] = (empty($gp['mpn'])) ? '' : $gp['mpn'];

global $Itemid;

$main =& $this->element;
if(!empty($this->element->main)) {
    $main =& $this->element->main;
}

$hasStock = $main->product_quantity != 0;

if (isset($this->element->variants) && $main->product_quantity == -1) {
    $hasStock = false;
    foreach ($this->element->variants as $key => $variant) {
        if ($variant->product_quantity != 0) {
            $hasStock = true;
            break;
        }
    }
}

if(($inStockOnly == '1') && !$hasStock)
    return;

$mpn = '';
if($addCode == '1')
    $mpn = $main->product_code;

$selected_price = $this->priceSelected($this->element, $priceDisplayed, $noDiscount, $taxedPrice);

if($selected_price === 0)
    return;

$config = hikashop_config();
$uploadFolder = ltrim(JPath::clean(html_entity_decode((string)$config->get('uploadfolder', ''))),DS);
$uploadFolder = rtrim($uploadFolder,DS).DS;
$main_uploadFolder_url = str_replace(DS,'/',$uploadFolder);

$main_img_tbl = array();
if (isset($main->images)) {
    $imageHelper = hikashop_get('helper.image');
    foreach ($main->images as $key => $image) {
        $check = $imageHelper->getThumbnail($image->file_path, array('width' => 100, 'height' => 100));
        if($check->success) {
             $url = $check->origin_url;
             if (strpos($url, 'http') !== 0) {
                 $base = JURI::base(true);
                 if(!empty($base) && strpos($url, $base) === 0) {
                     $url = substr($url, strlen($base));
                 }
                 $url = JURI::root() . ltrim($url, '/');
             }
             $main_img_tbl[] = $url;
        }
    }
}

if ($main->product_quantity == 0)
    $stock = "OutOfStock";
else
    $stock = "InStock";

$conditionMap = array(
    'new' => 'NewCondition',
    'used' => 'UsedCondition',
    'refurbished' => 'RefurbishedCondition',
    'NewCondition' => 'NewCondition',
    'UsedCondition' => 'UsedCondition',
    'RefurbishedCondition' => 'RefurbishedCondition',
);
$rawCondition = '';
if(!empty($main->product_condition))
    $rawCondition = $main->product_condition;
elseif(!empty($gp['condition']))
    $rawCondition = $gp['condition'];
$itemCondition = 'https://schema.org/' . (isset($conditionMap[$rawCondition]) ? $conditionMap[$rawCondition] : 'NewCondition');

$hasVariants = isset($this->element->variants);
if (!$hasVariants) {
    $product_id = $this->element->product_id;
    $product_type = 'Product';
} else {
    $product_id = $this->element->product_parent_id;
    $product_type = ($includeVariants == '1') ? 'ProductGroup' : 'Product';
}

$db = JFactory::getDBO();
$query = 'SELECT * FROM '.hikashop_table('vote').' WHERE vote_type = \'product\' AND vote_published > 0 AND vote_ref_id = '.(int)$product_id;
$db->setQuery($query);
$voteComments = $db->loadObjectList();

if (!empty($voteComments)) {
    $config = hikashop_config();
    $hikashop_vote_nb_star = $config->get('vote_star_number');

    $allRatingTot = 0;
    $ratedCount = 0;
    foreach ($voteComments as $k => $review) {
        if($review->vote_rating <= 0)
            continue;
        $allRatingTot += $review->vote_rating;
        $ratedCount++;
    }
    $averageRating = 0;
    if ($allRatingTot != 0 && $ratedCount != 0) {
        $averageRating = round($allRatingTot / $ratedCount, 2);
    }

    $type = '@type';
    $reviewObject  = array();
    foreach ($voteComments as $k => $review) {
        if($review->vote_rating <= 0)
            continue;
        $authorName = (!empty($review->vote_pseudo) && $review->vote_pseudo !== '0') ? $review->vote_pseudo : '';
        if(empty($authorName) && !empty($review->vote_user_id)) {
            $userClass = hikashop_get('class.user');
            $voteUser = $userClass->get($review->vote_user_id);
            if(!empty($voteUser->name))
                $authorName = $voteUser->name;
            elseif(!empty($voteUser->username))
                $authorName = $voteUser->username;
        }
        if(empty($authorName))
            continue;

        $author_obj = new stdClass();
        $author_obj->$type = "Person";
        $author_obj->name = $authorName;

        $reviewRating_obj = new stdClass();
        $reviewRating_obj->$type = "Rating";
        $reviewRating_obj->ratingValue = $review->vote_rating;
        $reviewRating_obj->bestRating = $hikashop_vote_nb_star;

        $review_obj = new stdClass();
        $review_obj->$type = "Review";
        $review_obj->reviewRating = $reviewRating_obj;
        $review_obj->author = $author_obj;
        $review_obj->datePublished = date('Y-m-d', $review->vote_date);
        if(!empty($review->vote_comment))
            $review_obj->reviewBody = $review->vote_comment;

        $reviewObject[] = $review_obj;
    }

    if ($ratedCount > 0) {
        $aggregateRating_obj = new stdClass();
        $aggregateRating_obj->$type = "AggregateRating";
        $aggregateRating_obj->ratingValue = $averageRating;
        $aggregateRating_obj->bestRating = $hikashop_vote_nb_star;
        $aggregateRating_obj->reviewCount = $ratedCount;
    }
}

$type = '@type';
if (isset($this->manufacturer->category_name)) {
    $brand_obj = new stdClass();
    $brand_obj->$type = "Brand";
    $brand_obj->name = $this->manufacturer->category_name;
}

$main_description = strip_tags(JHTML::_('content.prepare',preg_replace('#<hr *id="system-readmore" */?>#i','',$main->product_description)));
$main_description = trim(preg_replace('/\s+/', ' ', $main_description));
if(mb_strlen($main_description) > 5000)
	$main_description = mb_substr($main_description, 0, 4997) . '...';

$offer_url = hikashop_contentLink('index.php?option=com_hikashop&ctrl=product&task=show&cid='.$main->product_id.'&name='.$this->element->alias.'&Itemid='.$Itemid, $main);
$offer_obj = new stdClass();
$offer_obj->$type = "Offer";
$offer_obj->url = $offer_url;
$offer_obj->itemCondition = $itemCondition;
$offer_obj->availability = "https://schema.org/".$stock;
$offer_obj->price = $selected_price;
$offer_obj->priceCurrency = $this->currency->currency_code;
$returnPolicy = $this->_buildReturnPolicy($main, $gp);
if($returnPolicy !== null)
	$offer_obj->hasMerchantReturnPolicy = $returnPolicy;
$shippingDetails = $this->_buildShippingDetails($main, $gp, $this->currency->currency_code);
if(!empty($shippingDetails))
	$offer_obj->shippingDetails = $shippingDetails;

$context = '@context';
if ($product_type == 'Product') {
    $obj = new stdClass();
    $obj->$context = "https://schema.org/";
    $obj->$type = 'Product';
    $obj->name = strip_tags($main->product_name);
    if(!empty($main_img_tbl))
        $obj->image = $main_img_tbl;
    if(!empty($main_description))
        $obj->description = $main_description;
    $obj->url = hikashop_contentLink('index.php?option=com_hikashop&ctrl=product&task=show&cid='.$main->product_id.'&name='.$this->element->alias.'&Itemid='.$Itemid, $main);
    $obj->sku = $main->product_code;
    if ($mpn != '')
        $obj->mpn = $mpn;
    if (isset($brand_obj))
        $obj->brand = $brand_obj;
    $obj->offers = $offer_obj;

    if (isset($aggregateRating_obj)) {
        $obj->review = $reviewObject;
        $obj->aggregateRating = $aggregateRating_obj;
    }

    $params_array = $this->_additionalParameter($main, $google_products_params);

    if(count($google_products_params) > 0) {
        foreach ($params_array as $key => $value) {
            $key = strval($key);
            if ($value != '') {
                if (($key != 'mpn')) {
                    $obj->$key = $value;
                }
                if (!isset($obj->mpn) && ($key == 'mpn')) {
                    $obj->$key = $value;
                }
            }
        }
    }
}
else {
    $characteristic_array = array();
    foreach ($this->characteristics as $k => $characteristics) {
        $characteristic_array[$characteristics->variant_characteristic_id] = array(
            "label" => $characteristics->characteristic_value
        );
    }

    $variant_products_all = array();
    $variesBy = array();
    foreach ($this->element->variants as $k => $variant) {
        if (isset($variant->product_published) && $variant->product_published == "-1")
		    continue;

        if ($variant->product_quantity == 0)
            $stock = "OutOfStock";
        else
            $stock = "InStock";

        $img_tbl = array();
        if (isset($variant->images)) {
            $imageHelper = hikashop_get('helper.image');
            foreach ($variant->images as $k => $image) {
                $check = $imageHelper->getThumbnail($image->file_path, array('width' => 100, 'height' => 100));
                if($check->success) {
                     $url = $check->origin_url;
                     if (strpos($url, 'http') !== 0) {
                         $base = JURI::base(true);
                         if(!empty($base) && strpos($url, $base) === 0) {
                             $url = substr($url, strlen($base));
                         }
                         $url = JURI::root() . ltrim($url, '/');
                     }
                     $img_tbl[] = $url;
                }
            }
        }
        if(empty($img_tbl))
            $img_tbl = $main_img_tbl;

        $selected_price = $this->priceSelected($variant, $priceDisplayed, $noDiscount, $taxedPrice);

        $variant_offer = new stdClass();
        $variant_offer->$type = "Offer";
        $variant_offer->url = hikashop_contentLink('index.php?option=com_hikashop&ctrl=product&task=show&cid='.$variant->product_id.'&name='.$this->element->alias.'&Itemid='.$Itemid, $main, false, false, false, false, $variant->product_id);
        $variant_offer->itemCondition = $itemCondition;
        $variant_offer->priceCurrency = $this->currency->currency_code;
        $variant_offer->price = $selected_price;
        $variant_offer->availability = "https://schema.org/".$stock;
        $returnPolicy = $this->_buildReturnPolicy($variant, $gp);
        if($returnPolicy !== null)
            $variant_offer->hasMerchantReturnPolicy = $returnPolicy;
        $shippingDetails = $this->_buildShippingDetails($variant, $gp, $this->currency->currency_code);
        if(!empty($shippingDetails))
            $variant_offer->shippingDetails = $shippingDetails;

        $variant_products = new stdClass();
        $variant_products->$type = "Product";
        $variant_products->sku = $variant->product_code;
        if(!empty($img_tbl))
            $variant_products->image = $img_tbl;
        $variant_products->name = strip_tags($variant->product_name);
        $variant_desc = strip_tags(JHTML::_('content.prepare',preg_replace('#<hr *id="system-readmore" */?>#i','',$variant->product_description)));
        $variant_desc = trim(preg_replace('/\s+/', ' ', $variant_desc));
        if(mb_strlen($variant_desc) > 5000)
            $variant_desc = mb_substr($variant_desc, 0, 4997) . '...';
        $desc = !empty($variant_desc) ? $variant_desc : $main_description;
        if(!empty($desc))
            $variant_products->description = $desc;
        $variant_products->offers = $variant_offer;

        $params_array = $this->_additionalParameter($variant, $google_products_params);
        if(count($google_products_params) > 0) {
            foreach ($params_array as $key => $value) {
                $key = strval($key);
                if ($value != '') {
                    if (($key != 'mpn'))
                        $variant_products->$key = $value;
                    if($addCode == '1')
                        $variant_products->mpn = $variant->product_code;
                    if (!isset($variant_products->mpn) && ($key == 'mpn'))
                        $variant_products->$key = $value;
                }
            }
        }

        foreach ($variant->characteristics as $k => $val) {
            $chara_key = $val->characteristic_parent_id;
            if(!isset($characteristic_array[$chara_key]))
                continue;
            $label = (string)$characteristic_array[$chara_key]["label"];
            if(!in_array($label, array('size', 'color', 'suggestedAge', 'suggestedGender', 'material', 'pattern')))
                continue;
            $variesByKey = 'https://schema.org/'.$label;
            if(array_search($variesByKey, $variesBy) === false)
                $variesBy[] = $variesByKey;
            $value = (string)$val->characteristic_value;
            $variant_products->$label = $value;
        }
        $variant_products_all[] = $variant_products;
    }

    $obj = new stdClass();
    $obj->$context = "https://schema.org/";
    $obj->$type = 'ProductGroup';
    $obj->name = strip_tags($main->product_name);
    $obj->productGroupID = $main->product_code;
    if(!empty($main_img_tbl))
        $obj->image = $main_img_tbl;
    if(!empty($main_description))
        $obj->description = $main_description;
    $obj->url = hikashop_contentLink('index.php?option=com_hikashop&ctrl=product&task=show&cid='.$main->product_id.'&name='.$this->element->alias.'&Itemid='.$Itemid, $main);
    $obj->sku = $main->product_code;
    if (isset($aggregateRating_obj)) {
        $obj->aggregateRating = $aggregateRating_obj;
        $obj->review = $reviewObject;
    }
    if(isset($brand_obj))
        $obj->brand = $brand_obj;
    if(count($variesBy))
        $obj->variesBy = $variesBy;
    $obj->hasVariant = $variant_products_all;
}
$app = JFactory::getApplication();
JPluginHelper::importPlugin('hikashop');
$app->triggerEvent('onHikashopMicrodataProductInfo', array(&$obj, &$this->element, $main));

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK;
$json = json_encode($obj, $jsonFlags);
$doc = JFactory::getDocument();
$doc->addScriptDeclaration($json, 'application/ld+json');

$extraObjects = array();
$app->triggerEvent('onHikashopMicrodataExtraObjects', array(&$extraObjects, &$this->element, $main));
foreach($extraObjects as $extra) {
	$doc->addScriptDeclaration(json_encode($extra, $jsonFlags), 'application/ld+json');
}
