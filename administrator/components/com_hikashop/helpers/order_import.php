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
class hikashopOrderImportHelper {

	var $warnings = array();
	var $errors = array();

	public function detectFormat($content, $filename = '') {
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if($ext == 'xml')
			return 'xml';
		if(in_array($ext, array('csv', 'tsv', 'txt')))
			return 'csv';

		$trimmed = ltrim($content);
		if(strpos($trimmed, '<?xml') === 0 || strpos($trimmed, '<orders') === 0 || strpos($trimmed, '<order') === 0)
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
			$this->errors[] = JText::_('ORDER_IMPORT_INVALID_FORMAT');
			return array('header' => array(), 'data' => array());
		}

		$data = array();
		$header = array();

		$orders = $xml->order;
		if(empty($orders))
			$orders = $xml->children();

		foreach($orders as $order) {
			$row = array();
			foreach($order->children() as $child) {
				$name = $child->getName();
				if($name == 'products' || $name == 'items') {
					$itemIndex = 1;
					foreach($child->children() as $product) {
						foreach($product->children() as $field) {
							$fieldName = $field->getName();
							if(strpos($fieldName, 'order_product_') !== 0 && strpos($fieldName, 'item_') !== 0) {
								$fieldName = 'order_product_' . $fieldName;
							}
							$key = 'item_' . $itemIndex . '_' . $fieldName;
							$row[$key] = (string)$field;
						}
						$itemIndex++;
					}
				} else {
					$row[$name] = (string)$child;
				}
			}
			if(!empty($row)) {
				foreach(array_keys($row) as $key) {
					if(!in_array($key, $header))
						$header[] = $key;
				}
				$data[] = $row;
			}
		}

		foreach($data as &$row) {
			foreach($header as $key) {
				if(!isset($row[$key]))
					$row[$key] = '';
			}
		}
		unset($row);

		return array('header' => $header, 'data' => $data);
	}

	public function autoMatchColumns($headers, $supportedColumns) {
		$matches = array();
		$used = array();

		$aliases = array(
			'email' => 'user_email',
			'customer_email' => 'user_email',
			'e_mail' => 'user_email',
			'country' => 'billing_address_country',
			'billing_country' => 'billing_address_country',
			'shipping_country' => 'shipping_address_country',
			'sku' => 'order_product_code',
			'product_code' => 'order_product_code',
			'item_code' => 'order_product_code',
			'product_name' => 'order_product_name',
			'item_name' => 'order_product_name',
			'qty' => 'order_product_quantity',
			'quantity' => 'order_product_quantity',
			'price' => 'order_full_price',
			'total' => 'order_full_price',
			'amount' => 'order_full_price',
			'status' => 'order_status',
			'date' => 'order_created',
			'order_date' => 'order_created',
			'created' => 'order_created',
			'first_name' => 'billing_address_firstname',
			'firstname' => 'billing_address_firstname',
			'billing_firstname' => 'billing_address_firstname',
			'shipping_firstname' => 'shipping_address_firstname',
			'last_name' => 'billing_address_lastname',
			'lastname' => 'billing_address_lastname',
			'billing_lastname' => 'billing_address_lastname',
			'shipping_lastname' => 'shipping_address_lastname',
			'address1' => 'billing_address_street',
			'street' => 'billing_address_street',
			'billing_street' => 'billing_address_street',
			'shipping_street' => 'shipping_address_street',
			'city' => 'billing_address_city',
			'billing_city' => 'billing_address_city',
			'shipping_city' => 'shipping_address_city',
			'zip' => 'billing_address_post_code',
			'zipcode' => 'billing_address_post_code',
			'postal_code' => 'billing_address_post_code',
			'postcode' => 'billing_address_post_code',
			'billing_zip' => 'billing_address_post_code',
			'billing_postcode' => 'billing_address_post_code',
			'shipping_zip' => 'shipping_address_post_code',
			'shipping_postcode' => 'shipping_address_post_code',
			'state' => 'billing_address_state',
			'billing_state' => 'billing_address_state',
			'shipping_state' => 'shipping_address_state',
			'phone' => 'billing_address_telephone',
			'telephone' => 'billing_address_telephone',
			'billing_phone' => 'billing_address_telephone',
			'shipping_phone' => 'shipping_address_telephone',
			'currency' => 'order_currency_id',
			'currency_code' => 'order_currency_id',
			'order_number' => 'order_number',
			'payment_method' => 'order_payment_method',
			'shipping_method' => 'order_shipping_method',
			'name' => 'billing_address_full_name',
			'full_name' => 'billing_address_full_name',
			'customer_name' => 'billing_address_full_name',
			'billing_name' => 'billing_address_full_name',
			'billing_full_name' => 'billing_address_full_name',
			'shipping_name' => 'shipping_address_full_name',
			'shipping_full_name' => 'shipping_address_full_name',
			'house_number' => 'billing_address_house_number',
			'street_number' => 'billing_address_house_number',
			'billing_house_number' => 'billing_address_house_number',
			'billing_street_number' => 'billing_address_house_number',
			'shipping_house_number' => 'shipping_address_house_number',
			'shipping_street_number' => 'shipping_address_house_number',
			'street_name' => 'billing_address_street_name',
			'billing_street_name' => 'billing_address_street_name',
			'shipping_street_name' => 'shipping_address_street_name',
		);

		foreach($headers as $col) {
			$col = trim($col);
			$normalized = strtolower(preg_replace('/[\s\-]+/', '_', $col));
			$match = '';

			if(isset($supportedColumns[$normalized])) {
				$match = $normalized;
			}

			if(empty($match)) {
				$prefixes = array('order_', 'address_', 'billing_', 'shipping_', 'user_', 'order_product_');
				foreach($prefixes as $prefix) {
					$candidate = $prefix . $normalized;
					if(isset($supportedColumns[$candidate])) {
						$match = $candidate;
						break;
					}
				}
				if(empty($match)) {
					foreach($prefixes as $prefix) {
						if(strpos($normalized, $prefix) === 0) {
							$stripped = substr($normalized, strlen($prefix));
							if(isset($supportedColumns[$stripped])) {
								$match = $stripped;
								break;
							}
						}
					}
				}
			}

			if(empty($match) && isset($aliases[$normalized])) {
				$alias = $aliases[$normalized];
				if(isset($supportedColumns[$alias])) {
					$match = $alias;
				}
			}

			if(empty($match)) {
				foreach($supportedColumns as $key => $val) {
					$normalizedKey = strtolower(preg_replace('/[\s\-]+/', '_', $key));
					if($normalizedKey == $normalized) {
						$match = $key;
						break;
					}
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

	public function detectImportMode($data, $header) {
		foreach($header as $col) {
			if(preg_match('/^item_\d+_/', $col)) {
				return 'one_order_per_row';
			}
		}

		$orderIdentifiers = array();
		$hasOrderProduct = false;
		$hasOrderIdentifier = false;

		foreach($header as $col) {
			$normalized = strtolower(preg_replace('/[\s\-]+/', '_', $col));
			if(in_array($normalized, array('order_product_code', 'order_product_name', 'order_product_quantity', 'sku', 'product_code', 'product_name', 'qty', 'quantity', 'item_code', 'item_name'))) {
				$hasOrderProduct = true;
			}
			if(in_array($normalized, array('order_number', 'order_id'))) {
				$hasOrderIdentifier = true;
			}
		}

		if($hasOrderProduct && $hasOrderIdentifier && count($data) > 1) {
			$identifierCol = null;
			foreach($header as $col) {
				$normalized = strtolower(preg_replace('/[\s\-]+/', '_', $col));
				if(in_array($normalized, array('order_number', 'order_id'))) {
					$identifierCol = $col;
					break;
				}
			}
			if($identifierCol) {
				$seen = array();
				foreach($data as $row) {
					$val = isset($row[$identifierCol]) ? $row[$identifierCol] : '';
					if(!empty($val)) {
						if(isset($seen[$val])) {
							return 'one_product_per_row';
						}
						$seen[$val] = true;
					}
				}
			}
		}

		return 'one_order_per_row';
	}

	public function convertValue($value, $column, &$orderData) {
		if($value === '')
			return $value;

		$db = JFactory::getDBO();

		if(in_array($column, array('billing_address_country', 'shipping_address_country'))) {
			return $this->resolveCountry($value);
		}

		if(in_array($column, array('billing_address_state', 'shipping_address_state'))) {
			$parentCountry = '';
			if($column == 'billing_address_state' && !empty($orderData['billing_address_country'])) {
				$parentCountry = $orderData['billing_address_country'];
			} elseif($column == 'shipping_address_state' && !empty($orderData['shipping_address_country'])) {
				$parentCountry = $orderData['shipping_address_country'];
			}
			return $this->resolveState($value, $parentCountry);
		}

		if($column == 'order_currency_id') {
			return $this->resolveCurrency($value);
		}

		if(in_array($column, array('order_created', 'order_modified', 'order_invoice_created', 'user_created'))) {
			if(is_numeric($value))
				return (int)$value;
			$ts = strtotime($value);
			if($ts !== false)
				return $ts;
			return $value;
		}

		if($column == 'order_status') {
			return $this->resolveOrderStatus($value);
		}

		if(in_array($column, array('order_full_price', 'order_shipping_price', 'order_discount_price', 'order_payment_price', 'order_product_price', 'order_product_tax'))) {
			if($column == 'order_full_price' && empty($orderData['order_currency_id'])) {
				$extracted = $this->extractCurrencyFromPrice($value);
				if($extracted !== false) {
					$orderData['order_currency_id'] = $extracted['currency_id'];
					$value = $extracted['price'];
				}
			}
			return hikashop_toFloat($value);
		}

		return $value;
	}

	protected function resolveCountry($value) {
		$db = JFactory::getDBO();
		$db->setQuery(
			'SELECT zone_namekey FROM ' . hikashop_table('zone') . ' WHERE zone_type=' . $db->Quote('country') . ' AND (' .
			'zone_namekey = ' . $db->Quote($value) . ' OR ' .
			'zone_code_2 = ' . $db->Quote($value) . ' OR ' .
			'zone_code_3 = ' . $db->Quote($value) . ' OR ' .
			'zone_name = ' . $db->Quote($value) . ' OR ' .
			'zone_name_english = ' . $db->Quote($value) . ' OR ' .
			'zone_id = ' . (int)$value .
			') LIMIT 1'
		);
		$result = $db->loadResult();
		if(!empty($result))
			return $result;

		$this->warnings[] = JText::sprintf('ORDER_IMPORT_FIELD_CONVERTED', 'country', $value, '(not found)');
		return $value;
	}

	protected function resolveState($value, $parentCountry = '') {
		$db = JFactory::getDBO();
		$query = 'SELECT z.zone_namekey FROM ' . hikashop_table('zone') . ' AS z';

		if(!empty($parentCountry)) {
			$query .= ' INNER JOIN ' . hikashop_table('zone_link') . ' AS zl ON z.zone_namekey = zl.zone_child_namekey';
			$query .= ' WHERE z.zone_type=' . $db->Quote('state');
			$query .= ' AND zl.zone_parent_namekey = ' . $db->Quote($parentCountry);
		} else {
			$query .= ' WHERE z.zone_type=' . $db->Quote('state');
		}

		$query .= ' AND (' .
			'z.zone_namekey = ' . $db->Quote($value) . ' OR ' .
			'z.zone_code_2 = ' . $db->Quote($value) . ' OR ' .
			'z.zone_code_3 = ' . $db->Quote($value) . ' OR ' .
			'z.zone_name = ' . $db->Quote($value) . ' OR ' .
			'z.zone_name_english = ' . $db->Quote($value) . ' OR ' .
			'z.zone_id = ' . (int)$value .
			') LIMIT 1';

		$db->setQuery($query);
		$result = $db->loadResult();
		if(!empty($result))
			return $result;

		return $value;
	}

	protected function resolveCurrency($value) {
		if(is_numeric($value)) {
			$db = JFactory::getDBO();
			$db->setQuery('SELECT currency_id FROM ' . hikashop_table('currency') . ' WHERE currency_id = ' . (int)$value . ' LIMIT 1');
			$result = $db->loadResult();
			if(!empty($result))
				return (int)$result;
		}

		$db = JFactory::getDBO();
		$db->setQuery('SELECT currency_id FROM ' . hikashop_table('currency') . ' WHERE currency_code = ' . $db->Quote($value) . ' LIMIT 1');
		$result = $db->loadResult();
		if(!empty($result))
			return (int)$result;

		return $value;
	}

	protected function extractCurrencyFromPrice($value) {
		$db = JFactory::getDBO();
		$patterns = array(
			'/^([A-Z]{3})\s*([0-9.,]+)$/',      // "USD 120"
			'/^([0-9.,]+)\s*([A-Z]{3})$/',        // "120 USD"
			'/^([\$€£¥₹])\s*([0-9.,]+)$/',       // "$120"
			'/^([0-9.,]+)\s*([\$€£¥₹])$/',        // "120$"
		);

		foreach($patterns as $pattern) {
			if(preg_match($pattern, trim($value), $m)) {
				$currencyPart = null;
				$pricePart = null;

				if(is_numeric(str_replace(array(',', '.'), '', $m[1]))) {
					$pricePart = $m[1];
					$currencyPart = $m[2];
				} else {
					$currencyPart = $m[1];
					$pricePart = $m[2];
				}

				$db->setQuery(
					'SELECT currency_id FROM ' . hikashop_table('currency') .
					' WHERE currency_code = ' . $db->Quote($currencyPart) .
					' OR currency_symbol = ' . $db->Quote($currencyPart) .
					' LIMIT 1'
				);
				$currencyId = $db->loadResult();

				if(!empty($currencyId)) {
					$this->warnings[] = JText::sprintf('ORDER_IMPORT_CURRENCY_EXTRACTED', $currencyPart);
					return array('currency_id' => (int)$currencyId, 'price' => $pricePart);
				}
			}
		}
		return false;
	}

	protected function resolveOrderStatus($value) {
		$db = JFactory::getDBO();
		$db->setQuery('SELECT orderstatus_namekey FROM ' . hikashop_table('orderstatus') . ' WHERE orderstatus_namekey = ' . $db->Quote($value) . ' LIMIT 1');
		$result = $db->loadResult();
		if(!empty($result))
			return $result;

		$db->setQuery('SELECT orderstatus_namekey FROM ' . hikashop_table('orderstatus') . ' WHERE LOWER(orderstatus_name) = ' . $db->Quote(strtolower($value)) . ' LIMIT 1');
		$result = $db->loadResult();
		if(!empty($result))
			return $result;

		return $value;
	}

	public function validateData($data, $mapping) {
		$this->warnings = array();
		$this->errors = array();

		if(empty($data)) {
			$this->errors[] = JText::_('ORDER_IMPORT_NO_DATA');
			return false;
		}

		$mappedColumns = array_filter($mapping, function($v) { return $v !== 'ignore'; });

		if(empty($mappedColumns)) {
			$this->errors[] = JText::_('ORDER_IMPORT_NO_DATA');
			return false;
		}

		$hasEmail = in_array('user_email', $mappedColumns);
		$hasProduct = false;
		$hasOrderId = false;
		foreach($mappedColumns as $col) {
			if(strpos($col, 'order_product_') === 0 || preg_match('/^item_\d+_/', $col))
				$hasProduct = true;
			if(in_array($col, array('order_id', 'order_number')))
				$hasOrderId = true;
		}

		if(!$hasEmail && !$hasOrderId) {
			$this->warnings[] = JText::_('ORDER_IMPORT_NO_USER_WARNING');
		}

		if(!$hasProduct) {
			$this->warnings[] = JText::_('ORDER_IMPORT_NO_PRODUCT_WARNING');
		}

		return empty($this->errors);
	}

	public function processImport($data, $mapping, $mode) {
		$this->warnings = array();
		$this->errors = array();
		$imported = 0;

		if($mode == 'one_product_per_row') {
			$imported = $this->processOneProductPerRow($data, $mapping);
		} else {
			$imported = $this->processOneOrderPerRow($data, $mapping);
		}

		return $imported;
	}

	protected function processOneOrderPerRow($data, $mapping) {
		$imported = 0;
		$orderClass = hikashop_get('class.order');
		$orderClass->sendEmailAfterOrderCreation = false;

		foreach($data as $rowIndex => $row) {
			$orderData = array();
			$products = array();

			foreach($mapping as $csvCol => $hkCol) {
				if($hkCol == 'ignore' || !isset($row[$csvCol]))
					continue;
				$value = $row[$csvCol];

				if(preg_match('/^item_(\d+)_(.+)$/', $hkCol, $m)) {
					$itemIndex = (int)$m[1];
					$fieldName = $m[2];
					if(!isset($products[$itemIndex]))
						$products[$itemIndex] = array();
					$products[$itemIndex][$fieldName] = $value;
				} else {
					$orderData[$hkCol] = $value;
				}
			}

			foreach($row as $csvCol => $value) {
				if(preg_match('/^item_(\d+)_(.+)$/', $csvCol, $m)) {
					$mappedCol = isset($mapping[$csvCol]) ? $mapping[$csvCol] : '';
					if($mappedCol == 'ignore')
						continue;
					$itemIndex = (int)$m[1];
					$fieldName = $m[2];
					if(!empty($mappedCol) && preg_match('/^item_(\d+)_(.+)$/', $mappedCol, $m2)) {
						$itemIndex = (int)$m2[1];
						$fieldName = $m2[2];
					}
					if(!isset($products[$itemIndex]))
						$products[$itemIndex] = array();
					if(empty($products[$itemIndex][$fieldName]))
						$products[$itemIndex][$fieldName] = $value;
				}
			}

			if(empty($products)) {
				$product = array();
				foreach($orderData as $key => $value) {
					if(strpos($key, 'order_product_') === 0) {
						$product[$key] = $value;
						unset($orderData[$key]);
					}
				}
				if(!empty($product))
					$products[1] = $product;
			}

			foreach($orderData as $key => &$value) {
				$value = $this->convertValue($value, $key, $orderData);
			}
			unset($value);

			$result = $this->createOrder($orderData, $products);
			if($result) {
				$imported++;
			} else {
				$this->errors[] = JText::sprintf('ORDER_IMPORT_ROW_ERROR', $rowIndex + 1);
			}
		}

		return $imported;
	}

	protected function processOneProductPerRow($data, $mapping) {
		$imported = 0;
		$orderClass = hikashop_get('class.order');
		$orderClass->sendEmailAfterOrderCreation = false;

		$groups = array();
		$groupKey = null;

		$mappedColumns = array_flip(array_filter($mapping, function($v) { return $v !== 'ignore'; }));
		if(isset($mappedColumns['order_number'])) {
			$groupKey = 'order_number';
		} elseif(isset($mappedColumns['order_id'])) {
			$groupKey = 'order_id';
		}

		$groupCsvCol = null;
		if(!empty($groupKey)) {
			$groupCsvCol = $mappedColumns[$groupKey];
		}

		foreach($data as $row) {
			if(!empty($groupCsvCol) && !empty($row[$groupCsvCol])) {
				$key = $row[$groupCsvCol];
			} else {
				$emailCol = isset($mappedColumns['user_email']) ? $mappedColumns['user_email'] : '';
				$dateCol = isset($mappedColumns['order_created']) ? $mappedColumns['order_created'] : '';
				$email = !empty($emailCol) && isset($row[$emailCol]) ? $row[$emailCol] : '';
				$date = !empty($dateCol) && isset($row[$dateCol]) ? $row[$dateCol] : '';
				$key = $email . '_' . $date;
			}
			if(!isset($groups[$key]))
				$groups[$key] = array();
			$groups[$key][] = $row;
		}

		foreach($groups as $groupRows) {
			$orderData = array();
			$products = array();

			foreach($groupRows as $row) {
				$product = array();

				foreach($mapping as $csvCol => $hkCol) {
					if($hkCol == 'ignore' || !isset($row[$csvCol]))
						continue;

					$value = $row[$csvCol];

					if(strpos($hkCol, 'order_product_') === 0) {
						$product[$hkCol] = $value;
					} else {
						if(!isset($orderData[$hkCol]))
							$orderData[$hkCol] = $value;
					}
				}

				if(!empty($product))
					$products[] = $product;
			}

			foreach($orderData as $key => &$value) {
				$value = $this->convertValue($value, $key, $orderData);
			}
			unset($value);

			$result = $this->createOrder($orderData, $products);
			if($result) {
				$imported++;
			} else {
				$this->errors[] = JText::_('ERROR_IMPORTING_ROW');
			}
		}

		return $imported;
	}

	protected function createOrder($orderData, $products) {
		$orderClass = hikashop_get('class.order');
		$orderClass->sendEmailAfterOrderCreation = false;

		$order = new stdClass();

		$userId = 0;
		if(!empty($orderData['user_email'])) {
			$userId = $this->getOrCreateUser($orderData['user_email']);
		}
		if(!empty($userId))
			$order->order_user_id = $userId;

		$orderFields = array(
			'order_status', 'order_currency_id', 'order_full_price', 'order_created',
			'order_modified', 'order_number', 'order_type', 'order_payment_method',
			'order_payment_id', 'order_shipping_method', 'order_shipping_id',
			'order_shipping_price', 'order_discount_price', 'order_payment_price',
			'order_invoice_created', 'order_invoice_number', 'order_ip',
			'order_discount_code', 'order_shipping_tax',
		);
		foreach($orderFields as $field) {
			if(isset($orderData[$field]) && $orderData[$field] !== '')
				$order->$field = $orderData[$field];
		}

		$this->processVirtualAddressFields($orderData);

		$this->crossFillAddressNames($orderData);

		$billingAddressId = $this->createAddress($orderData, 'billing', $userId);
		if(!empty($billingAddressId))
			$order->order_billing_address_id = $billingAddressId;

		$shippingAddressId = $this->createAddress($orderData, 'shipping', $userId);
		if(!empty($shippingAddressId))
			$order->order_shipping_address_id = $shippingAddressId;

		$orderClass->skipLockedCheck = true;
		$orderClass->skipEmailSending = true;
		$result = $orderClass->save($order);
		$orderClass->skipLockedCheck = false;
		$orderClass->skipEmailSending = false;
		if(empty($result))
			return false;

		$orderId = $order->order_id;

		$hasProductPrice = false;
		$orderProducts = array();
		if(!empty($products)) {
			foreach($products as $productData) {
				$orderProduct = new stdClass();
				$orderProduct->order_id = $orderId;
				$orderProduct->product_id = 0;

				$productFields = array(
					'order_product_name', 'order_product_code', 'order_product_quantity',
					'order_product_price', 'order_product_tax', 'order_product_weight',
					'order_product_weight_unit', 'order_product_width', 'order_product_height',
					'order_product_length', 'order_product_dimension_unit',
				);

				foreach($productFields as $field) {
					if(isset($productData[$field]) && $productData[$field] !== '') {
						$orderProduct->$field = $productData[$field];
					}
				}

				if(empty($orderProduct->order_product_quantity))
					$orderProduct->order_product_quantity = 1;

				$db = JFactory::getDBO();
				if(!empty($orderProduct->order_product_code)) {
					$db->setQuery('SELECT product_id, product_name FROM ' . hikashop_table('product') . ' WHERE product_code = ' . $db->Quote($orderProduct->order_product_code) . ' LIMIT 1');
					$existingProduct = $db->loadObject();
					if(!empty($existingProduct)) {
						$orderProduct->product_id = $existingProduct->product_id;
						if(empty($orderProduct->order_product_name))
							$orderProduct->order_product_name = $existingProduct->product_name;
					}
				}
				if(empty($orderProduct->product_id) && !empty($orderProduct->order_product_name)) {
					$db->setQuery('SELECT product_id, product_code FROM ' . hikashop_table('product') . ' WHERE product_name = ' . $db->Quote($orderProduct->order_product_name) . ' LIMIT 1');
					$existingProduct = $db->loadObject();
					if(!empty($existingProduct)) {
						$orderProduct->product_id = $existingProduct->product_id;
						if(empty($orderProduct->order_product_code))
							$orderProduct->order_product_code = $existingProduct->product_code;
					}
				}

				if(isset($orderProduct->order_product_price)) {
					$orderProduct->order_product_price = hikashop_toFloat($orderProduct->order_product_price);
					$hasProductPrice = true;
				}
				if(isset($orderProduct->order_product_tax))
					$orderProduct->order_product_tax = hikashop_toFloat($orderProduct->order_product_tax);
				$orderProduct->order_product_quantity = (int)$orderProduct->order_product_quantity;

				$orderProducts[] = $orderProduct;
			}
		}

		$hasFullPrice = isset($order->order_full_price) && $order->order_full_price !== '';

		if(!$hasProductPrice && $hasFullPrice && count($orderProducts) == 1) {
			$fullPrice = (float)$order->order_full_price;
			$shippingPrice = isset($order->order_shipping_price) ? (float)$order->order_shipping_price : 0;
			$paymentPrice = isset($order->order_payment_price) ? (float)$order->order_payment_price : 0;
			$discountPrice = isset($order->order_discount_price) ? (float)$order->order_discount_price : 0;
			$orderProducts[0]->order_product_price = $fullPrice - $shippingPrice - $paymentPrice + $discountPrice;
		}

		if(!empty($orderProducts)) {
			$orderProductClass = hikashop_get('class.order_product');
			$orderProductClass->skipDefaultFields = true;
			$orderProductClass->save($orderProducts);
			$orderProductClass->skipDefaultFields = false;
		}

		if(!$hasFullPrice && $hasProductPrice) {
			$order->product = $orderProducts;
			$orderClass->recalculateFullPrice($order, $orderProducts);
			$orderClass->skipLockedCheck = true;
			$orderClass->skipEmailSending = true;
			$orderClass->save($order);
			$orderClass->skipLockedCheck = false;
			$orderClass->skipEmailSending = false;
		}

		return $orderId;
	}

	protected function getOrCreateUser($email) {
		$userClass = hikashop_get('class.user');
		$db = JFactory::getDBO();

		$db->setQuery('SELECT user_id FROM ' . hikashop_table('user') . ' WHERE user_email = ' . $db->Quote($email) . ' ORDER BY FIELD(user_type, \'registered\') DESC, user_id DESC LIMIT 1');
		$userId = $db->loadResult();

		if(!empty($userId))
			return (int)$userId;

		$db->setQuery('SELECT id FROM ' . hikashop_table('users', false) . ' WHERE email = ' . $db->Quote($email) . ' LIMIT 1');
		$cmsId = $db->loadResult();

		$user = new stdClass();
		$user->user_email = $email;
		if(!empty($cmsId))
			$user->user_cms_id = (int)$cmsId;

		$userId = $userClass->save($user);
		return !empty($userId) ? (int)$userId : 0;
	}

	protected function processVirtualAddressFields(&$orderData) {
		foreach(array('billing', 'shipping') as $type) {
			$prefix = $type . '_address_';

			$fullNameKey = $prefix . 'full_name';
			if(!empty($orderData[$fullNameKey])) {
				$fullName = trim($orderData[$fullNameKey]);
				if(empty($orderData[$prefix . 'firstname']) && empty($orderData[$prefix . 'lastname'])) {
					if(strpos($fullName, ' ') !== false) {
						$parts = preg_split('/\s+/', $fullName, 2);
						$orderData[$prefix . 'firstname'] = $parts[0];
						$orderData[$prefix . 'lastname'] = $parts[1];
					} else {
						$orderData[$prefix . 'firstname'] = '';
						$orderData[$prefix . 'lastname'] = $fullName;
					}
				}
				unset($orderData[$fullNameKey]);
			}

			$houseKey = $prefix . 'house_number';
			$streetNameKey = $prefix . 'street_name';
			if(!empty($orderData[$houseKey]) || !empty($orderData[$streetNameKey])) {
				if(empty($orderData[$prefix . 'street'])) {
					$houseNumber = isset($orderData[$houseKey]) ? trim($orderData[$houseKey]) : '';
					$streetName = isset($orderData[$streetNameKey]) ? trim($orderData[$streetNameKey]) : '';
					if(!empty($houseNumber) && !empty($streetName))
						$orderData[$prefix . 'street'] = $houseNumber . ' ' . $streetName;
					elseif(!empty($houseNumber))
						$orderData[$prefix . 'street'] = $houseNumber;
					elseif(!empty($streetName))
						$orderData[$prefix . 'street'] = $streetName;
				}
				unset($orderData[$houseKey], $orderData[$streetNameKey]);
			}
		}
	}

	protected function crossFillAddressNames(&$orderData) {
		$fields = array('firstname', 'lastname');
		foreach($fields as $field) {
			$billingKey = 'billing_address_' . $field;
			$shippingKey = 'shipping_address_' . $field;

			$hasBilling = !empty($orderData[$billingKey]);
			$hasShipping = !empty($orderData[$shippingKey]);

			if($hasBilling && !$hasShipping && $this->hasAddressData($orderData, 'shipping')) {
				$orderData[$shippingKey] = $orderData[$billingKey];
			} elseif($hasShipping && !$hasBilling && $this->hasAddressData($orderData, 'billing')) {
				$orderData[$billingKey] = $orderData[$shippingKey];
			}
		}
	}

	protected function hasAddressData($orderData, $type) {
		$prefix = $type . '_address_';
		$addressFields = array('firstname', 'lastname', 'street', 'street2', 'city', 'post_code', 'telephone', 'fax', 'company', 'country', 'state', 'vat');
		foreach($addressFields as $field) {
			if(!empty($orderData[$prefix . $field]))
				return true;
		}
		return false;
	}

	protected function createAddress($orderData, $type, $userId) {
		$prefix = $type . '_address_';
		$addressData = array();
		$addressFields = array('firstname', 'lastname', 'street', 'street2', 'city', 'post_code', 'telephone', 'fax', 'company', 'country', 'state', 'vat');

		foreach($addressFields as $field) {
			$key = $prefix . $field;
			if(isset($orderData[$key]) && $orderData[$key] !== '') {
				$addressData['address_' . $field] = $orderData[$key];
			}
		}

		if(empty($addressData))
			return 0;

		$addressClass = hikashop_get('class.address');
		$address = new stdClass();
		foreach($addressData as $key => $value) {
			$address->$key = $value;
		}
		if(!empty($userId))
			$address->address_user_id = $userId;

		$result = $addressClass->save($address);
		return !empty($result) ? (int)$result : 0;
	}
}
