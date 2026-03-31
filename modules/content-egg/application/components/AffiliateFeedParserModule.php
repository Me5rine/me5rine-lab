<?php

namespace ContentEgg\application\components;

defined('\ABSPATH') || exit;

use ContentEgg\application\helpers\TemplateHelper;
use ContentEgg\application\components\ModuleManager;
use ContentEgg\application\helpers\CsvSettingsDetector;
use ContentEgg\application\helpers\TextHelper;
use ContentEgg\application\Plugin;

/**
 * AffiliateFeedParserModule abstract class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */
abstract class AffiliateFeedParserModule extends AffiliateParserModule
{
    const TRANSIENT_LAST_IMPORT_DATE = 'cegg_products_last_import_';
    const PRODUCTS_TTL = 43200;
    const MULTIPLE_INSERT_ROWS = 100;
    const IMPORT_TIME_LIMT = 600;
    const DATAFEED_DIR_NAME = 'cegg-datafeeds';
    const TRANSIENT_LAST_IMPORT_ERROR = 'cegg_last_import_error_';

    protected $rmdir;
    protected $product_model;
    protected $product_node;

    abstract public function getProductModel();

    abstract public function getFeedUrl();

    abstract protected function feedProductPrepare(array $data);

    public function __construct($module_id = null)
    {
        parent::__construct($module_id);
        $this->product_model = $this->getProductModel();

        // download feed in background
        \add_action('cegg_' . $this->getId() . '_init_products', array(get_called_class(), 'initProducts'), 10, 1);
    }

    public static function initProducts($module_id)
    {
        $m = ModuleManager::factory($module_id);

        try
        {
            $m->maybeImportProducts();
        }
        catch (\Exception $e)
        {
            $error = $e->getMessage();
            if (!strstr($error, 'Product import is in progress'))
            {
                $m->setLastImportError($error);
            }
        }
    }

    public function requirements()
    {
        $required_version = '5.6.4';
        $mysql_version = $this->product_model->getDb()->get_var('SELECT VERSION();');
        $errors = array();

        if (version_compare($required_version, $mysql_version, '>'))
        {
            $errors[] = sprintf('You are using MySQL %s. This module requires at least <strong>MySQL %s</strong>.', $mysql_version, $required_version);
        }

        return $errors;
    }

    public function isCompressedFeed(): bool
    {
        return $this->isZippedFeed() || $this->isGzipFeed();
    }

    public function isZippedFeed()
    {
        return false;
    }

    public function isGzipFeed()
    {
        return false;
    }

    public function maybeCreateProductTable()
    {
        if (!$this->product_model->isTableExists())
        {
            $this->dbDelta();
        }
    }

    protected function dbDelta()
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = $this->product_model->getDump();
        dbDelta($sql);
    }

    public function getLastImportDate()
    {
        return \get_transient(self::TRANSIENT_LAST_IMPORT_DATE . $this->getId());
    }

    public function getLastImportError()
    {
        return \get_transient(self::TRANSIENT_LAST_IMPORT_ERROR . $this->getId());
    }

    public function setLastImportDate($time = null)
    {
        if ($time === null)
            $time = time();

        \set_transient(self::TRANSIENT_LAST_IMPORT_DATE . $this->getId(), $time);
    }

    public function setLastImportError($error)
    {
        $error = TextHelper::truncate($error, 500);
        \set_transient(self::TRANSIENT_LAST_IMPORT_ERROR . $this->getId(), $error);
    }

    public function maybeImportProducts()
    {
        $last_export = $this->getLastImportDate();

        // product import is in progress?
        if ($last_export && $last_export < 0)
        {
            if (time() + $last_export > static::IMPORT_TIME_LIMT)
                $last_export = 0;
            else
                throw new \Exception('Product import is in progress. Try later.');
        }

        if ($this->isImportTime())
        {
            // remove shedule if exists
            $hook = 'cegg_' . $this->getId() . '_init_products';
            if (\wp_next_scheduled($hook, array('module_id' => $this->getId())))
            {
                \wp_unschedule_event(\wp_next_scheduled($hook, array('module_id' => $this->getId())), $hook, array('module_id' => $this->getId()));
            }

            $this->deleteTemporaryFiles();
            $this->setLastImportDate(time() * -1); // set in progress flag
            $this->maybeCreateProductTable();

            if (! $this->product_model->isTableExists())
            {
                throw new \Exception(
                    sprintf(
                        esc_html__('Table %s does not exist', 'content-egg'),
                        esc_html($this->product_model->tableName())
                    )
                );
            }
            $this->importProducts($this->getFeedUrl());

            return true;
        }

        return false;
    }

    public function getProductsTtl()
    {
        $ttl = (int) \apply_filters('cegg_feed_products_ttl', self::PRODUCTS_TTL);
        $ttl = (int) \apply_filters('cegg_feed_products_module_ttl', $ttl, $this->getId());
        return $ttl;
    }

    public function isImportTime()
    {
        $last_import = $this->getLastImportDate();

        if (!$last_import)
            return true;

        if (\apply_filters('cegg_is_feed_import_time', false, $this->getId(), $last_import))
            return true;

        if (time() - $last_import > $this->getProductsTtl())
            return true;
        else
            return false;
    }

    public function importProducts($feed_url)
    {
        if (!defined('\WP_CLI') || !\WP_CLI)
            @set_time_limit(static::IMPORT_TIME_LIMT);

        \wp_raise_memory_limit();
        $this->setLastImportError('');
        register_shutdown_function(array($this, 'fatalHandler'));
        $this->product_model->truncateTable();
        $file = $this->downloadFeed($feed_url);

        $this->processFeed($file);
        $this->setLastImportDate();

        @unlink($file);
        if ($this->rmdir)
        {
            @rmdir($this->rmdir);
            $this->rmdir = null;
        }
    }

    /**
     * Download (and—if zipped—unzip) a feed in the most memory-efficient way.
     *
     * Supports: http, https, ftp, ftps (incl. anonymous and file at root like /feed.xml).
     *
     * @param string $feed_url
     * @return string Absolute path to the downloaded (or extracted) file.
     * @throws \Exception On failure to download or extract.
     */
    protected function downloadFeed(string $feed_url): string
    {
        if (! function_exists('download_url'))
        {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $scheme = strtolower((string) parse_url($feed_url, PHP_URL_SCHEME));

        // 1) Stream-download (HTTP(S) via WP; FTP(S) via our helper)
        if ($scheme === 'ftp' || $scheme === 'ftps')
        {
            $tmp_file = $this->downloadViaFtp($feed_url, 900);
            if (! $tmp_file)
            {
                $this->setLastImportDate(0);
                throw new \Exception('Failed to download FTP/FTPS feed (unknown error).');
            }
        }
        else
        {
            $tmp_file = \download_url($feed_url, 900);
            if (\is_wp_error($tmp_file))
            {
                $this->setLastImportDate(0);
                throw new \Exception(
                    sprintf(
                        esc_html__('Failed to download feed URL: %s', 'content-egg'),
                        esc_html($tmp_file->get_error_message())
                    )
                );
            }
        }

        // 2) If no archive handling, return as-is
        if (! $this->isCompressedFeed())
        {
            return $tmp_file;
        }

        // 3) ZIP → extract
        if ($this->isZippedFeed())
        {
            $dest_dir = trailingslashit($this->getDatafeedDir())
                . wp_unique_filename($this->getDatafeedDir(), basename($tmp_file) . '-unzipped-dir');

            $result = $this->unzipSingleFeed($tmp_file, $dest_dir);
            if (is_wp_error($result))
            {
                @unlink($tmp_file);
                $this->setLastImportDate(0);
                throw new \Exception(
                    sprintf(
                        esc_html__('Unable to unzip feed archive: %s', 'content-egg'),
                        esc_html($result->get_error_message())
                    )
                );
            }

            @unlink($tmp_file);
            $this->rmdir = $dest_dir; // keep for later cleanup
            return $result;
        }

        // 3) GZ → decompress to a sibling file and return it
        if ($this->isGzipFeed())
        {
            try
            {
                $out = $this->gunzipToFile($tmp_file); // see helper below
                return $out;
            }
            catch (\Throwable $e)
            {
                @unlink($tmp_file);
                $this->setLastImportDate(0);
                throw new \Exception(
                    'Unable to gunzip feed: ' . esc_html(wp_strip_all_tags($e->getMessage()))
                );
            }
        }
    }

    /**
     * Download via FTP/FTPS to a temp file, streaming to disk.
     * Tries: cURL → PHP FTP extension → FTP stream wrapper (FTP only).
     *
     * @param string $ftp_url  ftp://user:pass@host/path/file.xml or ftps://...
     * @param int    $timeout  Total timeout (seconds)
     * @return string Absolute path to temp file.
     * @throws \Exception
     */
    protected function downloadViaFtp(string $ftp_url, int $timeout = 900): string
    {
        if (! function_exists('wp_tempnam'))
        {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $parts = parse_url($ftp_url);
        if (! $parts || empty($parts['host']))
        {
            throw new \Exception(
                esc_html__('Invalid FTP/FTPS URL.', 'content-egg')
            );
        }

        // Normalize path (support `/feed.xml` and deeper paths). Must point to a file.
        $path = isset($parts['path']) ? (string) $parts['path'] : '';
        if ($path === '' || substr($path, -1) === '/')
        {
            $safe = $this->redactUrlCredentials($ftp_url);
            throw new \Exception(
                sprintf(
                    esc_html__('FTP URL must point to a file (got: %s).', 'content-egg'),
                    esc_html($safe)
                )
            );
        }

        // Create temp file
        $tmp_file = \wp_tempnam($ftp_url);
        if (! $tmp_file || ! is_writable($tmp_file))
        {
            throw new \Exception('Could not create a temporary file for FTP download.');
        }

        $errors = [];

        // --- TRY 1: cURL (supports FTP + FTPS; best option) ---
        if (function_exists('curl_init'))
        {
            $fh = @fopen($tmp_file, 'wb');
            if (! $fh)
            {
                @unlink($tmp_file);
                throw new \Exception('Failed opening temp file for writing (FTP).');
            }

            $ch = curl_init();
            $ua = 'ContentEgg/FTPDownloader (+' . home_url('/') . ')';

            $connectTimeout = min(30, max(5, (int) floor($timeout / 3)));

            $curlopts = [
                CURLOPT_URL            => $ftp_url,
                CURLOPT_FILE           => $fh,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 2,
                CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_USERAGENT      => $ua,
                CURLOPT_NOPROGRESS     => true,
                CURLOPT_TRANSFERTEXT   => false, // binary
            ];

            // Passive is default, but enforce if supported
            if (defined('CURLOPT_FTP_USE_EPSV'))
            {
                $curlopts[CURLOPT_FTP_USE_EPSV] = true;
            }

            curl_setopt_array($ch, $curlopts);
            $ok    = curl_exec($ch);
            $errno = curl_errno($ch);
            $err   = curl_error($ch);
            curl_close($ch);
            fclose($fh);

            if ($ok && $errno === 0 && @filesize($tmp_file) > 0)
            {
                return $tmp_file;
            }

            // Cleanup and fall through to next method
            @unlink($tmp_file);
            $errors[] = $err ? 'cURL: ' . $err : 'cURL transfer error';

            // Recreate temp file for next attempt
            $tmp_file = \wp_tempnam($ftp_url);
            if (! $tmp_file)
            {
                throw new \Exception('Could not re-create a temporary file after cURL failure.');
            }
        }

        // --- TRY 2: PHP FTP extension (FTP + FTPS via ftp_ssl_connect) ---
        if (function_exists('ftp_connect') || function_exists('ftp_ssl_connect'))
        {
            $scheme = strtolower(isset($parts['scheme']) ? (string) $parts['scheme'] : 'ftp');

            $host  = (string) $parts['host'];
            $port  = isset($parts['port'])
                ? (int) $parts['port']
                : ($scheme === 'ftps' ? 21 /* explicit TLS default */ : 21);

            $user  = isset($parts['user']) ? rawurldecode((string) $parts['user']) : 'anonymous';
            $pass  = isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : 'anonymous@';

            // Connect
            if ($scheme === 'ftps' && function_exists('ftp_ssl_connect'))
            {
                $conn = @ftp_ssl_connect($host, $port, min($timeout, 30));
            }
            else
            {
                $conn = @ftp_connect($host, $port, min($timeout, 30));
            }

            if ($conn)
            {
                if (function_exists('ftp_set_option') && defined('FTP_TIMEOUT_SEC'))
                {
                    @ftp_set_option($conn, FTP_TIMEOUT_SEC, max(5, min($timeout, 90)));
                }

                if (@ftp_login($conn, $user, $pass))
                {
                    @ftp_pasv($conn, true);
                    // Download (binary)
                    $ok = @ftp_get($conn, $tmp_file, $path, defined('FTP_BINARY') ? FTP_BINARY : 2);
                    @ftp_close($conn);

                    if ($ok && @filesize($tmp_file) > 0)
                    {
                        return $tmp_file;
                    }
                    else
                    {
                        $errors[] = 'FTP extension: transfer failed';
                    }
                }
                else
                {
                    @ftp_close($conn);
                    $errors[] = 'FTP extension: authentication failed';
                }
            }
            else
            {
                $errors[] = 'FTP extension: unable to connect';
            }

            @unlink($tmp_file);
            $tmp_file = \wp_tempnam($ftp_url);
            if (! $tmp_file)
            {
                throw new \Exception('Could not re-create a temporary file after FTP extension failure.');
            }
        }

        // --- TRY 3: FTP stream wrapper (FTP only; no FTPS support) ---
        $scheme = strtolower(isset($parts['scheme']) ? (string) $parts['scheme'] : 'ftp');
        if (
            $scheme === 'ftp'
            && filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)
            && in_array('ftp', (array) @stream_get_wrappers(), true)
        )
        {
            $in = @fopen($ftp_url, 'rb', false);
            if ($in)
            {
                $out = @fopen($tmp_file, 'wb');
                if ($out)
                {
                    $copied = @stream_copy_to_stream($in, $out);
                    @fclose($in);
                    @fclose($out);
                    if ($copied !== false && $copied > 0)
                    {
                        return $tmp_file;
                    }
                    $errors[] = 'FTP wrapper: copy failed';
                }
                else
                {
                    @fclose($in);
                    $errors[] = 'FTP wrapper: cannot open temp file for writing';
                }
            }
            else
            {
                $errors[] = 'FTP wrapper: cannot open remote stream';
            }
        }
        else
        {
            if ($scheme === 'ftp')
            {
                $errors[] = 'FTP wrapper not available';
            }
            else
            { // ftps
                $errors[] = 'FTPS not supported by PHP stream wrapper';
            }
        }

        @unlink($tmp_file);

        $safeUrl = $this->redactUrlCredentials($ftp_url);
        $msg = implode('; ', array_filter($errors));
        throw new \Exception(
            sprintf(
                esc_html__('Failed to download %s: %s', 'content-egg'),
                esc_html($safeUrl),
                esc_html($msg ?: 'no supported method available')
            )
        );
    }

    /**
     * Redact credentials in a URL (user:pass@host → ***:***@host).
     *
     * @param string $url
     * @return string
     */
    protected function redactUrlCredentials(string $url): string
    {
        $parts = parse_url($url);
        if (! $parts)
        {
            return $url;
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $auth   = '';
        if (! empty($parts['user']))
        {
            $auth = '***';
            if (! empty($parts['pass']))
            {
                $auth .= ':***';
            }
            $auth .= '@';
        }
        $host  = isset($parts['host']) ? $parts['host'] : '';
        $port  = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path  = isset($parts['path']) ? $parts['path'] : '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $frag  = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $scheme . $auth . $host . $port . $path . $query . $frag;
    }

    /**
     * Safely and efficiently unzip a single feed file.
     *
     * @param string      $zip_path    Absolute path to the .zip file.
     * @param string      $dest_dir    Absolute path to the directory that should receive the file.
     * @param string|null $feed_inside Optional. Exact relative path (inside the ZIP) of the entry to extract.
     *                                 Leave null to auto-detect the first regular file.
     * @return string|\WP_Error Absolute path to the extracted feed file, or WP_Error on failure.
     */
    protected function unzipSingleFeed($zip_path, $dest_dir, $feed_inside = null)
    {
        if (! file_exists($zip_path) || ! is_readable($zip_path))
        {
            return new \WP_Error('zip_not_found', 'ZIP file does not exist or is not readable.');
        }
        if (! wp_mkdir_p($dest_dir))
        {
            return new \WP_Error('dest_dir_unwritable', 'Destination directory is not writable.', $dest_dir);
        }

        /** ------------------------------------------------------------------
         *  FAST PATH – use ZipArchive if available (streams, no memory spike)
         * ----------------------------------------------------------------- */
        if (class_exists('\ZipArchive'))
        {
            $zip = new \ZipArchive();
            $opened = $zip->open($zip_path, \ZipArchive::CHECKCONS);
            if (true !== $opened)
            {
                return new \WP_Error('zip_open_failed', 'Could not open ZIP archive.', $opened);
            }

            // 1. Decide which entry we will extract.
            if (empty($feed_inside))
            {
                for ($i = 0; $i < $zip->numFiles; $i++)
                {
                    $info = $zip->statIndex($i);
                    if (! $info || str_ends_with($info['name'], '/') || str_starts_with($info['name'], '__MACOSX/'))
                    {
                        continue;               // skip directories & Mac resource forks
                    }
                    if (0 !== validate_file($info['name']))
                    {
                        continue;               // invalid path ­→ skip
                    }
                    $feed_inside = $info['name'];
                    break;
                }
            }
            elseif (false === $zip->locateName($feed_inside, \ZipArchive::FL_NOCASE))
            {
                $zip->close();
                return new \WP_Error('entry_not_found', 'Requested file does not exist in the archive.', $feed_inside);
            }

            if (empty($feed_inside))
            {
                $zip->close();
                return new \WP_Error('no_valid_entry', 'No valid feed file found inside the archive.');
            }

            // 2. Extract just that entry.
            if (! $zip->extractTo($dest_dir, $feed_inside))
            {
                $zip->close();
                return new \WP_Error('extract_failed', 'Could not extract file from archive.', $feed_inside);
            }
            $zip->close();

            return trailingslashit($dest_dir) . basename($feed_inside);
        }

        /** ------------------------------------------------------------------
         *  FALLBACK – ZipArchive missing → use WordPress unzip_file()
         * ----------------------------------------------------------------- */

        if (! function_exists('unzip_file'))
        {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if (! function_exists('WP_Filesystem'))
        {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        global $wp_filesystem;
        if (! $wp_filesystem || ! is_a($wp_filesystem, '\WP_Filesystem_Base'))
        {
            WP_Filesystem();
        }

        $result  = unzip_file($zip_path, $dest_dir);
        if (is_wp_error($result))
        {
            return $result; // propagate core error
        }

        // Locate the feed file we want inside the temporary extraction tree.
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dest_dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $found = null;
        foreach ($iterator as $fileinfo)
        {
            if ($fileinfo->isDir())
            {
                continue;
            }
            if (
                empty($feed_inside) ||
                $fileinfo->getFilename() === basename($feed_inside) ||
                wp_normalize_path($fileinfo->getPathname()) === wp_normalize_path(trailingslashit($dest_dir) . $feed_inside)
            )
            {
                $found = $fileinfo->getPathname();
                break;
            }
        }
        if (! $found)
        {
            return new \WP_Error('entry_not_found', 'Requested file does not exist in the archive.', $feed_inside);
        }

        $dest_file = trailingslashit($dest_dir) . basename($found);

        if (! rename($found, $dest_file))
        {
            return new \WP_Error('move_failed', 'Could not move extracted file to destination.');
        }

        return $dest_file;
    }

    protected function processFeed(string $file): void
    {
        $format = strtolower(trim($this->config('feed_format', 'csv')));

        switch ($format)
        {
            case 'xml':
                $this->processFeedXml($file);
                break;

            case 'json':
                $this->processFeedJson($file);
                break;

            case 'csv':
                $this->processFeedCsv($file);
                break;

            default:
                throw new \InvalidArgumentException(sprintf(
                    'Unsupported feed format: %s',
                    esc_html($format)
                ));
        }
    }

    protected function processFeedCsv($file)
    {
        $encoding      = $this->config('encoding', 'UTF-8');
        $in_stock_only = $this->config('in_stock', false);

        // Read optional manual overrides
        $custom_csv_delimiter = $this->config('csv_delimiter', 'auto'); // 'auto', "\t", ';', ',', '|'
        $custom_csv_enclosure = $this->config('csv_enclosure', 'auto'); // 'auto', '"', "'", 'none'

        // Decide delimiter and enclosure:
        // If BOTH are set (non-auto), skip detection entirely.
        if ($custom_csv_delimiter !== 'auto' && $custom_csv_enclosure !== 'auto')
        {
            $delimiter = (string) $custom_csv_delimiter;
            $enclosure = ($custom_csv_enclosure === 'none') ? "\0" : (string) $custom_csv_enclosure;
        }
        else
        {
            // Detect once
            $csv_settings = $this->detectCsvSettings($file);

            // Apply overrides individually (if any)
            $delimiter = ($custom_csv_delimiter !== 'auto')
                ? (string) $custom_csv_delimiter
                : $csv_settings['delimiter'];

            if ($custom_csv_enclosure === 'none')
            {
                $enclosure = "\0";
            }
            else
            {
                $enclosure = ($custom_csv_enclosure !== 'auto')
                    ? (string) $custom_csv_enclosure
                    : $csv_settings['enclosure'];
            }
        }

        // Open file AFTER deciding settings
        $handle = fopen($file, 'r');
        if (!$handle)
        {
            $this->setLastImportError('Cannot open CSV file.');
            return;
        }

        $fields    = [];
        $products  = [];
        $inserted  = 0;

        $skipped = [
            'invalid_column_count' => 0,
            'exception'            => 0,
            'empty_product'        => 0,
            'out_of_stock'         => 0,
        ];

        $escape = '\\';

        while (($data = fgetcsv($handle, null, $delimiter, $enclosure, $escape)) !== false)
        {
            $data = self::convertEncoding($data, $encoding);

            if (!$fields)
            {
                // first row → header
                $data   = str_replace("\xEF\xBB\xBF", '', $data);   // strip BOM
                $fields = array_map('trim', $data);
                continue;
            }

            // Trim spaces and plain quotes around values (keeps inner quotes)
            $data = array_map(static fn($item) => trim((string)$item, " '"), $data);

            // ignore unnamed columns
            if (count($data) > count($fields))
            {
                $data = array_slice($data, 0, count($fields));
            }

            if (count($fields) !== count($data))
            {
                ++$skipped['invalid_column_count'];
                continue;
            }

            $data = array_combine($fields, $data);

            try
            {
                $product = $this->feedProductPrepare($data);
            }
            catch (\Exception $e)
            {
                if ($inserted > 0)
                {
                    ++$skipped['exception'];
                    continue;
                }
                $this->setLastImportError($e->getMessage());
                fclose($handle);
                return;
            }

            if (!$product)
            {
                ++$skipped['empty_product'];
                continue;
            }

            if (!empty($product['ean']))
            {
                $product['ean'] = TextHelper::fixEan($product['ean']);
            }

            if (
                $in_stock_only &&
                $product['stock_status'] == ContentProduct::STOCK_STATUS_OUT_OF_STOCK
            )
            {
                ++$skipped['out_of_stock'];
                continue;
            }

            $products[] = $product;
            ++$inserted;

            if ($inserted % static::MULTIPLE_INSERT_ROWS === 0)
            {
                $this->product_model->multipleInsert($products, static::MULTIPLE_INSERT_ROWS);
                $products = [];
            }
        }

        if ($products)
        {
            $this->product_model->multipleInsert($products, static::MULTIPLE_INSERT_ROWS);
        }

        // build warning about skipped products
        $skipped = array_filter($skipped);
        if (Plugin::isDevEnvironment() && $skipped)
        {
            $parts = [];
            foreach ($skipped as $reason => $count)
            {
                $parts[] = sprintf('%d %s', $count, str_replace('_', ' ', $reason));
            }
            $warning_skipped_products = 'Skipped products: ' . implode(', ', $parts);
            $this->setLastImportError($warning_skipped_products);
        }

        fclose($handle);
    }

    protected function processFeedJson($file)
    {
        $encoding = $this->config('encoding', 'UTF-8');
        $in_stock_only = $this->config('in_stock', false);

        $json = file_get_contents($file);
        $json_arr = json_decode($json, true);

        if (!$json_arr)
        {
            $this->setLastImportError(trim('Cannot decode JSON source. ' . json_last_error_msg()));
            return;
        }

        $node = $this->getProductNode($file, 'json');

        if (!$node && is_array($json_arr))
        {
            $node = 'products';
            $json_arr = array($node => $json_arr);
        }

        if (!isset($json_arr[$node]) || !is_array($json_arr[$node]))
        {
            $this->setLastImportError('The product node "' . \esc_html($node) . '" does not exist.');

            return;
        }

        $i = 0;
        foreach ($json_arr[$node] as $data)
        {
            if (!$data)
            {
                continue;
            }

            $data = self::convertEncoding($data, $encoding);

            try
            {
                $product = $this->feedProductPrepare($data);
            }
            catch (\Exception $e)
            {
                if ($i > 0)
                {
                    continue;
                }
                $this->setLastImportError($e->getMessage());

                return;
            }

            if (!$product)
            {
                continue;
            }

            if (!empty($product['ean']))
            {
                $product['ean'] = TextHelper::fixEan($product['ean']);
            }

            if ($in_stock_only && $product['stock_status'] == ContentProduct::STOCK_STATUS_OUT_OF_STOCK)
            {
                continue;
            }

            $products[] = $product;
            $i++;
            if ($i % static::MULTIPLE_INSERT_ROWS == 0)
            {
                $this->product_model->multipleInsert($products, static::MULTIPLE_INSERT_ROWS);
                $products = array();
            }
            $i++;
        }
        if ($products)
        {
            $this->product_model->multipleInsert($products, static::MULTIPLE_INSERT_ROWS);
        }
    }

    /**
     * Process a feed XML file using a selectable XML processor.
     */
    protected function processFeedXml($file)
    {
        $processor = (string) $this->config('xml_processor', 'XmlStringStreamer');
        if ($processor === 'XmlReader')
        {
            $this->processFeedXmlReader($file);
        }
        else
        {
            // Default (and back-compat)
            $this->processFeedXmlStreamer($file);
        }
    }

    /** ===========================
     *  Shared small helpers
     *  ===========================
     */

    /** Sanitize a node’s XML string to avoid libxml choking on bad bytes */
    protected function cleanXmlString($xml)
    {
        // Strip UTF-8 BOM if present
        $xml = preg_replace('/^\xEF\xBB\xBF/', '', $xml);
        // Remove non-printable control chars (keep newline & tab)
        $xml = preg_replace('/[^\P{C}\n\t]/u', '', $xml);
        // Ensure valid UTF-8 (repairs broken sequences)
        $xml = mb_convert_encoding($xml, 'UTF-8', 'UTF-8');
        return trim($xml);
    }

    /** Libxml flags we’ll use in both paths */
    protected function getLibxmlFlags()
    {
        $flags = 0;
        if (defined('LIBXML_NOCDATA'))
        {
            $flags |= LIBXML_NOCDATA;
        }
        if (defined('LIBXML_NONET'))
        {
            $flags |= LIBXML_NONET;
        }
        if (defined('LIBXML_PARSEHUGE'))
        {
            $flags |= LIBXML_PARSEHUGE;
        }
        return $flags;
    }

    /** Identify real top-level product nodes (skip inner <URL><product>) */
    protected function isTopLevelProductNode(\SimpleXMLElement $n)
    {
        // Most “product” entries in these feeds have @product_id
        return isset($n['product_id']) && (string)$n['product_id'] !== '';
    }

    /** =======================================
     *  XmlStringStreamer implementation (hardened)
     *  ======================================= */
    protected function processFeedXmlStreamer($file)
    {
        if (!is_string($file) || !is_readable($file) || filesize($file) < 16)
        {
            $this->setLastImportError('Feed file is empty or unreadable.');
            return;
        }

        $uniqueNode = $this->getProductNode($file, 'xml');
        if (!$uniqueNode)
        {
            $uniqueNode = 'product';
        }

        // Create the streamer
        $streamer = \ContentEgg\application\vendor\XmlStringStreamer\XmlStringStreamer::createUniqueNodeParser(
            $file,
            array('uniqueNode' => $uniqueNode)
        );

        $in_stock_only = (bool) $this->config('in_stock', false);
        $encoding      = (string) $this->config('encoding', 'UTF-8');
        $xmlFlags      = $this->getLibxmlFlags();

        libxml_use_internal_errors(true);

        $i = 0;
        $products = array();

        while ($node_string = $streamer->getNode())
        {
            // Encoding normalization (optional)
            if ($encoding !== 'UTF-8')
            {
                $converted = @iconv($encoding, 'UTF-8//TRANSLIT//IGNORE', $node_string);
                if ($converted !== false)
                {
                    $node_string = $converted;
                }
            }

            // Clean bytes
            $node_string = $this->cleanXmlString($node_string);
            if ($node_string === '')
            {
                continue;
            }

            libxml_clear_errors();
            $node = @simplexml_load_string($node_string, 'SimpleXMLElement', $xmlFlags);

            if ($node === false)
            {
                $error = libxml_get_last_error();
                $msg   = $error ? trim($error->message) : 'unknown XML error';

                // If the chunk contains multiple siblings → wrap and iterate
                if ($error && stripos($msg, 'extra content at the end of the document') !== false)
                {
                    libxml_clear_errors();
                    $wrapped = '<__ce_wrapper>' . $node_string . '</__ce_wrapper>';
                    $root    = @simplexml_load_string($wrapped, 'SimpleXMLElement', $xmlFlags);

                    if ($root !== false)
                    {
                        // Prefer only elements that look like top-level products
                        $candidates = $root->xpath('./' . $uniqueNode . '[@product_id]');
                        if ($candidates === false || $candidates === null)
                        {
                            // Fallback: iterate all named nodes; we’ll filter below
                            $candidates = $root->{$uniqueNode};
                        }

                        foreach ($candidates as $n)
                        {
                            if (!$n instanceof \SimpleXMLElement)
                            {
                                continue;
                            }
                            // Skip inner URL/product, etc.
                            if ($uniqueNode === 'product' && !$this->isTopLevelProductNode($n))
                            {
                                continue;
                            }

                            $data = $this->mapXmlData($n);

                            try
                            {
                                $product = $this->feedProductPrepare($data);
                            }
                            catch (\Exception $e)
                            {
                                if ($i > 0)
                                {
                                    continue;
                                }
                                $this->setLastImportError($e->getMessage());
                                return;
                            }

                            if (!$product)
                            {
                                continue;
                            }
                            if (!empty($product['ean']))
                            {
                                $product['ean'] = TextHelper::fixEan($product['ean']);
                            }
                            if ($in_stock_only && $product['stock_status'] == ContentProduct::STOCK_STATUS_OUT_OF_STOCK)
                            {
                                continue;
                            }

                            $products[] = $product;
                            $i++;
                            if ($i % static::MULTIPLE_INSERT_ROWS == 0)
                            {
                                $this->product_model->multipleInsert($products, static::MULTIPLE_INSERT_ROWS);
                                $products = array();
                            }
                        }
                        // Done with this chunk
                        continue;
                    }
                }

                // Hard failure for this chunk
                $this->setLastImportError(
                    'Unable to load XML source. ' . $msg
                        . ' (line ' . (int)($error ? $error->line : 0) . ', col ' . (int)($error ? $error->column : 0) . ')'
                );
                return;
            }

            // Normal single-node path; guard against inner URL/product when uniqueNode is 'product'
            if ($uniqueNode === 'product' && !$this->isTopLevelProductNode($node))
            {
                continue;
            }

            $data = $this->mapXmlData($node);

            try
            {
                $product = $this->feedProductPrepare($data);
            }
            catch (\Exception $e)
            {
                if ($i > 0)
                {
                    continue;
                }
                $this->setLastImportError($e->getMessage());
                return;
            }

            if (!$product)
            {
                continue;
            }
            if (!empty($product['ean']))
            {
                $product['ean'] = TextHelper::fixEan($product['ean']);
            }
            if ($in_stock_only && $product['stock_status'] == ContentProduct::STOCK_STATUS_OUT_OF_STOCK)
            {
                continue;
            }

            $products[] = $product;
            $i++;
            if ($i % static::MULTIPLE_INSERT_ROWS == 0)
            {
                $this->product_model->multipleInsert($products, static::MULTIPLE_INSERT_ROWS);
                $products = array();
            }
        }

        if ($i == 0)
        {
            $this->setLastImportError('Product node not found in the feed.');
        }

        if ($products)
        {
            $this->product_model->multipleInsert($products, static::MULTIPLE_INSERT_ROWS);
        }
    }

    /** ==================================
     *  XmlReader implementation (robust)
     *  ================================== */
    protected function processFeedXmlReader($file)
    {
        if (!is_string($file) || !is_readable($file) || filesize($file) < 16)
        {
            $this->setLastImportError('Feed file is empty or unreadable.');
            return;
        }

        $uniqueNode = $this->getProductNode($file, 'xml');
        if (!$uniqueNode)
        {
            $uniqueNode = 'product';
        }

        $in_stock_only = (bool) $this->config('in_stock', false);
        $encoding      = (string) $this->config('encoding', 'UTF-8');
        $xmlFlags      = $this->getLibxmlFlags();

        libxml_use_internal_errors(true);

        $reader = new \XMLReader();
        $openFlags = 0;
        if (defined('LIBXML_NONET'))
        {
            $openFlags |= LIBXML_NONET;
        }
        if (defined('LIBXML_PARSEHUGE'))
        {
            $openFlags |= LIBXML_PARSEHUGE;
        }

        if (!$reader->open($file, null, $openFlags))
        {
            $this->setLastImportError('Unable to open XML file for streaming.');
            return;
        }

        $i = 0;
        $products = array();

        while ($reader->read())
        {
            if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->name !== $uniqueNode)
            {
                continue;
            }

            // Skip nested <URL><product> when unique node is 'product'
            if ($uniqueNode === 'product')
            {
                $productId = $reader->getAttribute('product_id');
                if ($productId === null || $productId === '')
                {
                    // Fast-forward past this nested element
                    if ($reader->isEmptyElement)
                    {
                        continue;
                    }
                    $reader->next($uniqueNode);
                    continue;
                }
            }

            $nodeXml = $reader->readOuterXML();
            if ($nodeXml === false || $nodeXml === '')
            {
                continue;
            }

            if ($encoding !== 'UTF-8')
            {
                $converted = @iconv($encoding, 'UTF-8//TRANSLIT//IGNORE', $nodeXml);
                if ($converted !== false)
                {
                    $nodeXml = $converted;
                }
            }

            $nodeXml = $this->cleanXmlString($nodeXml);

            libxml_clear_errors();
            $node = @simplexml_load_string($nodeXml, 'SimpleXMLElement', $xmlFlags);
            if ($node === false)
            {
                $err = libxml_get_last_error();
                // Skip bad product but keep importing others
                $this->setLastImportError('Unable to load XML source for a product: ' . ($err ? trim($err->message) : 'unknown'));
                continue;
            }

            $data = $this->mapXmlData($node);

            try
            {
                $product = $this->feedProductPrepare($data);
            }
            catch (\Exception $e)
            {
                if ($i > 0)
                {
                    continue;
                }
                $this->setLastImportError($e->getMessage());
                $reader->close();
                return;
            }

            if (!$product)
            {
                continue;
            }

            if (!empty($product['ean']))
            {
                $product['ean'] = TextHelper::fixEan($product['ean']);
            }

            if ($in_stock_only && $product['stock_status'] == ContentProduct::STOCK_STATUS_OUT_OF_STOCK)
            {
                continue;
            }

            $products[] = $product;
            $i++;

            if ($i % static::MULTIPLE_INSERT_ROWS == 0)
            {
                $this->product_model->multipleInsert($products, static::MULTIPLE_INSERT_ROWS);
                $products = array();
            }
        }

        $reader->close();

        if ($i == 0)
        {
            $this->setLastImportError('Product node not found in the feed.');
        }

        if ($products)
        {
            $this->product_model->multipleInsert($products, static::MULTIPLE_INSERT_ROWS);
        }
    }

    protected function mapXmlData(\SimpleXMLElement $node): array
    {
        $data       = [];
        $mapping    = $this->config('mapping', []);
        $fields     = array_values($mapping);

        $attributes = $node->attributes();
        $children   = get_object_vars($node);

        foreach ($fields as $field)
        {
            $value = $this->extractXmlField($node, $field, $attributes, $children);
            if ($value !== null)
            {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    private function extractXmlField(
        \SimpleXMLElement $node,
        string $field,
        ?\SimpleXMLElement $attributes = null,
        array $children = []
    ): ?string
    {
        // 1) XPath if it's a path
        if (strpos($field, '/') !== false)
        {
            $result = $node->xpath($field);
            return $this->sanitizeXPathResult($result);
        }

        // 2) Attribute of the current node
        if ($attributes && isset($attributes[$field]))
        {
            return (string) $attributes[$field];
        }

        // 3) Direct child element
        if (isset($children[$field]))
        {
            return $this->sanitizeString((string) $children[$field]);
        }

        // 4) Fallback: maybe someone slipped in a non‐XPath, non‐direct name?
        $result = $node->xpath($field);
        return $this->sanitizeXPathResult($result);
    }

    private function sanitizeXPathResult($result): ?string
    {
        if (empty($result) || !isset($result[0]))
        {
            return null;
        }
        return $this->sanitizeString((string) $result[0]);
    }

    private function sanitizeString(string $input): string
    {
        return trim(\wp_strip_all_tags($input));
    }

    public function isImportInProgress()
    {
        $last_import = $this->getLastImportDate();

        if ($last_import && $last_import < 0)
        {
            return true;
        }

        return false;
    }

    public function isImportScheduled()
    {
        $hook = 'cegg_' . $this->getId() . '_init_products';
        if (\wp_next_scheduled($hook, array('module_id' => $this->getId())))
        {
            return true;
        }

        return false;
    }

    public function getLastImportDateReadable()
    {
        $last_import = $this->getLastImportDate();

        if (empty($last_import))
        {
            return '';
        }

        if ($last_import < 0)
        {
            return __('Product import is in progress', 'content-egg');
        }

        if (time() - $last_import <= 43200)
        {
            return sprintf(__('%s ago', '%s = human-readable time difference', 'content-egg'), \human_time_diff($last_import, time()));
        }

        return TemplateHelper::dateFormatFromGmt($last_import, true);
    }

    public function getProductCount()
    {
        if (!$this->product_model->isTableExists())
        {
            return 0;
        }

        return $this->product_model->count();
    }

    protected function getDatafeedDir()
    {
        $upload_dir = \wp_upload_dir();
        $datafeed_dir = $upload_dir['basedir'] . '/' . static::DATAFEED_DIR_NAME;

        if (is_dir($datafeed_dir))
        {
            return $datafeed_dir;
        }

        $files = array(
            array(
                'file' => 'index.html',
                'content' => '',
            ),
            array(
                'file' => '.htaccess',
                'content' => 'deny from all',
            ),
        );

        foreach ($files as $file)
        {
            if (\wp_mkdir_p($datafeed_dir) && !file_exists(trailingslashit($datafeed_dir) . $file['file']))
            {
                if ($file_handle = @fopen(trailingslashit($datafeed_dir) . $file['file'], 'w'))
                {
                    fwrite($file_handle, $file['content']);
                    fclose($file_handle);
                }
            }
        }

        if (!is_dir($datafeed_dir))
        {
            throw new \Exception('Can not create temporary directory for datafeed.');
        }

        return $datafeed_dir;
    }

    protected function detectCsvSettings($file)
    {
        $detector = new CsvSettingsDetector();
        return $detector->detect($file);
    }

    public function fatalHandler()
    {
        if (!$error = error_get_last())
        {
            return;
        }

        if (!isset($error['file']) || !strpos($error['file'], 'AffiliateFeedParserModule.php'))
        {
            return;
        }

        $message = $error['message'];
        if (strstr($message, 'Allowed memory size'))
        {
            $message .= '. ' . __('Your data feed is too large and cannot be imported. Use a smaller feed or increase WP_MAX_MEMORY_LIMIT.', 'content-egg');
        }

        $this->setLastImportError($message);
    }

    public function deleteTemporaryFiles()
    {
        $dir = trailingslashit($this->getDatafeedDir());
        $parts = explode('/', $dir);
        if ($parts[count($parts) - 2] !== self::DATAFEED_DIR_NAME)
        {
            throw new \Exception('Unexpected error while cleaning temporary directory.');

            return;
        }

        $scanned = array_values(array_diff(scandir($dir), array('..', '.', 'index.html', '.htaccess')));
        if (!$scanned)
        {
            return;
        }

        global $wp_filesystem;
        if (!$wp_filesystem)
        {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            \WP_Filesystem();
        }

        foreach ($scanned as $s)
        {
            $path = $dir . $s;

            if (is_dir($path) && !preg_match('/-unzipped-dir$/', $path))
            {
                continue;
            }

            if (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) !== 'csv')
            {
                continue;
            }

            if ($wp_filesystem->exists($path) && time() - filemtime($path) > 1200)
            {
                $wp_filesystem->delete($path, true);
            }
        }
    }

    public function getProductNode($file, $format)
    {
        $mapping = $this->config('mapping');
        if (!empty($mapping['product node']))
        {
            $this->product_node = $mapping['product node'];
        }
        elseif ($format == 'xml')
        {
            $this->product_node = $this->detectLikelyProductNode($file, $format);
        }

        return $this->product_node;
    }

    public static function extractShippingCost($shipping_cost)
    {
        $shipping_cost = \apply_filters('cegg_shipping_cost_value', $shipping_cost);

        if (strstr($shipping_cost, ':') && strstr($shipping_cost, ','))
        {
            $parts = explode(',', $shipping_cost);
            $shipping_cost = reset($parts);
        }
        elseif (strstr($shipping_cost, ':'))
        {
            $parts = explode(':', $shipping_cost);
            foreach ($parts as $p)
            {
                if (strstr($p, 'EUR') || strstr($p, 'USD'))
                {
                    $shipping_cost = $p;
                    break;
                }
            }
        }

        if ($shipping_cost == '')
            return '';

        return (float) TextHelper::parsePriceAmount($shipping_cost);
    }

    public function refreshFeedData($is_active)
    {
        $this->setLastImportDate(0);
        $this->setLastImportError('');

        $hook = 'cegg_' . $this->getId() . '_init_products';

        if ($is_active && !$this->isImportScheduled())
        {
            \wp_schedule_single_event(time() + 1, $hook, array('module_id' => $this->getId()));
        }

        if (!$is_active && $this->isImportScheduled())
        {
            \wp_clear_scheduled_hook($hook, array('module_id' => $this->getId()));
        }
    }

    public static function convertEncoding(array $data, string $encoding): array
    {
        array_walk_recursive($data, function (&$value) use ($encoding)
        {
            if (!is_string($value))
            {
                return;
            }
            $value = mb_convert_encoding(
                $value,
                'UTF-8',
                $encoding === 'ISO-8859-1' ? 'ISO-8859-1' : 'UTF-8'
            );
        });

        return $data;
    }

    /**
     * Detect the most likely product node name by sampling the first-level children
     * of the XML root. Honors the "xml_processor" option: XmlReader or XmlStringStreamer.
     *
     * @param string $filePath
     * @param string $format     Expected 'xml'; others return null.
     * @param int    $sampleCount How many first-level child elements to sample before deciding.
     * @return string|null
     */
    public function detectLikelyProductNode(string $filePath, string $format, int $sampleCount = 100): ?string
    {
        if (strtolower($format) !== 'xml' || !is_readable($filePath))
        {
            return null;
        }

        $processor = (string) $this->config('xml_processor', 'XmlStringStreamer');

        if ($processor === 'XmlReader' && class_exists('\XMLReader'))
        {
            return $this->detectProductNodeWithXmlReader($filePath, $sampleCount);
        }

        // Fallback to streamer (default/back-compat)
        return $this->detectProductNodeWithStreamer($filePath, $sampleCount);
    }

    /**
     * XmlReader-based detector: counts element names at depth=1 (direct children of root).
     */
    protected function detectProductNodeWithXmlReader(string $filePath, int $sampleCount): ?string
    {
        $counts = [];

        libxml_use_internal_errors(true);

        $reader = new \XMLReader();
        $flags  = 0;
        if (defined('LIBXML_NONET'))
        {
            $flags |= LIBXML_NONET;
        }
        if (defined('LIBXML_PARSEHUGE'))
        {
            $flags |= LIBXML_PARSEHUGE;
        }

        if (!$reader->open($filePath, null, $flags))
        {
            return null;
        }

        // Move to the root element
        while ($reader->read() && $reader->nodeType !== \XMLReader::ELEMENT)
        { /* skip */
        }
        if ($reader->nodeType !== \XMLReader::ELEMENT)
        {
            $reader->close();
            return null;
        }
        $rootDepth = $reader->depth; // usually 0

        // Count direct children of root (depth = rootDepth + 1)
        while ($reader->read())
        {
            if ($reader->nodeType === \XMLReader::ELEMENT && $reader->depth === $rootDepth + 1)
            {
                $name = $reader->name;
                if (!isset($counts[$name]))
                {
                    $counts[$name] = 0;
                }
                $counts[$name]++;

                // stop early
                $total = array_sum($counts);
                if ($total >= $sampleCount)
                {
                    break;
                }
            }
            // Fast-exit when we leave the root scope
            if ($reader->depth < $rootDepth)
            {
                break;
            }
        }

        $reader->close();

        if (!$counts)
        {
            return null;
        }

        arsort($counts);
        return array_key_first($counts);
    }

    /**
     * XmlStringStreamer-based detector: samples nodes and counts their direct child names.
     * Back-compat path when XmlReader is not selected/available.
     */
    protected function detectProductNodeWithStreamer(string $filePath, int $sampleCount): ?string
    {
        // Using the vendor streamer directly (generic walker; we don't yet know the unique node)
        $stream  = new \ContentEgg\application\vendor\XmlStringStreamer\Stream\File($filePath);
        $parser  = new \ContentEgg\application\vendor\XmlStringStreamer\Parser\StringWalker();
        $streamer = new \ContentEgg\application\vendor\XmlStringStreamer\XmlStringStreamer($parser, $stream);

        libxml_use_internal_errors(true);

        $counts = [];

        while ($node = $streamer->getNode())
        {
            // Be tolerant of bad bytes
            $node = $this->cleanXmlStringForDetection($node);

            $xml = @simplexml_load_string(
                $node,
                'SimpleXMLElement',
                (defined('LIBXML_NONET') ? LIBXML_NONET : 0) | (defined('LIBXML_PARSEHUGE') ? LIBXML_PARSEHUGE : 0)
            );
            if (!$xml)
            {
                continue;
            }

            // Count direct children of this element (works when node is the root or a large container)
            foreach ($xml->children() as $child)
            {
                $name = $child->getName();
                if (!isset($counts[$name]))
                {
                    $counts[$name] = 0;
                }
                $counts[$name]++;
            }

            // Stop early if enough samples gathered
            $total = array_sum($counts);
            if ($total >= $sampleCount)
            {
                break;
            }
        }

        if (!$counts)
        {
            return null;
        }

        arsort($counts);
        return array_key_first($counts);
    }

    /**
     * Light sanitizer for detection (avoid libxml choking during sampling).
     */
    protected function cleanXmlStringForDetection(string $xml): string
    {
        // Strip UTF-8 BOM
        $xml = preg_replace('/^\xEF\xBB\xBF/', '', $xml);
        // Remove non-printable control chars except \n and \t
        $xml = preg_replace('/[^\P{C}\n\t]/u', '', $xml);
        // Ensure valid UTF-8
        $xml = mb_convert_encoding($xml, 'UTF-8', 'UTF-8');
        return trim($xml);
    }

    /**
     * Decompress a .gz file to a sibling file (streamed, low-memory).
     * Returns the decompressed file path. Deletes the original .gz on success.
     *
     * @throws \Exception
     */
    protected function gunzipToFile(string $gz_path): string
    {
        if (!file_exists($gz_path) || !is_readable($gz_path))
        {
            throw new \Exception('GZIP file does not exist or is not readable.');
        }
        if (!function_exists('gzopen'))
        {
            throw new \Exception('zlib is not available on this PHP installation.');
        }

        $dir = dirname($gz_path);
        $base = basename($gz_path);
        $out  = preg_replace('/\.gz$/i', '', $base);
        if (!$out || $out === $base)
        {
            $out = $base . '.out';
        }
        $out_path = trailingslashit($dir) . $out;

        $in = @gzopen($gz_path, 'rb');
        if (!$in)
        {
            throw new \Exception('Unable to open gzip file for reading.');
        }
        $fh = @fopen($out_path, 'wb');
        if (!$fh)
        {
            @gzclose($in);
            throw new \Exception('Unable to open output file for gzip decompression.');
        }

        // Stream in chunks to avoid memory spikes
        while (!gzeof($in))
        {
            $buf = gzread($in, 131072); // 128 KiB
            if ($buf === false)
            {
                @gzclose($in);
                @fclose($fh);
                @unlink($out_path);
                throw new \Exception('Gzip read error.');
            }
            if (fwrite($fh, $buf) === false)
            {
                @gzclose($in);
                @fclose($fh);
                @unlink($out_path);
                throw new \Exception('Gzip write error.');
            }
        }

        @gzclose($in);
        @fclose($fh);
        @unlink($gz_path); // remove the .gz source

        return $out_path;
    }
}
