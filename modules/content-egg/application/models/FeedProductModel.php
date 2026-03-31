<?php

namespace ContentEgg\application\models;

defined('\ABSPATH') || exit;

use ContentEgg\application\helpers\TextHelper;

use function ContentEgg\prnx;

/**
 * FeedProductModel class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
abstract class FeedProductModel extends Model
{

    public function getDump()
    {
        return "CREATE TABLE " . $this->tableName() . " (
                    id bigint(20) unsigned NOT NULL,
                    stock_status tinyint(1) DEFAULT 0,
                    price float(12,2) DEFAULT NULL,
                    title text,
                    ean varchar(13) DEFAULT NULL,
                    orig_url text,
                    product text,
                    PRIMARY KEY  (id),
                    KEY uid (stock_status),
                    KEY orig_url (orig_url(60)),
                    KEY ean (ean(13)),
                    KEY price (price),
                    FULLTEXT (title)
                    ) $this->charset_collate;";
    }

    public function searchByUrl($url, $partial_match = false, $limit = 1)
    {
        $db    = $this->getDb();
        $table = $this->tableName();

        $limit = (int) $limit;
        if ($limit < 1)
        {
            $limit = 1;
        }

        $like = $db->esc_like($url);

        if ($partial_match)
        {
            // STARTS WITH: only trailing %
            $like .= '%';
        }
        else
        {
            // exact match => no wildcard, limit 1
            $limit = 1;
        }

        $sql = $db->prepare(
            "SELECT * FROM {$table} WHERE orig_url LIKE %s LIMIT %d",
            $like,
            $limit
        );

        return $db->get_results($sql, ARRAY_A);
    }

    public function searchByEan($ean, $limit = 10, $options = array())
    {
        $where = 'ean = %s';
        if (!empty($options['price_min']))
            $where .= $this->getDb()->prepare('AND price >= %d', $options['price_min']);
        if (!empty($options['price_max']))
            $where .= $this->getDb()->prepare(' AND price <= %d', $options['price_max']);

        $ean = TextHelper::fixEan($ean);
        $sql = $this->getDb()->prepare('SELECT * FROM ' . $this->tableName() . ' WHERE ' . $where . ' LIMIT %d', $ean, $limit);

        return $this->getDb()->get_results($sql, \ARRAY_A);
    }

    public function searchByKeyword($keyword, $limit = 10, $options = array())
    {
        $where = '';
        if (!empty($options['price_min']))
            $where = $this->getDb()->prepare('price >= %d', $options['price_min']);

        if (!empty($options['price_max']))
        {
            if ($where)
                $where .= ' AND ';
            $where .= $this->getDb()->prepare('price <= %d', $options['price_max']);
        }
        if ($where)
            $where = ' AND ' . $where;

        if (isset($options['search_type']) && $options['search_type'] == 'exact')
            $sql = $this->getDb()->prepare('SELECT * FROM ' . $this->tableName() . ' WHERE title COLLATE utf8mb4_unicode_520_ci LIKE %s' . $where . ' LIMIT %d', '%' . $keyword . '%', $limit);
        elseif (isset($options['search_type']) && $options['search_type'] == 'strict')
            $sql = $this->getDb()->prepare('SELECT * FROM ' . $this->tableName() . ' WHERE MATCH (title) AGAINST (%s IN BOOLEAN MODE)' . $where . ' LIMIT %d', self::prepareStricKeyword($keyword), $limit);
        else
            $sql = $this->getDb()->prepare('SELECT * FROM ' . $this->tableName() . ' WHERE MATCH (title) AGAINST (%s)' . $where . ' LIMIT %d', $keyword, $limit);

        return $this->getDb()->get_results($sql, \ARRAY_A);
    }

    public function prepareStricKeyword($keyword)
    {
        $keyword = str_replace(array('+', '-', '/', '"', '\'', '\\'), '', $keyword);
        $keyword = trim(preg_replace('/\s+/', ' ', $keyword));
        $keyword = str_replace(' ', ' +', $keyword);
        $keyword = '+' . $keyword;

        return $keyword;
    }

    public function searchById($id)
    {
        $sql = $this->getDb()->prepare('SELECT * FROM ' . $this->tableName() . ' WHERE id = %s LIMIT 1', $id);

        return $this->getDb()->get_row($sql, \ARRAY_A);
    }

    public function getAllUrls()
    {
        return $this->getDb()->get_col('SELECT orig_url FROM ' . $this->tableName());
    }

    public function getEans()
    {
        return $this->getDb()->get_col('SELECT ean FROM ' . $this->tableName() . ' WHERE ean != ""');
    }

    public function getDublicateEans()
    {
        return $this->getDb()->get_col('SELECT ean, COUNT(*) c FROM ' . $this->tableName() . ' WHERE ean != "" GROUP BY ean HAVING c > 1');
    }
}
