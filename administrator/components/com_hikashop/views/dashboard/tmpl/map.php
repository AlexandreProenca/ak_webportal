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
$i=0;
if(isset($this->edit)){
	$showLegend='true';
}else{
	$showLegend='false';
}
if(empty($this->widget->elements)){
	$data[] = 'data.setValue(0, 0, null);
			data.setValue(0, 1, 0);
			data.setValue(0, 2, null);';
}else{
	foreach($this->widget->elements as $element){
		$data[] = 'data.setValue('.$i.', 0, \''.str_replace("'","\'",$element->zone_code_2).'\');
					data.setValue('.$i.', 1, '.(int)$element->total.');';
		$i++;
	}
}

if(isset($this->edit) && $this->edit){
	$size="";
	$height="350";
}else{
	$height="210";
	$width = "300px";

	if(isset($this->widget->dashboard_class)) {
		if(strpos($this->widget->dashboard_class, 'hkc-sm-12') !== false && strpos($this->widget->dashboard_class, 'hkc-lg') === false) {
			$height = '400';
			$width = '100%';
		} elseif(strpos($this->widget->dashboard_class, 'hkc-lg-8') !== false) {
			$height = '350';
			$width = '100%';
		} elseif(strpos($this->widget->dashboard_class, 'hkc-lg-6') !== false) {
			$height = '300';
			$width = '100%';
		} elseif(strpos($this->widget->dashboard_class, 'hkc-lg-4') !== false) {
			$height = '250';
			$width = '100%';
		}
	}

	$size="options['width'] = '".$width."';
				options['height'] = '".$height."px'";
}

$contentLabel = isset($this->widget->widget_params->content) && is_string($this->widget->widget_params->content) 
	? strtoupper($this->widget->widget_params->content) 
	: 'ORDERS';

$js="
google.load('visualization', '49', {'packages':['geochart']});
			google.setOnLoadCallback(drawChart_".$this->widget->widget_id.");
			function drawChart_".$this->widget->widget_id."() {
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'Code');
				data.addColumn('number', '".JText::_($contentLabel)."');
				data.addRows(".count($data).");
				".implode("\n",$data)."
				var options = {};
				".$size."
				options['showLegend'] = ".$showLegend.";";

$region = 'world';
if(isset($this->widget->widget_params->REGION) && is_string($this->widget->widget_params->REGION) && !empty($this->widget->widget_params->REGION)) {
	$region = $this->widget->widget_params->REGION;
}
$js .= "
				options['region']='" . addslashes($region) . "';";

$colorLow = isset($this->widget->widget_params->COLOR_LOW) && is_string($this->widget->widget_params->COLOR_LOW) ? $this->widget->widget_params->COLOR_LOW : '';
$colorHigh = isset($this->widget->widget_params->COLOR_HIGH) && is_string($this->widget->widget_params->COLOR_HIGH) ? $this->widget->widget_params->COLOR_HIGH : '';
if(!empty($colorLow) && !empty($colorHigh)) {
	$js .= "
				options['colorAxis'] = {colors: ['" . addslashes($colorLow) . "', '" . addslashes($colorHigh) . "']};";
}

$js .= "
				var chart = new google.visualization.GeoChart(document.getElementById('graph_".$this->widget->widget_id."'));
				chart.draw(data, options);
			}";
$doc = JFactory::getDocument();
$doc->addScriptDeclaration($js);
?>
<div id="graph_<?php echo $this->widget->widget_id; ?>" style="height: <?php echo $height; ?>px;" class="hk_center hikashop_google_graph"></div>
