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
$data = array();
$types=array();
$values='';
foreach($this->widget->elements as $element){
	if(isset($element->type) && !isset($types[$element->type])){
		$types[$element->type]="data.addColumn('number', '".$element->type."')";
	}
}
if(empty($types)){
	$types[]="data.addColumn(data.addColumn('number', undefined))";
}

foreach($this->widget->elements as $element){
	if(isset($element->type)){
		$values=array();
		foreach($types as $type => $k){
			if ($type==$element->type){
				$values[]=(int)$element->total;
			}else{
				$values[]='null';
			}
		}
		$values=implode(', ',$values);
	}else{
		$values=(int)$element->total;
	}
	$data[] = '[new Date('.$element->year.', '.(int)$element->month.', '.(int)$element->day.', '.@(int)$element->hour.'), '.$values.']';
}
if(!isset($this->widget->widget_id)){
	$id='preview';
}else{
	$id=$this->widget->widget_id;
}
$js="
google.load('visualization', '49', {'packages':['annotatedtimeline']});
			google.setOnLoadCallback(drawChart_".$id.");
			function drawChart_".$id."() {
				var data = new google.visualization.DataTable();
				data.addColumn('date', undefined);
				 ".implode('; ', $types)."
				data.addRows([
					".implode(', ',$data)."
				]);
		var el = document.getElementById('graph_".$id."');
				var chart = new google.visualization.AnnotatedTimeLine(el);
				chart.draw(data,{'wmode':'transparent'});
				el.style.width = null;
			}";
$doc = JFactory::getDocument();
$doc->addScriptDeclaration($js);
if(isset($this->edit) && $this->edit){
	$size='width: 900px; height: 500px;';
}else{
	$height='210px';
	$width = '300px';

	if(isset($this->widget->dashboard_class)) {
		$width = '100%';
		if(strpos($this->widget->dashboard_class, 'hkc-sm-12') !== false && strpos($this->widget->dashboard_class, 'hkc-lg') === false) {
			$height = '400px';
		} elseif(strpos($this->widget->dashboard_class, 'hkc-lg-8') !== false) {
			$height = '350px';
		} elseif(strpos($this->widget->dashboard_class, 'hkc-lg-6') !== false) {
			$height = '300px';
		} elseif(strpos($this->widget->dashboard_class, 'hkc-lg-4') !== false) {
			$height = '250px';
		}
	}
	$size='width: '.$width.'; height: '.$height.';';
}
?>
<div id="graph_<?php echo $id; ?>" style="<?php echo $size; ?>" class="hk_center hikashop_google_graph"></div>
