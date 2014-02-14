<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Http;

use Nette,
	Nette\Utils\Strings;



/**
 * Current HTTP request factory.
 *
 * @author     David Grudl
 */
class RequestFactory extends Nette\Object
{
	/** @internal */
	const NONCHARS = '#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u';

	/** @var array */
	public $urlFilters = array(
		'path' => array('#/{2,}#' => '/'), // '%20' => ''
		'url' => array(), // '#[.,)]$#' => ''
	);

	/** @var string */
	private $encoding;



	/**
	 * @param  string
	 * @return RequestFactory  provides a fluent interface
	 */
	public function setEncoding($encoding)
	{
		$this->encoding = $encoding;
		return $this;
	}



	/**
	 * Creates current HttpRequest object.
	 * @return Request
	 */
	public function createHttpRequest()
	{
		// DETECTS URI, base path and script path of the request.
		$url = new UrlScript;
		$url->scheme = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https' : 'http';
		$url->user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
		$url->password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';

		// host & port
		if (isset($_SERVER['HTTP_HOST'])) {
			$pair = explode(':', $_SERVER['HTTP_HOST']);

		} elseif (isset($_SERVER['SERVER_NAME'])) {
			$pair = explode(':', $_SERVER['SERVER_NAME']);

		} else {
			$pair = array('');
		}

		$url->host = preg_match('#^[-._a-z0-9]+$#', $pair[0]) ? $pair[0] : '';

		if (isset($pair[1])) {
			$url->port = (int) $pair[1];

		} elseif (isset($_SERVER['SERVER_PORT'])) {
			$url->port = (int) $_SERVER['SERVER_PORT'];
		}

		// path & query
		if (isset($_SERVER['REQUEST_URI'])) { // Apache, IIS 6.0
			$requestUrl = $_SERVER['REQUEST_URI'];

		} elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 (PHP as CGI ?)
			$requestUrl = $_SERVER['ORIG_PATH_INFO'];
			if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
				$requestUrl .= '?' . $_SERVER['QUERY_STRING'];
			}
		} else {
			$requestUrl = '';
		}

		$requestUrl = Strings::replace($requestUrl, $this->urlFilters['url']);
		$tmp = explode('?', $requestUrl, 2);
		$url->path = Strings::replace($tmp[0], $this->urlFilters['path']);
		$url->query = isset($tmp[1]) ? $tmp[1] : '';

		// normalized url
		$url->canonicalize();
		$url->path = Strings::fixEncoding($url->path);

		// detect script path
		if (isset($_SERVER['SCRIPT_NAME'])) {
			$script = $_SERVER['SCRIPT_NAME'];
		} elseif (isset($_SERVER['DOCUMENT_ROOT'], $_SERVER['SCRIPT_FILENAME'])
			&& strncmp($_SERVER['DOCUMENT_ROOT'], $_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT'])) === 0
		) {
			$script = '/' . ltrim(strtr(substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT'])), '\\', '/'), '/');
		} else {
			$script = '/';
		}

		$path = strtolower($url->path) . '/';
		$script = strtolower($script) . '/';
		$max = min(strlen($path), strlen($script));
		for ($i = 0; $i < $max; $i++) {
			if ($path[$i] !== $script[$i]) {
				break;
			} elseif ($path[$i] === '/') {
				$url->scriptPath = substr($url->path, 0, $i + 1);
			}
		}

		// GET, POST, COOKIE
		$useFilter = (!in_array(ini_get('filter.default'), array('', 'unsafe_raw')) || ini_get('filter.default_flags'));

		parse_str($url->query, $query);
		if (!$query) {
			$query = $useFilter ? filter_input_array(INPUT_GET, FILTER_UNSAFE_RAW) : (empty($_GET) ? array() : $_GET);
		}
		$post = $useFilter ? filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW) : (empty($_POST) ? array() : $_POST);
		$cookies = $useFilter ? filter_input_array(INPUT_COOKIE, FILTER_UNSAFE_RAW) : (empty($_COOKIE) ? array() : $_COOKIE);

		$gpc = (bool) get_magic_quotes_gpc();
		$old = error_reporting(error_reporting() ^ E_NOTICE);

		// remove fucking quotes and check (and optionally convert) encoding
		if ($gpc || $this->encoding) {
			$utf = strcasecmp($this->encoding, 'UTF-8') === 0;
			$list = array(& $query, & $post, & $cookies);
			while (list($key, $val) = each($list)) {
				foreach ($val as $k => $v) {
					unset($list[$key][$k]);

					if ($gpc) {
						$k = stripslashes($k);
					}

					if ($this->encoding && is_string($k) && (preg_match(self::NONCHARS, $k) || preg_last_error())) {
						// invalid key -> ignore

					} elseif (is_array($v)) {
						$list[$key][$k] = $v;
						$list[] = & $list[$key][$k];

					} else {
						if ($gpc && !$useFilter) {
							$v = stripSlashes($v);
						}
						if ($this->encoding) {
							if ($utf) {
								$v = Strings::fixEncoding($v);

							} else {
								if (!Strings::checkEncoding($v)) {
									$v = iconv($this->encoding, 'UTF-8//IGNORE', $v);
								}
								$v = html_entity_decode($v, ENT_QUOTES, 'UTF-8');
							}
							$v = preg_replace(self::NONCHARS, '', $v);
						}
						$list[$key][$k] = $v;
					}
				}
			}
			unset($list, $key, $val, $k, $v);
		}


		// FILES and create FileUpload objects
		$files = array();
		$list = array();
		if (!empty($_FILES)) {
			foreach ($_FILES as $k => $v) {
				if ($this->encoding && is_string($k) && (preg_match(self::NONCHARS, $k) || preg_last_error())) {
					continue;
				}
				$v['@'] = & $files[$k];
				$list[] = $v;
			}
		}

		while (list(, $v) = each($list)) {
			if (!isset($v['name'])) {
				continue;

			} elseif (!is_array($v['name'])) {
				if ($gpc) {
					$v['name'] = stripSlashes($v['name']);
				}
				if ($this->encoding) {
					$v['name'] = preg_replace(self::NONCHARS, '', Strings::fixEncoding($v['name']));
				}
				$v['@'] = new FileUpload($v);
				continue;
			}

			foreach ($v['name'] as $k => $foo) {
				if ($this->encoding && is_string($k) && (preg_match(self::NONCHARS, $k) || preg_last_error())) {
					continue;
				}
				$list[] = array(
					'name' => $v['name'][$k],
					'type' => $v['type'][$k],
					'size' => $v['size'][$k],
					'tmp_name' => $v['tmp_name'][$k],
					'error' => $v['error'][$k],
					'@' => & $v['@'][$k],
				);
			}
		}

		error_reporting($old);


		// HEADERS
		if (function_exists('apache_request_headers')) {
			$headers = array_change_key_case(apache_request_headers(), CASE_LOWER);
		} else {
			$headers = array();
			foreach ($_SERVER as $k => $v) {
				if (strncmp($k, 'HTTP_', 5) == 0) {
					$k = substr($k, 5);
				} elseif (strncmp($k, 'CONTENT_', 8)) {
					continue;
				}
				$headers[ strtr(strtolower($k), '_', '-') ] = $v;
			}
		}

		return new Request($url, $query, $post, $files, $cookies, $headers,
			isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : NULL,
			isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : NULL,
			isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : NULL
		);
	}

}
