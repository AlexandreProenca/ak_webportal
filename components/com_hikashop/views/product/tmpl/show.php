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
if(!empty($this->canonical)) {
	$doc = JFactory::getDocument();
	$doc->addHeadLink(hikashop_cleanURL($this->canonical), 'canonical');
}
$css_button = $this->config->get('css_button', 'hikabtn');

$classes = array();
if(!empty($this->categories)) {
	foreach($this->categories as $category) {
		$classes[] = 'hikashop_product_of_category_'.$category->category_id;
	}
}
?>
<div id="hikashop_product_<?php echo preg_replace('#[^a-z0-9]#i','_',(string)@$this->element->product_code); ?>_page" class="hikashop_product_page <?php echo implode(' ',$classes); ?> hikashop_product_<?php echo $this->productlayout; ?>">
<?php
$app = JFactory::getApplication();
if(empty($this->element)) {
	if($this->config->get('404_when_product_not_found',1)){
		throw new Exception(JText::_('PRODUCT_NOT_FOUND'), 404);
		echo '</div>';
		return;
	}
	$app->enqueueMessage(JText::_('PRODUCT_NOT_FOUND'));
	hikashop_setPageTitle(JText::_('PRODUCT_NOT_FOUND'));
	echo '</div>';
	return;
}

if(!empty($this->links->previous)) {
	echo '<div  data-toggle="hk-tooltip" data-original-title="'.JText::_('PREVIOUS_PRODUCT').'" class="hikashop_previous_product_btn">'.
		'<a href="'.$this->links->previous.'" class="'.$css_button.'">'.
			'<div class="hikashop_previous_product">'.
				'<i class="fas fa-caret-left fa-2x"></i>'.
			'</div>'.
			'<p>'.JText::_('HIKA_PREVIOUS_PRODUCT_MAIN').'</p>'.
		'</a>'.
	'</div>';
}
if(!empty($this->links->next)) {
	echo '<div data-toggle="hk-tooltip" data-original-title="'.JText::_('NEXT_PRODUCT').'" class="hikashop_next_product_btn">'.
		'<a  href="'.$this->links->next.'" class="'.$css_button.'">'.
			'<div class="hikashop_next_product">'.
				'<i class="fas fa-caret-right fa-2x"></i>'.
			'</div>'.
			'<p>'.JText::_('HIKA_NEXT_PRODUCT_MAIN').'</p>'.
		'</a>'.
	'</div>';
}
?>
	<div class='clear_both'></div>
<script type="text/javascript">
function hikashop_product_form_check() {
	var d = document, el = d.getElementById('hikashop_product_quantity_main');
	if(!el)
		return true;
	var inputs = el.getElementsByTagName('input');
	if(inputs && inputs.length > 0)
		return true;
	var links = el.getElementsByTagName('a');
	if(links && links.length > 0)
		return true;
	return false;
}
</script>
<?php

$this->variant_name ='';
if(!empty($this->element->variants) && $this->config->get('variant_increase_perf', 1) && !empty($this->element->main)) {
	foreach(get_object_vars($this->element->main) as $name => $value) {
		if(!is_array($name) && !is_object($name)) {
			if(empty($this->element->$name)) {
				if($name == 'product_quantity' && $this->element->$name == 0) {
					continue;
				}
				$this->element->$name = $this->element->main->$name;
				continue;
			}
		}
		if($this->params->get('characteristic_display') == 'list' && !empty($this->element->characteristics) && !empty($this->element->main->characteristics)) {
			$this->element->$name = $this->element->main->$name;
		}
	}
}

if($this->productlayout == 'show_tabular') {
?>
	<form action="<?php echo hikashop_completeLink('product&task=updatecart'); ?>" method="post" name="hikashop_product_form" id="hikashop_product_form" onsubmit="return hikashop_product_form_check();" enctype="multipart/form-data">
<?php
} else {
?>
	<form action="<?php echo hikashop_completeLink('product&task=updatecart'); ?>" method="post" name="hikashop_product_form" id="hikashop_product_form" onsubmit="return hikashop_product_form_check();" enctype="multipart/form-data">
		<input type="hidden" name="cart_type" id="type" value="cart"/>
		<input type="hidden" name="add" value="<?php echo !$this->config->get('synchronized_add_to_cart', 0); ?>"/>
		<input type="hidden" name="ctrl" value="product"/>
		<input type="hidden" name="task" value="updatecart"/>
		<input type="hidden" name="return_url" value="<?php echo urlencode(base64_encode(urldecode($this->redirect_url))); ?>"/>
	</form>
<?php
}

$this->formName = '';
$this->currentFormName = 'hikashop_product_form';
$this->setLayout($this->productlayout);
echo $this->loadTemplate();

if($this->productlayout != 'show_tabular') {
}

if($this->params->get('characteristic_display') == 'list') {
	$this->setLayout('show_block_characteristic');
	echo $this->loadTemplate();
}

if($this->productlayout != 'show_tabular') {
	$enable_status_vote = $this->config->get('enable_status_vote', '');
	if(in_array($enable_status_vote, array('comment', 'two', 'both'))) {
?>
	<form action="<?php echo hikashop_currentURL() ?>" method="post" name="adminForm_hikashop_comment_form" id="hikashop_comment_form">
		<div id="hikashop_vote_listing" data-votetype="product" class="hikashop_product_vote_listing">
<?php
		if($this->params->get('show_vote_product')) {
			$js = '';
			if(isset($this->element->main)) {
				$product_id = $this->element->main->product_id;
			} else {
				$product_id = $this->element->product_id;
			}
			$this->params->set('product_id',$product_id);
			echo hikashop_getLayout('vote', 'listing', $this->params, $js);
?>
		</div>
		<div id="hikashop_vote_form" data-votetype="product" class="hikashop_product_vote_form">
<?php
			$js = '';
			if(isset($this->element->main)) {
				$product_id = $this->element->main->product_id;
			} else {
				$product_id = $this->element->product_id;
			}
			$this->params->set('product_id',$product_id);
			echo hikashop_getLayout('vote', 'form', $this->params, $js);
		}
?>
		</div>
		<input type="hidden" name="add" value="1"/>
		<input type="hidden" name="ctrl" value="product"/>
		<input type="hidden" name="task" value="show"/>
		<input type="hidden" name="return_url" value="<?php echo urlencode(base64_encode(urldecode($this->redirect_url))); ?>"/>
	</form>
<?php
	}
}

$contact = $this->config->get('product_contact',0);

if(empty($this->element->variants) || $this->params->get('characteristic_display') == 'list') {
	if(hikashop_level(1) && !empty($this->element->options)) {
		$priceUsed = 0;
		$unit_price = false;
		if(!empty($this->element->prices)) {
			foreach($this->element->prices as $price) {
				if(!isset($price->price_min_quantity) || !empty($this->cart_product_price) || $unit_price)
					continue;
				if($price->price_min_quantity <= 1)
					$unit_price = true;

				$name = 'price_value';
				if($this->params->get('price_with_tax'))
					$name = 'price_value_with_tax';

				if(!$unit_price && $price->$name > $priceUsed)
					continue;

				$priceUsed = $price->$name;
			}
		}
		if(!empty($this->displayVariants['prices']) && $this->params->get('characteristic_display') == 'list') {
			$priceUsed = 0;
		}
?>
	<input type="hidden" name="hikashop_price_product" value="<?php echo (int)$this->element->product_id; ?>" form="hikashop_product_form" />
	<input type="hidden" id="hikashop_price_product_<?php echo (int)$this->element->product_id; ?>" value="<?php echo $priceUsed; ?>" form="hikashop_product_form" />
	<input type="hidden" id="hikashop_price_product_with_options_<?php echo (int)$this->element->product_id; ?>" value="<?php echo $priceUsed; ?>" form="hikashop_product_form" />
<?php
	}
} else {
	$this->setLayout('show_variants');
	echo $this->loadTemplate();
}

$this->params->set('show_price_weight', 0);
$this->product = $this->element;

?>
	<div class="hikashop_submodules" id="hikashop_submodules" style="clear:both">
<?php
	if(!empty ($this->modules) && is_array($this->modules)) {
		jimport('joomla.application.module.helper');
		foreach($this->modules as $module) {
			echo JModuleHelper::renderModule($module);
		}
	}
?>
	</div>
	<div class="hikashop_external_comments" id="hikashop_external_comments" style="clear:both">
<?php
if($this->config->get('comments_feature') == 'jcomments') {
	$comments = HIKASHOP_ROOT . 'components' . DS . 'com_jcomments' . DS . 'jcomments.php';
	if(file_exists($comments)) {
		require_once ($comments);
		if(hikashop_getCID('product_id') != $this->product->product_id && isset($this->product->main->product_name)) {
			$product_id = $this->product->main->product_id;
			$product_name = $this->product->main->product_name;
		} else {
			$product_id = $this->product->product_id;
			$product_name = $this->product->product_name;
		}
		if(class_exists('JComments'))
			echo JComments::show($product_id, 'com_hikashop', $product_name);
	}
} elseif($this->config->get('comments_feature') == 'jomcomment') {
	$comments = HIKASHOP_ROOT . 'plugins' . DS . 'content' . DS . 'jom_comment_bot.php';
	if(file_exists($comments)) {
		require_once ($comments);
		if(hikashop_getCID('product_id') != $this->product->product_id && isset($this->product->main->product_name))
			$product_id = $this->product->main->product_id;
		else
			$product_id = $this->product->product_id;
		if(function_exists('jomcomment'))
			echo jomcomment($product_id, 'com_hikashop');
	}
} elseif($this->config->get('comments_feature') == 'komento') {
	$comments = HIKASHOP_ROOT . 'components' . DS . 'com_komento' . DS . 'bootstrap.php';
	if(file_exists($comments)) {
		require_once ($comments);
		if(class_exists('KT'))
			echo KT::commentify('com_hikashop', $this->product, array('params' => ''));
	}
}
?>
	</div>
</div>
