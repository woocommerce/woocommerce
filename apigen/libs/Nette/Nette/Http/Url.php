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

use Nette;



/**
 * URI Syntax (RFC 3986).
 *
 * <pre>
 * scheme  user  password  host  port  basePath   relativeUrl
 *   |      |      |        |      |    |             |
 * /--\   /--\ /------\ /-------\ /--\/--\/----------------------------\
 * http://john:x0y17575@nette.org:8042/en/manual.php?name=param#fragment  <-- absoluteUrl
 *        \__________________________/\____________/^\________/^\______/
 *                     |                     |           |         |
 *                 authority               path        query    fragment
 * </pre>
 *
 * - authority:   [user[:password]@]host[:port]
 * - hostUrl:     http://user:password@nette.org:8042
 * - basePath:    /en/ (everything before relative URI not including the script name)
 * - baseUrl:     http://user:password@nette.org:8042/en/
 * - relativeUrl: manual.php
 *
 * @author     David Grudl
 *
 * @property   string $scheme
 * @property   string $user
 * @property   string $password
 * @property   string $host
 * @property   string $port
 * @property   string $path
 * @property   string $query
 * @property   string $fragment
 * @property-read string $absoluteUrl
 * @property-read string $authority
 * @property-read string $hostUrl
 * @property-read string $basePath
 * @property-read string $baseUrl
 * @property-read string $relativeUrl
 */
class Url extends Nette\FreezableObject
{
	/** @var array */
	public static $defaultPorts = array(
		'http' => 80,
		'https' => 443,
		'ftp' => 21,
		'news' => 119,
		'nntp' => 119,
	);

	/** @var string */
	private $scheme = '';

	/** @var string */
	private $user = '';

	/** @var string */
	private $pass = '';

	/** @var string */
	private $host = '';

	/** @var int */
	private $port = NULL;

	/** @var string */
	private $path = '';

	/** @var string */
	private $query = '';

	/** @var string */
	private $fragment = '';



	/**
	 * @param  string  URL
	 * @throws Nette\InvalidArgumentException
	 */
	public function __construct($url = NULL)
	{
		if (is_string($url)) {
			$parts = @parse_url($url); // @ - is escalated to exception
			if ($parts === FALSE) {
				throw new Nette\InvalidArgumentException("Malformed or unsupported URI '$url'.");
			}

			foreach ($parts as $key => $val) {
				$this->$key = $val;
			}

			if (!$this->port && isset(self::$defaultPorts[$this->scheme])) {
				$this->port = self::$defaultPorts[$this->scheme];
			}

			if ($this->path === '' && ($this->scheme === 'http' || $this->scheme === 'https')) {
				$this->path = '/';
			}

		} elseif ($url instanceof self) {
			foreach ($this as $key => $val) {
				$this->$key = $url->$key;
			}
		}
	}



	/**
	 * Sets the scheme part of URI.
	 * @param  string
	 * @return Url  provides a fluent interface
	 */
	public function setScheme($value)
	{
		$this->updating();
		$this->scheme = (string) $value;
		return $this;
	}



	/**
	 * Returns the scheme part of URI.
	 * @return string
	 */
	public function getScheme()
	{
		return $this->scheme;
	}



	/**
	 * Sets the user name part of URI.
	 * @param  string
	 * @return Url  provides a fluent interface
	 */
	public function setUser($value)
	{
		$this->updating();
		$this->user = (string) $value;
		return $this;
	}



	/**
	 * Returns the user name part of URI.
	 * @return string
	 */
	public function getUser()
	{
		return $this->user;
	}



	/**
	 * Sets the password part of URI.
	 * @param  string
	 * @return Url  provides a fluent interface
	 */
	public function setPassword($value)
	{
		$this->updating();
		$this->pass = (string) $value;
		return $this;
	}



	/**
	 * Returns the password part of URI.
	 * @return string
	 */
	public function getPassword()
	{
		return $this->pass;
	}



	/**
	 * Sets the host part of URI.
	 * @param  string
	 * @return Url  provides a fluent interface
	 */
	public function setHost($value)
	{
		$this->updating();
		$this->host = (string) $value;
		return $this;
	}



	/**
	 * Returns the host part of URI.
	 * @return string
	 */
	public function getHost()
	{
		return $this->host;
	}



	/**
	 * Sets the port part of URI.
	 * @param  string
	 * @return Url  provides a fluent interface
	 */
	public function setPort($value)
	{
		$this->updating();
		$this->port = (int) $value;
		return $this;
	}



	/**
	 * Returns the port part of URI.
	 * @return string
	 */
	public function getPort()
	{
		return $this->port;
	}



	/**
	 * Sets the path part of URI.
	 * @param  string
	 * @return Url  provides a fluent interface
	 */
	public function setPath($value)
	{
		$this->updating();
		$this->path = (string) $value;
		return $this;
	}



	/**
	 * Returns the path part of URI.
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}



	/**
	 * Sets the query part of URI.
	 * @param  string|array
	 * @return Url  provides a fluent interface
	 */
	public function setQuery($value)
	{
		$this->updating();
		$this->query = (string) (is_array($value) ? http_build_query($value, '', '&') : $value);
		return $this;
	}



	/**
	 * Appends the query part of URI.
	 * @param  string|array
	 * @return void
	 */
	public function appendQuery($value)
	{
		$this->updating();
		$value = (string) (is_array($value) ? http_build_query($value, '', '&') : $value);
		$this->query .= ($this->query === '' || $value === '') ? $value : '&' . $value;
	}



	/**
	 * Returns the query part of URI.
	 * @return string
	 */
	public function getQuery()
	{
		return $this->query;
	}



	/**
	 * Sets the fragment part of URI.
	 * @param  string
	 * @return Url  provides a fluent interface
	 */
	public function setFragment($value)
	{
		$this->updating();
		$this->fragment = (string) $value;
		return $this;
	}



	/**
	 * Returns the fragment part of URI.
	 * @return string
	 */
	public function getFragment()
	{
		return $this->fragment;
	}



	/**
	 * Returns the entire URI including query string and fragment.
	 * @return string
	 */
	public function getAbsoluteUrl()
	{
		return $this->scheme . '://' . $this->getAuthority() . $this->path
			. ($this->query === '' ? '' : '?' . $this->query)
			. ($this->fragment === '' ? '' : '#' . $this->fragment);
	}



	/**
	 * Returns the [user[:pass]@]host[:port] part of URI.
	 * @return string
	 */
	public function getAuthority()
	{
		$authority = $this->host;
		if ($this->port && isset(self::$defaultPorts[$this->scheme]) && $this->port !== self::$defaultPorts[$this->scheme]) {
			$authority .= ':' . $this->port;
		}

		if ($this->user !== '' && $this->scheme !== 'http' && $this->scheme !== 'https') {
			$authority = $this->user . ($this->pass === '' ? '' : ':' . $this->pass) . '@' . $authority;
		}

		return $authority;
	}



	/**
	 * Returns the scheme and authority part of URI.
	 * @return string
	 */
	public function getHostUrl()
	{
		return $this->scheme . '://' . $this->getAuthority();
	}



	/**
	 * Returns the base-path.
	 * @return string
	 */
	public function getBasePath()
	{
		$pos = strrpos($this->path, '/');
		return $pos === FALSE ? '' : substr($this->path, 0, $pos + 1);
	}



	/**
	 * Returns the base-URI.
	 * @return string
	 */
	public function getBaseUrl()
	{
		return $this->scheme . '://' . $this->getAuthority() . $this->getBasePath();
	}



	/**
	 * Returns the relative-URI.
	 * @return string
	 */
	public function getRelativeUrl()
	{
		return (string) substr($this->getAbsoluteUrl(), strlen($this->getBaseUrl()));
	}



	/**
	 * URI comparsion (this object must be in canonical form).
	 * @param  string
	 * @return bool
	 */
	public function isEqual($url)
	{
		// compare host + path
		$part = self::unescape(strtok($url, '?#'), '%/');
		if (strncmp($part, '//', 2) === 0) { // absolute URI without scheme
			if ($part !== '//' . $this->getAuthority() . $this->path) {
				return FALSE;
			}

		} elseif (strncmp($part, '/', 1) === 0) { // absolute path
			if ($part !== $this->path) {
				return FALSE;
			}

		} else {
			if ($part !== $this->scheme . '://' . $this->getAuthority() . $this->path) {
				return FALSE;
			}
		}

		// compare query strings
		$part = preg_split('#[&;]#', self::unescape(strtr((string) strtok('?#'), '+', ' '), '%&;=+'));
		sort($part);
		$query = preg_split('#[&;]#', $this->query);
		sort($query);
		return $part === $query;
	}



	/**
	 * Transform to canonical form.
	 * @return void
	 */
	public function canonicalize()
	{
		$this->updating();
		$this->path = $this->path === '' ? '/' : self::unescape($this->path, '%/');
		$this->host = strtolower(rawurldecode($this->host));
		$this->query = self::unescape(strtr($this->query, '+', ' '), '%&;=+');
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getAbsoluteUrl();
	}



	/**
	 * Similar to rawurldecode, but preserve reserved chars encoded.
	 * @param  string to decode
	 * @param  string reserved characters
	 * @return string
	 */
	public static function unescape($s, $reserved = '%;/?:@&=+$,')
	{
		// reserved (@see RFC 2396) = ";" | "/" | "?" | ":" | "@" | "&" | "=" | "+" | "$" | ","
		// within a path segment, the characters "/", ";", "=", "?" are reserved
		// within a query component, the characters ";", "/", "?", ":", "@", "&", "=", "+", ",", "$" are reserved.
		preg_match_all('#(?<=%)[a-f0-9][a-f0-9]#i', $s, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		foreach (array_reverse($matches) as $match) {
			$ch = chr(hexdec($match[0][0]));
			if (strpos($reserved, $ch) === FALSE) {
				$s = substr_replace($s, $ch, $match[0][1] - 1, 3);
			}
		}
		return $s;
	}



	/** @deprecated */
	function getRelativeUri()
	{
		trigger_error(__METHOD__ . '() is deprecated; use ' . __CLASS__ . '::getRelativeUrl() instead.', E_USER_WARNING);
		return $this->getRelativeUrl();
	}

	/** @deprecated */
	function getAbsoluteUri()
	{
		trigger_error(__METHOD__ . '() is deprecated; use ' . __CLASS__ . '::getAbsoluteUrl() instead.', E_USER_WARNING);
		return $this->getAbsoluteUrl();
	}

	/** @deprecated */
	function getHostUri()
	{
		trigger_error(__METHOD__ . '() is deprecated; use ' . __CLASS__ . '::getHostUrl() instead.', E_USER_WARNING);
		return $this->getHostUrl();
	}

	/** @deprecated */
	function getBaseUri()
	{
		trigger_error(__METHOD__ . '() is deprecated; use ' . __CLASS__ . '::getBaseUrl() instead.', E_USER_WARNING);
		return $this->getBaseUrl();
	}

}
