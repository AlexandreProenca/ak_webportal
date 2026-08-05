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
if(!defined('DS'))
	define('DS',DIRECTORY_SEPARATOR);
if(!defined('HIKASHOP_WORDPRESS'))
	include_once(JPATH_ROOT.'/administrator/components/com_hikashop/pluginCompat.php');
if(!class_exists('hikashopJoomlaPlugin')) return;
class plgSystemHikashopmassaction extends hikashopJoomlaPlugin {

	function __construct(&$subject, $config){
		parent::__construct($subject, $config);
		if(!isset($this->params)){

			if(HIKASHOP_J50 && !class_exists('JPluginHelper'))
				class_alias('Joomla\CMS\Plugin\PluginHelper', 'JPluginHelper');
			$plugin = JPluginHelper::getPlugin('system', 'hikashopmassaction');

			if(HIKASHOP_J50 && !class_exists('JRegistry'))
				class_alias('Joomla\Registry\Registry', 'JRegistry');
			$this->params = new JRegistry(@$plugin->params);
		}
	}

	function onMassactionTableTriggersLoad(&$table, &$triggers, &$triggers_html, &$loadedData) {
		$massactionClass = hikashop_get('class.massaction');
		$type = 'trigger';
		if(empty($loadedData->massaction_triggers)){
			$loadedData->massaction_triggers = array();
		}

		$triggers['onHikashopCronTriggerMinutes']=JText::_('EVERY_MINUTES');
		$triggers['onHikashopCronTriggerHours']=JText::_('EVERY_HOURS');
		$triggers['onHikashopCronTriggerDays']=JText::_('EVERY_DAYS');
		$triggers['onHikashopCronTriggerWeeks']=JText::_('EVERY_WEEKS');
		$triggers['onHikashopCronTriggerMonths']=JText::_('EVERY_MONTHS');
		$triggers['onHikashopCronTriggerYears']=JText::_('EVERY_YEARS');

		$triggers['http']=JText::_('ON_HTTP_REQUEST');
		$loadedData->massaction_triggers['__num__'] = new stdClass();
		$loadedData->massaction_triggers['__num__']->name = 'http';
		$loadedData->massaction_triggers['__num__']->type = $table->table;
		$loadedData->massaction_triggers['__num__']->data = array();
		$loadedData->massaction_triggers['__num__']->data['endpoint'] = '/api/'.$table->table.'/{'.$table->table.'_id}';
		$loadedData->massaction_triggers['__num__']->data['access'] = 'api_key';
		$loadedData->massaction_triggers['__num__']->data['load'] = '';
		$loadedData->massaction_triggers['__num__']->data['create'] = '';
		$loadedData->massaction_triggers['__num__']->data['update'] = '';
		$loadedData->massaction_triggers['__num__']->html = '';

		foreach($loadedData->massaction_triggers as $key => &$value) {
			if($value->name != 'http' || ($table->table != $loadedData->massaction_table && is_int($key)))
				continue;
			$api = $this->params->get('api');
			$apikey = $this->params->get('api_key');

			$value->type = $table->table;
			if(!isset($value->data['endpoint'])) $value->data['endpoint'] = '/api/'.$table->table.'/{'.$table->table.'_id}';
			if(!isset($value->data['access'])) $value->data['access'] = 'api_key';
			if(!isset($value->data['load'])) $value->data['load'] = '';
			if(!isset($value->data['create'])) $value->data['create'] = '';
			if(!isset($value->data['update'])) $value->data['update'] = '';
			$output = '<div id="'.$table->table.'trigger'.$key.'http">';
			if(empty($api)) {
				$output .= '<b>'.JText::_('HIKASHOP_API_NOT_ACTIVATED').'</b>';
			} else {
				$checked = ($value->data['access'] == 'api_key') ? 'checked="checked"' : '';
				$checked2 = ($value->data['access'] == 'public') ? 'checked="checked"' : '';
				$output .= '<b>'.JText::_('HIKASHOP_ENDPOINT_ACCESS').'</b> : 
				<input type="radio" id="trigger'.$table->table.''.$key.'httpaccessapi_key" name="trigger['.$table->table.']['.$key.'][http][access]" '.$checked.' value="api_key"> 
				<label for="trigger'.$table->table.''.$key.'httpaccessapi_key">'.JText::_('WITH_API_KEY').'</label>&nbsp;
				<input type="radio" id="trigger'.$table->table.''.$key.'httpaccesspublic" name="trigger['.$table->table.']['.$key.'][http][access]" '.$checked2.' value="public"> 
				<label for="trigger'.$table->table.''.$key.'httpaccesspublic">'.JText::_('HIKASHOP_PUBLIC').'</label><br/>';
				if(empty($apikey)) {
					$output .= '<b>'.JText::_('HIKASHOP_API_KEY_NOT_SET').'</b>';
				} else {
					$output .= '<em>'.JText::_('HIKASHOP_ENDPOINT_ACCESS_DESC').'</em>';
				}
				$output .= '<br/><br/><b>'.JText::_('HIKASHOP_ENDPOINT').'</b> : <input type="text" name="trigger['.$table->table.']['.$key.'][http][endpoint]" value="'.$value->data['endpoint'].'" style="width:100%"/>';
				$output .= '<br/><em>'.JText::_('HIKASHOP_ENDPOINT_EXPLANATION').'</em>';
				$output .= '<br/><br/><b>'.JText::_('HIKASHOP_ENDPOINT_ACTIONS').'</b> : ';
				$actions = array('load' => JText::_('HIKASHOP_LOAD_DATA'), 'create' => JText::_('HIKASHOP_POST_DATA_CREATE'), 'update' => JText::_('HIKASHOP_POST_DATA_UPDATE'));
				foreach($actions as $action => $label) {
					$checked = (isset($value->data[$action]) && $value->data[$action]) ? 'checked="checked"' : '';
					$output .= '<input type="checkbox" id="trigger'.$table->table.''.$key.'http'.$action.'" name="trigger['.$table->table.']['.$key.'][http]['.$action.']" '.$checked.' value="1"> 
					<label for="trigger'.$table->table.''.$key.'http'.$action.'">'.$label.'</label>&nbsp;';
				}
				$output .= '<br/><em>'.JText::_('HIKASHOP_ENDPOINT_ACTIONS_EXPLANATION').'</em>';

			}
			$output .= '</div>';
			$triggers_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
		}


		$triggers['mcp']=JText::_('ON_MCP_REQUEST');
		$loadedData->massaction_triggers['__num__'] = new stdClass();
		$loadedData->massaction_triggers['__num__']->name = 'mcp';
		$loadedData->massaction_triggers['__num__']->type = $table->table;
		$loadedData->massaction_triggers['__num__']->data = array();
		$loadedData->massaction_triggers['__num__']->data['args'] = array();
		$loadedData->massaction_triggers['__num__']->html = '';


		foreach($loadedData->massaction_triggers as $key => &$value) {
			if($value->name != 'mcp' || ($table->table != $loadedData->massaction_table && is_int($key)))
				continue;

			$value->type = $table->table;
			$mcp_enabled = $this->params->get('mcp_enable') || $this->params->get('mcp_enable') === null;
			$api_key = $this->params->get('api_key');

			$output = '<div id="'.$table->table.'trigger'.$key.'mcp">';
			if(empty($mcp_enabled)) {
				$output .= '<b>'.JText::_('MCP_SERVER_NOT_ENABLED').'</b>';
			} else if(empty($api_key)) {
				$output .= '<b>'.JText::_('API_KEY_NOT_SET').'</b>';
			} else {
				$output .= '<b>'.JText::_('DYNAMIC_FILTER_MAPPING').'</b><br/>';
				$output .= '<em>'.JText::_('DYNAMIC_FILTER_MAPPING_DESC').'</em><br/><br/>';

				$db = JFactory::getDBO();
				$columns = $db->getTableColumns('#__hikashop_'.$table->table);
				$defaultColumn = $table->table . '_id';

				$jsColumnOptions = '';
				if(!empty($columns)) {
					foreach($columns as $colName => $colType) {
						$selected = ($colName == $defaultColumn) ? 'selected="selected"' : '';
						$jsColumnOptions .= '<option value="'.$colName.'">'.$colName.'</option>';
					}
				}

				$output .= '<table class="table table-striped table-condensed">';
				$output .= '<thead><tr><th>'.JText::_('TOOL_ARGUMENT_NAME').'</th><th>'.JText::_('MASS_ACTION_FILTER').'</th><th></th></tr></thead>';
				$output .= '<tbody id="mcp_args_tbody_'.$table->table.'_'.$key.'">';

				$i = 0;
				if(!empty($value->data['args'])){ // Use mcpValue data
					foreach($value->data['args'] as $arg){
						$arg = (array)$arg;
						$argName = isset($arg['name']) ? $arg['name'] : '';
						$filterName = isset($arg['filter']) ? $arg['filter'] : '';

						$output .= '<tr id="mcp_arg_row_'.$table->table.'_'.$key.'_'.$i.'">';
						$output .= '<td><input type="text" name="trigger['.$table->table.']['.$key.'][mcp][args]['.$i.'][name]" value="'.htmlspecialchars($argName).'" /></td>';

						$output .= '<td><select class="input-large form-select custom-select" name="trigger['.$table->table.']['.$key.'][mcp][args]['.$i.'][filter]">';
						foreach($columns as $colName => $colType) {
							$selected = ($colName == $filterName) ? 'selected="selected"' : '';
							$output .= '<option value="'.$colName.'" '.$selected.'>'.$colName.'</option>';
						}
						$output .= '</select></td>';

						$output .= '<td><a class="btn btn-danger btn-small" href="javascript:void(0);" onclick="document.getElementById(\'mcp_arg_row_'.$key.'_'.$i.'\').parentNode.removeChild(document.getElementById(\'mcp_arg_row_'.$key.'_'.$i.'\')); return false;"><i class="icon-trash"></i></a></td>';
						$output .= '</tr>';
						$i++;
					}
				}
				$output .= '</tbody>';
				$output .= '<tfoot><tr><td colspan="3"><a class="btn btn-success btn-small" href="javascript:void(0);" onclick="hikashop_mcp_add_arg('.$key.', \''.$table->table.'\'); return false;"><i class="icon-plus"></i> '.JText::_('ADD_ARGUMENT').'</a></td></tr></tfoot>';
				$output .= '</table>';

				$jsOptions = $jsColumnOptions; 
				$jsOptions = str_replace(array("\n", "\r", "'"), array("", "", "\\'"), $jsOptions);

				$output .= '<script type="text/javascript">
					if(typeof hikashop_mcp_options === "undefined") {
						var hikashop_mcp_options = {};
					}
					hikashop_mcp_options["'.$table->table.'_'.$key.'"] = \''.$jsOptions.'\';

					if(typeof hikashop_mcp_add_arg === "undefined") {
						function hikashop_mcp_add_arg(key, table) {
							var tbody = document.getElementById("mcp_args_tbody_" + table + "_" + key);
							var count = tbody.getElementsByTagName("tr").length;
							var row = document.createElement("tr");
							var options = hikashop_mcp_options[table + "_" + key];
							if(!options) options = "";

							row.id = "mcp_arg_row_" + table + "_" + key + "_" + count;
							row.innerHTML = \'<td><input type="text" name="trigger[\' + table + \'][\' + key + \'][mcp][args][\' + count + \'][name]" value="" /></td>\' +
										  \'<td><select class="input-large form-select custom-select" name="trigger[\' + table + \'][\' + key + \'][mcp][args][\' + count + \'][filter]">\'+options+\'</select></td>\' +
										  \'<td><a class="btn btn-danger btn-small" href="javascript:void(0);" onclick="document.getElementById(\\\'mcp_arg_row_\' + table + \'_\' + key + \'_\' + count + \'\\\').parentNode.removeChild(document.getElementById(\\\'mcp_arg_row_\' + table + \'_\' + key + \'_\' + count + \'\\\')); return false;"><i class="icon-trash"></i></a></td>\';
							tbody.appendChild(row);
						}
					}
				</script>';
			}
			$output .= '</div>';

			$triggers_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);

		}
	}

	public function afterInitialise() {
		return $this->onAfterInitialise();
	}

	private function _loadHikaShop() {
		if(!defined('DS'))
			define('DS', DIRECTORY_SEPARATOR);
		if(!include_once(rtrim(JPATH_ADMINISTRATOR,DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php')) return true;
	}

	public function onAfterInitialise() {
		if(isset($_GET['debug_mcp'])) die('MCP Plugin Triggered');
		if(empty($_SERVER['PATH_INFO'])) {
			return;
		}

		if(HIKASHOP_J50 && !class_exists('JFactory'))
			class_alias('Joomla\CMS\Factory', 'JFactory');

		$app = JFactory::getApplication();
		$site = false;
		if(version_compare(JVERSION,'4.0','>=') && $app->isClient('site'))
			$site = true;
		if(version_compare(JVERSION,'4.0','<') && $app->isSite())
			$site = true;
		if(!$site)
			return;

		$mcp_enabled = $this->params->get('mcp_enable');
		if($mcp_enabled || $mcp_enabled === null) {
			$mcp_slug = trim($this->params->get('mcp_slug', 'hikashop-mcp'), '/');
			$path = trim($_SERVER['PATH_INFO'], '/');
			$isMcpRequest = substr($path, -strlen($mcp_slug)) === $mcp_slug;

			if($isMcpRequest) {
				if(file_exists(__DIR__ . '/vendor/autoload.php')) require_once __DIR__ . '/vendor/autoload.php';
				if(file_exists(__DIR__ . '/HikashopMcpTransport.php')) require_once __DIR__ . '/HikashopMcpTransport.php';

				$apikey = $this->params->get('api_key');
				$headers = function_exists('getallheaders') ? getallheaders() : array();
				if(empty($headers['X-API-KEY']) && !empty($_SERVER['HTTP_X_API_KEY'])) $headers['X-API-KEY'] = $_SERVER['HTTP_X_API_KEY'];

				$requestKey = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : ($_GET['key'] ?? '');

				if(!empty($apikey) && $requestKey !== $apikey) {
					header('HTTP/1.0 403 Forbidden');
					die('Invalid API Key');
				}

				$this->_loadHikaShop();

				$serverBuilder = \Mcp\Server::builder()
					->setServerInfo('HikaShop MassAction', '1.0');

				$cache = JFactory::getCache('plg_system_hikashopmassaction');
				$cache->setCaching(1);
				$massactionsFromDB = $cache->get(array($this,'_loadMassactions'), array());

				if(!empty($massactionsFromDB['mcp'])) {
					foreach($massactionsFromDB['mcp'] as $massaction) {
						$mcpTrigger = null;
						if(empty($massaction->massaction_triggers)) continue;

						foreach($massaction->massaction_triggers as $trigger) {
							if($trigger->name == 'mcp') {
								$mcpTrigger = $trigger;
								break;
							}
						}
						if(!$mcpTrigger) continue;

						$toolName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $massaction->massaction_name);
						$toolDesc = !empty($massaction->massaction_description) ? $massaction->massaction_description : "Execute " . $massaction->massaction_name;

						$properties = array();
						if(!empty($mcpTrigger->data['args'])) {
							foreach($mcpTrigger->data['args'] as $arg) {
								if(empty($arg['name'])) continue;
								$properties[$arg['name']] = array('type' => 'string');
							}
						}
						$inputSchema = array(
							'type' => 'object',
							'properties' => array(
								'params' => array(
									'type' => 'object',
									'properties' => $properties,
									'description' => 'Parameters for the action'
								)
							)
						);

						$serverBuilder->addTool(
							function($params) use ($massaction, $mcpTrigger) {
								return $this->_executeMcpAction($massaction, $mcpTrigger, $params);
							},
							$toolName,
							$toolDesc,
							null,
							$inputSchema
						);
					}
				}

				try {
					$transport = new HikashopMcpTransport();
					$server = $serverBuilder->build();
					$server->run($transport);
				} catch (\Throwable $e) {
					if(function_exists('hikashop_writeToLog')) {
						hikashop_writeToLog($e->getMessage(), 'MCP Server Error');
					}
					header('HTTP/1.0 500 Internal Server Error');
					echo "Server Error: ".$e->getMessage();
				}
				exit;
			}
		}




		$api = $this->params->get('api');
		if(empty($api)) {
			return;
		}

		$cache = JFactory::getCache('plg_system_hikashopmassaction');
		$cache->setCaching( 1 );
		$massactionsFromDB = $cache->get(array($this,'_loadMassactions'), array());

		if(empty($massactionsFromDB) || !is_array($massactionsFromDB) || empty($massactionsFromDB['http'])) {
			return;
		}
		foreach($massactionsFromDB['http'] as $massaction) {
			if(empty($massaction->massaction_triggers) || !is_array($massaction->massaction_triggers)) {
				continue;
			}
			foreach($massaction->massaction_triggers as $trigger) {
				if(empty($trigger->data['endpoint'])) {
					continue;
				}

				header('Content-Type: application/json');

				$endpoint = $trigger->data['endpoint'];

				$data = file_get_contents('php://input');

				preg_match_all('#\{([-_a-z0-9]+)\}#iU', $endpoint, $matches, PREG_OFFSET_CAPTURE);
				$tags = array();

				if(!empty($matches) && !empty($matches[1])) {
					foreach($matches[1] as $tag) {
						$tags[] = $tag[0];
						$endpoint = str_replace('{'.$tag[0].'}', '([-_a-zA-Z0-9]+)', $endpoint);
					}
				}
				if(preg_match('#^'.$endpoint.'$#', $_SERVER['PATH_INFO'], $matches) && count($matches) == count($tags)+1) {
					$this->_loadHikaShop();

					if($trigger->data['access'] == 'api_key') {
						$headers = getallheaders();
						$apikey = $this->params->get('api_key');

						if(empty($apikey)) {
							$this->_outputErrors(JText::_('HIKASHOP_API_KEY_NOT_SET'), $headers, 500, 'Internal Server Error');
						}
						if(empty($headers['X-API-KEY']) || $headers['X-API-KEY'] != $apikey) {
							$this->_outputErrors(JText::_('HIKASHOP_ENDPOINT_ACCESS_DENIED'), $headers, 403, 'Forbidden');
						}
					}


					$database = JFactory::getDBO();
					if(empty($data)) {
						if(empty($trigger->data['load'])) {
							$this->_outputErrors(JText::_('HIKASHOP_LOAD_OF_ELEMENTS_NOT_ALLOWED'));
						}
						$filters = array();
						if(!empty($matches) && count($matches) > 1 && !empty($matches[1])) {
							foreach($matches as $k => $match) {
								if($k == 0) {
									continue;
								}
								$filters[] = $tags[$k-1].' = '.$database->Quote($match);
							}
						}

						$limit = 50;
						$start = 0;
						if(!empty($_GET['limit']) && is_numeric($_GET['limit']) && (int)$_GET['limit'] > 0 && (int)$_GET['limit'] <= 500) {
							$limit = (int)$_GET['limit'];
						}
						if(!empty($_GET['start']) && is_numeric($_GET['start'])) {
							$start = (int)$_GET['start'];
						}

						$order = '';
						$direction = 'ASC';
						if(!empty($_GET['order'])) {
							$sanitized = preg_replace('#[^a-z0-9_]#i', '', $_GET['order']);
							$allowedTable = preg_replace('#[^a-z0-9_]#i', '', $massaction->massaction_table);
							$db2 = JFactory::getDBO();
							$db2->setQuery('SHOW COLUMNS FROM #__hikashop_'.$allowedTable);
							$columns = $db2->loadColumn();
							if(in_array($sanitized, $columns)) {
								$order = $sanitized;
							}
						}
						if(!empty($_GET['direction']) && in_array(strtoupper($_GET['direction']), array('ASC', 'DESC'))) {
							$direction = strtoupper($_GET['direction']);
						}
						if(count($filters) > 0) {
							$where = ' WHERE '.implode(' AND ', $filters);
						}
						$query = 'SELECT * FROM #__hikashop_'.$allowedTable.$where.
							(!empty($order) ? ' ORDER BY '.$order.' '.$direction : '');
						$database->setQuery($query, $start, $limit);
						$elements = $database->loadObjectList();
					} else {
						$elements = array();
						$data = json_decode($data);
						if(empty($data)) {
							$this->_outputErrors(JText::_('HIKASHOP_INVALID_JSON_IN_POST_DATA'));
						}
						$class = hikashop_get('class.'.$massaction->massaction_table);
						$pkey = $class->pkeys[0];

						$action = 'create';
						if(!empty($data->$pkey)) {
							$action = 'update';
						}
						if(empty($trigger->data[$action])) {
							$this->_outputErrors(JText::_('HIKASHOP_'.strtoupper($action).'_OF_ELEMENT_NOT_ALLOWED'), $data);
						}

						$app = JFactory::getApplication();
						$old_messages = $app->getMessageQueue();

						$result = $class->save($data); // update the element in the database
						if($result) {
							$elements[] = $data;
						} else {
							$new_messages = $app->getMessageQueue();
							$messages = array();
							if(!empty($new_messages)) {
								foreach($new_messages as $new_message) {
									if(empty($old_messages) || !in_array($new_message, $old_messages)) {
										$messages[] = $new_message['message'];
									}
								}
							}
							if(!empty($class->messages)) {
								$messages = array_merge($messages, $class->messages);
							}
							if(!empty($class->message)) {
								$messages[] = $class->message;
							}
							if(empty($messages)) {
								$messages[] = JText::_('HIKASHOP_ERROR_UNKNOW_WHEN_UPDATING_ELEMENT_WITH_POST_DATA');
							}
							$this->_outputErrors($messages, $data);
						}
					}

					if(!count($elements)) {
						$this->_outputErrors(JText::_('HIKASHOP_NO_ELEMENT_FOUND'), $_GET);
					}

					$massactionClass = hikashop_get('class.massaction');
					$massactionClass->api(true);
					$result = $massactionClass->process($massaction, $elements);
					$messages = $massactionClass->report;

					echo json_encode(array('success' => $result, 'messages' => $messages));
					exit;
				}
			}
		}
	}

	function _outputErrors($messages, $data=null, $code=500, $http_text='Internal Server Error') {
		header($_SERVER['SERVER_PROTOCOL'] .' '.$code.' '. $http_text, true, $code);
		if(!is_array($messages)) {
			$messages = array($messages);
		}
		if(function_exists('hikashop_writeToLog')) {
			if(!is_null($data)) {
				hikashop_writeToLog($data, 'Massaction HTTP trigger');
			}
			hikashop_writeToLog($messages, 'Massaction HTTP trigger');
		}
		echo json_encode(array('success' => false, 'messages' => $messages));
		exit;
	}

	function onAfterMassactionUpdate(&$element) {
		$cache = JFactory::getCache('plg_system_hikashopmassaction');
		$cache->setCaching( 1 );
		$cache->clean();
	}
	function onAfterMassactionCreate(&$element) {
		$cache = JFactory::getCache('plg_system_hikashopmassaction');
		$cache->setCaching( 1 );
		$cache->clean();
	}


	function _loadMassactions() {

		$database = JFactory::getDBO();
		$database->setQuery('SELECT * FROM #__hikashop_massaction WHERE massaction_published=1 && massaction_triggers!=\'\' ORDER BY massaction_id ASC');
		$massactionsFromDB = $database->loadObjectList();
		$ordered = array();
		if(!empty($massactionsFromDB)){
			foreach($massactionsFromDB as $massactionFromDB){
				$this->_prepare($massactionFromDB);
				if(!empty($massactionFromDB->massaction_triggers) && is_array($massactionFromDB->massaction_triggers) && count($massactionFromDB->massaction_triggers)){
					foreach($massactionFromDB->massaction_triggers as $k=>$data){
						if(!isset($ordered[$data->name])){
							$ordered[$data->name] = array();
						}
						$ordered[$data->name][] = $massactionFromDB;
					}
				}
			}
		}
		$massactionsFromDB = $ordered;
		return $massactionsFromDB;
	}
	function _prepare(&$massaction){
		$vars = array('triggers','actions','filters');
		foreach($vars as $var){
			$key = 'massaction_'.$var;
			if(!empty($massaction->$key)){
				$massaction->$key = $this->_unserialize($massaction->$key);
			} else {
				$massaction->$key = array();
			}
		}
	}
	function _unserialize($data) {
		if(!is_string($data))
			return false;
		if(!preg_match_all('#[OC]:[0-9]+:"([-_a-zA-Z0-9]+)":[0-9]+:\{#iU', $data, $matches))
			return unserialize($data);
		if(!empty($matches[1])) {
			foreach($matches[1] as $m) {
				if($m != 'stdClass')
					return false;
			}
		}
		return unserialize($data);
	}

	function onMassactionTableFiltersLoad(&$table,&$filters,&$filters_html,&$loadedData){
		$db = JFactory::getDBO();
		$operators = hikashop_get('type.operators');
		$cid = hikashop_getCID();
		$tables = array();
		$custom = '';
		$type = 'filter';
		$massactionClass = hikashop_get('class.massaction');
		if(empty($loadedData->massaction_filters)){
			$loadedData->massaction_filters = array();
		}

		if(!HIKASHOP_J30){
			$fieldsTable = $db->getTableFields('#__hikashop_user');
			$hkUsers = reset($fieldsTable);
			$fieldsTable = $db->getTableFields('#__users');
			$jUsers = reset($fieldsTable);
		} else {
			$hkUsers = $db->getTableColumns('#__hikashop_user');
			$jUsers = $db->getTableColumns('#__users');
		}
		ksort($hkUsers);
		ksort($jUsers);

		if($table->table == 'address')
			$tables = array('address','user');
		if($table->table == 'category')
			$tables = array('category','parent_category');
		if($table->table == 'order')
			$tables = array('order','order_product','address','user');
		if($table->table == 'product')
			$tables = array('product','price','category','characteristic','product_related','product_option');
		if($table->table == 'user')
			$tables = array('user','address');
		$loadedData->massaction_filters['__num__'] = new stdClass();
		$loadedData->massaction_filters['__num__']->type = $table->table;
		$loadedData->massaction_filters['__num__']->data = array();
		$loadedData->massaction_filters['__num__']->data['type'] = '';
		$loadedData->massaction_filters['__num__']->data['operator'] = '';
		$loadedData->massaction_filters['__num__']->data['value'] = '';
		$loadedData->massaction_filters['__num__']->name = '';
		$loadedData->massaction_filters['__num__']->html = '';

		foreach($loadedData->massaction_filters as $key => &$value) {
			if(!isset($value->data['type']))
				$value->data['type'] = '';
			if(!isset($value->data['operator']))
				$value->data['operator'] = '=';
			if(!isset($value->data['value']))
				$value->data['value'] = '';
			if(!isset($value->data['address']))
				$value->data['address'] = '';

			if(!empty($tables)){
				if(!is_array($tables)) $tables = array($tables);
				foreach($tables as $relatedTable){
					$column = $relatedTable.'Column';
					$loadedData->massaction_filters['__num__']->name = $column;
					$filters[$column]=JText::_(''.strtoupper($relatedTable).'_COLUMN');
					if($relatedTable == 'product_option') $relatedTable = 'product_related';
					if($relatedTable == 'parent_category') $relatedTable = 'category';
					if(!HIKASHOP_J30){
						$fieldsTable = $db->getTableFields('#__hikashop_'.$relatedTable);
						$fields = reset($fieldsTable);
					} else {
						$fields = $db->getTableColumns('#__hikashop_'.$relatedTable);
					}
					ksort($fields);
					$typeField = array();
					if(!empty($fields)) {
						foreach($fields as $oneField => $fieldType){
							$typeField[] = JHTML::_('select.option',$oneField,$oneField);
						}
					}
					$user = '<select class="custom-select chzn-done not-processed" name="filter['.$table->table.']['.$key.'][userColumn][type]" onchange="countresults(\''.$table->table.'\','.$key.')" >';
						$user .='<optgroup label="HIKA_USER">';
							foreach($hkUsers as $key2 => $hkUser){
								$tmpVal = str_replace('hk_user.','',$value->data['type']);
								if($key2 == $tmpVal)
									$user .= '<option value="hk_user.'.$key2.'" selected="selected">'.$key2.'</option>';
								else
									$user .= '<option value="hk_user.'.$key2.'">'.$key2.'</option>';
							}
						$user .= '</optgroup>';
						$user .='<optgroup label="JOOMLA_USER">';
							foreach($jUsers as $key2 => $jUser){
								$tmpVal = str_replace('joomla_user.','',$value->data['type']);
								if($key2 == $tmpVal)
									$user .= '<option value="joomla_user.'.$key2.'" selected="selected">'.$key2.'</option>';
								else
									$user .= '<option value="joomla_user.'.$key2.'">'.$key2.'</option>';
							}
						$user .= '</optgroup>';
					$user .= '</select>';
					switch($table->table){
						case 'product':
							if($relatedTable == 'characteristic'){
								$db->setQuery('SELECT * FROM '.hikashop_table('characteristic').' WHERE characteristic_parent_id = 0');
								$characteristics = $db->loadObjectList();
								if(is_array($characteristics)){
									$custom = '<select class="custom-select chzn-done not-processed" name="filter['.$table->table.']['.$key.'][characteristicColumn][type]" onchange="countresults('.$table->table.','.$key.')" >';
									foreach($characteristics as $charact){
										$selected = '';
										if($charact->characteristic_value == $value->data['type']) $selected = 'selected="selected"';
										$custom .= '<option value="'.$charact->characteristic_value.'" '.$selected.'>'.$charact->characteristic_value.'</option>';
									}
									$custom .= '</select>';
								}
							}
							elseif(in_array($relatedTable, array('product_related','product_option'))){
								if(!HIKASHOP_J30){
									$fieldsTable = $db->getTableFields('#__hikashop_product');
									$fields = reset($fieldsTable);
								} else {
									$fields = $db->getTableColumns('#__hikashop_product');
								}
								ksort($fields);
								$typeField = array();
								if(!empty($fields)) {
									foreach($fields as $oneField => $fieldType){
										$typeField[] = JHTML::_('select.option',$oneField,$oneField);
									}
								}
								$custom = JHTML::_('select.genericlist', $typeField, "filter[".$table->table."][$key][".$column."][type]", 'class="custom-select chzn-done not-processed" onchange="countresults(\''.$table->table.'\','.$key.')" size="1"', 'value', 'text',$value->data['type']);
							}else{
								$custom = '';
							}
							break;
						case 'category':
							$custom = '';
							break;
						case 'order':
							if($relatedTable == 'address'){
								$datas = array('both' => 'DISPLAY_BOTH','bill' => 'HIKASHOP_BILLING_ADDRESS','ship' => 'HIKASHOP_SHIPPING_ADDRESS');
								$custom = '<select class="custom-select chzn-done not-processed" onchange="countresults(\''.$table->table.'\','.$key.')" name="filter['.$table->table.']['.$key.'][addressColumn][address]" >';
								foreach($datas as $k => $data){
									$selected = '';
									if($k == $value->data['address']) $selected = 'selected="selected"';
									$custom .= '<option value="'.$k.'" '.$selected.'>'.JText::sprintf(''.$data.'').'</option>';
								}
								$custom .= '</select>';
							}elseif($relatedTable == 'user'){
								$custom = $user;
							}else{
								$custom = '';
							}
							break;
						case 'user':
							if($relatedTable == 'user'){
								$custom = $user;
							}else{
								$custom = '';
							}
							break;
						case 'address':
							if($relatedTable == 'user'){
								$custom = $user;
							}else{
								$custom = '';
							}
							break;
					}
					if($value->name != $column || ($table->table != $loadedData->massaction_table && is_int($key)))
						continue;

					$value->type = $relatedTable;
					$output = $custom;
					if(!in_array($relatedTable, array('characteristic','product_related','user'))){
						$output .= JHTML::_('select.genericlist', $typeField, "filter[".$table->table."][".$key."][".$column."][type]", 'class="custom-select chzn-done not-processed" onchange="countresults(\''.$table->table.'\',\''.$key.'\')" size="1"', 'value', 'text', $value->data['type']);
					}
					$operators->extra = 'onchange="countresults(\''.$table->table.'\',\''.$key.'\')"';
					$output .= $operators->display('filter['.$table->table.']['.$key.']['.$column.'][operator]',$value->data['operator'], "chzn-done not-processed");
					$output .= ' <input class="inputbox" type="text" name="filter['.$table->table.']['.$key.']['.$column.'][value]" size="50" value="'.htmlspecialchars((string)$value->data['value'], ENT_COMPAT, 'UTF-8').'" onchange="countresults(\''.$table->table.'\',\''.$key.'\')" />';

					$filters_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
				}
			}
		}

				$loadedData->massaction_filters['__num__'] = new stdClass();
				$loadedData->massaction_filters['__num__']->name = 'limit';
				$loadedData->massaction_filters['__num__']->type = $table->table;
				$loadedData->massaction_filters['__num__']->data = array();
				$loadedData->massaction_filters['__num__']->data['start'] = '0';
				$loadedData->massaction_filters['__num__']->data['value'] = '500';
				$loadedData->massaction_filters['__num__']->html = '';

				foreach($loadedData->massaction_filters as $key => &$value) {
					if($value->name != 'limit' || ($table->table != $loadedData->massaction_table && is_int($key)))
						continue;

					$value->type = $table->table;
					if(!isset($value->data['start'])) $value->data['start'] = 0;
					if(!isset($value->data['value'])) $value->data['value'] = 500;
					$output = '<div id="'.$table->table.'filter'.$key.'limit">'.JText::_('HIKA_START').' : <input type="text" name="filter['.$table->table.']['.$key.'][limit][start]" value="'.$value->data['start'].'" /> '.JText::_('VALUE').' : <input type="text" name="filter['.$table->table.']['.$key.'][limit][value]" value="'.$value->data['value'].'"/>'.'</div>';
					$filters_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
				}

				$filters['ordering']=JText::_('ORDERING');
				$loadedData->massaction_filters['__num__'] = new stdClass();
				$loadedData->massaction_filters['__num__']->name = 'ordering';
				$loadedData->massaction_filters['__num__']->type = $table->table;
				$loadedData->massaction_filters['__num__']->data = array();
				$loadedData->massaction_filters['__num__']->data['value'] = '';
				$loadedData->massaction_filters['__num__']->html = '';

				foreach($loadedData->massaction_filters as $key => &$value) {
					if($value->name != 'ordering' || ($table->table != $loadedData->massaction_table && is_int($key)))
						continue;

					$value->type = $table->table;
					if(!isset($value->data['value'])) $value->data['value'] = $table->table.'_id';

					if(!HIKASHOP_J30) {
						$fieldsTable = $db->getTableFields('#__hikashop_'.$table->table);
						$fields = reset($fieldsTable);
					} else {
						$fields = $db->getTableColumns('#__hikashop_'.$table->table);
					}
					ksort($fields);
					if(!isset($value->data['value'])) $value->data['value'] = $table->table.'_id';
					$output = '<div id="'.$table->table.'filter'.$key.'ordering">'.JText::_('VALUE').' : ';
					$output .= '<select class="custom-select chzn-done not-processed" name="filter['.$table->table.']['.$key.'][ordering][value]">';
					foreach($fields as $field => $fieldType){
					$selected = '';
						if($value->data['value'] == $field)
							$selected = 'selected="selected"';
						$output .= '<option value="'.$field.'" '.$selected.'>'.$field.'</option>';
					}
					$output .= '</select></div>';
					$filters_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
				}

				$filters['direction']=JText::_('MASSACTION_ORDERING_DIRECTION');
				$loadedData->massaction_filters['__num__'] = new stdClass();
				$loadedData->massaction_filters['__num__']->name = 'direction';
				$loadedData->massaction_filters['__num__']->type = $table->table;
				$loadedData->massaction_filters['__num__']->data = array();
				$loadedData->massaction_filters['__num__']->data['value'] = '';
				$loadedData->massaction_filters['__num__']->html = '';

				foreach($loadedData->massaction_filters as $key => &$value) {
					if($value->name != 'direction' || ($table->table != $loadedData->massaction_table && is_int($key)))
						continue;

					$value->type = $table->table;
					if(!isset($value->data['value'])) $value->data['value'] = 'ASC';
					$output = '<div id="'.$table->table.'filter'.$key.'direction">'.JText::_('VALUE').' : <select class="custom-select chzn-done not-processed" name="filter['.$table->table.']['.$key.'][direction][value]">';
					$values = array('ASC','DESC');
					foreach($values as $oneValue){
						$selected = '';
						if($value->data['value'] == $oneValue)
							$selected = 'selected="selected"';
						$output .= '<option value="'.$oneValue.'" '.$selected.'>'.$oneValue.'</option>';
					}
					$output .= '</select>'.'</div>';
					$filters_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
				}

			if(hikashop_level(2)){
				if(in_array($table->table,array('product','category'))){
					$filters['accessLevel']=JText::_('ACCESS_LEVEL');
				}else{
					$filters['accessLevel']=JText::_('USER_WITH_ACL');
				}
				$loadedData->massaction_filters['__num__'] = new stdClass();
				$loadedData->massaction_filters['__num__']->name = 'accessLevel';
				$loadedData->massaction_filters['__num__']->type = $table->table;
				$loadedData->massaction_filters['__num__']->data = array();
				$loadedData->massaction_filters['__num__']->data['type'] = '';
				$loadedData->massaction_filters['__num__']->data['group'] = '';
				$loadedData->massaction_filters['__num__']->html = '';

				if(defined('HIKASHOP_WORDPRESS')) {
					$groups = array();
					$wp_roles = wp_roles();
					$rid = 1;
					foreach($wp_roles->role_names as $slug => $rname) {
						$group = new stdClass();
						$group->id = $rid;
						$group->value = $rid;
						$group->text = translate_user_role($rname);
						$group->title = $group->text;
						$group->parent_id = 0;
						$group->level = 0;
						$groups[$rid] = $group;
						$rid++;
					}
				} else {
				$db = JFactory::getDBO();
				$db->setQuery('SELECT a.*, a.title as text, a.id as value  FROM #__usergroups AS a ORDER BY a.lft ASC');
				$groups = $db->loadObjectList('id');
				foreach($groups as $id => $group){
					if(isset($groups[$group->parent_id])){
						$groups[$id]->level = intval(@$groups[$group->parent_id]->level) + 1;
						$groups[$id]->text = str_repeat('- - ',$groups[$id]->level).$groups[$id]->text;
					}
				}
				}

				$inoperator = hikashop_get('type.operatorsin');
				foreach($loadedData->massaction_filters as $key => &$value) {
					if($value->name != 'accessLevel' || ($table->table != $loadedData->massaction_table && is_int($key)))
						continue;

					$value->type = $table->table;
					$inoperator->js = 'onchange="countresults(\''.$table->table.'\','.$key.')"';
					$output = $inoperator->display("filter[".$table->table."][$key][accessLevel][type]",$value->data['type'], 'chzn-done not-processed').' '.JHTML::_('select.genericlist',   $groups, "filter[".$table->table."][$key][accessLevel][group]", 'class="custom-select chzn-done not-processed" size="1" onchange="countresults(\''.$table->table.'\','.$key.')"', 'value', 'text',$value->data['group']);

					$filters_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
				}
			}
?>
<script type="text/javascript">
	function hikashop_verifycsvcolumns(k){
		var target = "productfilter"+k+"csvImport_verify";
		var url = "<?php echo hikashop_completeLink('massaction&task=displayassociate&tmpl=component',false,false,true); ?>";
		var data = "cid=<?php echo hikashop_getCID(); ?>&current_filter="+k+"&csv_path=" + encodeURIComponent(document.getElementById("productfilter"+k+"csvImport_path_value").value);
		if(data != ""){
			window.hikashop.xRequest(url, {update: target, mode: "POST", data: data});
		}
	}
	function hikashop_switchmode(el,k) {
		var d = document, v = el.value, modes = ['upload','path'], e = null;
		for(var i = 0; i < modes.length; i++) {
			mode = modes[i];
			e = d.getElementById('productfilter'+k+'csvImport_'+mode);
			if(!e) continue;
			if(v == mode) {
				e.style.display = '';
			} else {
				e.style.display = 'none';
			}
			if(v != 'upload'){
				d.getElementById('productfilter'+k+'csvImport_path').style.display = '';
				d.getElementById('productfilter'+k+'csvImport_upload').style.display = 'none';
			}else{
				d.getElementById('productfilter'+k+'csvImport_path').style.display = 'none';
				d.getElementById('productfilter'+k+'csvImport_upload').style.display = '';
			}
		}
	}
</script>
<?php
	}

	public function onMassactionSpecialActions(&$special) {
		foreach(array('sendHttp', 'mysqlQuery', 'phpCode') as $name) {
			if(!in_array($name, $special))
				$special[] = $name;
		}
	}

	function onMassactionTableActionsLoad(&$table,&$actions,&$actions_html,&$loadedData){
		$db = JFactory::getDBO();
		$dispTables = array();
		$updTables = array();
		$customCheckboxes = '';
		$database = JFactory::getDBO();
		$type = 'action';
		if(empty($loadedData->massaction_filters)){
			$loadedData->massaction_filters = array();
		}
		$massactionClass = hikashop_get('class.massaction');
		$nameboxType = hikashop_get('type.namebox');
		$actions['displayResults']=JText::_('DISPLAY_RESULTS');
		$actions['exportCsv']=JText::_('EXPORT_CSV');
		$actions['updateValues']=JText::_('UPDATE_VALUES');
		$actions['deleteElements']=JText::_('DELETE_ELEMENTS');
		$actions['sendEmail']=JText::_('MASS_SEND_EMAIL');
		switch($table->table){
			case 'product':
				$dispTables = array('product','price','category');
				$updTables = array('product','price');

				$actions['updateCategories']=JText::_('UPDATE_CATEGORIES');
				$actions['updateRelateds']=JText::_('UPDATE_RELATEDS');
				$actions['updateOptions']=JText::_('UPDATE_OPTIONS');
				$actions['updateCharacteristics']=JText::_('UPDATE_CHARACTERISTICS');
				$actions['setCanonical']=JText::_('HK_SET_CANONICAL');

				break;
			case 'category':
				$dispTables = array('category');
				$updTables = array('category');
				break;
			case 'order':
				$dispTables = array('order','order_product','address','user','joomla_users');
				$updTables = array('order','order_product');
				$actions['changeStatus']=JText::_('CHANGE_STATUS');
				$actions['addProduct']=JText::_('ADD_EXISTING_PRODUCT');
				$actions['changeGroup']=JText::_('CHANGE_USER_GROUP');
				break;
			case 'user':
				$dispTables = array('user','joomla_users','address');
				$updTables = array('user','joomla_users');
				$actions['changeGroup']=JText::_('CHANGE_USER_GROUP');
				break;
			case 'address':
				$dispTables = array('address','user','joomla_users');
				$updTables = array('address');
				break;
			case 'user':
				$dispTables = array('user','joomla_users','address');
				$updTables = array('user','joomla_users');
			default:
				return false;
			break;
		}

			$actions['sendHttp']=JText::_('SEND_HTTP_REQUEST');
			$loadedData->massaction_actions['__num__'] = new stdClass();
			$loadedData->massaction_actions['__num__']->type = $table->table;
			$loadedData->massaction_actions['__num__']->data = array('type' => '','value' => '','operation' => '');
			$loadedData->massaction_actions['__num__']->name = 'sendHttp';
			$loadedData->massaction_actions['__num__']->html = '';
			foreach($loadedData->massaction_actions as $key => &$value) {
				if($value->name != 'sendHttp' || ($table->table != $loadedData->massaction_table && is_int($key)))
					continue;
				$output = '<div id="'.$table->table.'action'.$key.'sendHttp">';
				$output .= '<b>'.JText::_('URL').'</b> : ';
				$output .= '<input class="inputbox" type="text" name="action['.$table->table.']['.$key.'][sendHttp][url]" style="width:100%" value="'. htmlspecialchars((string)@$value->data['url'], ENT_COMPAT, 'UTF-8').'" />';
				$output .= '<br/><em>'.JText::_('HIKASHOP_URL_EXPLANATION').'</em>';
				$methods = array('GET','POST','PUT','DELETE','HEAD','PATCH');
				$output .= '<br/><br/><b>'.JText::_('HIKASHOP_METHOD').'</b> : ';
				$output .= '<select onchange="sendHttpDynamicDisplay(\''.$table->table.'action'.$key.'sendHttp_method\', this.value);" class="custom-select chzn-done not-processed" name="action['.$table->table.']['.$key.'][sendHttp][method]">';
				foreach($methods as $oneMethod){
					$selected = '';
					if($oneMethod == @$value->data['method'])
						$selected = 'selected="selected"';
					$output .= '<option value="'.$oneMethod.'" '.$selected.'>'.$oneMethod.'</option>';
				}
				$output .= '</select>';
				$output .= '<br/><em>'.JText::_('HIKASHOP_METHOD_EXPLANATION').'</em>';
				$output .= '<br/><br/><b>'.JText::_('HIKASHOP_AUTHENTICATION').'</b> : ';
				$authentications = array('none' => JText::_('HIKA_NONE'), 'basic' => JText::_('HIKASHOP_BASIC'), 'oauth2' => JText::_('OAUTH2'));
				$output .= '<select onchange="sendHttpDynamicDisplay(\''.$table->table.'action'.$key.'sendHttp_authentication\', this.value);" class="custom-select chzn-done not-processed" name="action['.$table->table.']['.$key.'][sendHttp][authentication]">';
				foreach($authentications as $oneAuthentication => $text){
					$selected = '';
					if($oneAuthentication == @$value->data['authentication'])
						$selected = 'selected="selected"';
					$output .= '<option value="'.$oneAuthentication.'" '.$selected.'>'.$text.'</option>';
				}
				$output .= '</select>';
				$output .= '<br/><em>'.JText::_('HIKASHOP_AUTHENTICATION_EXPLANATION').'</em>';
				$output .= '<div id="'.$table->table.'action'.$key.'sendHttp_authentication_basic" style="margin-left:20px;'.((@$value->data['authentication'] != 'basic')?'display:none;':'').'">';
				$output .= '<br/><b>'.JText::_('HIKA_USERNAME').'</b> : ';
				$output .= '<input class="inputbox" type="text" name="action['	.$table->table.']['.$key.'][sendHttp][username]" value="'. htmlspecialchars((string)@$value->data['username'], ENT_COMPAT, 'UTF-8').'" style="width:100%"/>';
				$output .= '<br/><br/><b>'.JText::_('HIKA_PASSWORD').'</b> : ';
				$output .= '<input class="inputbox" type="password" name="action['.$table->table.']['.$key.'][sendHttp][password]" value="'. htmlspecialchars((string)@$value->data['password'], ENT_COMPAT, 'UTF-8').'" style="width:100%" />';
				$output .= '</div>';
				$output .= '<div id="'.$table->table.'action'.$key.'sendHttp_authentication_oauth2" style="margin-left:20px;'.((@$value->data['authentication'] != 'oauth2')?'display:none;':'').'">';
				$output .= '<br/><b>'.JText::_('HIKASHOP_TOKEN_URL').'</b> : ';
				$output .= '<input class="inputbox" type="text" name="action['.$table->table.']['.$key.'][sendHttp][token_url]" value="'. htmlspecialchars((string)@$value->data['token_url'], ENT_COMPAT, 'UTF-8').'" style="width:100%"/>';
				$output .= '<br/><em>'.JText::_('HIKASHOP_TOKEN_URL_EXPLANATION').'</em>';
				$output .= '<br/><br/><b>'.JText::_('HIKASHOP_ACCESS_TOKEN_HEADERS').'</b> : ';
				$output .= '<br/><textarea placeholder="Content-Type: application/x-www-form-urlencoded" name="action['.$table->table.']['.$key.'][sendHttp][token_headers]" rows="5" cols="100" style="width:100%">'. htmlspecialchars((string)@$value->data['token_headers'], ENT_COMPAT, 'UTF-8').'</textarea>';
				$output .= '<br/><em>'.JText::_('HIKASHOP_ACCESS_TOKEN_HEADERS_EXPLANATION').'</em>';
				$output .= '<br/><br/><b>'.JText::_('HIKASHOP_ACCESS_TOKEN_METHOD').'</b> : ';
				$methods = array('GET', 'POST');
				$output .= '<select class="custom-select chzn-done not-processed" name="action['.$table->table.']['.$key.'][sendHttp][token_method]">';
				foreach ($methods as $oneMethod) {
					$selected = ($oneMethod == @$value->data['token_method']) ? 'selected="selected"' : '';
					$output .= '<option value="'.$oneMethod.'" '.$selected.'>'.$oneMethod.'</option>';
				}
				$output .= '</select>';
				$output .= '<br/><em>'.JText::_('HIKASHOP_ACCESS_TOKEN_METHOD_EXPLANATION').'</em>';
				$output .= '<br/><br/><b>'.JText::_('HIKASHOP_ACCESS_TOKEN_POST').'</b> : ';
				$output .= '<br/><textarea placeholder="client_id=XXXXXXXXXXXXXXXXX&client_secret=YYYYYYYYYYYYYYYYYYYYYYYYYY&..." name="action['.$table->table.']['.$key.'][sendHttp][token_post]" rows="5" cols="100" style="width:100%">'. htmlspecialchars((string)@$value->data['token_post'], ENT_COMPAT, 'UTF-8').'</textarea>';
				$output .= '<br/><em>'.JText::_('HIKASHOP_ACCESS_TOKEN_POST_EXPLANATION').'</em>';
				$output .= '<br/><br/><b>'.JText::_('HIKASHOP_ACCESS_TOKEN_REGEX_EXTRACT').'</b> : ';
				$output .= '<br/><textarea placeholder="\"access_token\"\s*:\s*\"([^"]+)\"" name="action['.$table->table.']['.$key.'][sendHttp][token_regex_extract]" rows="5" cols="100" style="width:100%">'. htmlspecialchars((string)@$value->data['token_regex_extract'], ENT_COMPAT, 'UTF-8').'</textarea>';
				$output .= '<br/><em>'.JText::_('HIKASHOP_ACCESS_TOKEN_REGEX_EXTRACT_EXPLANATION').'</em>';
				$output .= '</div>';
				$output .= '<br/><br/><b>'.JText::_('HIKASHOP_ADDITIONAL_HEADERS').'</b> : ';
				$output .= '<br/><textarea placeholder="Content-Type: application/x-www-form-urlencoded" name="action['.$table->table.']['.$key.'][sendHttp][headers]" rows="5" cols="100" style="width:100%">'. htmlspecialchars((string)@$value->data['headers'], ENT_COMPAT, 'UTF-8').'</textarea>';
				$output .= '<br/><em>'.JText::_('HIKASHOP_ADDITIONAL_HEADERS_EXPLANATION').'</em>';
				$output .= '<div id="'.$table->table.'action'.$key.'sendHttp_post" style="margin-left:20px;'.((!in_array(@$value->data['method'], array('POST','PUT','PATCH')))?'display:none;':'').'">';
				$output .= '<br/><br/><b>'.JText::_('HIKASHOP_POST_CONTENT').'</b> : ';
				$output .= '<br/><textarea placeholder="'.$table->table.'_id={'.$table->table.'_id}&task=create_'.$table->table.'&..." name="action['.$table->table.']['.$key.'][sendHttp][post]" rows="15" cols="100" style="width:100%">'. htmlspecialchars((string)@$value->data['post'], ENT_COMPAT, 'UTF-8').'</textarea>';
				$output .= '<br/><em>'.JText::_('HIKASHOP_POST_CONTENT_EXPLANATION').'</em>';
				$output .= '</div>';
				$output .= '</div>';
				$output .= '<script type="text/javascript">
					function sendHttpDynamicDisplay(id, value) {
						var d = document;
						if(id.indexOf("method") > 0) {
							var ePost = d.getElementById("'.$table->table.'action'.$key.'sendHttp_post");
							if(ePost) {
								if(value == "POST" || value == "PUT" || value == "PATCH") {
									ePost.style.display = "";
								} else {
									ePost.style.display = "none";
								}
							}
						} else if(id.indexOf("authentication") > 0) {
							var authentications = ["none","basic","oauth2"];
							for(var i = 0; i < authentications.length; i++) {
								var oneAuth = authentications[i];
								var area = d.getElementById("'.$table->table.'action'.$key.'sendHttp_authentication_"+oneAuth);
								if(area) {
									if(oneAuth == value) {
										area.style.display = "";
									} else {
										area.style.display = "none";
									}
								}
							}
						}
					}
					window.hikashop.ready(function(){
						sendHttpDynamicDisplay("'.$table->table.'action'.$key.'sendHttp_method", "'.((isset($value->data['method']) && $value->data['method'] != '') ? $value->data['method'] : 'GET').'");'
						.'sendHttpDynamicDisplay("'.$table->table.'action'.$key.'sendHttp_authentication", "'.((isset($value->data['authentication']) && $value->data['authentication'] != '') ? $value->data['authentication'] : 'none').'");'
					.'});
				</script>';
				$actions_html[$value->name] =  $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
			}


			$actions['mysqlQuery']=JText::_('RUN_MYSQL_QUERY');
			$loadedData->massaction_actions['__num__'] = new stdClass();
			$loadedData->massaction_actions['__num__']->type = $table->table;
			$loadedData->massaction_actions['__num__']->data = array('type' => '','value' => '','operation' => '');
			$loadedData->massaction_actions['__num__']->name = 'mysqlQuery';
			$loadedData->massaction_actions['__num__']->html = '';
			foreach($loadedData->massaction_actions as $key => &$value) {
				if($value->name != 'mysqlQuery' || ($table->table != $loadedData->massaction_table && is_int($key)))
					continue;
				$output = ' <textarea name="action['.$table->table.']['.$key.'][mysqlQuery][query]" rows="15" cols="100">'. htmlspecialchars((string)@$value->data['query'], ENT_COMPAT, 'UTF-8').'</textarea>';
				$actions_html[$value->name] =  $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
			}

			$actions['phpCode']=JText::_('RUN_PHP_CODE');
			$loadedData->massaction_actions['__num__'] = new stdClass();
			$loadedData->massaction_actions['__num__']->type = $table->table;
			$loadedData->massaction_actions['__num__']->data = array('type' => '','value' => '','operation' => '');
			$loadedData->massaction_actions['__num__']->name = 'phpCode';
			$loadedData->massaction_actions['__num__']->html = '';
			foreach($loadedData->massaction_actions as $key => &$value) {
				if($value->name != 'phpCode' || ($table->table != $loadedData->massaction_table && is_int($key)))
					continue;
				$output = ' <textarea name="action['.$table->table.']['.$key.'][phpCode][code]" rows="15" cols="100">'. htmlspecialchars((string)@$value->data['code'], ENT_COMPAT, 'UTF-8').'</textarea>';
				$actions_html[$value->name] =  $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
			}


			$actions['cancelOperation']=JText::_('CANCEL_OPERATION');
			$loadedData->massaction_actions['__num__'] = new stdClass();
			$loadedData->massaction_actions['__num__']->type = $table->table;
			$loadedData->massaction_actions['__num__']->data = array('message' => '');
			$loadedData->massaction_actions['__num__']->name = 'cancelOperation';
			$loadedData->massaction_actions['__num__']->html = '';
			foreach($loadedData->massaction_actions as $key => &$value) {
				if($value->name != 'cancelOperation' || ($table->table != $loadedData->massaction_table && is_int($key)))
					continue;
				$output = '<div id="'.$table->table.'action'.$key.'cancelOperation">';
				$output .= '<div class="alert alert-warning">'.JText::_('CANCEL_OPERATION_WARNING').'</div>';
				$output .= '<b>'.JText::_('CANCEL_OPERATION_MESSAGE').'</b> : <br/>';
				$output .= '<textarea name="action['.$table->table.']['.$key.'][cancelOperation][message]" rows="3" cols="100" style="width:100%">'. htmlspecialchars((string)@$value->data['message'], ENT_COMPAT, 'UTF-8').'</textarea>';
				$output .= '</div>';
				$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
			}

		if(is_array($dispTables)){

			$loadedData->massaction_actions['__num__'] = new stdClass();
			$loadedData->massaction_actions['__num__']->type = $table->table;
			$loadedData->massaction_actions['__num__']->data = array();
			$loadedData->massaction_actions['__num__']->name = 'displayResults';
			$loadedData->massaction_actions['__num__']->html = '';

			foreach($loadedData->massaction_actions as $key => &$value) {
				if($value->name != 'displayResults' || ($table->table != $loadedData->massaction_table && is_int($key)))
					continue;

				$value->type = $dispTables[0];

				$margin = '';
				$output = '';
				$customCheckboxes='';
				foreach($dispTables as $relatedTable){
					if(!HIKASHOP_J30){
						if(preg_match('/joomla_/',$relatedTable)) $fieldsTable = $db->getTableFields('#__'.str_replace('joomla_','',$relatedTable));
						else $fieldsTable = $db->getTableFields('#__hikashop_'.$relatedTable);
						$fields = reset($fieldsTable);
					} else {
						if(preg_match('/joomla_/',$relatedTable)) $fields = $db->getTableColumns('#__'.str_replace('joomla_','',$relatedTable));
						else $fields = $db->getTableColumns('#__hikashop_'.$relatedTable);
					}
					if(!empty($fields)) {
						$output .= '<div id="action_'.$table->table.'_'.$key.'_displayResults_'.$relatedTable.'Column_div" class="hika_massaction_checkbox"> <a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_displayResults_'.$relatedTable.'Column_div\',\'check\');">'.JText::_('SELECT_ALL').'</a> / <a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_displayResults_'.$relatedTable.'Column_div\',\'uncheck\');">'.JText::_('UNSELECT_ALL').'</a> <br/>';

						foreach($fields as $key2 => $field){
							if($key2 == 'address_state'){
								$fields['zone_state_name'] = $field;
								$fields['zone_state_name_english'] = $field;
								$fields['zone_state_code_2'] = $field;
								$fields['zone_state_code_3'] = $field;
							}elseif($key2 == 'address_country'){
								$fields['zone_country_name'] = $field;
								$fields['zone_country_name_english'] = $field;
								$fields['zone_country_code_2'] = $field;
								$fields['zone_country_code_3'] = $field;
							}
						}
						ksort($fields);
						foreach($fields as $key2 => $field){
							$checked='';
							if(isset($value->data[$relatedTable]) && isset($value->data[$relatedTable][$key2])){
								$checked='checked="checked"';
							}
							$output .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_displayResults_'.$relatedTable.'Column_'.$key2.'" name="action['.$table->table.']['.$key.'][displayResults]['.$relatedTable.']['.$key2.']" value="'.$key2.'" />'.
							'<label for="action_'.$table->table.'_'.$key.'_displayResults_'.$relatedTable.'Column_'.$key2.'">'.$key2.'</label>';
						}
						if($relatedTable == 'order'){
							$key2 = 'order_tax_amount';
							$checked='';
							if(isset($value->data[$relatedTable]) && isset($value->data[$relatedTable][$key2])){
								$checked='checked="checked"';
							}
							$output .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_displayResults_'.$relatedTable.'Column_'.$key2.'" name="action['.$table->table.']['.$key.'][displayResults]['.$relatedTable.']['.$key2.']" value="'.$key2.'" />'.
							'<label for="action_'.$table->table.'_'.$key.'_displayResults_'.$relatedTable.'Column_'.$key2.'">'.$key2.'</label>';
							$key2 = 'order_tax_namekey';
							$checked='';
							if(isset($value->data[$relatedTable]) && isset($value->data[$relatedTable][$key2])){
								$checked='checked="checked"';
							}
							$output .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_displayResults_'.$relatedTable.'Column_'.$key2.'" name="action['.$table->table.']['.$key.'][displayResults]['.$relatedTable.']['.$key2.']" value="'.$key2.'" />'.
							'<label for="action_'.$table->table.'_'.$key.'_displayResults_'.$relatedTable.'Column_'.$key2.'">'.$key2.'</label>';
						}
						$output .= '</div>';
						$margin = 'margin-left: 20px;';
					}
				}

				switch($table->table){
					case 'product':
						$db->setQuery('SELECT * FROM '.hikashop_table('characteristic').' WHERE characteristic_parent_id = 0');
						$characteristics = $db->loadObjectList();
						if(is_array($characteristics)){
							$customCheckboxes .= '<div id="action_'.$table->table.'_'.$key.'_displayResults_characteristicColumn_div" class="hika_massaction_checkbox"><a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_displayResults_characteristicColumn_div\',\'check\');">'.JText::_('SELECT_ALL').'</a> / <a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_displayResults_characteristicColumn_div\',\'uncheck\');">'.JText::_('UNSELECT_ALL').'</a><br/>';
							foreach($characteristics as $characteristic){
								$checked='';
								if(isset($value->data['characteristic'][$characteristic->characteristic_value])){
									$checked='checked="checked"';
								}
								$customCheckboxes .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_displayResults_characteristicColumn_'.$characteristic->characteristic_value.'" name="action['.$table->table.']['.$key.'][displayResults][characteristic]['.$characteristic->characteristic_value.']" value="'.$characteristic->characteristic_value.'" />'.
								'<label for="action_'.$table->table.'_'.$key.'_displayResults_characteristicColumn_'.$characteristic->characteristic_value.'">'.$characteristic->characteristic_value.'</label>';
							}
							$customCheckboxes .= '</div>';
						}
						$db->setQuery('SELECT DISTINCT product_related_type FROM '.hikashop_table('product_related'));
						$relateds = $db->loadObjectList();
						if(is_array($relateds)){
							$customCheckboxes .= '<div id="action_'.$table->table.'_'.$key.'_displayResults_relatedColumn_div" class="hika_massaction_checkbox"><a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_displayResults_relatedColumn_div\',\'check\');">'.JText::_('SELECT_ALL').'</a> / <a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_displayResults_relatedColumn_div\',\'uncheck\');">'.JText::_('UNSELECT_ALL').'</a><br/>';
							$displayed = array();
							foreach($relateds as $related){
								$checked='';
								if(isset($value->data['related'][$related->product_related_type])){
									$checked='checked="checked"';
								}
								if(!in_array($related->product_related_type, $displayed))
									$customCheckboxes .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_displayResults_relatedColumn_'.$related->product_related_type.'" name="action['.$table->table.']['.$key.'][displayResults][related]['.$related->product_related_type.']" value="'.$related->product_related_type.'" />'.
										'<label for="action_'.$table->table.'_'.$key.'_displayResults_relatedColumn_'.$related->product_related_type.'">'.
											$related->product_related_type.
										'</label>';
								$displayed[] = $related->product_related_type;
							}
							$customCheckboxes .= '</div>';
						}

						break;
					case 'user':
						$checked='';
						if(isset($value->data['usergroups'])){
							$checked='checked="checked"';
						}
						$customCheckboxes .= '<div class="hika_massaction_checkbox">';
						$customCheckboxes .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_displayResults_usergroupsColumn_title" name="action['.$table->table.']['.$key.'][displayResults][usergroups][title]" value="usergroups" />'.
							'<label for="action_'.$table->table.'_'.$key.'_displayResults_usergroupsColumn_title">'.JText::_('GROUP_NAME').
						'</label>';
						$customCheckboxes .= '</div>';
						break;
				}

				$output .= $customCheckboxes;
				$output .= '<div style="clear:both;"></div>';

				$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);

			}


			$loadedData->massaction_actions['__num__'] = new stdClass();
			$loadedData->massaction_actions['__num__']->type = $table->table;
			$loadedData->massaction_actions['__num__']->data = array('path'=>'');
			$loadedData->massaction_actions['__num__']->name = 'exportCsv';
			$loadedData->massaction_actions['__num__']->html = '';
			foreach($loadedData->massaction_actions as $key => &$value) {
				if($value->name != 'exportCsv' || ($table->table != $loadedData->massaction_table && is_int($key)))
					continue;
				if(!isset($value->data['formatExport']['path'])) $value->data['formatExport']['path'] = '';
				if(!isset($value->data['formatExport']['email'])) $value->data['formatExport']['email'] = '';
				$output='';
				$margin = '';
				$customCheckboxes='';
				foreach($dispTables as $relatedTable){
					if(!HIKASHOP_J30){
						if(preg_match('/joomla_/',$relatedTable)) $fieldsTable = $db->getTableFields('#__'.str_replace('joomla_','',$relatedTable));
						else $fieldsTable = $db->getTableFields('#__hikashop_'.$relatedTable);
						$fields = reset($fieldsTable);
					} else {
						if(preg_match('/joomla_/',$relatedTable)) $fields = $db->getTableColumns('#__'.str_replace('joomla_','',$relatedTable));
						else $fields = $db->getTableColumns('#__hikashop_'.$relatedTable);
					}
					if(!empty($fields)) {
						$output .= '<div id="action_'.$table->table.'_'.$key.'_exportCsv_'.$relatedTable.'Column_div" class="hika_massaction_checkbox"> <a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_exportCsv_'.$relatedTable.'Column_div\',\'check\');">'.JText::_('SELECT_ALL').'</a> / <a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_exportCsv_'.$relatedTable.'Column_div\',\'uncheck\');">'.JText::_('UNSELECT_ALL').'</a> <br/>';

						foreach($fields as $key2 => $field){
							if($key2 == 'address_state'){
								$fields['zone_state_name'] = $field;
								$fields['zone_state_name_english'] = $field;
								$fields['zone_state_code_2'] = $field;
								$fields['zone_state_code_3'] = $field;
							}elseif($key2 == 'address_country'){
								$fields['zone_country_name'] = $field;
								$fields['zone_country_name_english'] = $field;
								$fields['zone_country_code_2'] = $field;
								$fields['zone_country_code_3'] = $field;
							}
						}
						ksort($fields);
						foreach($fields as $key2 => $field){
							$checked='';
							if(isset($value->data[$relatedTable]) && isset($value->data[$relatedTable][$key2])){
								$checked='checked="checked"';
							}
							$output .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_exportCsv_'.$relatedTable.'Column_'.$key2.'" name="action['.$table->table.']['.$key.'][exportCsv]['.$relatedTable.']['.$key2.']" value="'.$key2.'" />'.
								'<label for="action_'.$table->table.'_'.$key.'_exportCsv_'.$relatedTable.'Column_'.$key2.'">'.$key2.'</label>';
						}

						if($relatedTable == 'order'){
							$key2 = 'order_full_tax';
							if(isset($value->data[$relatedTable]) && isset($value->data[$relatedTable][$key2])){
								$checked='checked="checked"';
								$output .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_exportCsv_'.$relatedTable.'Column_'.$key2.'" name="action['.$table->table.']['.$key.'][exportCsv]['.$relatedTable.']['.$key2.']" value="'.$key2.'" />'.
									'<label for="action_'.$table->table.'_'.$key.'_exportCsv_'.$relatedTable.'Column_'.$key2.'">'.$key2.'</label>';
							}

							$key2 = 'order_tax_amount';
							$checked='';
							if(isset($value->data[$relatedTable]) && isset($value->data[$relatedTable][$key2])){
								$checked='checked="checked"';
							}
							$output .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_exportCsv_'.$relatedTable.'Column_'.$key2.'" name="action['.$table->table.']['.$key.'][exportCsv]['.$relatedTable.']['.$key2.']" value="'.$key2.'" />'.
								'<label for="action_'.$table->table.'_'.$key.'_exportCsv_'.$relatedTable.'Column_'.$key2.'">'.$key2.'</label>';
						}
						if($relatedTable == 'price'){
							$checked='';
							if(isset($value->data[$relatedTable]) && !empty($value->data[$relatedTable]['price_value_with_tax'])){
								$checked='checked="checked"';
							}
							$output .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_exportCsv_'.$relatedTable.'Column_price_value_with_tax" name="action['.$table->table.']['.$key.'][exportCsv]['.$relatedTable.'][price_value_with_tax]" value="price_value_with_tax" />'.
								'<label for="action_'.$table->table.'_'.$key.'_exportCsv_'.$relatedTable.'Column_price_value_with_tax">price_value_with_tax</label>';
						}
						$output .= '</div>';
						$margin = 'margin-left: 20px;';
					}
				}

				switch($table->table){
					case 'product':
						$db->setQuery('SHOW COLUMNS FROM '.hikashop_table('file'));
						$imageColumns = $db->loadObjectList();
						$customCheckboxes .= '<div id="action_'.$table->table.'_'.$key.'_exportCsv_filesColumn_div" class="hika_massaction_checkbox"><a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_exportCsv_filesColumn_div\',\'check\');">'.JText::_('SELECT_ALL').'</a> / <a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_exportCsv_filesColumn_div\',\'uncheck\');">'.JText::_('UNSELECT_ALL').'</a><br/><b>'.JText::_('FILES').'</b><br/>';
						foreach($imageColumns as $imageColumn){
							$checked='';
							if(isset($value->data['files'][$imageColumn->Field])){
								$checked='checked="checked"';
							}
							$customCheckboxes .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_exportCsv_filesColumn_'.$imageColumn->Field.'" name="action['.$table->table.']['.$key.'][exportCsv][files]['.$imageColumn->Field.']" value="'.$imageColumn->Field.'" />'.
								'<label for="action_'.$table->table.'_'.$key.'_exportCsv_filesColumn_'.$imageColumn->Field.'">'.$imageColumn->Field.'</label>';
						}
						$customCheckboxes .= '</div>';

						$db->setQuery('SHOW COLUMNS FROM '.hikashop_table('file'));
						$imageColumns = $db->loadObjectList();
						$customCheckboxes .= '<div id="action_'.$table->table.'_'.$key.'_exportCsv_imagesColumn_div" class="hika_massaction_checkbox"><a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_exportCsv_imagesColumn_div\',\'check\');">'.JText::_('SELECT_ALL').'</a> / <a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_exportCsv_imagesColumn_div\',\'uncheck\');">'.JText::_('UNSELECT_ALL').'</a><br/><b>'.JText::_('HIKA_IMAGES').'</b><br/>';
						foreach($imageColumns as $imageColumn){
							$checked='';
							if(isset($value->data['images'][$imageColumn->Field])){
								$checked='checked="checked"';
							}
							$customCheckboxes .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_exportCsv_imagesColumn_'.$imageColumn->Field.'" name="action['.$table->table.']['.$key.'][exportCsv][images]['.$imageColumn->Field.']" value="'.$imageColumn->Field.'" />'.
								'<label for="action_'.$table->table.'_'.$key.'_exportCsv_imagesColumn_'.$imageColumn->Field.'">'.$imageColumn->Field.'</label>';
						}
						$customCheckboxes .= '</div>';

						$db->setQuery('SELECT * FROM '.hikashop_table('characteristic').' WHERE characteristic_parent_id = 0');
						$characteristics = $db->loadObjectList();
						if(is_array($characteristics)){
							$customCheckboxes .= '<div id="action_'.$table->table.'_'.$key.'_exportCsv_characteristicColumn_div" class="hika_massaction_checkbox"><a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_exportCsv_characteristicColumn_div\',\'check\');">'.JText::_('SELECT_ALL').'</a> / <a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_exportCsv_characteristicColumn_div\',\'uncheck\');">'.JText::_('UNSELECT_ALL').'</a><br/>';
							foreach($characteristics as $characteristic){
								$checked='';
								if(isset($value->data['characteristic'][$characteristic->characteristic_value])){
									$checked='checked="checked"';
								}
								$customCheckboxes .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_exportCsv_characteristicColumn_'.$characteristic->characteristic_value.'" name="action['.$table->table.']['.$key.'][exportCsv][characteristic]['.$characteristic->characteristic_value.']" value="'.$characteristic->characteristic_value.'" />'.
									'<label for="action_'.$table->table.'_'.$key.'_exportCsv_characteristicColumn_'.$characteristic->characteristic_value.'">'.$characteristic->characteristic_value.'</label>';
							}
							$customCheckboxes .= '</div>';
						}
						$db->setQuery('SELECT * FROM '.hikashop_table('product_related'));
						$relateds = $db->loadObjectList();
						if(is_array($relateds)){
							$customCheckboxes .= '<div id="action_'.$table->table.'_'.$key.'_exportCsv_relatedColumn_div" class="hika_massaction_checkbox"><a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_exportCsv_relatedColumn_div\',\'check\');">'.JText::_('SELECT_ALL').'</a> / <a style="cursor: pointer;" onclick="checkAll(\'action_'.$table->table.'_'.$key.'_exportCsv_relatedColumn_div\',\'uncheck\');">'.JText::_('UNSELECT_ALL').'</a><br/>';
							$displayed = array();
							foreach($relateds as $related){
								$checked='';
								if(isset($value->data['related'][$related->product_related_type])){
									$checked='checked="checked"';
								}
								if(!in_array($related->product_related_type, $displayed))
									$customCheckboxes .= '<br/><input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_exportCsv_relatedColumn_'.$related->product_related_type.'" name="action['.$table->table.']['.$key.'][exportCsv][related]['.$related->product_related_type.']" value="'.$related->product_related_type.'" />'.
									'<label for="action_'.$table->table.'_'.$key.'_exportCsv_relatedColumn_'.$related->product_related_type.'">'.$related->product_related_type.'</label>';
								$displayed[] = $related->product_related_type;
							}
							$customCheckboxes .= '</div>';
						}

						break;
					case 'user':
						$checked='';
						if(isset($value->data['usergroups'])){
							$checked='checked="checked"';
						}
						$customCheckboxes .= '<div class="hika_massaction_checkbox">';
						$customCheckboxes .= '<input type="checkbox" '.$checked.' id="action_'.$table->table.'_'.$key.'_exportCsv_usergroupsColumn_title" name="action['.$table->table.']['.$key.'][exportCsv][usergroups][title]" value="usergroups" />'.
						'<label for="action_'.$table->table.'_'.$key.'_exportCsv_usergroupsColumn_title">'.JText::_('GROUP_NAME').'</label>';
						$customCheckboxes .= '</div>';
						break;
				}

				$output .= $customCheckboxes;
				$output .= '<div style="clear:both;"></div>';
				$checked='';
				if(isset($value->data['formatExport']['format']) && $value->data['formatExport']['format']=='xls')
					$checked='checked="checked"';
				$checked2='';
				if(isset($value->data['formatExport']['format']) && $value->data['formatExport']['format']=='xlsx')
					$checked2='checked="checked"';
				$output .='
				<input type="radio" id="action'.$table->table.''.$key.'exportCsvformatExportformatcsv" name="action['.$table->table.']['.$key.'][exportCsv][formatExport][format]" checked value="csv"> 
				<label for="action'.$table->table.''.$key.'exportCsvformatExportformatcsv">'.JText::_('CSV').'</label> 
				<input type="radio" id="action'.$table->table.''.$key.'exportCsvformatExportformatxls" name="action['.$table->table.']['.$key.'][exportCsv][formatExport][format]" '.$checked.' value="xls"> 
				<label for="action'.$table->table.''.$key.'exportCsvformatExportformatxls">'.JText::_('XLS').'</label> 
				<input type="radio" id="action'.$table->table.''.$key.'exportCsvformatExportformatxlsx" name="action['.$table->table.']['.$key.'][exportCsv][formatExport][format]" '.$checked2.' value="xlsx"> 
				<label for="action'.$table->table.''.$key.'exportCsvformatExportformatxlsx">'.JText::_('XLSX').'</label>';

				if($table->table == 'order') {
					$checked='';
					if(isset($value->data['formatExport']['oneProductPerRow']))
						$checked='checked="checked"';
					$output .= '<br/>'.'<input type="checkbox" id="action'.$table->table.''.$key.'exportCsvOneProductPerRow" name="action['.$table->table.']['.$key.'][exportCsv][formatExport][oneProductPerRow]" '. $checked.' value="1"> <label for="action'.$table->table.''.$key.'exportCsvOneProductPerRow">'.JText::_('ONE_PRODUCT_PER_ROW').'</label>';
				}
				$output .= '<br/>'.JText::_('EXPORT_PATH').': <input type="text" name="action['.$table->table.']['.$key.'][exportCsv][formatExport][path]" value="'.$value->data['formatExport']['path'].'" />';
				$output .= '<br/>'.JText::_('TO_ADDRESS').': <input type="text" name="action['.$table->table.']['.$key.'][exportCsv][formatExport][email]" value="'.$value->data['formatExport']['email'].'" /> '.JText::_('FILL_PATH_TO_USE_EMAIL');
				$output .= '<div style="clear:both;"></div>';

				$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
			}


			$loadedData->massaction_actions['__num__'] = new stdClass();
			$loadedData->massaction_actions['__num__']->type = $table->table;
			$loadedData->massaction_actions['__num__']->data = array('type' => '','value' => '','operation' => '');
			$loadedData->massaction_actions['__num__']->name = 'updateValues';
			$loadedData->massaction_actions['__num__']->html = '';

			foreach($loadedData->massaction_actions as $key => &$value) {
				if($value->name != 'updateValues' || ($table->table != $loadedData->massaction_table && is_int($key)))
					continue;

				$output='';
				$typeField = array();
				foreach($updTables as $relatedTable){
					if(!HIKASHOP_J30){
						if(preg_match('/joomla_/',$relatedTable)){
							$fieldsTable = $db->getTableFields('#__'.str_replace('joomla_','',$relatedTable));
							$fields = reset($fieldsTable);
							foreach($fields as $key2 => $field){
								$fields[$relatedTable.'_'.$key2] = $fields[$key2];
								unset($fields[$key2]);
							}
							$fieldsTable = $fields;
						}
						else{
							$fieldsTable = $db->getTableFields('#__hikashop_'.$relatedTable);
							$fields = reset($fieldsTable);
						}
					} else {
						if(preg_match('/joomla_/',$relatedTable)){
							$fields = $db->getTableColumns('#__'.str_replace('joomla_','',$relatedTable));
							foreach($fields as $key2 => $field){
								$fields[$relatedTable.'_'.$key2] = $fields[$key2];
								unset($fields[$key2]);
							}
						}
						else $fields = $db->getTableColumns('#__hikashop_'.$relatedTable);
					}
					ksort($fields);
					$typeField[] = JHTML::_('select.option', '<OPTGROUP>',JText::_(strtoupper($relatedTable)));
					if(!empty($fields)) {
						foreach($fields as $key2 => $field){
							$typeField[] = JHTML::_('select.option',$key2,$key2);
						}
					}
					$typeField[] = JHTML::_('select.option', '</OPTGROUP>');
				}

				$selected1='';$selected2='';$selected3='';$selected4='';
				$operations=array('int', 'float', 'string', 'operation');
				$options='';
				foreach($operations as $op){
					$selected='';
					if($op==$value->data['operation'])
						$selected='selected="selected"';
					$options .='<option '.$selected.' value="'.$op.'">'.JText::_(strtoupper($op)).'</option>';
				}
				$output .= JHTML::_('select.genericlist', $typeField, "action[".$table->table."][".$key."][updateValues][type]", 'class="custom-select chzn-done not-processed"  size="1"', 'value', 'text', $value->data['type']);
				$output .= ' = <select class="custom-select chzn-done not-processed" onchange="if(this.value == \'operation\'){document.getElementById(\'updateValues_message\').style.display = \'inline\';}" name="action['.$table->table.']['.$key.'][updateValues][operation]">
														'.$options.'
													 </select>';
				$output .= ' <input class="inputbox" type="text" name="action['.$table->table.']['.$key.'][updateValues][value]" size="50" value="'. htmlspecialchars((string)$value->data['value'], ENT_COMPAT, 'UTF-8').'"  />';

				$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
			}



			$loadedData->massaction_actions['__num__'] = new stdClass();
			$loadedData->massaction_actions['__num__']->type = $table->table;
			$loadedData->massaction_actions['__num__']->name = 'deleteElements';
			$loadedData->massaction_actions['__num__']->html = '';

			foreach($loadedData->massaction_actions as $key => &$value) {
				if($value->name != 'deleteElements' || ($table->table != $loadedData->massaction_table && is_int($key)))
					continue;

				$output = JText::_('DELETE_FILTERED_ELEMENTS'); //'This will delete all the elements returned in the filter.';
				$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);

			}


			$loadedData->massaction_actions['__num__'] = new stdClass();
			$loadedData->massaction_actions['__num__']->type = $table->table;
			$loadedData->massaction_actions['__num__']->data = array('emailAddress' => '','emailSubject' => '','bodyData' => '');
			$loadedData->massaction_actions['__num__']->name = 'sendEmail';
			$loadedData->massaction_actions['__num__']->html = '';

			foreach($loadedData->massaction_actions as $key => &$value) {
				if($value->name != 'sendEmail' || ($table->table != $loadedData->massaction_table && is_int($key)))
					continue;
				if(!isset($value->data['emailAddress'])) $value->data['emailAddress'] = '';
				if(!isset($value->data['emailSubject'])) $value->data['emailSubject'] = '';
				if(!isset($value->data['bodyData'])) $value->data['bodyData'] = '';
				$output .= '<br/>'.JText::_('TO_ADDRESS').': <input type="text" name="action['.$table->table.']['.$key.'][sendEmail][emailAddress]" value="'.$value->data['emailAddress'].'" />';
				$output .= '<br/>'.JText::_('EMAIL_SUBJECT').': <input type="text" name="action['.$table->table.']['.$key.'][sendEmail][emailSubject]" value="'.$value->data['emailSubject'].'" />';
				$output .= '<br/>'.JText::_('MASS_EMAIL_BODY_DATA').': <textarea name="action['.$table->table.']['.$key.'][sendEmail][bodyData]">'.$value->data['bodyData'].'</textarea>';
				$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);

			}

			if($table->table == 'order'){

				$loadedData->massaction_actions['__num__'] = new stdClass();
				$loadedData->massaction_actions['__num__']->type = $table->table;
				$loadedData->massaction_actions['__num__']->name = 'changeStatus';
				$loadedData->massaction_actions['__num__']->html = '';
				$loadedData->massaction_actions['__num__']->data = array('value' => '', 'notify' => '');

				foreach($loadedData->massaction_actions as $key => &$value) {
					if(($value->name != 'changeStatus' || ($table->table != $loadedData->massaction_table && is_int($key))))
						continue;

					$db->setQuery('SELECT `orderstatus_namekey` FROM '.hikashop_table('orderstatus'));
					$orderStatuses = $db->loadObjectList();

					$output='<div id="'.$table->table.'action'.$key.'changeStatus">';
					$output.= JText::_('NEW_ORDER_STATUS').': <select class="custom-select chzn-done not-processed" id="action_'.$table->table.'_'.$key.'_changeStatus_value" name="action['.$table->table.']['.$key.'][changeStatus][value]">';
					if(is_array($orderStatuses)){
						foreach($orderStatuses as $orderStatus){
							$orderStatus = $orderStatus->orderstatus_namekey;
							$selected='';
							if($orderStatus==@$value->data['value']){
								$selected='selected="selected"';
							}
							$output.='<option '.$selected.' value="'.$orderStatus.'">'.hikashop_orderStatus($orderStatus).'</option>';
						}
					}
					$checked='';
					if(isset($value->data['notify']) && $value->data['notify']==1){
						$checked='checked="checked"';
					}
					$output.='</select><input type="checkbox" '.$checked.' value="1" id="action_'.$table->table.'_'.$key.'_changeStatus_notify" name="action['.$table->table.']['.$key.'][changeStatus][notify]"/><label for="action_'.$table->table.'_'.$key.'_changeStatus_notify">'.JText::_('SEND_NOTIFICATION_EMAIL').'</label></div>';
					$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
				}


				$loadedData->massaction_actions['__num__'] = new stdClass();
				$loadedData->massaction_actions['__num__']->type = $table->table;
				$loadedData->massaction_actions['__num__']->name = 'addProduct';
				$loadedData->massaction_actions['__num__']->html = '';
				$loadedData->massaction_actions['__num__']->data = array('value' => '', 'type' => '', 'quantity' => '1');

				foreach($loadedData->massaction_actions as $key => &$value) {
					if($value->name != 'addProduct' || ($table->table != $loadedData->massaction_table && is_int($key)))
						continue;
					if(!isset($value->data['type'])) $value->data['type'] = 'add';
					$products=array();
					if(!empty($value->data) && !empty($value->data['value'])){
						hikashop_toInteger($value->data['value']);
						$query = 'SELECT product_id,product_name FROM '.hikashop_table('product').' WHERE product_id IN ('.implode(',',$value->data['value']).')';
						$database->setQuery($query);
						$products = $database->loadColumn();
					}
					if(!isset($value->data['quantity'])) $value->data['quantity'] = '1';

					$productSelect = $nameboxType->display(
						'action['.$table->table.']['.$key.'][addProduct][value]',
						$products,
						hikashopNameboxType::NAMEBOX_MULTIPLE,
						'product',
						array(
							'delete' => true,
							'default_text' => '<em>'.JText::_('HIKA_NONE').'</em>',
						)
					);

					$output ='<select class="custom-select chzn-done not-processed" id="action_'.$table->table.'_'.$key.'_addProduct_type" name="action['.$table->table.']['.$key.'][addProduct][type]">';
					$datas = array('add'=>'ADD', 'remove'=>'REMOVE');
					foreach($datas as $k => $data){
						$selected = '';
						if($k == $value->data['type']) $selected = 'selected="selected"';
						$output .='<option value="'.$k.'" '.$selected.'>'.JText::_($data).'</option>';
					}
					$output .='</select>';
					$output .='<input class="inputbox" type="text" name="action['.$table->table.']['.$key.'][addProduct][quantity]" size="10" value="'.$value->data['quantity'].'"  /> '.JText::_('PRODUCTS');
					$output .= $productSelect;
					if(isset($value->data['update'])) $checked = 'checked="checked"';
					else $checked = '';
					$output .= '<input type="checkbox" value="update" id="action_'.$table->table.'_'.$key.'_addProduct_update" name="action['.$table->table.']['.$key.'][addProduct][update]" '.$checked.'/><label for="action_'.$table->table.'_'.$key.'_addProduct_update">'.JText::_('UPDATE_PRODUCT_STOCK').'</label>';

					$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
				}
			}

			if($table->table == 'product'){


				$loadedData->massaction_actions['__num__'] = new stdClass();
				$loadedData->massaction_actions['__num__']->type = $table->table;
				$loadedData->massaction_actions['__num__']->name = 'updateCategories';
				$loadedData->massaction_actions['__num__']->html = '';
				$loadedData->massaction_actions['__num__']->data = array('value' => '', 'type' => '');

				foreach($loadedData->massaction_actions as $key => &$value) {
					if($value->name != 'updateCategories' || ($table->table != $loadedData->massaction_table && is_int($key)))
						continue;

					if(empty($value->data['type']))
						$value->data['type'] = 'add';

					$categories=array();
					if(!empty($value->data) && !empty($value->data['value'])){
						$query = 'SELECT category_id, category_name FROM '.hikashop_table('category').' WHERE category_id IN ('.implode(',', $value->data['value']).')';
						$database->setQuery($query);
						$categories = $database->loadColumn();
					}

					$categorySelect = $nameboxType->display(
						'action['.$table->table.']['.$key.'][updateCategories][value]',
						$categories,
						hikashopNameboxType::NAMEBOX_MULTIPLE,
						'category',
						array(
							'delete' => true,
							'default_text' => '<em>'.JText::_('HIKA_NONE').'</em>',
						)
					);

					$output ='<select id="action_'.$table->table.'_'.$key.'_updateCategories_type" class="custom-select select-listing chzn-done not-processed" name="action['.$table->table.']['.$key.'][updateCategories][type]">';
					$datas = array('add'=>'ADD', 'replace'=>'REPLACE','remove'=>'REMOVE');
					foreach($datas as $k => $data){
						$selected = '';
						if($k == $value->data['type']) $selected = 'selected="selected"';
						$output .='<option value="'.$k.'" '.$selected.'>'.JText::_($data).'</option>';
					}
					$output .='</select>';
					$output .= $categorySelect;

					$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
				}




				$loadedData->massaction_actions['__num__'] = new stdClass();
				$loadedData->massaction_actions['__num__']->type = $table->table;
				$loadedData->massaction_actions['__num__']->name = 'updateRelateds';
				$loadedData->massaction_actions['__num__']->html = '';
				$loadedData->massaction_actions['__num__']->data = array('value' => '', 'type' => '');


				foreach($loadedData->massaction_actions as $key => &$value) {
					if($value->name != 'updateRelateds' || ($table->table != $loadedData->massaction_table && is_int($key)))
						continue;

					$products=array();
					if(!empty($value->data) && !empty($value->data['value'])){
						hikashop_toInteger($value->data['value']);
						$query = 'SELECT product_id,product_name FROM '.hikashop_table('product').' WHERE product_id IN ('.implode(',',$value->data['value']).')';
						$database->setQuery($query);
						$products = $database->loadColumn();
					}
					$productSelect = $nameboxType->display(
						'action['.$table->table.']['.$key.'][updateRelateds][value]',
						$products,
						hikashopNameboxType::NAMEBOX_MULTIPLE,
						'product',
						array(
							'delete' => true,
							'default_text' => '<em>'.JText::_('HIKA_NONE').'</em>',
						)
					);

					$output ='<select class="custom-select chzn-done not-processed" id="action_'.$table->table.'_'.$key.'_updateRelateds_type" name="action['.$table->table.']['.$key.'][updateRelateds][type]">';
					$datas = array('add'=>'ADD', 'replace'=>'REPLACE');
					foreach($datas as $k => $data){
						$selected = '';
						if($k == $value->data['type']) $selected = 'selected="selected"';
						$output .='<option value="'.$k.'" '.$selected.'>'.JText::_($data).'</option>';
					}
					$output .='</select>';
					$output .= $productSelect;

					$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
				}



				$loadedData->massaction_actions['__num__'] = new stdClass();
				$loadedData->massaction_actions['__num__']->type = $table->table;
				$loadedData->massaction_actions['__num__']->name = 'updateOptions';
				$loadedData->massaction_actions['__num__']->html = '';
				$loadedData->massaction_actions['__num__']->data = array('value' => '', 'type' => '');


				foreach($loadedData->massaction_actions as $key => &$value) {
					if($value->name != 'updateOptions' || ($table->table != $loadedData->massaction_table && is_int($key)))
						continue;

					$options=array();
					if(!empty($value->data) && !empty($value->data['value'])){
						hikashop_toInteger($value->data['value']);
						$query = 'SELECT product_id,product_name FROM '.hikashop_table('product').' WHERE product_id IN ('.implode(',',$value->data['value']).')';
						$database->setQuery($query);
						$options = $database->loadColumn();
					}
					$productSelect = $nameboxType->display(
						'action['.$table->table.']['.$key.'][updateOptions][value]',
						$options,
						hikashopNameboxType::NAMEBOX_MULTIPLE,
						'product',
						array(
							'delete' => true,
							'default_text' => '<em>'.JText::_('HIKA_NONE').'</em>',
						)
					);

					$output ='<select class="custom-select chzn-done not-processed" id="action_'.$table->table.'_'.$key.'_updateOptions_type" name="action['.$table->table.']['.$key.'][updateOptions][type]">';
					$datas = array('add'=>'ADD', 'replace'=>'REPLACE');
					foreach($datas as $k => $data){
						$selected = '';
						if($k == $value->data['type']) $selected = 'selected="selected"';
						$output .='<option value="'.$k.'" '.$selected.'>'.JText::_($data).'</option>';
					}
					$output .='</select>';
					$output .= $productSelect;

					$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
				}



				$loadedData->massaction_actions['__num__'] = new stdClass();
				$loadedData->massaction_actions['__num__']->type = $table->table;
				$loadedData->massaction_actions['__num__']->name = 'updateCharacteristics';
				$loadedData->massaction_actions['__num__']->html = '';
				$loadedData->massaction_actions['__num__']->data = array('value' => '', 'type' => '');


				foreach($loadedData->massaction_actions as $key => &$value) {
					if($value->name != 'updateCharacteristics' || ($table->table != $loadedData->massaction_table && is_int($key)))
						continue;

					$characteristics=array();
					$query = 'SELECT * FROM '.hikashop_table('characteristic').' WHERE characteristic_parent_id = 0';
					$database->setQuery($query);
					$characteristics = $database->loadObjectList();

					if(!empty($characteristics)){
						$output ='<select class="custom-select chzn-done not-processed" id="action_'.$table->table.'_'.$key.'_updateCharacteristics_type" name="action['.$table->table.']['.$key.'][updateCharacteristics][type]">';
						$datas = array('add'=>'ADD', 'delete'=>'HIKA_DELETE');
						foreach($datas as $k => $data){
							$selected = '';
							if($k == $value->data['type']) $selected = 'selected="selected"';
							$output .='<option value="'.$k.'" '.$selected.'>'.JText::_($data).'</option>';
						}
						$output .='</select><br/><div class="hika_massaction_checkbox" >';
						if(!isset($value->data['value'])) $value->data['value'] = '';
						if(!is_array($value->data['value'])) $value->data['value'] = (array)$value->data['value'];
							foreach($characteristics as $characteristic){
								$checked = '';
								if(in_array($characteristic->characteristic_id,$value->data['value'])) $checked = 'checked="checked"';
								$output .= '<br/><input type="checkbox" name="action['.$table->table.']['.$key.'][updateCharacteristics][value][]" '.$checked.' value="'.$characteristic->characteristic_id.'" />'.$characteristic->characteristic_value;
							}
						$output .= '</div>';
					}else{
						$output = '<div class="alert">'.JText::_('MASSACTION_NO_CHARACTERISTICS').'</div>';
					}

					$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
				}


				$loadedData->massaction_actions['__num__'] = new stdClass();
				$loadedData->massaction_actions['__num__']->type = $table->table;
				$loadedData->massaction_actions['__num__']->name = 'setCanonical';
				$loadedData->massaction_actions['__num__']->html = '';
				$loadedData->massaction_actions['__num__']->data = array('value' => '', 'type' => '');

				foreach($loadedData->massaction_actions as $key => &$value) {
					if($value->name != 'setCanonical' || ($table->table != $loadedData->massaction_table && is_int($key)))
						continue;
					if(!isset($value->data['value'])) $value->data['value'] = '';
					$output = '<input type="text" id="action_'.$table->table.'_'.$key.'_setCanonical_value" name="action['.$table->table.']['.$key.'][setCanonical][value]" value="'.$value->data['value'].'">';
					$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
				}

			}

			if($table->table == 'order' || $table->table == 'user'){
				$loadedData->massaction_actions['__num__'] = new stdClass();
				$loadedData->massaction_actions['__num__']->type = $table->table;
				$loadedData->massaction_actions['__num__']->name = 'changeGroup';
				$loadedData->massaction_actions['__num__']->html = '';
				$loadedData->massaction_actions['__num__']->data = array('value' => '', 'type' => '');


				foreach($loadedData->massaction_actions as $key => &$value) {
					if($value->name != 'changeGroup' || ($table->table != $loadedData->massaction_table && is_int($key)))
						continue;
					if(!isset($value->data['type'])) $value->data['type'] = 'add';
					if(!isset($value->data['value'])) $value->data['value'] = '1';
					if(defined('HIKASHOP_WORDPRESS')) {
						$groups = array();
						$wp_roles = wp_roles();
						$rid = 1;
						foreach($wp_roles->role_names as $slug => $rname) {
							$group = new stdClass();
							$group->id = $rid;
							$group->title = translate_user_role($rname);
							$groups[] = $group;
							$rid++;
						}
					} else {
					$query = 'SELECT * FROM '.hikashop_table('usergroups',false);
					$database->setQuery($query);
					$groups = $database->loadObjectList();
					}

					$output ='<select class="custom-select chzn-done not-processed" id="action_'.$table->table.'_'.$key.'_changeGroup_type" name="action['.$table->table.']['.$key.'][changeGroup][type]">';
					$datas = array('add'=>'ADD', 'replace'=>'REPLACE','remove' =>'REMOVE');
					foreach($datas as $k => $data){
						$selected = '';
						if($k == $value->data['type']) $selected = 'selected="selected"';
						$output .='<option value="'.$k.'" '.$selected.'>'.JText::_($data).'</option>';
					}
					$output .='</select>';

					$output .= '<select class="custom-select chzn-done not-processed" id="action_'.$table->table.'_'.$key.'_changeGroup_value" name="action['.$table->table.']['.$key.'][changeGroup][value]">'; // categories
					foreach($groups as $group){
						$selected = '';
						if($group->id == $value->data['value']) $selected = 'selected="selected"';
						$output .='<option value="'.$group->id.'" '.$selected.'>'.JText::_($group->title).'</option>';
					}
					$output .= '</select>';

					$actions_html[$value->name] = $massactionClass->initDefaultDiv($value, $key, $type, $table->table, $loadedData, $output);
				}
			}
		}else{
			$actions_html['displayResults'] = '<div id="'.$table->table.'action__num__displayResults"></div>';
			$actions_html['exportCsv'] = '<div id="'.$table->table.'action__num__exportCsv"></div>';
			$actions_html['updateValues'] = '<div id="'.$table->table.'action__num__updateValues"></div>';
		}

		$js="
			function checkAll(id, type){
				var toCheck = document.getElementById(id).getElementsByTagName('input');
				for (i = 0 ; i < toCheck.length ; i++) {
					if (toCheck[i].type == 'checkbox') {
						if(type == 'check'){
							toCheck[i].checked = true;
						}else{
							toCheck[i].checked = false;
						}
					}
				}
			}";

		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration( "<!--\n".$js."\n//-->\n" );
	}

	function onReloadPageMassActionAfterEdition(&$reload){
		$reload['category']['category_id']='category_id';
		$reload['price']['price_product_id']='price_product_id';
		$reload['price']['price_currency_id']='price_currency_id';
		$reload['product']['product_dimension_unit']='product_dimension_unit';
		$reload['address']['address_id']='address_id';
		$reload['product'][]='';
		$reload['product'][]='';
	}

	function onSaveEditionSquareMassAction($data,$data_id,$table,$column,$value,$id,$type){
		$database = JFactory::getDBO();
		switch($data){
			case 'product':
			$class = hikashop_get('class.product');
				switch($table){
					case 'category':
						if($class->getProducts($data_id)){
							$object = $class->get($data_id);
						}
						$query = 'SELECT category_id';
						$query .= ' FROM '.hikashop_table('product_category');
						$query .= ' WHERE product_id='.$database->Quote($data_id).' AND category_id !='.$database->Quote($id);
						$database->setQuery($query);
						$tmp = $database->loadObjectList();
						$categories[$value] = $value;
						foreach($tmp as $val){
							$categories[$val->category_id] = $val->category_id;
						}
						unset($object->alias);
						$object->categories = $categories;
						$class->updateCategories($object,$data_id);
						break;
					case 'price':
						if($class->getProducts($data_id)){
							$object = $class->get($data_id);
						}
						$query = 'SELECT *';
						$query .= ' FROM '.hikashop_table('price');
						$query .= ' WHERE price_product_id='.$database->Quote($data_id);
						$database->setQuery($query);
						$prices = $database->loadObjectList();
						foreach($prices as $price){
							if($price->price_id == $id){
								$price->$column = $value;
							}
						}
						unset($object->alias);
						$object->prices = $prices;
						$class->updatePrices($object,$data_id);
						break;
					case 'characteristic':
						if($class->getProducts($data_id)){
							$object = $class->get($data_id);
						}
						$characteristics = array();

						$query = 'SELECT c1.characteristic_id as \'default_id\',c2.characteristic_id,c1.characteristic_parent_id,v2.ordering,c2.characteristic_value';
						$query .= ' FROM '.hikashop_table('variant').' AS v1';
						$query .= ' INNER JOIN '.hikashop_table('characteristic').' AS c1 ON c1.characteristic_id = v1.variant_characteristic_id';
						$query .= ' INNER JOIN '.hikashop_table('variant').' AS v2 ON c1.characteristic_parent_id = v2.variant_characteristic_id';
						$query .= ' INNER JOIN '.hikashop_table('characteristic').' AS c2 ON c2.characteristic_parent_id = c1.characteristic_parent_id';
						$query .= ' WHERE c1.characteristic_parent_id!=0 AND v1.variant_product_id='.$database->Quote($data_id);
						$database->setQuery($query);
						$results = $database->loadObjectList();

						foreach($results as $result){
							$test = false;
							foreach($characteristics as $charac){
								if($charac->characteristic_id == $result->characteristic_parent_id){
									$charac->values[$result->characteristic_id] = $result->characteristic_value;
									if($result->characteristic_value == $value){
										$charac->default_id = $result->characteristic_id;
									}
									$test = true;
								}
							}
							if(!$test){
								$tmp = new stdClass();
								$tmp->characteristic_id = $result->characteristic_parent_id;
								$tmp->ordering = $result->ordering;
								if($result->characteristic_value == $value){
									$tmp->default_id = $result->characteristic_id;
								}else{
									$tmp->default_id = $result->default_id;
								}
								$tmp->values[$result->characteristic_id] = $result->characteristic_value;
								$characteristics[] = $tmp;
								$object->oldCharacteristics[] = $result->characteristic_parent_id;
							}
						}
						foreach($characteristics as $characteristic){
							foreach($characteristic->values as $v=>$k){
								if($v == $value){
									$characteristic->default_id = $value;
								}
							}
						}
						$object->characteristics = $characteristics;
						$class->updateCharacteristics($object,$data_id);
						break;
					case 'product' :
						if($class->getProducts($data_id)){
							$object = $class->get($data_id);
						}
						unset($object->alias);
						$object->$column = $value;
						$class->save($object);
						break;

					case 'related' :
						if($class->getProducts($data_id)){
							$object = $class->get($data_id);
						}
						unset($object->alias);

						$query = 'SELECT product_related_id';
						$query .= ' FROM '.hikashop_table('product_related');
						$query .= ' WHERE product_id='.$database->Quote($data_id).' AND product_related_type = \'related\'';
						$database->setQuery($query);
						$results = $database->loadObjectList();
						$related = array();
						foreach($results as $result){
							if($result->product_related_id != $id){
								$related[$result->product_related_id] = new stdClass();
								$related[$result->product_related_id]->product_related_id = $result->product_related_id;
								$related[$result->product_related_id]->product_related_ordering = 0;
							}
						}
						$related[$value] = new stdClass();
						$related[$value]->product_related_id = $value;
						$related[$value]->product_related_ordering = 0;
						$object->related = $related;
						$class->updateRelated($object,$data_id,'related');
						break;
					case 'options' :
						if($class->getProducts($data_id)){
							$object = $class->get($data_id);
						}
						unset($object->alias);

						$query = 'SELECT product_related_id';
						$query .= ' FROM '.hikashop_table('product_related');
						$query .= ' WHERE product_id='.$database->Quote($data_id).' AND product_related_type = \'options\'';
						$database->setQuery($query);
						$results = $database->loadObjectList();
						$options = array();
						foreach($results as $result){
							if($result->product_related_id != $id){
								$options[$result->product_related_id] = new stdClass();
								$options[$result->product_related_id]->product_related_id = $result->product_related_id;
								$options[$result->product_related_id]->product_related_ordering = 0;
							}
						}
						$options[$value] = new stdClass();
						$options[$value]->product_related_id = $value;
						$options[$value]->product_related_ordering = 0;
						$object->options = $options;
						$class->updateRelated($object,$data_id,'options');
						break;
				}
				break;

			case 'category':
				$class = hikashop_get('class.category');
				switch($table){
					case 'category':
						$object = $class->get($data_id);
						if($object){
							$object->$column = $value;
							$class->save($object);
						}
						break;
				}
				break;
			case 'user':
				$class = hikashop_get('class.user');
				switch($table){
					case 'user':
						$object = $class->get($data_id);
						foreach($object as $key=>$element){
							if(!strstr($key,'user_')){
								unset($object->$key);
							}
						}
						if($object){
							$object->$column = $value;
							$class->save($object);
						}
						break;
					case 'address':
						$address = hikashop_get('class.address');
						$object = $address->get($id);
						$object->$column = $value;
						$address->save($object);
						break;
					case 'usergroup':
						die('Never');
						break;
					case 'joomla_users':
						if($column == 'joomla_users_id'){
							$column = 'id';
						}
						$user = JFactory::getUser($id);
						if(!empty($user)){
							$user->$column = $value;
							$user->save();
						}
						break;
				}
				break;

			case 'order':
				$class = hikashop_get('class.order');
				switch($table){
					case 'address':
						$order = $class->get($data_id);
						$address = hikashop_get('class.address');
						$object = $address->get($id);
						$object->$column = $value;

						if($order->order_shipping_address_id == $id && $order->order_billing_address_id == $id){
							$address->save($object);
							$class->save($order);
						}else if($order->order_shipping_address_id == $id){
							$order->order_shipping_address_id = $address->save($object,$data_id,'shipping');
							$class->save($order);
						}else if($order->order_billing_address_id == $id){
							$order->order_billing_address_id = $address->save($object,$data_id,'billing');
							$class->save($order);
						}
						break;
					case 'order_product':
						$info = $class->get($data_id);

						$query = 'SELECT *';
						$query .= ' FROM '.hikashop_table('order_product');
						$query .= ' WHERE order_product_id='.$database->Quote($id);
						$database->setQuery($query);
						$row = $database->loadObject();
						$object = new stdClass();
						$object->order_id = $data_id;
						$object->product = $row;
						$object->product->$column = $value;

						$history = new stdClass();
						$history->history_reason = JText::sprintf('MODIFICATION_USERS');
						$history->history_notified = '0';
						$history->history_type = 'modification';

						$object->history = $history;
						$class->save($object);
						break;
					case 'order' :
						$object = $class->get($data_id);
						if($object){
							if(isset($_POST['checkbox'])){
								$history->history_reason = JText::sprintf('MODIFICATION_USERS');
								$history->history_notified = '1';
								$history->history_type = 'modification';
							}
							$object->$column = $value;
							$class->save($object);
						}
						break;
					case 'payment':
						$query = 'SELECT payment_type';
						$query .= ' FROM '.hikashop_table('payment');
						$query .= ' WHERE payment_id='.$database->Quote($value);
						$database->setQuery($query);
						$row = $database->loadObject();

						$object = $class->get($data_id);
						$object->order_payment_id = $value;
						$object->order_payment_method = $row->payment_type;
						$history = new stdClass();
						$history->history_reason = JText::sprintf('MODIFICATION_USERS');
						$history->history_notified = '0';
						$history->history_type = 'modification';
						$object->history = $history;
						$class->save($object);

						break;
					case 'shipping':
						$query = 'SELECT shipping_type';
						$query .= ' FROM '.hikashop_table('shipping');
						$query .= ' WHERE shipping_id='.$database->Quote($value);
						$database->setQuery($query);
						$row = $database->loadObject();

						$object = $class->get($data_id);
						$object->order_shipping_id = $value;
						$object->order_shipping_method = $row->shipping_type;
						$history = new stdClass();
						$history->history_reason = JText::sprintf('MODIFICATION_USERS');
						$history->history_notified = '0';
						$history->history_type = 'modification';
						$object->history = $history;
						$class->save($object);

						break;

						case 'user':
							die('Never');
							break;
						case 'joomla_users':
							die('Never');
							break;
					}
				break;

			case 'address':
				$class = hikashop_get('class.address');
				switch($table){
					case 'address':
						$object = $class->get($data_id);
						$object->$column = $value;
						$class->save($object);
						break;
					case 'user':
						$user = hikashop_get('class.user');
						$object = $user->get($id);
						foreach($object as $key=>$element){
							if(!strstr($key,'user_')){
								unset($object->$key);
							}
						}
						if($object){
							$object->$column = $value;
							$user->save($object,true);
						}

						break;

					case 'joomla_users':
						if($column == 'joomla_users_id'){
							$column = 'id';
						}
						$user = JFactory::getUser($id);
						if(!empty($user)){
							$user->$column = $value;
							$user->save();
						}
						break;
				}
				break;
		}
	}

	function onLoadDatatMassActionBeforeEdition($data,$data_id,$table,$column,$type,$ids,&$query,&$view){
		$database = JFactory::getDBO();
		hikashop_toInteger($ids);
		switch($type){
			case 'price':
				$query = 'SELECT '.$column.','.$table.'_id';
				$query .= ' FROM '.hikashop_table($table);
				$query .= ' WHERE '.$table.'_id IN ('.implode(',',$ids).')';

				break;

			case 'joomla_users':
				if($column == 'jommla_users_id'){
					$column = 'id';
				}
				$query = 'SELECT DISTINCT '.$column.', id as \'joomla_users_id\'';
				$query .= ' FROM '.hikashop_table('users',false);
				$query .= ' WHERE id IN ('.implode(',',$ids).')';
				break;

			case 'layout':
				$layout = hikashop_get('type.layout');
				$view->assignRef('layout',$layout);

				$query = 'SELECT '.$column.','.$table.'_id';
				$query .= ' FROM '.hikashop_table($table);
				$query .= ' WHERE '.$table.'_id IN ('.implode(',',$ids).')';
				break;

			case 'method_name':

				$query = 'SELECT '.$column.','.$table.'_id,'.$table.'_type';
				$query .= ' FROM '.hikashop_table($table);

				break;

			case 'usergroups':
				if(defined('HIKASHOP_WORDPRESS')) {
					$wp_roles = wp_roles();
					$rows = array();
					$rid = 1;
					foreach($wp_roles->role_names as $slug => $rname) {
						$obj = new stdClass();
						$obj->title = translate_user_role($rname);
						$obj->usergroups_id = $rid;
						$rows[] = $obj;
						$rid++;
					}
					$view->assignRef('rows', $rows);
					break;
				}

				$query = 'SELECT DISTINCT title, id as \'usergroups_id\'';
				$query .= ' FROM '.hikashop_table('usergroups',false);

				break;
			case 'status':
				$status = hikashop_get('type.categorysub');
				$status->type = 'status';
				$view->assignRef('status',$status);


				$query = 'SELECT '.$column.','.$table.'_id';
				$query .= ' FROM '.hikashop_table($table);
				$query .= ' WHERE '.$table.'_id IN ('.implode(',',$ids).')';

				break;
			case 'yesno':
				$query = 'SELECT '.$column.','.$table.'_id';
				$query .= ' FROM '.hikashop_table($table);
				$query .= ' WHERE '.$table.'_id IN ('.implode(',',$ids).')';

				break;
			case 'currency':
				$types = hikashop_get('type.currency');
				$view->assignRef('types',$types);

				$query = 'SELECT '.$column.','.$table.'_id';
				$query .= ' FROM '.hikashop_table($table);
				$query .= ' WHERE '.$table.'_id IN ('.implode(',',$ids).')';

				break;
			case 'dimension':
				$query = 'SELECT '.$column.','.$table.'_id';
				$query .= ' FROM '.hikashop_table($table);
				$query .= ' WHERE '.$table.'_id IN ('.implode(',',$ids).')';
				break;

			case 'dimension_unit':
				$volume = hikashop_get('type.volume');
				$view->assignRef('volume',$volume);
				$query = 'SELECT '.$column.','.$table.'_id';
				$query .= ' FROM '.hikashop_table($table);
				$query .= ' WHERE '.$table.'_id IN ('.implode(',',$ids).')';
				break;
			case 'weight':
				$query = 'SELECT '.$column.','.$table.'_id';
				$query .= ' FROM '.hikashop_table($table);
				$query .= ' WHERE '.$table.'_id IN ('.implode(',',$ids).')';
				break;

			case 'weight_unit':
				$weight = hikashop_get('type.weight');
				$view->assignRef('weight',$weight);
				$query = 'SELECT '.$column.','.$table.'_id';
				$query .= ' FROM '.hikashop_table($table);
				$query .= ' WHERE '.$table.'_id IN ('.implode(',',$ids).')';
				break;

			case 'characteristic':
				$query = 'SELECT DISTINCT c1.characteristic_id, c1.characteristic_value';
				$query .= ' FROM '.hikashop_table('characteristic').' AS c1';
				$query .= ' INNER JOIN '.hikashop_table('characteristic').' AS c2 ON c1.characteristic_parent_id = c2.characteristic_id';
				$query .= ' WHERE  c2.characteristic_value='.$database->Quote($column);
				break;
			case 'related' :
				$query = 'SELECT DISTINCT p.product_id as \'related_id\',p.product_name';
				$query .= ' FROM '.hikashop_table('product').' AS p';
				break;
			case 'options' :
				$query = 'SELECT DISTINCT p.product_id as \'options_id\',p.product_name';
				$query .= ' FROM '.hikashop_table('product').' AS p';
				break;
			case 'parent':
				$query = 'SELECT '.$column.','.$table.'_id, '.$table.'_name';
				$query .= ' FROM '.hikashop_table($table);
				$view->assignRef('ids', $ids);
				break;
			case 'id':
				$query = 'SELECT '.$column;
				if($table != 'price' && $table!='address'){
					$query .= ','.$table.'_name';
				}
				$query .= ' FROM '.hikashop_table($table);
				if($table == 'category'){
					$query .= ' WHERE category_type = \'product\' ';
				}
				$view->assignRef('ids', $ids);
				break;

			case 'sub_id':
				if(strstr($column, '_')!==false){
					$a = explode("_", $column);
				}
				if(strstr($column, 'partner')===false){
					foreach($a as $k=>$chaine){
						if($chaine === $table || $chaine === 'id'){
							unset($a[$k]);
						}
					}
					$table_tmp = implode('_',$a);
				}else{
					$table_tmp = 'user';
				}
				$column_tmp = $table_tmp.'_id';
				$query = 'SELECT '.$column_tmp.' as '.$column;
				$query .= ' FROM '.hikashop_table($table_tmp);
				$view->assignRef('ids', $ids);

				$view->table = 'order';

				break;

			default:
				$joomlaTable = true;
				if(preg_match('/joomla_/',$table)){
					$table = str_replace('joomla_','',$table);
					$joomlaTable = false;
				}

				if(strpos($type,'custom_') === 0){
					$f = substr_replace($view->type,'',0,7);
					$fields = hikashop_get('class.field');
					$view->assignRef('fields',$fields);
					$query = 'SELECT *';
					$query .= ' FROM '.hikashop_table($table,$joomlaTable);
					$query .= ' WHERE '.$table.'_id IN ('.implode(',',$ids).')';
					$database->setQuery($query);
					$elements = $database->loadObjectList();

					if($elements == null){
						die('Undefined row');
					}

					$view->assignRef('elements',$elements);

					$allFields = array();
					$column_id = $view->table.'_id';
					foreach($elements as $element){
						$f = $fields->getFields('backend',$element,$table,'user&task=state');
						if(preg_match('/joomla_users_/',$column_id)){
							$column_id = str_replace('joomla_users_','',$column_id);
						}
						$f['id'] = $element->$column_id;
						$allFields[] = $f;
					}

					$view->assignRef('allFields',$allFields);

					$query = 'SELECT '.$column.','.$table.'_id';
					$query .= ' FROM '.hikashop_table($table,$joomlaTable);
					$query .= ' WHERE '.$table.'_id IN ('.implode(',',$ids).')';
				}else if(!$joomlaTable){
					$query = 'SELECT '.$column.',id';
					$query .= ' FROM '.hikashop_table($table,$joomlaTable);
					$query .= ' WHERE id IN ('.implode(',',$ids).')';
					$view->assignRef('ids', $ids);
				}else{
					$query = 'SELECT '.$column.','.$table.'_id';
					$query .= ' FROM '.hikashop_table($table);
					$query .= ' WHERE '.$table.'_id IN ('.implode(',',$ids).')';
					$view->assignRef('ids', $ids);
				}
				break;
		}
	}

	function onLoadResultMassActionAfterEdition($data,$data_id,$table,$column,$type,$id,$value,&$query){
		$database = JFactory::getDBO();
		switch($data){
			case 'product':
				switch($table){
					case 'product':
						$query = 'SELECT '.$column.',product_id,product_dimension_unit';
						$query .= ' FROM '.hikashop_table('product');
						$query .= ' WHERE product_id = '.$database->Quote($id);

						break;

					case 'price':
						$query = 'SELECT '.$column.',price_currency_id, price_product_id, price_id';
						$query .= ' FROM '.hikashop_table('price');
						$query .= ' WHERE price_id ='.$database->Quote($id);

						break;

					case 'category':
						$query = 'SELECT '.hikashop_table('category').'.'.$column.','.hikashop_table('product_category').'.product_id,'.hikashop_table('category').'.category_id';
						$query .= ' FROM '.hikashop_table('category');
						$query .= ' INNER JOIN '.hikashop_table('product_category').' ON '.hikashop_table('product_category').'.category_id = '.hikashop_table('category').'.category_id';
						$query .= ' WHERE product_id = '.$database->Quote($id);

						break;

					case 'characteristic':

						$query = 'SELECT c1.characteristic_value,c1.characteristic_id';
						$query .= ' FROM '.hikashop_table('characteristic').' as c1';
						$query .= ' WHERE c1.characteristic_id ='.$database->Quote($value);

						break;
					case 'related':
						$query = 'SELECT p.product_id as \'related_id\',product_name,r.product_related_type';
						$query .= ' FROM '.hikashop_table('product').' AS p';
						$query .= ' INNER JOIN '.hikashop_table('product_related').' AS r ON r.product_related_id = p.product_id';
						$query .= ' WHERE r.product_id = '.$database->Quote($data_id).' AND r.product_related_type = \'related\'';

						$database->setQuery($query);
						$query = $database->loadObjectList();
						break;
					case 'options':
						$query = 'SELECT p.product_id as \'options_id\',product_name,r.product_related_type';
						$query .= ' FROM '.hikashop_table('product').' AS p';
						$query .= ' INNER JOIN '.hikashop_table('product_related').' AS r ON r.product_related_id = p.product_id';
						$query .= ' WHERE r.product_id = '.$database->Quote($data_id).' AND r.product_related_type = \'options\'';

						$database->setQuery($query);
						$query = $database->loadObjectList();
						break;
				}
				break;
			case 'user':
				switch($table){
					case 'user':
						$query = 'SELECT '.$column.',user_id';
						$query .= ' FROM '.hikashop_table('user');
						$query .= ' WHERE user_id = '.$database->Quote($id);
						break;

					case 'joomla_users':
						$query = 'SELECT DISTINCT '.$column.', id as \'joomla_users_id\'';
						$query .= ' FROM '.hikashop_table('users',false);
						$query .= ' WHERE id = '.$database->Quote($id);
						break;

					case 'usergroups':
						if(defined('HIKASHOP_WORDPRESS')) {
							$wp_roles = wp_roles();
							$rid = 1;
							foreach($wp_roles->role_names as $slug => $rname) {
								if($rid == (int)$id) {
									$obj = new stdClass();
									$obj->title = translate_user_role($rname);
									$obj->user_id = 0;
									$obj->usergroups_id = $rid;
									$query = array($obj);
									break;
								}
								$rid++;
							}
							break;
						}
						$query = 'SELECT DISTINCT usergroups.title, hk_user.user_id, usergroups.id as \'usergroups_id\'';
						$query .= ' FROM '.hikashop_table('usergroups',false).' AS usergroups';
						$query .= ' INNER JOIN '.hikashop_table('user_usergroup_map',false).' AS user_usergroup ON usergroups.id = user_usergroup.group_id';
						$query .= ' INNER JOIN '.hikashop_table('users',false).' AS user ON user.id = user_usergroup.user_id';
						$query .= ' INNER JOIN '.hikashop_table('user').' AS hk_user ON user.id = hk_user.user_cms_id';
						$query .= ' WHERE usergroups.id = '.$database->Quote($id);
						break;

					case 'address':
						$query = 'SELECT '.$column.', address_user_id, address_id';
						$query .= ' FROM '.hikashop_table('address');
						$query .= ' WHERE address_id = '.$database->Quote($id);
						break;
				}
				break;
			case 'category':
				switch($table){
					case 'category':
						$query = 'SELECT '.$column.',category_id';
						$query .= ' FROM '.hikashop_table('category');
						$query .= ' WHERE category_id = '.$database->Quote($id);
						break;
				}
			case 'order':
				switch($table){
					case 'order':
						$query = 'SELECT '.$column.', order_id,order_currency_id,order_partner_currency_id';
						$query .= ' FROM '.hikashop_table('order');
						$query .= ' WHERE order_id = '.$database->Quote($id);
						break;
					case 'order_product':
						$query = 'SELECT '.$column.', order_id, order_product_id';
						$query .= ' FROM '.hikashop_table('order_product');
						$query .= ' WHERE order_id = '.$database->Quote($id);
						break;
					case 'payment':
						$query = 'SELECT o.order_id,p.payment_name, p.payment_id';
						$query .= ' FROM '.hikashop_table('order').' as o';
						$query .= ' LEFT JOIN '.hikashop_table('payment').' as p ON o.order_payment_id = p.payment_id';
						$query .= ' WHERE p.payment_id = '.$database->Quote($value);
						break;
					case 'shipping':
						$query = 'SELECT o.order_id,s.shipping_name, s.shipping_id';
						$query .= ' FROM '.hikashop_table('order').' as o';
						$query .= ' LEFT JOIN '.hikashop_table('shipping').' as s ON o.order_shipping_id = s.shipping_id';
						$query .= ' WHERE s.shipping_id = '.$database->Quote($value);
						break;
					case 'address':
						$query = 'SELECT '.$column.', address_id';
						$query .= ' FROM '.hikashop_table('address');
						$query .= ' WHERE address_id ='.$database->Quote($id);
						break;
				}
				break;
			case 'address':
				switch($table){
					case 'user':
						$query = 'SELECT '.$column.', user_id, address_id';
						$query .= ' FROM '.hikashop_table('user').' AS user';
						$query .= ' INNER JOIN '.hikashop_table('address').' AS address ON user.user_id = address.address_user_id';
						$query .= ' WHERE user.user_id = '.$database->Quote($id);
						break;
					case 'joomla_users':
						$query = 'SELECT user.'.$column.', id as \'joomla_users_id\', address.address_id';
						$query .= ' FROM '.hikashop_table('users',false).' AS user';
						$query .= ' INNER JOIN '.hikashop_table('user').' AS hk_user ON user.id = hk_user.user_cms_id';
						$query .= ' INNER JOIN '.hikashop_table('address').' AS address ON hk_user.user_id = address.address_user_id';
						$query .= ' WHERE user.id = '.$database->Quote($id);
						break;
					case 'address':
						$query = 'SELECT a.'.$column.', a.address_id';
						$query .= ' FROM '.hikashop_table('address').' as a';
						$query .= ' WHERE a.address_id ='.$database->Quote($data_id);
						break;
				}
		}
	}

	function onBeforeMassactionUpdate(&$element){
		if(!empty($element->massaction_filters)){
			foreach($element->massaction_filters as $k => $filter){
				if($filter->name == 'csvImport'){
					if($element->massaction_filters[$k]->data['pathType'] == 'upload'){
						$importFile = hikaInput::get()->files->getVar('filter_product_'.$k.'_csvImport_upload', array(), 'array');
						$importHelper = hikashop_get('helper.import');
						$element->massaction_filters[$k]->data['path'] = $importHelper->importFromFile($importFile, false);
						$element->massaction_filters[$k]->data['pathType'] = 'path';
					}
				}
			}
		}
	}

	function onHikashopCronTrigger(&$messages){
		$config =& hikashop_config();
		$periods = array('minutes','hours','days','weeks','months','years');
		$massactionClass = hikashop_get('class.massaction');
		foreach($periods as $period){
			$last_trigger = $config->get('massaction_last_trigger_'.$period);
			$next_trigger = strtotime('+1 '.$period,(int)$last_trigger);
			if(time()<$next_trigger) continue;
			$pref = new stdClass();
			$key = 'massaction_last_trigger_'.$period;
			$pref->$key =  time();
			$config->save($pref);
			$massactionClass->_trigger('onHikashopCronTrigger'.ucfirst($period));
		}
		if(count($massactionClass->report)) $messages = array_merge($messages,$massactionClass->report);
	}
	function onProcessOrderMassActionphpCode(&$elements,&$action,$k) {
		$this->_processPHP($elements,$action);
	}
	function onProcessProductMassActionphpCode(&$elements,&$action,$k){
		$this->_processPHP($elements,$action);
	}
	function onProcessAddressMassActionphpCode(&$elements,&$action,$k){
		$this->_processPHP($elements,$action);
	}
	function onProcessUserMassActionphpCode(&$elements,&$action,$k){
		$this->_processPHP($elements,$action);
	}
	function onProcessCategoryMassActionphpCode(&$elements,&$action,$k){
		$this->_processPHP($elements,$action);
	}
	function _processPHP(&$elements, &$action) {
		if(!empty($action['code'])) {
			$blocked = array('exec', 'system', 'passthru', 'shell_exec', 'popen', 'proc_open', 'pcntl_exec', 'dl', 'putenv', 'ini_set', 'apache_setenv');
			foreach($blocked as $func) {
				if(preg_match('#(?<![\\w$>:])'.preg_quote($func, '#').'\\s*\\(#i', $action['code'])) {
					$app = JFactory::getApplication();
					$app->enqueueMessage(JText::sprintf('ACTION_PHP_CODE_IS_WRONG', 'Use of "'.$func.'" is not allowed in mass action PHP code.'), 'error');
					return;
				}
			}

			$db = JFactory::getDBO();
			foreach($elements as $e) {
				$attributes = get_object_vars($e);
				$code = $action['code'];
				foreach($attributes as $key => $value) {
					if(is_object($value) || is_array($value))
						continue;
					$code = str_replace('{'.$key.'}', (string)$value, $code);
				}
				try {
					eval($code);
				} catch (Error $e) {
					$app = JFactory::getApplication();
					$app->enqueueMessage(JText::sprintf('ACTION_PHP_CODE_IS_WRONG', $e->getMessage()) , 'error');
				}
			}
		}
	}

	function onProcessOrderMassActioncancelOperation(&$elements, &$action, $k) {
		$this->_processCancelOperation($elements, $action);
	}
	function onProcessProductMassActioncancelOperation(&$elements, &$action, $k) {
		$this->_processCancelOperation($elements, $action);
	}
	function onProcessAddressMassActioncancelOperation(&$elements, &$action, $k) {
		$this->_processCancelOperation($elements, $action);
	}
	function onProcessUserMassActioncancelOperation(&$elements, &$action, $k) {
		$this->_processCancelOperation($elements, $action);
	}
	function onProcessCategoryMassActioncancelOperation(&$elements, &$action, $k) {
		$this->_processCancelOperation($elements, $action);
	}
	function _processCancelOperation(&$elements, &$action) {
		if(empty($elements))
			return;

		hikashopMassactionClass::$cancelledStatic = true;
		hikashopMassactionClass::$cancelMessageStatic = isset($action['message']) ? $action['message'] : '';
	}

	function onProcessOrderMassActionsendHttp(&$elements,&$action,$k) {
		$this->_processHttp($elements,$action);
	}
	function onProcessProductMassActionsendHttp(&$elements,&$action,$k){
		$this->_processHttp($elements,$action);
	}
	function onProcessAddressMassActionsendHttp(&$elements,&$action,$k){
		$this->_processHttp($elements,$action);
	}
	function onProcessUserMassActionsendHttp(&$elements,&$action,$k){
		$this->_processHttp($elements,$action);
	}
	function onProcessCategoryMassActionsendHttp(&$elements,&$action,$k){
		$this->_processHttp($elements,$action);
	}
	function _processHttp(&$elements, &$action) {
		if(empty($action['url'])) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('URL_MISSING_IN_ACTION') , 'error');
			return;
		}
		$headers = array();
		if(!empty($action['authentication'])) {
			switch($action['authentication']) {
				case 'basic':
					if(empty($action['username']) || empty($action['password'])) {
						$app = JFactory::getApplication();
						$app->enqueueMessage(JText::_('BASIC_AUTHENTICATION_REQUIRES_USERNAME_AND_PASSWORD') , 'error');
						return;
					}
					$headers[] = 'Authorization: Basic '.base64_encode($action['username'].':'.$action['password']);
					break;
				case 'oauth2':
					if(empty($action['token_url'])) {
						$app = JFactory::getApplication();
						$app->enqueueMessage(JText::_('OAUTH2_AUTHENTICATION_REQUIRES_TOKEN_URL') , 'error');
						return;
					}
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $action['token_url']);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		  			curl_setopt($ch, CURLOPT_CAINFO, __DIR__.'/cacert.pem');
		 			curl_setopt($ch, CURLOPT_CAPATH, __DIR__.'/cacert.pem');
					$token_headers = array();
					if(!empty($action['token_headers'])) {
						$lines = explode("\n", $action['token_headers']);
						foreach($lines as $line) {
							$line = trim($line);
							if(empty($line))
								continue;
							$token_headers[] = $line;
						}
					}
					curl_setopt($ch, CURLOPT_HTTPHEADER, $token_headers);
					$method = 'GET';
					if(!empty($action['token_method'])) {
						$method = strtoupper($action['token_method']);
					}
					if($method == 'POST') {
						curl_setopt($ch, CURLOPT_POST, true);
						if(!empty($action['token_post'])) {
							$body = $action['token_post'];
							curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
						}
					}
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
					$response = curl_exec($ch);
					$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					if($http_code != 200) {
						$app = JFactory::getApplication();
						$app->enqueueMessage(JText::sprintf('ACTION_OAUTH2_TOKEN_REQUEST_FAILED', $http_code . $response) , 'error');
						return;
					}
					if(empty($response)) {
						$app = JFactory::getApplication();
						$app->enqueueMessage(JText::_('ACTION_OAUTH2_TOKEN_REQUEST_RETURNED_EMPTY_RESPONSE') , 'error');
						return;
					}
					$regex = '"access_token"\s*:\s*"([^"]+)"';
					if(!empty($action['token_regex_extract'])) {
						$regex = $action['token_regex_extract'];
					}
					preg_match('/'.$regex.'/', $response, $matches);
					if(empty($matches[1])) {
						$app = JFactory::getApplication();
						$app->enqueueMessage(JText::sprintf('ACTION_OAUTH2_TOKEN_NOT_FOUND_IN_RESPONSE', $response) , 'error');
						return;
					}
					$token = $matches[1];
					$headers[] = 'Authorization: Bearer '.$token;
					break;
				case 'none':
				default:
					break;
			}
		}
		foreach($elements as $e) {
			$attributes = get_object_vars($e);
			$url = $action['url'];
			$headers_raw = $action['headers'];
			$post = $action['post'];
			preg_match_all('/\{([a-zA-Z0-9_\.]+)(?:\|([a-zA-Z0-9_]+)(?:\((.*?)\))?)?\}/', $url . $headers_raw . $post, $matches, PREG_SET_ORDER);

			foreach ($matches as $match) {
				$tag = $match[1]; // e.g., "key" or "key.subkey"
				$format = isset($match[2]) ? $match[2] : null; // e.g., "uppercase", "lowercase", etc.
				$params = isset($match[3]) ? $match[3] : null; // e.g., parameters for the format

				$parts = explode('.', $tag);
				$value = $attributes;

				foreach ($parts as $part) {
					if (is_object($value) && isset($value->$part)) {
						$value = $value->$part;
					} elseif (is_array($value) && isset($value[$part])) {
						$value = $value[$part];
					} else {
						$value = null;
						break;
					}
				}

				if ($value !== null) {
					if ($format) {
						switch ($format) {
							case 'uppercase':
								$value = strtoupper($value);
								break;
							case 'lowercase':
								$value = strtolower($value);
								break;
							case 'number':
								$decimals = is_numeric($params) ? (int)$params : 0;
								$value = number_format($value, $decimals, '.', '');
								break;
							case 'date':
								$dateFormat = $params ?: 'Y-m-d';
								$value = date($dateFormat, $value);
								break;
							case 'urlencode':
								$value = urlencode($value);
								break;
							case 'currency':
								$db = JFactory::getDBO();
								$query = 'SELECT currency_code FROM '.hikashop_table('currency').' WHERE currency_id='.(int)$value;
								$db->setQuery($query);
								$value = $db->loadResult();
								break;
							case 'zone':
								if(empty($params)) $params = 'zone_name';
								$db = JFactory::getDBO();
								$query = 'SELECT '.$db->quoteName($params).' FROM '.hikashop_table('zone').' WHERE zone_namekey='.$db->quote($value);
								$db->setQuery($query);
								$value = $db->loadResult();
								break;
							default:
								break;
						}
					}


					$url = str_replace($match[0], urlencode((string)$value), $url);

					$post = str_replace($match[0], str_replace('"', '\"', (string)$value), $post);
					$headers_raw = str_replace($match[0], (string)$value, $headers_raw);
					$post = str_replace($match[0], (string)$value, $post);
				} else {
					$url = str_replace($match[0], '', $url);
					$headers_raw = str_replace($match[0], '', $headers_raw);
					$post = str_replace($match[0], '', $post);
				}
			}
			if(!empty($headers_raw)) {
				$lines = explode("\n", $headers_raw);
				foreach($lines as $line) {
					$line = trim($line);
					if(empty($line))
						continue;
					$headers[] = $line;
				}
			}
			$method = 'GET';
			if(!empty($action['method'])) {
				$method = strtoupper($action['method']);
			}
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  			curl_setopt($ch, CURLOPT_CAINFO, __DIR__.'/cacert.pem');
 			curl_setopt($ch, CURLOPT_CAPATH, __DIR__.'/cacert.pem');
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			if(!empty($post)) {
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			}
			$response = curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if($http_code != 200) {
				$app = JFactory::getApplication();
				$app->enqueueMessage(JText::sprintf('ACTION_HTTP_REQUEST_FAILED', $http_code . $response) , 'error');
			}
			new dbug($http_code . ' ' . $response); // For debug purposes
		}
	}

	function onProcessOrderMassActionmysqlQuery(&$elements,&$action,$k) {
		$this->_processSQL($elements,$action);
	}
	function onProcessProductMassActionmysqlQuery(&$elements,&$action,$k){
		$this->_processSQL($elements,$action);
	}
	function onProcessAddressMassActionmysqlQuery(&$elements,&$action,$k){
		$this->_processSQL($elements,$action);
	}
	function onProcessUserMassActionmysqlQuery(&$elements,&$action,$k){
		$this->_processSQL($elements,$action);
	}
	function onProcessCategoryMassActionmysqlQuery(&$elements,&$action,$k){
		$this->_processSQL($elements,$action);
	}
	function _processSQL(&$elements, &$action) {
		if(!empty($action['query'])) {
			$db = JFactory::getDBO();
			foreach($elements as $e) {
				$attributes = get_object_vars($e);
				$query = $action['query'];
				foreach($attributes as $key => $value) {
					if(is_object($value) || is_array($value))
						continue;
					$query = str_replace('{'.$key.'}', (string)$value, $query);
				}
				$db->setQuery($query);
				$db->execute();
			}
		}
	}


	function _executeMcpAction($massaction, $trigger, $arguments) {
		$database = JFactory::getDBO();

		$filters = array();

		if(!empty($trigger->data['args']) && is_array($arguments)) {
			foreach($trigger->data['args'] as $mapping) {
				if(empty($mapping['name']) || empty($mapping['filter'])) continue;
				if(isset($arguments[$mapping['name']])) {
					$val = $database->Quote($arguments[$mapping['name']]);
					$filters[] = $database->quoteName($mapping['filter']) . ' = ' . $val;
				}
			}
		}


		$where = '';
		if(count($filters) > 0) {
			$where = ' WHERE '.implode(' AND ', $filters);
		}

		$limit = 50; 
		$query = 'SELECT * FROM ' . $database->quoteName('#__hikashop_'.$massaction->massaction_table) . $where;

		$database->setQuery($query, 0, $limit);
		$elements = $database->loadObjectList();

		if(empty($elements)) {
			return new \Mcp\Schema\Result\CallToolResult([
				new \Mcp\Schema\Content\TextContent("No elements found matching the criteria.")
			]);
		}

		$massactionClass = hikashop_get('class.massaction');
		$massactionClass->api('mcp'); // Enable API mode with specific MCP flag
		ob_start();
		$result = $massactionClass->process($massaction, $elements);
		$leakedOutput = ob_get_clean(); // Discard or log leaked output
		$report = $massactionClass->report;

		$output = "Processed " . count($elements) . " elements.\n";

		if(!empty($leakedOutput)) {
			$json = json_decode($leakedOutput, true);
			if(json_last_error() === JSON_ERROR_NONE) {
				$output .= "Results:\n" . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
			} else {
				$output .= "Raw Output:\n" . substr($leakedOutput, 0, 1000) . "...\n";
			}
		}

		if(!empty($report)) {
			foreach($report as $msg) {
				$output .= "- " . (is_string($msg) ? $msg : json_encode($msg)) . "\n";
			}
		}

		return new \Mcp\Schema\Result\CallToolResult([
			new \Mcp\Schema\Content\TextContent($output)
		]);
	}
}

