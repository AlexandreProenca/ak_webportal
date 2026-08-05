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
class hikashopDiscountImportHelper {

	var $warnings = array();
	var $errors = array();

	public function getSupportedColumns() {
		$cols = array(
			'discount_code',
			'discount_type',
			'discount_flat_amount',
			'discount_percent_amount',
			'discount_shipping_percent',
			'discount_start',
			'discount_end',
			'discount_minimum_order',
			'discount_maximum_order',
			'discount_minimum_products',
			'discount_maximum_products',
			'discount_quota',
			'discount_quota_per_user',
			'discount_currency_id',
			'discount_published',
			'discount_auto_load',
			'discount_tax',
			'discount_tax_id',
			'discount_coupon_nodoubling',
			'discount_coupon_product_only',
			'discount_category_childs',
			'discount_access',
			'discount_product_id',
			'discount_category_id',
			'discount_zone_id',
			'discount_user_id',
		);
		return array_combine($cols, $cols);
	}

	public function detectFormat($content, $filename = '') {
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if($ext == 'xml')
			return 'xml';
		if(in_array($ext, array('csv', 'tsv', 'txt')))
			return 'csv';

		$trimmed = ltrim($content);
		if(strpos($trimmed, '<?xml') === 0 || strpos($trimmed, '<discount') === 0 || strpos($trimmed, '<coupon') === 0)
			return 'xml';

		return 'csv';
	}

	public function parseCSV($content) {
		$bom = pack('H*', 'EFBBBF');
		$content = preg_replace("/^$bom/", '', $content);
		$content = str_replace(array("\r\n", "\r"), "\n", $content);

		$separator = ',';
		$listSeparators = array(',', ';', "\t");

		$firstLine = strtok($content, "\n");
		foreach($listSeparators as $sep) {
			if(strpos($firstLine, $sep) !== false) {
				$separator = $sep;
				break;
			}
		}

		$lines = str_getcsv($content, "\n", '"', '\\');
		$data = array();
		$header = array();

		foreach($lines as $line) {
			$line = trim($line);
			if(empty($line))
				continue;

			$row = str_getcsv($line, $separator, '"', '\\');

			if(empty($header)) {
				foreach($row as $k => $v) {
					$header[$k] = trim($v, " \t\n\r\0\x0B\"");
				}
				continue;
			}

			$headerCount = count($header);
			$rowCount = count($row);
			if($rowCount < $headerCount) {
				$row = array_pad($row, $headerCount, '');
			} elseif($rowCount > $headerCount) {
				$row = array_slice($row, 0, $headerCount);
			}

			foreach($row as $k => $v) {
				$row[$k] = trim($v, " \t\n\r\0\x0B\"");
			}
			$data[] = array_combine($header, $row);
		}

		return array('header' => $header, 'data' => $data);
	}

	public function parseXML($content) {
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET);
		if($xml === false) {
			$this->errors[] = JText::_('DISCOUNT_IMPORT_INVALID_FORMAT');
			return array('header' => array(), 'data' => array());
		}

		$data = array();
		$headerSet = array();

		$items = $xml->discount;
		if(empty($items))
			$items = $xml->coupon;
		if(empty($items))
			$items = $xml->children();

		foreach($items as $item) {
			$row = array();
			foreach($item->children() as $field) {
				$name = $field->getName();
				$row[$name] = (string)$field;
				$headerSet[$name] = true;
			}
			if(!empty($row))
				$data[] = $row;
		}

		$header = array_keys($headerSet);

		foreach($data as &$row) {
			foreach($header as $col) {
				if(!isset($row[$col]))
					$row[$col] = '';
			}
		}
		unset($row);

		return array('header' => $header, 'data' => $data);
	}

	public function autoMatchColumns($headers, $supportedColumns) {
		$matches = array();
		$used = array();

		$aliases = array(
			'code' => 'discount_code',
			'coupon' => 'discount_code',
			'coupon_code' => 'discount_code',
			'voucher' => 'discount_code',
			'voucher_code' => 'discount_code',
			'promo' => 'discount_code',
			'promo_code' => 'discount_code',
			'name' => 'discount_code',
			'type' => 'discount_type',
			'discount_kind' => 'discount_type',
			'amount' => 'discount_flat_amount',
			'flat' => 'discount_flat_amount',
			'flat_amount' => 'discount_flat_amount',
			'value' => 'discount_flat_amount',
			'percent' => 'discount_percent_amount',
			'percentage' => 'discount_percent_amount',
			'percent_amount' => 'discount_percent_amount',
			'discount_percent' => 'discount_percent_amount',
			'start' => 'discount_start',
			'start_date' => 'discount_start',
			'valid_from' => 'discount_start',
			'from' => 'discount_start',
			'end' => 'discount_end',
			'end_date' => 'discount_end',
			'valid_to' => 'discount_end',
			'to' => 'discount_end',
			'expires' => 'discount_end',
			'expiration' => 'discount_end',
			'expiration_date' => 'discount_end',
			'min_order' => 'discount_minimum_order',
			'min_total' => 'discount_minimum_order',
			'min_amount' => 'discount_minimum_order',
			'minimum_amount' => 'discount_minimum_order',
			'minimum_total' => 'discount_minimum_order',
			'max_order' => 'discount_maximum_order',
			'max_total' => 'discount_maximum_order',
			'max_amount' => 'discount_maximum_order',
			'maximum_amount' => 'discount_maximum_order',
			'maximum_total' => 'discount_maximum_order',
			'min_products' => 'discount_minimum_products',
			'minimum_quantity' => 'discount_minimum_products',
			'min_qty' => 'discount_minimum_products',
			'max_products' => 'discount_maximum_products',
			'maximum_quantity' => 'discount_maximum_products',
			'max_qty' => 'discount_maximum_products',
			'quota' => 'discount_quota',
			'usage_limit' => 'discount_quota',
			'usage_count_limit' => 'discount_quota',
			'max_uses' => 'discount_quota',
			'uses_limit' => 'discount_quota',
			'quota_per_user' => 'discount_quota_per_user',
			'per_user_limit' => 'discount_quota_per_user',
			'uses_per_customer' => 'discount_quota_per_user',
			'currency' => 'discount_currency_id',
			'currency_code' => 'discount_currency_id',
			'currency_id' => 'discount_currency_id',
			'published' => 'discount_published',
			'enabled' => 'discount_published',
			'active' => 'discount_published',
			'status' => 'discount_published',
			'no_combine' => 'discount_coupon_nodoubling',
			'cannot_combine' => 'discount_coupon_nodoubling',
			'no_doubling' => 'discount_coupon_nodoubling',
			'product_only' => 'discount_coupon_product_only',
			'tax_included' => 'discount_tax',
			'taxed' => 'discount_tax',
		);

		foreach($headers as $col) {
			$col = trim($col);
			$normalized = strtolower(preg_replace('/[\s\-]+/', '_', $col));
			$match = '';

			if(isset($supportedColumns[$normalized])) {
				$match = $normalized;
			}

			if(empty($match)) {
				$candidate = 'discount_' . $normalized;
				if(isset($supportedColumns[$candidate])) {
					$match = $candidate;
				}
			}

			if(empty($match) && isset($aliases[$normalized])) {
				$alias = $aliases[$normalized];
				if(isset($supportedColumns[$alias])) {
					$match = $alias;
				}
			}

			if(!empty($match) && isset($used[$match])) {
				$match = '';
			}
			if(!empty($match)) {
				$used[$match] = true;
			}

			$matches[$col] = $match;
		}

		return $matches;
	}

	protected function resolveCurrency($value) {
		static $cache = array();
		if($value === '' || $value === null) {
			$config = hikashop_config();
			return (int)$config->get('main_currency', 1);
		}
		$key = strtolower(trim((string)$value));
		if(isset($cache[$key]))
			return $cache[$key];

		$db = JFactory::getDBO();
		if(ctype_digit((string)$value)) {
			$db->setQuery('SELECT currency_id FROM ' . hikashop_table('currency') . ' WHERE currency_id = ' . (int)$value);
			$id = (int)$db->loadResult();
			if($id) {
				$cache[$key] = $id;
				return $id;
			}
		}

		$db->setQuery('SELECT currency_id FROM ' . hikashop_table('currency') .
			' WHERE LOWER(currency_code) = ' . $db->Quote(strtoupper($key)) .
			' OR LOWER(currency_name) = ' . $db->Quote($key) .
			' LIMIT 1');
		$id = (int)$db->loadResult();
		if(!$id) {
			$config = hikashop_config();
			$id = (int)$config->get('main_currency', 1);
		}
		$cache[$key] = $id;
		return $id;
	}

	protected function resolveDate($value) {
		$value = trim((string)$value);
		if($value === '')
			return 0;
		if(ctype_digit($value))
			return (int)$value;
		$ts = strtotime($value);
		if($ts === false)
			return 0;
		return $ts;
	}

	protected function resolveBool($value) {
		$value = strtolower(trim((string)$value));
		if(in_array($value, array('1', 'yes', 'true', 'y', 'on', 'enabled', 'published', 'active'), true))
			return 1;
		return 0;
	}

	public function validateData($data, $mapping) {
		$this->warnings = array();
		$this->errors = array();

		if(empty($data)) {
			$this->errors[] = JText::_('DISCOUNT_IMPORT_NO_DATA');
			return false;
		}

		$mappedColumns = array_filter($mapping, function($v) { return $v !== 'ignore' && $v !== ''; });
		if(empty($mappedColumns)) {
			$this->errors[] = JText::_('DISCOUNT_IMPORT_NO_MAPPING');
			return false;
		}

		$hasAmount = in_array('discount_flat_amount', $mappedColumns) || in_array('discount_percent_amount', $mappedColumns);
		if(!$hasAmount) {
			$this->warnings[] = JText::_('DISCOUNT_IMPORT_NO_AMOUNT_WARNING');
		}

		return empty($this->errors);
	}

	public function processImport($data, $mapping, $defaultType = 'coupon') {
		if(!in_array($defaultType, array('coupon', 'discount'), true))
			$defaultType = 'coupon';
		$this->warnings = array();
		$this->errors = array();
		$imported = 0;

		$discountClass = hikashop_get('class.discount');
		$db = JFactory::getDBO();

		$existingCodes = array();
		$codeColumn = '';
		foreach($mapping as $csvCol => $hkCol) {
			if($hkCol === 'discount_code') {
				$codeColumn = $csvCol;
				break;
			}
		}
		if($codeColumn !== '') {
			$importedCodes = array();
			foreach($data as $row) {
				if(!isset($row[$codeColumn]))
					continue;
				$code = trim((string)$row[$codeColumn]);
				if($code === '')
					continue;
				$importedCodes[strtolower($code)] = $code;
			}
			if(!empty($importedCodes)) {
				$chunks = array_chunk(array_values($importedCodes), 1000);
				foreach($chunks as $chunk) {
					$quoted = array();
					foreach($chunk as $c) {
						$quoted[] = $db->Quote($c);
					}
					$db->setQuery('SELECT discount_code, discount_id FROM ' . hikashop_table('discount') .
						' WHERE discount_code IN (' . implode(',', $quoted) . ')');
					foreach((array)$db->loadObjectList() as $r) {
						$existingCodes[strtolower($r->discount_code)] = (int)$r->discount_id;
					}
				}
			}
		}

		foreach($data as $rowIndex => $row) {
			$discountData = array();

			foreach($mapping as $csvCol => $hkCol) {
				if($hkCol === 'ignore' || $hkCol === '' || !isset($row[$csvCol]))
					continue;
				$value = $row[$csvCol];
				if($value === '' || $value === null)
					continue;

				switch($hkCol) {
					case 'discount_currency_id':
						$discountData[$hkCol] = $this->resolveCurrency($value);
						break;
					case 'discount_start':
					case 'discount_end':
						$discountData[$hkCol] = $this->resolveDate($value);
						break;
					case 'discount_published':
					case 'discount_auto_load':
					case 'discount_tax':
					case 'discount_coupon_nodoubling':
					case 'discount_coupon_product_only':
					case 'discount_category_childs':
						$discountData[$hkCol] = $this->resolveBool($value);
						break;
					default:
						$discountData[$hkCol] = $value;
						break;
				}
			}

			if(empty($discountData)) {
				continue;
			}

			if(!empty($discountData['discount_type'])) {
				$rowType = strtolower($discountData['discount_type']);
				$discountData['discount_type'] = in_array($rowType, array('coupon', 'discount'), true)
					? $rowType
					: $defaultType;
			} else {
				$discountData['discount_type'] = $defaultType;
			}

			if($discountData['discount_type'] === 'coupon' && empty($discountData['discount_code'])) {
				$this->warnings[] = JText::sprintf('DISCOUNT_IMPORT_ROW_MISSING_CODE', $rowIndex + 2);
				continue;
			}

			if(!isset($discountData['discount_published']))
				$discountData['discount_published'] = 1;

			if(!empty($discountData['discount_flat_amount']) && empty($discountData['discount_currency_id'])) {
				$config = hikashop_config();
				$discountData['discount_currency_id'] = (int)$config->get('main_currency', 1);
			}

			$discount = (object)$discountData;

			if(!empty($discount->discount_code)) {
				$lc = strtolower($discount->discount_code);
				if(isset($existingCodes[$lc])) {
					$discount->discount_id = $existingCodes[$lc];
				}
			}

			$savedId = $discountClass->save($discount);
			if($savedId) {
				$imported++;
				if(!empty($discount->discount_code)) {
					$existingCodes[strtolower($discount->discount_code)] = (int)$savedId;
				}
			} else {
				$this->errors[] = JText::sprintf('DISCOUNT_IMPORT_ROW_ERROR', $rowIndex + 2);
			}
		}

		return $imported;
	}
}
