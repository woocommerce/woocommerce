<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Mail;

use Nette,
	Nette\Utils\Strings;



/**
 * MIME message part.
 *
 * @author     David Grudl
 *
 * @property-read array $headers
 * @property-write $contentType
 * @property   string $encoding
 * @property   mixed $body
 */
class MimePart extends Nette\Object
{
	/** encoding */
	const ENCODING_BASE64 = 'base64',
		ENCODING_7BIT = '7bit',
		ENCODING_8BIT = '8bit',
		ENCODING_QUOTED_PRINTABLE = 'quoted-printable';

	/** @internal */
	const EOL = "\r\n";
	const LINE_LENGTH = 76;

	/** @var array */
	private $headers = array();

	/** @var array */
	private $parts = array();

	/** @var string */
	private $body;



	/**
	 * Sets a header.
	 * @param  string
	 * @param  string|array  value or pair email => name
	 * @param  bool
	 * @return MimePart  provides a fluent interface
	 */
	public function setHeader($name, $value, $append = FALSE)
	{
		if (!$name || preg_match('#[^a-z0-9-]#i', $name)) {
			throw new Nette\InvalidArgumentException("Header name must be non-empty alphanumeric string, '$name' given.");
		}

		if ($value == NULL) { // intentionally ==
			if (!$append) {
				unset($this->headers[$name]);
			}

		} elseif (is_array($value)) { // email
			$tmp = & $this->headers[$name];
			if (!$append || !is_array($tmp)) {
				$tmp = array();
			}

			foreach ($value as $email => $recipient) {
				if ($recipient !== NULL && !Strings::checkEncoding($recipient)) {
					Nette\Utils\Validators::assert($recipient, 'unicode', "header '$name'");
				}
				if (preg_match('#[\r\n]#', $recipient)) {
					throw new Nette\InvalidArgumentException("Name must not contain line separator.");
				}
				Nette\Utils\Validators::assert($email, 'email', "header '$name'");
				$tmp[$email] = $recipient;
			}

		} else {
			$value = (string) $value;
			if (!Strings::checkEncoding($value)) {
				throw new Nette\InvalidArgumentException("Header is not valid UTF-8 string.");
			}
			$this->headers[$name] = preg_replace('#[\r\n]+#', ' ', $value);
		}
		return $this;
	}



	/**
	 * Returns a header.
	 * @param  string
	 * @return mixed
	 */
	public function getHeader($name)
	{
		return isset($this->headers[$name]) ? $this->headers[$name] : NULL;
	}



	/**
	 * Removes a header.
	 * @param  string
	 * @return MimePart  provides a fluent interface
	 */
	public function clearHeader($name)
	{
		unset($this->headers[$name]);
		return $this;
	}



	/**
	 * Returns an encoded header.
	 * @param  string
	 * @param  string
	 * @return string
	 */
	public function getEncodedHeader($name)
	{
		$offset = strlen($name) + 2; // colon + space

		if (!isset($this->headers[$name])) {
			return NULL;

		} elseif (is_array($this->headers[$name])) {
			$s = '';
			foreach ($this->headers[$name] as $email => $name) {
				if ($name != NULL) { // intentionally ==
					$s .= self::encodeHeader(
						strpbrk($name, '.,;<@>()[]"=?') ? '"' . addcslashes($name, '"\\') . '"' : $name,
						$offset
					);
					$email = " <$email>";
				}
				$email .= ',';
				if ($s !== '' && $offset + strlen($email) > self::LINE_LENGTH) {
					$s .= self::EOL . "\t";
					$offset = 1;
				}
				$s .= $email;
				$offset += strlen($email);
			}
			return substr($s, 0, -1); // last comma

		} elseif (preg_match('#^(\S+; (?:file)?name=)"(.*)"$#', $this->headers[$name], $m)) { // Content-Disposition
			$offset += strlen($m[1]);
			return $m[1] . '"' . self::encodeHeader($m[2], $offset) . '"';

		} else {
			return self::encodeHeader($this->headers[$name], $offset);
		}
	}



	/**
	 * Returns all headers.
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}



	/**
	 * Sets Content-Type header.
	 * @param  string
	 * @param  string
	 * @return MimePart  provides a fluent interface
	 */
	public function setContentType($contentType, $charset = NULL)
	{
		$this->setHeader('Content-Type', $contentType . ($charset ? "; charset=$charset" : ''));
		return $this;
	}



	/**
	 * Sets Content-Transfer-Encoding header.
	 * @param  string
	 * @return MimePart  provides a fluent interface
	 */
	public function setEncoding($encoding)
	{
		$this->setHeader('Content-Transfer-Encoding', $encoding);
		return $this;
	}



	/**
	 * Returns Content-Transfer-Encoding header.
	 * @return string
	 */
	public function getEncoding()
	{
		return $this->getHeader('Content-Transfer-Encoding');
	}



	/**
	 * Adds or creates new multipart.
	 * @param  MimePart
	 * @return MimePart
	 */
	public function addPart(MimePart $part = NULL)
	{
		return $this->parts[] = $part === NULL ? new self : $part;
	}



	/**
	 * Sets textual body.
	 * @param  mixed
	 * @return MimePart  provides a fluent interface
	 */
	public function setBody($body)
	{
		$this->body = $body;
		return $this;
	}



	/**
	 * Gets textual body.
	 * @return mixed
	 */
	public function getBody()
	{
		return $this->body;
	}



	/********************* building ****************d*g**/



	/**
	 * Returns encoded message.
	 * @return string
	 */
	public function generateMessage()
	{
		$output = '';
		$boundary = '--------' . Strings::random();

		foreach ($this->headers as $name => $value) {
			$output .= $name . ': ' . $this->getEncodedHeader($name);
			if ($this->parts && $name === 'Content-Type') {
				$output .= ';' . self::EOL . "\tboundary=\"$boundary\"";
			}
			$output .= self::EOL;
		}
		$output .= self::EOL;

		$body = (string) $this->body;
		if ($body !== '') {
			switch ($this->getEncoding()) {
			case self::ENCODING_QUOTED_PRINTABLE:
				$output .= function_exists('quoted_printable_encode') ? quoted_printable_encode($body) : self::encodeQuotedPrintable($body);
				break;

			case self::ENCODING_BASE64:
				$output .= rtrim(chunk_split(base64_encode($body), self::LINE_LENGTH, self::EOL));
				break;

			case self::ENCODING_7BIT:
				$body = preg_replace('#[\x80-\xFF]+#', '', $body);
				// break intentionally omitted

			case self::ENCODING_8BIT:
				$body = str_replace(array("\x00", "\r"), '', $body);
				$body = str_replace("\n", self::EOL, $body);
				$output .= $body;
				break;

			default:
				throw new Nette\InvalidStateException('Unknown encoding.');
			}
		}

		if ($this->parts) {
			if (substr($output, -strlen(self::EOL)) !== self::EOL) {
				$output .= self::EOL;
			}
			foreach ($this->parts as $part) {
				$output .= '--' . $boundary . self::EOL . $part->generateMessage() . self::EOL;
			}
			$output .= '--' . $boundary.'--';
		}

		return $output;
	}



	/********************* QuotedPrintable helpers ****************d*g**/



	/**
	 * Converts a 8 bit header to a quoted-printable string.
	 * @param  string
	 * @param  string
	 * @param  int
	 * @return string
	 */
	private static function encodeHeader($s, & $offset = 0)
	{
		$o = '';
		if ($offset >= 55) { // maximum for iconv_mime_encode
			$o = self::EOL . "\t";
			$offset = 1;
		}

		if (strspn($s, "!\"#$%&\'()*+,-./0123456789:;<>@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^`abcdefghijklmnopqrstuvwxyz{|}=? _\r\n\t") === strlen($s)
			&& ($offset + strlen($s) <= self::LINE_LENGTH)
		) {
			$offset += strlen($s);
			return $o . $s;
		}

		$o .= str_replace("\n ", "\n\t", substr(iconv_mime_encode(str_repeat(' ', $offset), $s, array(
			'scheme' => 'B', // Q is broken
			'input-charset' => 'UTF-8',
			'output-charset' => 'UTF-8',
		)), $offset + 2));

		$offset = strlen($o) - strrpos($o, "\n");
		return $o;
	}



	/**
	 * Converts a 8 bit string to a quoted-printable string.
	 * @param  string
	 * @return string
	 *//*5.2*
	public static function encodeQuotedPrintable($s)
	{
		$range = '!"#$%&\'()*+,-./0123456789:;<>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}'; // \x21-\x7E without \x3D
		$pos = 0;
		$len = 0;
		$o = '';
		$size = strlen($s);
		while ($pos < $size) {
			if ($l = strspn($s, $range, $pos)) {
				while ($len + $l > self::LINE_LENGTH - 1) { // 1 = length of suffix =
					$lx = self::LINE_LENGTH - $len - 1;
					$o .= substr($s, $pos, $lx) . '=' . self::EOL;
					$pos += $lx;
					$l -= $lx;
					$len = 0;
				}
				$o .= substr($s, $pos, $l);
				$len += $l;
				$pos += $l;

			} else {
				$len += 3;
				if ($len > self::LINE_LENGTH - 1) {
					$o .= '=' . self::EOL;
					$len = 3;
				}
				$o .= '=' . strtoupper(bin2hex($s[$pos]));
				$pos++;
			}
		}
		return rtrim($o, '=' . self::EOL);
	}*/

}
