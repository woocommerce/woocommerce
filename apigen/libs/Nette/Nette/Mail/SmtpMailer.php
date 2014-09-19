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

use Nette;



/**
 * Sends emails via the SMTP server.
 *
 * @author     David Grudl
 */
class SmtpMailer extends Nette\Object implements IMailer
{
	/** @var resource */
	private $connection;

	/** @var string */
	private $host;

	/** @var int */
	private $port;

	/** @var string */
	private $username;

	/** @var string */
	private $password;

	/** @var string ssl | tls | (empty) */
	private $secure;

	/** @var int */
	private $timeout;



	public function __construct(array $options = array())
	{
		if (isset($options['host'])) {
			$this->host = $options['host'];
			$this->port = isset($options['port']) ? (int) $options['port'] : NULL;
		} else {
			$this->host = ini_get('SMTP');
			$this->port = (int) ini_get('smtp_port');
		}
		$this->username = isset($options['username']) ? $options['username'] : '';
		$this->password = isset($options['password']) ? $options['password'] : '';
		$this->secure = isset($options['secure']) ? $options['secure'] : '';
		$this->timeout = isset($options['timeout']) ? (int) $options['timeout'] : 20;
		if (!$this->port) {
			$this->port = $this->secure === 'ssl' ? 465 : 25;
		}
	}



	/**
	 * Sends email.
	 * @param  Message
	 * @return void
	 */
	public function send(Message $mail)
	{
		$data = $mail->generateMessage();

		$this->connect();

		$from = $mail->getHeader('From');
		if ($from) {
			$from = array_keys($from);
			$this->write("MAIL FROM:<$from[0]>", 250);
		}

		foreach (array_merge(
			(array) $mail->getHeader('To'),
			(array) $mail->getHeader('Cc'),
			(array) $mail->getHeader('Bcc')
		) as $email => $name) {
			$this->write("RCPT TO:<$email>", array(250, 251));
		}

		$this->write('DATA', 354);
		$data = preg_replace('#^\.#m', '..', $data);
		$this->write($data);
		$this->write('.', 250);

		$this->write('QUIT', 221);

		$this->disconnect();
	}



	/**
	 * Connects and authenticates to SMTP server.
	 * @return void
	 */
	private function connect()
	{
		$this->connection = @fsockopen( // intentionally @
			($this->secure === 'ssl' ? 'ssl://' : '') . $this->host,
			$this->port, $errno, $error, $this->timeout
		);
		if (!$this->connection) {
			throw new SmtpException($error, $errno);
		}
		stream_set_timeout($this->connection, $this->timeout, 0);
		$this->read(); // greeting

		$self = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
		$this->write("EHLO $self");
		if ((int) $this->read() !== 250) {
			$this->write("HELO $self", 250);
		}

		if ($this->secure === 'tls') {
			$this->write('STARTTLS', 220);
			if (!stream_socket_enable_crypto($this->connection, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
				throw new SmtpException('Unable to connect via TLS.');
			}
			$this->write("EHLO $self", 250);
		}

		if ($this->username != NULL && $this->password != NULL) {
			$this->write('AUTH LOGIN', 334);
			$this->write(base64_encode($this->username), 334, 'username');
			$this->write(base64_encode($this->password), 235, 'password');
		}
	}



	/**
	 * Disconnects from SMTP server.
	 * @return void
	 */
	private function disconnect()
	{
		fclose($this->connection);
		$this->connection = NULL;
	}



	/**
	 * Writes data to server and checks response.
	 * @param  string
	 * @param  int   response code
	 * @param  string  error message
	 * @return void
	 */
	private function write($line, $expectedCode = NULL, $message = NULL)
	{
		fwrite($this->connection, $line . Message::EOL);
		if ($expectedCode && !in_array((int) $this->read(), (array) $expectedCode)) {
			throw new SmtpException('SMTP server did not accept ' . ($message ? $message : $line));
		}
	}



	/**
	 * Reads response from server.
	 * @return string
	 */
	private function read()
	{
		$s = '';
		while (($line = fgets($this->connection, 1e3)) != NULL) { // intentionally ==
			$s .= $line;
			if (substr($line, 3, 1) === ' ') {
				break;
			}
		}
		return $s;
	}

}



/**
 * SMTP mailer exception.
 *
 * @author     David Grudl
 */
class SmtpException extends \Exception
{
}
