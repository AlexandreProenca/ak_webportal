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
class hikashopAddress_layoutType extends hikashopType {
    protected $fields = null;

    public function display($map, $value) {
        $fieldClass = hikashop_get('class.field');
        $this->fields = $fieldClass->getData('frontcomp', 'address');

        $config = json_decode($value, true);
        if(empty($config)) {
            $config = $this->getDefaultConfig();
        }

        return $this->renderBuilder($map, $config);
    }

    protected function getDefaultConfig() {
        $rows = array();
        foreach($this->fields as $field) {
            $rows[] = array(
                'fields' => array(
                    array(
                        'namekey' => $field->field_namekey,
                        'width' => 4,
                        'hide_label' => false
                    )
                )
            );
        }
        return array(
            'enabled' => false,
            'columns' => 4,
            'rows' => $rows,
            'global_hide_labels' => false
        );
    }

    protected function renderBuilder($map, $config) {
		$fieldName = $map->name;
		$id = 'hikashop_address_layout_' . uniqid();

		$html = '<div id="'.$id.'_container">';
		$html .= '<input type="hidden" name="'.$fieldName.'" id="'.$id.'" value="'.htmlspecialchars(json_encode($config)).'" />';

		$html .= '<div class="alert alert-info">'.JText::_('CLICK_CONFIGURE_TO_EDIT_LAYOUT').'</div>';


		$html .= '</div>';

		return $html;
    }
}
