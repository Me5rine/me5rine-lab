<?php

namespace ContentEgg\application\helpers;

use ContentEgg\application\ImageProxy;



defined('\ABSPATH') || exit;

/**
 * ImageHelper class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 *
 */
class ImageHelper
{

	const DOWNLOAD_TIMEOUT = 10;
	const USERAGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.3 Safari/605.1.15';
	const USERAGENT2 = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0';

	public static function saveImgLocaly($img_uri, $title = '', $check_image_type = true)
	{
		$newfilename = TextHelper::truncate($title);
		$newfilename = preg_replace('/[^a-zA-Z0-9\-]/', '', $newfilename);
		$newfilename = strtolower($newfilename);
		if (!$newfilename)
		{
			$newfilename = time();
		}

		$uploads = \wp_upload_dir();

		if ($newfilename = self::downloadImg($img_uri, $uploads['path'], $newfilename, null, $check_image_type))
		{
			return $newfilename;
		}
		else
		{
			return false;
		}
	}

	static public function getUserAgent($img_uri)
	{
		if (strpos($img_uri, 'https://www.iciparisxl.nl/medias') !== false)
			$user_agent = self::USERAGENT2;
		else
			$user_agent = self::USERAGENT;

		return \apply_filters('cegg_image_user_agent', $user_agent, $img_uri);
	}

	public static function downloadImg($img_uri, $save_dir, $file_name, $file_ext = null, $check_image_type = true)
	{
		$response = \wp_remote_get($img_uri, array(
			'timeout'     => self::DOWNLOAD_TIMEOUT,
			'redirection' => 1,
			'sslverify'   => false,
			'user-agent'  => self::getUserAgent($img_uri),
		));

		if (\is_wp_error($response) || (int) \wp_remote_retrieve_response_code($response) !== 200)
			return false;

		if ($file_ext === null)
		{
			$img_path = parse_url($img_uri, PHP_URL_PATH);
			$file_ext = pathinfo(basename($img_path), PATHINFO_EXTENSION);
			if (!$file_ext || $file_ext == 'aspx' || $file_ext == 'image')
			{
				$headers = \wp_remote_retrieve_headers($response);
				if (empty($headers['content-type']))
					return false;

				$types = array_search(str_replace(';charset=UTF-8', '', $headers['content-type']), \wp_get_mime_types());

				if (!$types)
					return false;

				$exts     = explode('|', $types);
				$file_ext = $exts[0];
			}
		}
		if ($file_ext)
		{
			$file_name .= '.' . $file_ext;
		}

		$file_name = \wp_unique_filename($save_dir, $file_name);

		if ($check_image_type)
		{
			$filetype = \wp_check_filetype($file_name, null);

			if (substr($filetype['type'], 0, 5) != 'image')
			{
				return false;
			}
		}

		$image_string = \wp_remote_retrieve_body($response);
		$file_path    = \trailingslashit($save_dir) . $file_name;
		if (!file_put_contents($file_path, $image_string))
		{
			return false;
		}

		if ($check_image_type && !self::isImage($file_path))
		{
			@unlink($file_path);

			return false;
		}
		if (!defined('FS_CHMOD_FILE'))
		{
			define('FS_CHMOD_FILE', (fileperms(ABSPATH . 'index.php') & 0777 | 0644));
		}
		@chmod($file_path, FS_CHMOD_FILE);

		return $file_name;
	}

	public static function isImage($path)
	{
		if (!$a = getimagesize($path))
		{
			return false;
		}
		$image_type = $a[2];
		if (in_array($image_type, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP, IMAGETYPE_WEBP)))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function getFullImgPath($img_path)
	{
		$uploads = \wp_upload_dir();

		return trailingslashit($uploads['basedir']) . $img_path;
	}
}
