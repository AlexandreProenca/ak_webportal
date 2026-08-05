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
defined('_JEXEC') or die;

class HikashopUcpCatalog {

	var $params = null;
	var $mapper = null;
	var $ucpVersion = '2026-01-11';

	function __construct(&$params) {
		$this->params = $params;
	}

	function setMapper(&$mapper) {
		$this->mapper = $mapper;
	}

	function search() {
		header('Content-Type: application/json; charset=utf-8');

		try {
			$rawInput = file_get_contents('php://input');
			$input = !empty($rawInput) ? json_decode($rawInput, true) : array();
			if($input === null && !empty($rawInput)) {
				throw new InvalidArgumentException('Invalid JSON input');
			}

			$options = array();

			if(!empty($input['query'])) {
				$options['search'] = trim($input['query']);
			}

			if(!empty($input['category'])) {
				$categoryId = $input['category'];
				if(strpos($categoryId, 'gid://') === 0 && !empty($this->mapper)) {
					$categoryId = $this->mapper->parseGlobalId($categoryId);
				} elseif(!is_numeric($categoryId)) {
					$translationHelper = hikashop_get('helper.translation');
					$translatedIds = $translationHelper->searchTranslation(
						$categoryId,
						'category',
						array('category_alias', 'category_namekey', 'category_name'),
						true,
						true
					);

					if(!empty($translatedIds)) {
						$categoryId = reset($translatedIds);
					} else {
						$db = JFactory::getDBO();
						$catTerm = $db->Quote($categoryId);
						$query = 'SELECT category_id FROM ' . hikashop_table('category') . 
								 ' WHERE category_alias = ' . $catTerm . 
								 ' OR category_namekey = ' . $catTerm . 
								 ' OR category_name = ' . $catTerm;
						$db->setQuery($query, 0, 1);
						$resolvedId = $db->loadResult();

						if(!empty($resolvedId)) {
							$categoryId = $resolvedId;
						}
					}
				}

				$options['category_ids'] = array((int)$categoryId);
				$options['include_children'] = !empty($input['include_subcategories']);
			}

			$limit = isset($input['limit']) ? (int)$input['limit'] : 20;
			$options['limit'] = max(1, min(100, $limit)); // 1-100 range

			$offset = isset($input['offset']) ? (int)$input['offset'] : 0;
			$options['offset'] = max(0, $offset);

			if(isset($input['include_out_of_stock'])) {
				$options['include_out_of_stock'] = !empty($input['include_out_of_stock']);
			}

			if(!empty($input['sort'])) {
				$sortMap = array(
					'name' => 'product_name',
					'price' => 'product_sort_price',
					'date' => 'product_created',
					'popularity' => 'product_hit',
					'bestseller' => 'product_sales'
				);
				if(isset($sortMap[$input['sort']])) {
					$options['order_by'] = $sortMap[$input['sort']];
				}
			}
			if(!empty($input['sort_direction'])) {
				$options['order_dir'] = strtoupper($input['sort_direction']) === 'DESC' ? 'DESC' : 'ASC';
			}

			$options['load_images'] = true;
			$options['load_prices'] = true;
			$options['load_badges'] = false;
			$options['search_all_languages'] = true;

			$productClass = hikashop_get('class.product');
			$result = $productClass->searchProductsAndVariants($options);

			$currencyCode = null;
			if(!empty($this->mapper)) {
				$currencyCode = $this->mapper->getCurrencyCode();
			}

			$ucpProducts = array();
			if(!empty($result['products'])) {
				foreach($result['products'] as $product) {
					if(!empty($this->mapper)) {
						$ucpProducts[] = $this->mapper->productToUcp($product, $currencyCode);
					} else {
						$ucpProducts[] = array(
							'id' => (string)$product->product_id,
							'name' => !empty($product->product_name) ? hikashop_translate($product->product_name) : '',
							'sku' => !empty($product->product_code) ? $product->product_code : ''
						);
					}
				}
			}


			$response = array(
				'ucp' => array(
					'version' => $this->ucpVersion,
					'capabilities' => array(
						array('name' => 'dev.ucp.shopping.catalog', 'version' => $this->ucpVersion)
					)
				),
				'products' => $ucpProducts,
				'total' => !empty($result['total']) ? (int)$result['total'] : 0,
				'limit' => $options['limit'],
				'offset' => $options['offset']
			);

			echo json_encode($response);

		} catch(InvalidArgumentException $e) {
			header($_SERVER['SERVER_PROTOCOL'].' 400 Bad Request', true, 400);
			echo json_encode(array(
				'status' => 'error',
				'messages' => array(
					array('type' => 'error', 'message' => $e->getMessage())
				)
			));
		} catch(Exception $e) {
			header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
			echo json_encode(array(
				'status' => 'error',
				'messages' => array(
					array('type' => 'error', 'message' => $e->getMessage())
				)
			));
		}

		exit;
	}
}
