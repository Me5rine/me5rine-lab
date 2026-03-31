<?php

namespace ContentEgg\application\models;

defined('\ABSPATH') || exit;

use ContentEgg\application\admin\GeneralConfig;
use ContentEgg\application\components\ContentProduct;



/**
 * PriceHistoryModel class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
class PriceHistoryModel extends Model
{

	public function tableName()
	{
		return $this->getDb()->prefix . 'cegg_price_history';
	}

	public function getDump()
	{

		return "CREATE TABLE " . $this->tableName() . " (
                    unique_id varchar(255) NOT NULL,
                    module_id varchar(255) NOT NULL,
                    create_date datetime NOT NULL,
                    price float(12,2) NOT NULL,
                    price_old float(12,2) DEFAULT NULL,
                    price_old_date datetime DEFAULT NULL,
                    post_id bigint(20) unsigned DEFAULT NULL,
                    is_latest tinyint(1) DEFAULT 0,
                    KEY uid (unique_id(80),module_id(30)),
                    KEY create_date (create_date),
                    KEY price (price),
                    KEY price_old (price_old),
                    KEY is_latest (is_latest)
                    ) $this->charset_collate;";
	}

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function save(array $item)
	{
		$item['is_latest'] = 1;
		if (empty($item['create_date']))
		{
			$item['create_date'] = current_time('mysql');
		}

		if (empty($item['price_old']))
		{
			$old_data = $this->getOldPrice($item['unique_id'], $item['module_id']);
			if ($old_data)
			{
				$item['price_old']      = $old_data['price'];
				$item['price_old_date'] = $old_data['create_date'];
			}
		}

		$this->getDb()->update($this->tableName(), array('is_latest' => 0), array(
			'unique_id' => $item['unique_id'],
			'module_id' => $item['module_id']
		));
		$this->getDb()->insert($this->tableName(), $item);

		\do_action('content_egg_price_history_save', $item);

		return true;
	}

	private function getOldPrice($unique_id, $module_id)
	{
		// price known date
		$price_drops_days = (int) GeneralConfig::getInstance()->option('price_drops_days');
		$sql              = 'SELECT create_date FROM ' . $this->tableName() . ' WHERE unique_id = %s AND module_id = %s AND create_date <= NOW() - INTERVAL %d DAY ORDER BY create_date DESC LIMIT 1';
		$sql              = $this->getDb()->prepare($sql, array($unique_id, $module_id, $price_drops_days));
		$known_date       = $this->getDb()->get_var($sql);

		$where = '';
		if ($known_date)
		{
			$where = $this->getDb()->prepare('create_date > %s', array($known_date));
		}
		else
		{
			$where = $this->getDb()->prepare('create_date >= NOW() - INTERVAL %d DAY', array($price_drops_days));
		}

		$sql = 'SELECT t.*
            FROM ' . $this->tableName() . ' t
            WHERE price=(SELECT MAX(price) FROM ' . $this->tableName() . ' WHERE unique_id = %s AND module_id = %s AND ' . $where . ')
            AND unique_id = %s AND module_id = %s AND ' . $where;

		$sql      = $this->getDb()->prepare($sql, array($unique_id, $module_id, $unique_id, $module_id));
		$old_data = $this->getDb()->get_row($sql, \ARRAY_A);

		return $old_data;
	}

	public function getLastPriceValue($unique_id, $module_id, $offset = null)
	{
		$params = array(
			'select' => 'price',
			'where'  => array('unique_id = %s AND module_id = %s', array($unique_id, $module_id)),
			'order'  => 'create_date DESC',
			'limit'  => 1
		);
		if ($offset)
		{
			$params['offset'] = $offset;
		}
		$row = $this->find($params);
		if (!$row)
		{
			return null;
		}

		return $row['price'];
	}

	public function getPreviousPriceValue($unique_id, $module_id)
	{
		return $this->getLastPriceValue($unique_id, $module_id, 1);
	}

	public function getFirstDateValue($unique_id, $module_id)
	{
		$params = array(
			'select' => 'create_date',
			'where'  => array('unique_id = %s AND module_id = %s', array($unique_id, $module_id)),
			'order'  => 'create_date ASC',
			'limit'  => 1
		);
		$row    = $this->find($params);
		if (!$row)
		{
			return null;
		}

		return $row['create_date'];
	}

	public function getLastPrices($unique_id, $module_id, $limit = 5)
	{
		$params = array(
			'where' => array('unique_id = %s AND module_id = %s', array($unique_id, $module_id)),
			'order' => 'create_date DESC',
			'limit' => $limit,
		);

		return $this->findAll($params);
	}

	public function getMaxPrice($unique_id, $module_id)
	{
		$where = $this->prepareWhere((array(
			'unique_id = %s AND module_id = %s',
			array($unique_id, $module_id)
		)));
		$sql   = 'SELECT t.* FROM ' . $this->tableName() . ' t';
		$sql   .= ' JOIN (SELECT unique_id, MAX(price) maxPrice FROM ' . $this->tableName() . $where . ') t2 ON t.price = t2.maxPrice AND t.unique_id = t2.unique_id;';

		return $this->getDb()->get_row($sql, \ARRAY_A);
	}

	public function getMinPrice($unique_id, $module_id)
	{
		$where = $this->prepareWhere((array(
			'unique_id = %s AND module_id = %s',
			array($unique_id, $module_id)
		)));
		$sql   = 'SELECT t.* FROM ' . $this->tableName() . ' t';
		$sql   .= ' JOIN (SELECT unique_id, MIN(price) minPrice FROM ' . $this->tableName() . $where . ') t2 ON t.price = t2.minPrice AND t.unique_id = t2.unique_id;';

		return $this->getDb()->get_row($sql, \ARRAY_A);
	}

	public function saveData(array $data, $module_id, $post_id = null)
	{
		if (!$post_id)
		{
			global $post;
			if (!empty($post))
			{
				$post_id = $post->ID;
			}
		}
		$saved = 0;
		foreach ($data as $key => $d)
		{
			$stock_status = isset($d['stock_status']) ? $d['stock_status'] : ContentProduct::STOCK_STATUS_UNKNOWN;
			if (empty($d['unique_id']) || empty($d['price']) || $stock_status == ContentProduct::STOCK_STATUS_OUT_OF_STOCK)
			{
				continue;
			}

			$latest_price = $this->getLastPriceValue($d['unique_id'], $module_id);

			// price changed?
			if ($latest_price && (float) $latest_price == (float) $d['price'])
			{
				continue;
			}

			$save = array(
				'unique_id' => $d['unique_id'],
				'module_id' => $module_id,
				'price'     => $d['price'],
				'post_id'   => $post_id,
			);
			$this->save($save);
			$saved++;
		}

		// clean up & optimize
		if ($saved && rand(1, 10) == 10)
		{
			$this->cleanOld((int) GeneralConfig::getInstance()->option('price_history_days'));
		}
	}

	public function getPriceMovers(array $params = array(), $double_limit = false)
	{
		$defaults = array(
			'limit'              => 5,
			'last_update'        => 7,
			'drop_type'          => 'absolute',
			'direction'          => 'drops',
			'include_module_ids' => array(),
			'exclude_module_ids' => array(),
		);

		$params                = \wp_parse_args($params, $defaults);
		$params['limit']       = (int) $params['limit'];
		$params['last_update'] = (int) $params['last_update'];

		// ------------------------------------------------------------
		// Direction (drops or increases)
		// ------------------------------------------------------------
		if ($params['direction'] === 'drops')
		{
			$order           = 'DESC';
			$direction_where = 'price_old - price >= 0';
		}
		else
		{
			$order           = 'ASC';
			$direction_where = 'price_old - price <= 0';
		}

		// ------------------------------------------------------------
		// Limit handling (optionally doubled by caller)
		// ------------------------------------------------------------
		$limit = $params['limit'];
		if ($double_limit)
		{
			$limit *= 2;
		}

		// ------------------------------------------------------------
		// Calculate price change expression (absolute or relative)
		// ------------------------------------------------------------
		$change = ($params['drop_type'] === 'relative')
			? '(100 - (price * 100) / price_old)'
			: '(price_old - price)';

		// ------------------------------------------------------------
		// Dynamic WHERE clauses for module_id inclusion / exclusion
		// ------------------------------------------------------------
		$where_extra  = array();
		$query_params = array();

		// INCLUDED module IDs
		if (!empty($params['include_module_ids']))
		{
			$include_ids  = array_map('strval', (array) $params['include_module_ids']);
			$placeholders = implode(', ', array_fill(0, count($include_ids), '%s'));
			$where_extra[] = "price_history.module_id IN ($placeholders)";
			$query_params  = array_merge($query_params, $include_ids);
		}

		// EXCLUDED module IDs
		if (!empty($params['exclude_module_ids']))
		{
			$exclude_ids  = array_map('strval', (array) $params['exclude_module_ids']);
			$placeholders = implode(', ', array_fill(0, count($exclude_ids), '%s'));
			$where_extra[] = "price_history.module_id NOT IN ($placeholders)";
			$query_params  = array_merge($query_params, $exclude_ids);
		}

		$sql = "
        SELECT
            price_history.*,
            {$change} AS pchange
        FROM {$this->tableName()} AS price_history
        INNER JOIN {$this->getDb()->posts} AS post
            ON post.ID = price_history.post_id
            AND post.post_status = 'publish'
        WHERE {$direction_where}
          AND price_history.is_latest = 1
          AND price_history.create_date >= NOW() - INTERVAL %d DAY
    ";

		// Extra WHERE filters (module_id include / exclude)
		if (!empty($where_extra))
		{
			$sql .= "\n    AND " . implode(' AND ', $where_extra);
		}

		$sql .= "
        ORDER BY
            pchange {$order}
        LIMIT {$limit}
    ";

		// Prepend the last_update parameter so it matches the first %d
		array_unshift($query_params, $params['last_update']);

		$prepared_sql = $this->getDb()->prepare($sql, $query_params);
		$results      = $this->getDb()->get_results($prepared_sql, \ARRAY_A);

		return $results;
	}
}
