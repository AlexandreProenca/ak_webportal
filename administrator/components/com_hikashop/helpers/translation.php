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
class hikashopTranslationHelper {
	public $languages = array();
	public $falang = false;
	public $database = null;
	public $flagPath = null;

	function __construct(){
		$this->database = JFactory::getDBO();
		$app = JFactory::getApplication();
		$this->flagPath = 'media/mod_languages/images/';
		if(hikashop_isClient('administrator')){
			$this->flagPath = '../'.$this->flagPath;
		} else {
			$this->flagPath = rtrim(JURI::base(true),'/') . '/' . $this->flagPath;
		}
	}

	function isMulti($inConfig=false,$level=true){
		static $multi=array();
		static $falang = false;
		$this->falang =& $falang;
		$key = (int)$inConfig.'_'.(int)$level;
		if(!isset($multi[$key])){
			$multi[$key] = false;
			$config =& hikashop_config();
			if((hikashop_level(1) || !$level) && ($config->get('multi_language_edit',1) || $inConfig)){

				$multi[$key] = true;
				$oldQuery = $this->database->getQuery(false);
				$query = 'SHOW TABLES LIKE '.$this->database->Quote($this->database->getPrefix().substr(hikashop_table('falang_content',false),3));
				$this->database->setQuery($query);
				$table = $this->database->loadResult();
				if(!empty($table) && $this->isFalangEnabled()){
					$falang = true;
				}
				$this->database->setQuery($oldQuery);
			}
		}
		return $multi[$key];
	}

	function isFalangEnabled(){
		static $enabled = null;
		if($enabled !== null)
			return $enabled;
		$enabled = false;
		if(defined('HIKASHOP_WORDPRESS'))
			return $enabled;
		$this->database->setQuery('SELECT enabled FROM '.hikashop_table('extensions',false).' WHERE type='.$this->database->Quote('component').' AND element='.$this->database->Quote('com_falang'));
		$enabled = (bool)$this->database->loadResult();
		return $enabled;
	}

	function getFlag($id=0){
		$this->loadLanguages();
		if(isset($this->languages[$id])){
			return '<span style="background: url('.$this->flagPath.$this->languages[$id]->shortcode.'.gif) no-repeat;padding-left:20px">'.$this->languages[$id]->code.'</span>';
		}
		return $this->languages[$id]->code;
	}

	function loadLanguages($active = true){
		if(empty($this->languages)){
			if(defined('HIKASHOP_WORDPRESS')) {
				$locale = get_locale();
				$tag = str_replace('_', '-', $locale);
				$lang = new stdClass();
				$lang->id = 1;
				$lang->code = $tag;
				$lang->shortcode = substr($tag, 0, 2);
				$lang->active = 1;
				$this->languages[1] = $lang;
			} else {
				$query = 'SELECT lang_id as id, lang_code as code, image as shortcode, published as active FROM '.hikashop_table('languages',false).($active?' WHERE published=1':'');
				$this->database->setQuery($query);
				$this->languages = $this->database->loadObjectList('id');
			}
		}
		foreach($this->languages as $key => $language){
			if(empty($language->shortcode)){
				$this->languages[$key]->shortcode = substr($language->code,0,2);
			}
		}
		return $this->languages;
	}

	function loadLanguage($id){
		$query = 'SELECT lang_id as id, lang_code as code, image as shortcode, published as active FROM '.hikashop_table('languages',false).' WHERE lang_id='.(int)$id;
		$this->database->setQuery($query);
		return $this->database->loadObject();
	}

	function getId($code){
		$this->loadLanguages();
		if(empty($this->languages) || !is_array($this->languages)) return 0;
		foreach($this->languages as $lg){
			if($lg->code==$code) return $lg->id;
		}
		return 0;
	}
	function translateAlias(&$element, $type = 'product', $code = null) {

		$lang = JFactory::getLanguage();
		if(empty($code)) {
			$code = $lang->getTag();
		}

		if($this->falang) {
			$lang_id = $this->getId($code);
			$id = $type.'_id';
			$query = 'SELECT value FROM '.hikashop_table('falang_content',false).' WHERE language_id='.(int)$lang_id.' AND reference_table='.$this->database->Quote('hikashop_'.$type).' AND reference_field=\''.$type.'_alias\' AND reference_id = '.$this->database->Quote($element->$id);
			$this->database->setQuery($query);
			$result = $this->database->loadResult();
			if(!empty($result)) {
				$element->alias = $result;
			}
		} else {
			if(substr($element->alias,0,9) == '#notrans#') {
				$element->alias = substr($element->alias,9);
				return;
			}
			if(empty($element->alias))
				return;

			jimport('joomla.filesystem.folder');
			$path = hikashop_getLanguagePath(JPATH_ROOT);
			static $overrides = array();
			if(!isset($overrides[$code])) {
				$override_file_path = $path . '/overrides/'.$code.'.override.ini';
				if(file_exists($override_file_path)) {
					$overrides[$code] = parse_ini_file($override_file_path);
				}
			}
			if(!empty($overrides[$code])) {
				$key =	$this->getKey($element->alias);
				if(isset($overrides[$code][$key])) {
					$element->alias = $overrides[$code][$key];
				}
			}
		}
	}
	function getOriginalId($type, $name, $dynamic=false, $forceRefresh=false) {
		$lang = JFactory::getLanguage();
		$tag = $lang->getTag();
		if($this->falang) {
			$lang_id = $this->getId($tag);
			$condition = 'reference_field='.$this->database->Quote($type.'_alias').' AND value = '.$this->database->Quote(str_replace(':','-',$name));
			if($dynamic) {
				$name_regex = '^ *p?'.str_replace(array('-',':'),'.+',str_replace(array('*', '+', '(', ')', '?', '='), '',$name)).' *$';
				$condition = '((reference_field='.$this->database->Quote($type.'_alias').' AND (value = '.$this->database->Quote(str_replace(':','-',$name)).' OR value REGEXP '.$this->database->Quote($name_regex).')) OR (reference_field='.$this->database->Quote($type.'_name').' AND value REGEXP '.$this->database->Quote($name_regex).'))';
			}
			$query = 'SELECT reference_id FROM '.hikashop_table('falang_content',false).' WHERE language_id='.(int)$lang_id.' AND reference_table='.$this->database->Quote('hikashop_'.$type).' AND '. $condition;
			$this->database->setQuery($query);
			$retrieved_id = $this->database->loadResult();
			return $retrieved_id;
		}

		if(!isset($_SESSION['hikashop_alias_cache']))
			$_SESSION['hikashop_alias_cache'] = array();

		if(!isset($_SESSION['hikashop_alias_cache'][$type]) || $forceRefresh) {
			$condition = '';
			if($type == 'product') {
				$condition = ' WHERE product_type = \'main\'';
			}
			$query = 'SELECT '.$type.'_id as id,'.$type.'_alias,'.$type.'_name  FROM '.hikashop_table($type). $condition;
			$this->database->setQuery($query);
			$_SESSION['hikashop_alias_cache'][$type] = $this->database->loadObjectList('id');
			if(!empty($_SESSION['hikashop_alias_cache'][$type])) {
				$class = hikashop_get('class.'.$type);
				foreach($_SESSION['hikashop_alias_cache'][$type] as $k => $v) {
					$key = $type.'_id';
					$_SESSION['hikashop_alias_cache'][$type][$k]->$key = $v->id;
					$class->addAlias($_SESSION['hikashop_alias_cache'][$type][$k]);
				}
			}
		}
		if(empty($_SESSION['hikashop_alias_cache'][$type]))
			return false;

		foreach($_SESSION['hikashop_alias_cache'][$type] as $k => $v) {
			if($_SESSION['hikashop_alias_cache'][$type][$k]->alias == str_replace(':','-',$name)) {
				return $k;
			}
		}

		if(!$forceRefresh) {
			return $this->getOriginalId($type, $name, $dynamic, true);
		}

		return false;
	}

	function load($table,$id,&$element,$language_id=0){
		$where="";
		if(empty($language_id)){
			$this->loadLanguages();
			$languages =& $this->languages;
		}else{
			$where=' AND language_id='.(int)$language_id;
			$languages=array((int)$language_id=>$this->loadLanguage($language_id));
		}

		if(is_null($element))
			$element = new stdClass();
		$element->translations=array();

		if($this->falang){
			$trans_table = 'falang_content';
			$query = 'SELECT * FROM '.hikashop_table($trans_table,false).' WHERE reference_id='.(int)$id.' AND reference_table='.$this->database->Quote($table).$where;
			$this->database->setQuery($query);
			$data = $this->database->loadObjectList();

			if(!empty($data)){
				foreach($data as $entry){
					$field = $entry->reference_field;
					$lg = (int)$entry->language_id;
					if(!isset($element->translations[$lg])){
						$obj = new stdClass();
						$obj->$field = $entry;
						$element->translations[$lg] = $obj;
					}else{
						$element->translations[$lg]->$field=$entry;
					}
				}

			}
		} else {
			jimport('joomla.filesystem.folder');
			$path = hikashop_getLanguagePath(JPATH_ROOT);
			foreach($languages as $lang) {
				$override_file_path = $path . '/overrides/'.$lang->code.'.override.ini';
				if(file_exists($override_file_path)) {
					$overrides = parse_ini_file($override_file_path);
					foreach(get_object_vars($element) as $field => $var) {
						if(is_array($var) || is_object($var))
							continue;
						$val =	$this->getKey($var);
						if(isset($overrides[$val])) {
							$entry = new stdClass();
							$entry->value = $overrides[$val];
							$entry->id = uniqid();
							$entry->published = 1;
							$entry->reference_field = $field;
							$entry->reference_table = $table;
							$entry->reference_id = (int)$id;
							$entry->language_id = (int)$lang->id;
							if(!isset($element->translations[$entry->language_id])){
								$obj = new stdClass();
								$obj->$field = $entry;
								$element->translations[$entry->language_id] = $obj;
							}else{
								$element->translations[$entry->language_id]->$field = $entry;
							}
						}
					}
				}
			}
		}
		if(!empty($languages)){
			foreach($languages as $lg){
				$lgid = (int)$lg->id;
				if(!isset($element->translations[$lgid])){
					$element->translations[$lgid] = array();
				}
			}
		}
		ksort($element->translations);
	}

	function loadOne($src, $language_id) {
		static $overrides = array();
		if(!isset($overrides[$language_id])) {
			$lang = $this->loadLanguage($language_id);
			jimport('joomla.filesystem.folder');
			$path = hikashop_getLanguagePath(JPATH_ROOT);
			$override_file_path = $path . '/overrides/'.$lang->code.'.override.ini';
			if(file_exists($override_file_path)) {
				$overrides[$language_id] = parse_ini_file($override_file_path);
			}
		}
		if(empty($overrides[$language_id]))
			return '';

		$val =	$this->getKey($src);
		if(!isset($overrides[$language_id][$val]))
			return '';

		return $overrides[$language_id][$val];
	}

	function getTranslations(&$element) {
		$transArray = hikaInput::get()->get('translation', array(), 'array');
		foreach($transArray as $field => $trans){
			foreach($trans as $lg => $value){
				if(!empty($value)){
					$obj = new stdClass();
					$obj->reference_field = $field;
					$obj->language_id=(int)$lg;
					$obj->value = $value;
					if(!isset($element->translations)){
						$element->translations = array();
					}
					if(!isset($element->translations[(int)$lg])){
						$element->translations[(int)$lg] = new stdClass();
					}
					$element->translations[(int)$lg]->$field = $obj;
				}
			}
		}
		foreach($_POST as $name => $value) {
			if(preg_match('#^translation_([a-z_]+)_([0-9]+)$#i', $name, $match)) {
				$html_element = hikaInput::get()->getRaw($name, '');
				if(!empty($html_element)) {
					$obj = new stdClass();
					$type = $match[1];
					$obj->reference_field = $type;
					$obj->language_id = $match[2];
					$obj->value = $html_element;
					if(!isset($element->translations)){
						$element->translations = array();
					}
					if(!isset($element->translations[(int)$match[2]])){
						$element->translations[(int)$match[2]] = new stdClass();
					}
					$element->translations[$match[2]]->$type = $obj;
				}
			}
		}
	}

	function handleTranslations($table, $id, &$element, $table_prefix = 'hikashop_', $data = null, $auto_fill_prefix = false) {
		$alias_field = $table .'_alias';
		$name_field = $table .'_name';
		$class = hikashop_get('class.'.$table);

		if(!empty($table_prefix))
			$table = $table_prefix . $table;
		else
			$table = 'hikashop_' . $table;

		if(empty($data) || $data === null)
			$transArray = hikaInput::get()->get('translation', array(), 'array');
		else
			$transArray = $data;

		$config = hikashop_config();

		$arrayToSearch = array();
		$conditions = array();
		foreach($transArray as $field => $trans) {
			foreach($trans as $lg => $value) {
				if($config->get('alias_auto_fill', 1) && $auto_fill_prefix && $field == $name_field && !empty($trans) && empty($transArray[$alias_field][$lg])) {
					$this->loadLanguages();
					$languages =& $this->languages;

					$emptyObject = new stdClass();
					$emptyObject->$name_field = $value;
					$lg_code = '';
					foreach($languages as $lang) {
						if($lang->id == $lg)
							$lg_code = $lang->code;
					}
					if(!empty($lg_code) && (empty($element->product_type)  || $element->product_type == 'main')) {
						$class->addAlias($emptyObject, $lg_code);
						if($config->get('sef_remove_id', 0) && (int)$emptyObject->alias > 0)
							$emptyObject->alias = $config->get('alias_prefix', 'p') . $emptyObject->alias;

						$arrayToSearch[] = array(
							'value' => $emptyObject->alias,
							'language_id' => $lg,
							'reference_field' => $alias_field
						);
						$conditions[] = ' language_id = '.(int)$lg.' AND reference_field = '.$this->database->Quote($alias_field).' AND reference_table = '.$this->database->Quote($table).' AND reference_id='.(int)$id;
					}
				}

				$lg = (int)$lg;
				$field = hikashop_secureField($field);
				$arrayToSearch[] = array(
					'value' => $value,
					'language_id' => $lg,
					'reference_field' => $field
				);
				$conditions[] = ' language_id = '.(int)$lg.' AND reference_field = '.$this->database->Quote($field).' AND reference_table = '.$this->database->Quote($table).' AND reference_id='.(int)$id;
			}
		}

		if(empty($data) || $data === null) {
			foreach($_POST as $name => $value){
				if(!preg_match('#^translation_([a-z_]+)_([0-9]+)$#i', $name, $match))
					continue;

				$html_element = hikaInput::get()->getRaw($name, '');

				$lg = (int)$match[2];
				$field = hikashop_secureField($match[1]);
				$value = $html_element;

				if($config->get('alias_auto_fill', 1) && $auto_fill_prefix && $field == $name_field && !empty($value) && empty($_POST['translation_'.$alias_field.'_'.$lg])) {
					$this->loadLanguages();
					$languages =& $this->languages;

					$emptyObject = new stdClass();
					$emptyObject->$name_field = $value;
					$lg_code = '';
					foreach($languages as $lang) {
						if($lang->id == $lg)
							$lg_code = $lang->code;
					}
					if(!empty($lg_code) && (empty($element->product_type)  || $element->product_type == 'main')) {
						$class->addAlias($emptyObject, $lg_code);
						if($config->get('sef_remove_id', 0) && (int)$emptyObject->alias > 0)
							$emptyObject->alias = $config->get('alias_prefix', 'p') . $emptyObject->alias;

						$arrayToSearch[] = array(
							'value' => $emptyObject->alias,
							'language_id' => $lg,
							'reference_field' => $alias_field
						);
						$conditions[] = ' language_id = '.(int)$lg.' AND reference_field = '.$this->database->Quote($alias_field).' AND reference_table = '.$this->database->Quote($table).' AND reference_id='.(int)$id;
					}
				}

				$arrayToSearch[] = array(
					'value' => $value,
					'language_id' => $lg,
					'reference_field' => $field
				);
				$conditions[] = ' language_id = '.(int)$lg.' AND reference_field = '.$this->database->Quote($field).' AND reference_table = '.$this->database->Quote($table).' AND reference_id='.(int)$id;
			}
		}

		if(empty($arrayToSearch))
			return;

		$conf = hikashop_config();
		$default_translation_publish = (int)$conf->get('default_translation_publish', 1);
		$this->isMulti();
		if($this->falang) {
			$trans_table = 'falang_content';

			$query = 'SELECT * FROM '.hikashop_table($trans_table,false).' WHERE ('.implode(') OR (',$conditions).');';
			$this->database->setQuery($query);
			$entries = $this->database->loadObjectList('id');

			$user = JFactory::getUser();
			$userId = $user->get( 'id' );
			$toInsert = array();
			foreach($arrayToSearch as $item) {
				$already = false;
				if(!empty($entries)) {
					foreach($entries as $entry_id => $entry){
						if($item['language_id'] == $entry->language_id && $item['reference_field'] == $entry->reference_field) {
							if(empty($item['value'])) {
								$query = 'DELETE FROM '.hikashop_table($trans_table, false) .
									' WHERE id = ' . (int)$entry_id . ';';
							} else {
								$query = 'UPDATE '.hikashop_table($trans_table, false) .
									' SET value='.$this->database->Quote($item['value']).', modified_by=' . (int)$userId.', modified=NOW()'.
									' WHERE id = ' . (int)$entry_id . ';';
							}
							$this->database->setQuery($query);
							$this->database->execute();
							$already = true;
							break;
						}
					}
				}
				if(!$already) {
					$toInsert[] = $item;
				}
			}

			if(empty($toInsert))
				return;

			$rows = array();
			foreach($toInsert as $item) {
				if(empty($item['value']))
					continue;
				$field = $item['reference_field'];
				$rows[] = (int)$id.','.(int)$item['language_id'].','.$this->database->Quote($table).','.$this->database->Quote($item['value']).','.$this->database->Quote($field).','.$this->database->Quote(md5($element->$field)).','.(int)$default_translation_publish.','.(int)$userId.',\'\',NOW()';
			}
			if(count($rows)) {
				$query = 'INSERT IGNORE INTO '.hikashop_table($trans_table, false).' (reference_id,language_id,reference_table,value,reference_field,original_value,published,modified_by,original_text,modified) VALUES ('.implode('),(',$rows).');';
				$this->database->setQuery($query);
				$this->database->execute();
			}
		} else {
			$this->loadLanguages();
			$languages =& $this->languages;
			jimport('joomla.filesystem.folder');
			$path = hikashop_getLanguagePath(JPATH_ROOT);
			foreach($languages as $lang) {
				$override_file_path = $path . '/overrides/'.$lang->code.'.override.ini';
				$overrides = array();
				if(file_exists($override_file_path)) {
					$overrides = parse_ini_file($override_file_path);
					if($overrides === false) {
						echo '<script>alert(\''.str_replace("'","\'",JText::sprintf('TRANSLATION_OVERRIDE_IS_INVALID', $override_file_path)).'\');</script>';
						continue;
					}
				}
				$done = array();
				foreach($arrayToSearch as $entry) {
					if($lang->id != $entry['language_id'])
						continue;
					$field = $entry['reference_field'];

					$key =	$this->getKey($element->$field);

					if(!empty($done[$key]))
						continue;
					if(empty($key)) {
						if(!empty($entry['value']))
							echo '<script>alert(\''.str_replace("'","\'",JText::sprintf('TRANSLATION_OVERRIDE_COULD_NOT_BE_SAVED', $entry['value'])).'\');</script>';
						continue;
					}
					$overrides[$key] = $entry['value'];

					if(empty($overrides[$key]))
						unset($overrides[$key]);
					else
						$done[$key] = $entry['value'];
				}

				$data = '';
				foreach($overrides as $k => $v) {
					if(empty($k))
						continue;
					$data .= $k.'="'.str_replace(array('"',"\r\n","\r","\n"),array('\"','','',''),$v).'"'."\r\n";
				}
				file_put_contents($override_file_path, $data);
			}

		}
	}

	function saveOverrides() {
		$id = hikaInput::get()->getInt('language_id');
		if(empty($id))
			die('language_id missing in form');
		$lang = $this->loadLanguage($id);

		jimport('joomla.filesystem.folder');
		$path = hikashop_getLanguagePath(JPATH_ROOT);
		$override_file_path = $path . '/overrides/'.$lang->code.'.override.ini';
		$overrides = array();
		if(file_exists($override_file_path)) {
			$overrides = parse_ini_file($override_file_path);
			if($overrides === false) {
				die(JText::sprintf('TRANSLATION_OVERRIDE_IS_INVALID', $override_file_path));
			}
		}

		$originalsArray = hikaInput::get()->get('originals', array(), 'array');
		$translationsArray = hikaInput::get()->get('translations', array(), 'array');
		$done = array();
		foreach($originalsArray as $k => $orig) {
			if(empty($translationsArray[$k]))
				continue;

			$key =	$this->getKey($orig);
			if(empty($key)) {
				die(JText::sprintf('TRANSLATION_OVERRIDE_COULD_NOT_BE_SAVED', $translationsArray[$k]));
			}
			if(!empty($done[$key]))
				continue;

			$overrides[$key] = $translationsArray[$k];

			if(empty($overrides[$key]))
				unset($overrides[$key]);
			else
				$done[$key] = $translationsArray[$k];
		}
		$data = '';
		foreach($overrides as $k => $v) {
			if(empty($k))
				continue;
			$data .= $k.'="'.str_replace(array('"',"\r\n","\r","\n"),array('\"','','',''),$v).'"'."\r\n";
		}
		file_put_contents($override_file_path, $data);
	}

	function checkTranslations(&$element, $columns) {
		if($this->isMulti()){
			if(!$this->falang){
				$toUpdate = array();
				foreach($columns as $column) {
					if(
						isset($element->$column) && isset($element->old->$column) &&
						!is_array($element->$column) && !is_object($element->$column) &&
						!is_array($element->old->$column) && !is_object($element->old->$column) &&
						$element->$column !== $element->old->$column
					)
						$toUpdate[$element->old->$column] = $element->$column;
				}
				if(count($toUpdate)) {
					$this->loadLanguages();
					$languages =& $this->languages;
					jimport('joomla.filesystem.folder');
					$path = hikashop_getLanguagePath(JPATH_ROOT);
					foreach($languages as $lang) {
						$override_file_path = $path . '/overrides/'.$lang->code.'.override.ini';
						$overrides = array();
						if(file_exists($override_file_path)) {
							$overrides = parse_ini_file($override_file_path);
							if($overrides === false) {
								echo '<script>alert(\''.str_replace("'","\'",JText::sprintf('TRANSLATION_OVERRIDE_IS_INVALID', $override_file_path)).'\');</script>';
								continue;
							}
						}
						foreach($toUpdate as $old => $new) {
							$oldKey = $this->getKey($old);
							if(isset($overrides[$oldKey])) {
								$newKey = $this->getKey($new);
								if(empty($newKey))
									continue;
								$tmp = $overrides[$oldKey];
								unset($overrides[$oldKey]);
								$overrides[$newKey] = $tmp;
							}
						}

						$data = '';
						foreach($overrides as $k => $v) {
							if(empty($k))
								continue;
							$data .= $k.'="'.str_replace(array('"',"\r\n","\r","\n"),array('\"','','',''),$v).'"'."\r\n";
						}
						file_put_contents($override_file_path, $data);
					}
				}
			}
		}
	}

	function getKey($orig) {
		$key = preg_replace('#[^A-Z_0-9]#','',strtoupper((string)$orig));
		$config = hikashop_config();
		if((empty($key) || $config->get('non_latin_translation_keys', 0)) && !empty($orig)) {
			$key = 'T'.strtoupper(sha1($orig));
		}elseif(is_numeric($key)) {
			$key = 'T'.$key;
		}
		return $key;
	}

	function deleteTranslations($table,$ids){
		if($this->isMulti()){
			if(!is_array($ids))$ids = array($ids);

			if($this->falang){
				$trans_table = 'falang_content';
				$query = 'DELETE FROM '.hikashop_table($trans_table,false).' WHERE reference_table = '.$this->database->Quote('hikashop_'.$table).' AND reference_id IN ('.implode(',',$ids).')';
				$this->database->setQuery($query);
				$this->database->execute();
			} else {
				$query = 'SELECT * FROM #__hikashop_'.$table.' WHERE '.$table.'_id IN ('.implode(',',$ids).')';
				$this->database->setQuery($query);
				$elements = $this->database->loadObjectList($table.'_id');
				$this->loadLanguages();
				$languages =& $this->languages;
				jimport('joomla.filesystem.folder');
				$path = hikashop_getLanguagePath(JPATH_ROOT);
				foreach($languages as $lang) {
					$override_file_path = $path . '/overrides/'.$lang->code.'.override.ini';
					$overrides = array();
					if(file_exists($override_file_path)) {
						$overrides = parse_ini_file($override_file_path);
						if($overrides === false) {
							echo '<script>alert(\''.JText::sprintf('TRANSLATION_OVERRIDE_IS_INVALID', str_replace("'","\'",$override_file_path)).'\');</script>';
							continue;
						}
					}
					foreach($elements as $element) {
						foreach(get_object_vars($element) as $field => $val){
							if(is_array($val) || is_object($val))
								continue;
							$key =	$this->getKey($val);
							unset($overrides[$key]);
						}
					}
					$data = '';
					foreach($overrides as $k => $v) {
						if(empty($k))
							continue;
						$data .= $k.'="'.str_replace(array('"',"\r\n","\r","\n"),array('\"','','',''),$v).'"'."\r\n";
					}
					file_put_contents($override_file_path, $data);
				}
			}
		}
	}

	function getStatusTrans(){
		$config = JFactory::getConfig();
		if(HIKASHOP_J30){
			$locale = $config->get('language');
		} else {
			$locale = $config->getValue('config.language');
		}
		$user = JFactory::getUser();
		$current_locale = $user->getParam('language');
		if(empty($current_locale)){
			$current_locale=$locale;
		}
		$database = JFactory::getDBO();

		$query = 'SELECT a.category_name,a.category_id FROM '.hikashop_table('category'). ' AS a WHERE a.category_type=\'status\'';
		$database->setQuery($query);
		if(class_exists('JFDatabase')){
			$statuses = $database->loadObjectList('category_id',false);
		}else{
			$statuses = $database->loadObjectList('category_id');
		}
		if($this->isMulti(true, false) && $this->falang){
			$lgid = $this->getId($current_locale);
			$trans_table = 'falang_content';
			$query = 'SELECT value,reference_id FROM '.hikashop_table($trans_table,false).' WHERE reference_table=\'hikashop_category\' AND reference_field=\'category_name\' AND published=1 AND language_id='.$lgid.' AND reference_id IN('.implode(',',array_keys($statuses)).')';
			$database->setQuery($query);
			$trans = $database->loadObjectList('reference_id');
			foreach($statuses as $k => $stat){
				if(isset($trans[$k])){
					$statuses[$k]->status = $trans[$k]->value;
				}else{
					$val = str_replace(' ','_',strtoupper($statuses[$k]->category_name));
					$new = JText::_($val);
					if($val!=$new){
						$statuses[$k]->status=$new;
					}else{
						$statuses[$k]->status=$statuses[$k]->category_name;
					}
				}
			}
		}else{
			foreach($statuses as $k => $stat){
				$val = str_replace(' ','_',strtoupper($statuses[$k]->category_name));
				$new = JText::_($val);
				if($val!=$new){
					$statuses[$k]->status=$new;
				}else{
					$statuses[$k]->status=$statuses[$k]->category_name;
				}
			}
		}
		$cleaned_statuses = array();
		foreach($statuses as $status){
			$cleaned_statuses[$status->category_name]=$status->status;
		}
		return $cleaned_statuses;
	}


	function searchTranslation($term, $table, $fields, $allLanguages = false, $exact = false) {
		$ids = array();
		if(!$this->isMulti(true))
			return $ids;

		$db = JFactory::getDBO();

		if($this->falang) {
			$search = array();
			$flds = array();
			foreach($fields as $field) {
				if($exact)
					$search[] = 'value = ' . $db->Quote($term);
				else
					$search[] = 'value LIKE \'%' . hikashop_getEscaped($term, true) . '%\'';
				$flds[] = 'reference_field = ' . $db->Quote($field);
			}

			$query = 'SELECT DISTINCT reference_id FROM ' . hikashop_table('falang_content', false) .
				' WHERE reference_table = ' . $db->Quote('hikashop_' . $table) .
				' AND (' . implode(' OR ', $search) . ')' .
				' AND (' . implode(' OR ', $flds) . ')' .
				' AND published = 1';
			$db->setQuery($query);
			$ids = $db->loadColumn();
		} else {
			jimport('joomla.filesystem.folder');
			$path = hikashop_getLanguagePath(JPATH_ROOT);

			$languages = array();
			if($allLanguages) {
				$langs = $this->loadLanguages(true);
				foreach($langs as $l) {
					$languages[] = $l->code;
				}
			} else {
				$lg = JFactory::getLanguage();
				$languages[] = $lg->getTag();
			}

			$matchingKeys = array();
			foreach($languages as $language_code) {
				$language_code = filter_var($language_code, FILTER_SANITIZE_STRING); 
				if(empty($language_code) || strpos($language_code, '.') !== false || strpos($language_code, '/') !== false) continue;

				$override_file_path = $path . '/overrides/' . $language_code . '.override.ini';

				if(file_exists($override_file_path)) {
					$overrides = parse_ini_file($override_file_path);
					if(!empty($overrides)) {
						foreach($overrides as $k => $v) {
							$match = false;
							if($exact) {
								if(strcasecmp($v, $term) === 0) $match = true;
							} else {
								if(stripos($v, $term) !== false) $match = true;
							}

							if($match) {
								$matchingKeys[] = $db->Quote($k);
							}
						}
					}
				}
			}

			if(!empty($matchingKeys)) {
				$matchingKeys = array_unique($matchingKeys);
				$keyConditions = array();
				foreach($fields as $column) {
					$keyConditions[] = $column . ' IN (' . implode(',', $matchingKeys) . ')';
				}
				$col_id = $table . '_id';
				$filters = array($table.'_published=1');
				hikashop_addACLFilters($filters, $table.'_access', $table, 2, false, 0);
				$keyQuery = 'SELECT ' . $col_id . ' FROM ' . hikashop_table($table) .
					' WHERE (' . implode(' OR ', $keyConditions) . ') AND '.implode(' AND ', $filters);
				$db->setQuery($keyQuery);
				$ids = $db->loadColumn();
			}
		}

		return $ids;
	}


	function getAllLanguages()
	{
		if(defined('HIKASHOP_WORDPRESS'))
			return $this->_getAllLanguagesWP();

		jimport('joomla.filesystem.folder');
		$path = hikashop_getLanguagePath(JPATH_ROOT);
		$dirs = JFolder::folders( $path );
		$languages = array();
		$status_hika = HIKASHOP_IMAGES.'icons/icon-14-hikablue.png';
		$edit_image = HIKASHOP_IMAGES.'edit.png';
		$status_unavailable = HIKASHOP_IMAGES.'unavailable.png';
		$edit_add = HIKASHOP_IMAGES.'add.png';
		$tooltip_add = JText::_('ADD_HIKA_LANG');
		$tooltip_hika = JText::_('AVAILABLE_HIKA_LANG');
		$tooltip_unavailable = JText::_('IMPOSSIBLE_HIKA_LANG');
		$tooltip_edit = JText::_('EDIT_HIKA_LANG');
		$popupHelper = hikashop_get('helper.popup');

		$edit_add = 'fa fa-plus';
		$edit_image = 'fa fa-pen fa-pencil';
		$status_unavailable = 'fa fa-times';

		foreach ($dirs as $dir){
			$xmlFiles = JFolder::files( $path.DS.$dir, '^([-_A-Za-z]*)\.xml$' );
			$xmlFile = array_pop($xmlFiles);
			if($xmlFile=='install.xml') $xmlFile = array_pop($xmlFiles);
			if(empty($xmlFile)) continue;
			$data = JInstaller::parseXMLInstallFile($path.DS.$dir.DS.$xmlFile);
			$oneLanguage = new stdClass();
			$oneLanguage->language 	= $dir;
			$oneLanguage->name = $data['name'];
			$languageFiles = JFolder::files( $path.DS.$dir, '^(.*)\.com_hikashop\.ini$' );
			$languageFile = reset($languageFiles);

			if(!empty($languageFile)){
				$oneLanguage->edit = '<a href="index.php?option=com_hikashop&amp;ctrl=config&amp;task=language&amp;code='.$oneLanguage->language.'"><span id="image'.$oneLanguage->language.'" alt="'.JText::_('EDIT_LANGUAGE_FILE').'" style="color:#555; font-size:1.2em;"><i class="'. $edit_image.'"></i></span></a>';
				$oneLanguage->status = '<img id="image'.$oneLanguage->language.'" src="'. $status_hika.'" alt="'.$tooltip_hika.'"/>';

				$oneLanguage->edit_tooltip = $tooltip_edit;
				$oneLanguage->status_tooltip = $tooltip_hika;
			}else{
				$oneLanguage->edit = '<a href="index.php?option=com_hikashop&amp;ctrl=config&amp;task=language&amp;code='.$oneLanguage->language.'"><span id="image'.$oneLanguage->language.'"alt="'.$tooltip_add.'" style="color:#555; font-size:1.2em;"><i class="'. $edit_add.'"></i></span></a>';
				$oneLanguage->status = '<span id="image'.$oneLanguage->language.'" alt="'.JText::_('ADD_HIKA_LANG').'" style="color:#942a25; font-size:1.2em;"><i class="'. $status_unavailable.'"></i></span>';

				$oneLanguage->edit_tooltip = $tooltip_add;
				$oneLanguage->status_tooltip = $tooltip_unavailable;
			}
			$languages[] = $oneLanguage;
		}
		return $languages;
	}

	function _getAllLanguagesWP() {
		$path = hikashop_getLanguagePath(JPATH_ROOT);
		$languages = array();

		$status_hika = HIKASHOP_IMAGES.'icons/icon-14-hikablue.png';
		$tooltip_hika = JText::_('AVAILABLE_HIKA_LANG');
		$tooltip_edit = JText::_('EDIT_HIKA_LANG');
		$edit_image = 'fa fa-pen fa-pencil';

		$files = JFolder::files($path, '^[-a-zA-Z]+\.com_hikashop\.ini$');
		if(empty($files))
			return $languages;

		static $langNames = array(
			'af-ZA' => 'Afrikaans', 'ar-AA' => 'Arabic', 'ar-AR' => 'Arabic', 'ar-DZ' => 'Arabic (Algeria)',
			'bg-BG' => 'Bulgarian', 'bs-BA' => 'Bosnian', 'ca-ES' => 'Catalan',
			'ch-DE' => 'Swiss German', 'cs-CZ' => 'Czech', 'da-DK' => 'Danish',
			'de-DE' => 'German', 'el-GR' => 'Greek', 'en-AU' => 'English (Australia)',
			'en-GB' => 'English (United Kingdom)', 'en-US' => 'English (United States)',
			'es-CL' => 'Spanish (Chile)', 'es-ES' => 'Spanish', 'et-EE' => 'Estonian',
			'fa-IR' => 'Persian', 'fi-FI' => 'Finnish', 'fr-FR' => 'French',
			'he-IL' => 'Hebrew', 'hi-IN' => 'Hindi', 'hr-HR' => 'Croatian',
			'hu-HU' => 'Hungarian', 'id-ID' => 'Indonesian', 'it-IT' => 'Italian',
			'ja-JP' => 'Japanese', 'km-KH' => 'Khmer', 'ko-KR' => 'Korean',
			'lt-LT' => 'Lithuanian', 'lv-LV' => 'Latvian', 'mk-MK' => 'Macedonian',
			'mn-MN' => 'Mongolian', 'ms-MY' => 'Malay', 'nb-NO' => 'Norwegian (Bokmal)',
			'nl-NL' => 'Dutch', 'pl-PL' => 'Polish', 'pt-BR' => 'Portuguese (Brazil)',
			'pt-PT' => 'Portuguese', 'ro-RO' => 'Romanian', 'ru-RU' => 'Russian',
			'sk-SK' => 'Slovak', 'sl-SI' => 'Slovenian', 'sq-AL' => 'Albanian',
			'sr-RS' => 'Serbian', 'sv-SE' => 'Swedish', 'sw-KE' => 'Swahili',
			'ta-IN' => 'Tamil', 'th-TH' => 'Thai', 'tr-TR' => 'Turkish',
			'uk-UA' => 'Ukrainian', 'vi-VN' => 'Vietnamese', 'zh-CN' => 'Chinese (Simplified)',
			'zh-TW' => 'Chinese (Traditional)',
		);

		$seen = array();
		sort($files);
		foreach($files as $file) {
			$code = substr($file, 0, strpos($file, '.'));
			if(empty($code) || isset($seen[$code]))
				continue;
			$seen[$code] = true;

			$oneLanguage = new stdClass();
			$oneLanguage->language = $code;
			$oneLanguage->name = isset($langNames[$code]) ? $langNames[$code] : $code;

			$editUrl = admin_url('admin.php?page=hikashop-config&ctrl=config&task=language&code=' . urlencode($code));
			$oneLanguage->edit = '<a href="' . esc_url($editUrl) . '"><span style="color:#555; font-size:1.2em;"><i class="' . $edit_image . '"></i></span></a>';
			$oneLanguage->status = '<img src="' . $status_hika . '" alt="' . $tooltip_hika . '"/>';
			$oneLanguage->edit_tooltip = $tooltip_edit;
			$oneLanguage->status_tooltip = $tooltip_hika;

			$languages[] = $oneLanguage;
		}

		return $languages;
	}

}
