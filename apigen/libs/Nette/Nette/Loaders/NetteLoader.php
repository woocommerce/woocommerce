<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Loaders;

use Nette;



/**
 * Nette auto loader is responsible for loading Nette classes and interfaces.
 *
 * @author     David Grudl
 */
class NetteLoader extends AutoLoader
{
	/** @var NetteLoader */
	private static $instance;

	/** @var array */
	public $renamed = array(
		'Nette\Configurator' => 'Nette\Config\Configurator',
		'Nette\Http\User' => 'Nette\Security\User',
		'Nette\Templating\DefaultHelpers' => 'Nette\Templating\Helpers',
		'Nette\Latte\ParseException' => 'Nette\Latte\CompileException',
	);

	/** @var array */
	public $list = array(
		'NetteModule\MicroPresenter' => '/Application/MicroPresenter',
		'Nette\Application\AbortException' => '/Application/exceptions',
		'Nette\Application\ApplicationException' => '/Application/exceptions',
		'Nette\Application\BadRequestException' => '/Application/exceptions',
		'Nette\Application\ForbiddenRequestException' => '/Application/exceptions',
		'Nette\Application\InvalidPresenterException' => '/Application/exceptions',
		'Nette\ArgumentOutOfRangeException' => '/common/exceptions',
		'Nette\ArrayHash' => '/common/ArrayHash',
		'Nette\ArrayList' => '/common/ArrayList',
		'Nette\Callback' => '/common/Callback',
		'Nette\DI\MissingServiceException' => '/DI/exceptions',
		'Nette\DI\ServiceCreationException' => '/DI/exceptions',
		'Nette\DateTime' => '/common/DateTime',
		'Nette\DeprecatedException' => '/common/exceptions',
		'Nette\DirectoryNotFoundException' => '/common/exceptions',
		'Nette\Environment' => '/common/Environment',
		'Nette\FatalErrorException' => '/common/exceptions',
		'Nette\FileNotFoundException' => '/common/exceptions',
		'Nette\Framework' => '/common/Framework',
		'Nette\FreezableObject' => '/common/FreezableObject',
		'Nette\IFreezable' => '/common/IFreezable',
		'Nette\IOException' => '/common/exceptions',
		'Nette\Image' => '/common/Image',
		'Nette\InvalidArgumentException' => '/common/exceptions',
		'Nette\InvalidStateException' => '/common/exceptions',
		'Nette\Latte\CompileException' => '/Latte/exceptions',
		'Nette\Mail\SmtpException' => '/Mail/SmtpMailer',
		'Nette\MemberAccessException' => '/common/exceptions',
		'Nette\NotImplementedException' => '/common/exceptions',
		'Nette\NotSupportedException' => '/common/exceptions',
		'Nette\Object' => '/common/Object',
		'Nette\ObjectMixin' => '/common/ObjectMixin',
		'Nette\OutOfRangeException' => '/common/exceptions',
		'Nette\StaticClassException' => '/common/exceptions',
		'Nette\UnexpectedValueException' => '/common/exceptions',
		'Nette\UnknownImageFileException' => '/common/Image',
		'Nette\Utils\AssertionException' => '/Utils/Validators',
		'Nette\Utils\JsonException' => '/Utils/Json',
		'Nette\Utils\NeonEntity' => '/Utils/Neon',
		'Nette\Utils\NeonException' => '/Utils/Neon',
		'Nette\Utils\RegexpException' => '/Utils/Strings',
		'Nette\Utils\TokenizerException' => '/Utils/Tokenizer',
	);



	/**
	 * Returns singleton instance with lazy instantiation.
	 * @return NetteLoader
	 */
	public static function getInstance()
	{
		if (self::$instance === NULL) {
			self::$instance = new static;
		}
		return self::$instance;
	}



	/**
	 * Handles autoloading of classes or interfaces.
	 * @param  string
	 * @return void
	 */
	public function tryLoad($type)
	{
		$type = ltrim($type, '\\');
		/**/if (isset($this->renamed[$type])) {
			class_alias($this->renamed[$type], $type);
			trigger_error("Class $type has been renamed to {$this->renamed[$type]}.", E_USER_WARNING);

		} else/**/if (isset($this->list[$type])) {
			Nette\Utils\LimitedScope::load(NETTE_DIR . $this->list[$type] . '.php', TRUE);
			self::$count++;

		}/**/ elseif (substr($type, 0, 6) === 'Nette\\' && is_file($file = NETTE_DIR . strtr(substr($type, 5), '\\', '/') . '.php')) {
			Nette\Utils\LimitedScope::load($file, TRUE);
			self::$count++;
		}/**/
	}

}
