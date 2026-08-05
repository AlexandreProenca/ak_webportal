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
$width = 190;
$height = 190;
$divHeight = 210;

if(isset($this->widget->dashboard_class)) {
	if(strpos($this->widget->dashboard_class, 'hkc-sm-12') !== false && strpos($this->widget->dashboard_class, 'hkc-lg') === false) {
		$height = 400;
		$width = '100%';
		$divHeight = 400;
	} elseif(strpos($this->widget->dashboard_class, 'hkc-lg-8') !== false) {
		$height = 350;
		$width = '100%';
		$divHeight = 350;
	} elseif(strpos($this->widget->dashboard_class, 'hkc-lg-6') !== false) {
		$height = 300;
		$width = '100%';
		$divHeight = 300;
	} elseif(strpos($this->widget->dashboard_class, 'hkc-lg-4') !== false) {
		$height = 250;
		$width = '100%';
		$divHeight = 250;
	}
}
$widthOpt = is_numeric($width) ? $width : "'".$width."'";

$js="
google.load('visualization', '49', {packages:['gauge']});
			google.setOnLoadCallback(drawChart_".$this->widget->widget_id.");
			function drawChart_".$this->widget->widget_id."() {
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'Label');
				data.addColumn('number', 'Value');
				data.addRows(1);
				data.setValue(0, 0, '');
				data.setValue(0, 1, ".(int)$this->widget->main.");

				var chart = new google.visualization.Gauge(document.getElementById('graph_".$this->widget->widget_id."'));
				var options = {width: ".$widthOpt.", height: ".$height.", redFrom: 0, redTo: ".(int)($this->widget->average/2).",
						yellowFrom:".(int)($this->widget->average/2).", yellowTo: ".(int)$this->widget->average.",
						greenFrom:".(int)$this->widget->average.", greenTo: ".(int)($this->widget->average*2).", minorTicks: 5, min: 0, max: ".(int)($this->widget->average*2)."};
				chart.draw(data, options);
			}";
$doc = JFactory::getDocument();
$doc->addScriptDeclaration($js);
?>
<div id="graph_<?php echo $this->widget->widget_id; ?>" style="height: <?php echo $divHeight; ?>px;" class="hk_center hikashop_google_graph"></div>
