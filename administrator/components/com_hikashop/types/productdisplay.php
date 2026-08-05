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
class hikashopProductdisplayType extends hikashopType {
	var $default = array(
		'show_default',
		'show_reversed',
		'show_tabular',
		'show_gallery',
		'show_sticky',
		'show_modern',
		'show_fullwidth',
		'show_showcase'
	);

	function load(){
		if(!empty($this->values))
			return;
		$this->values = array();
		if(!HIKASHOP_J40) {
			if(hikaInput::get()->getCmd('from_display',false) == false)
				$this->values[] = JHTML::_('select.option', '', JText::_('HIKA_INHERIT'));
			$this->values[] = JHTML::_('select.optgroup', '-- '.JText::_('FROM_HIKASHOP').' --');
			foreach($this->default as $d) {
				$this->values[] = JHTML::_('select.option', $d, JText::_(strtoupper($d)));
			}
			$this->values[] = JHTML::_('select.optgroup', '-- '.JText::_('FROM_HIKASHOP').' --');

			$closeOpt = '';
			$values = $this->getLayout();
			foreach($values as $value) {
				if(substr($value,0,1) == '#') {
					if(!empty($closeOpt)){
						$this->values[] = JHTML::_('select.optgroup', $closeOpt);
					}
					$value = substr($value,1);
					$closeOpt = '-- ' . JText::sprintf('FROM_TEMPLATE',basename($value)) . ' --';
					$this->values[] = JHTML::_('select.optgroup', $closeOpt);
				} else {
					$this->values[] = JHTML::_('select.option', $value, JText::_(strtoupper($value)));
				}
			}
			if(!empty($closeOpt)){
				$this->values[] = JHTML::_('select.optgroup', $closeOpt);
			}
		} else {
			if(hikaInput::get()->getCmd('from_display',false) == false)
				$this->values[''] = array('items' => array( JHTML::_('select.option', '', JText::_('HIKA_INHERIT'))) );
			$this->values['core'] = array(
				'text' => '-- '.JText::_('FROM_HIKASHOP').' --',
				'items' => array()
			);
			foreach($this->default as $d) {
				$this->values['core']['items'][] = JHTML::_('select.option', $d, JText::_(strtoupper($d)));
			}

			$tmpl_name = '';
			$values = $this->getLayout();
			if(!empty($values)) foreach($values as $value) {
				if(substr($value,0,1) == '#') {
					$value = substr($value,1);
					$tmpl_name = basename($value);
					$this->values[$tmpl_name] = array(
						'text' => '-- ' . JText::sprintf('FROM_TEMPLATE',$tmpl_name) . ' --',
						'items' => array()
					);
				} else {
					$this->values[$tmpl_name]['items'][] = JHTML::_('select.option', $value, JText::_(strtoupper($value)));
				}
			}
		}

		JPluginHelper::importPlugin('hikashop');
		$app = JFactory::getApplication();
		$app->triggerEvent('onProductLayoutSelect', array(&$this->values));
	}

	function display($map,$value) {
		$this->load();
		if(!HIKASHOP_J40)
			return JHTML::_('select.genericlist', $this->values, $map, 'class="custom-select" size="1"', 'value', 'text', $value );
		return JHTML::_('select.groupedlist', $this->values, $map, array('list.attr'=>'class="custom-select"', 'group.id' => 'id', 'list.select' => array($value)) );
	}

	function check($name,$template) {
		if($name == '' || in_array($name, $this->default) || strpos($name, 'plg.') !== false)
			return true;
		$values = $this->getLayout($template);
		if(in_array($name, $values))
			return true;

		if(HIKASHOP_J40) {
			$app = JFactory::getApplication();
			$templateObj = $app->getTemplate(true);
			if(!empty($templateObj->parent) && $templateObj->parent !== $template) {
				$parentValues = $this->getLayout($templateObj->parent);
				return in_array($name, $parentValues);
			}
		}
		return false;
	}

	function getLayout($template = '') {
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
		static $cache = array();
		$cacheKey = $template === '' ? '__all__' : $template;
		if(isset($cache[$cacheKey]))
			return $cache[$cacheKey];
		$values = array();
		$client	= JApplicationHelper::getClientInfo(0); // 0: Front client
		$tplDir = $client->path.DS.'templates'.DS;
		$values = array();
		if(empty($template)) {
			$templates = JFolder::folders($tplDir);
			if(empty($templates))
				return null;
		} else {
			$templates = array($template);
		}
		$groupAdded = false;
		foreach($templates as $tpl) {
			$t = $tplDir.$tpl.DS.'html'.DS.HIKASHOP_COMPONENT.DS;
			if(!is_dir($t))
				continue;
			$folders = JFolder::folders($t);
			if(empty($folders))
				continue;
			foreach($folders as $folder) {
				$files = JFolder::files($t.$folder.DS);
				if(empty($files))
					continue;
				foreach($files as $file) {
					if(substr($file,-4) == '.php')
						$file = substr($file,0,-4);
					if(substr($file,0,5) == 'show_' && substr($file,0,14) != 'show_quantity_' && substr($file,0,11) != 'show_block_' && !in_array($file,$this->default)) {
						if(!$groupAdded) {
							$values[] = '#'.$tpl;
							$groupAdded = true;
						}
						$values[] = $file;
					}
				}
			}
		}
		$cache[$cacheKey] = $values;
		return $values;
	}

	public function displaySingle($map, $value, $display = '', $root = 0, $delete = false) {
		if(empty($this->nameboxType))
			$this->nameboxType = hikashop_get('type.namebox');

		return $this->nameboxType->display(
			$map,
			$value,
			hikashopNameboxType::NAMEBOX_SINGLE,
			'product',
			array(
				'delete' => $delete,
				'root' => $root,
				'displayFormat' => $display,
				'default_text' => '<em>'.JText::_('HIKA_NONE').'</em>',
			)
		);
	}

	public function displayMultiple($map, $values, $display = '', $root = 0) {
		if(empty($this->nameboxType))
			$this->nameboxType = hikashop_get('type.namebox');

		return $this->nameboxType->display(
			$map,
			$values,
			hikashopNameboxType::NAMEBOX_MULTIPLE,
			'product',
			array(
				'delete' => true,
				'root' => $root,
				'sort' => true,
				'displayFormat' => $display,
				'default_text' => '<em>'.JText::_('HIKA_NONE').'</em>',
			)
		);
	}
}
