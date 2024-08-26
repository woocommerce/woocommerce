<?php

namespace WooCommerce\Dev\CLI;

use lucatume\DI52\App;
use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

abstract class Command extends SymfonyCommand implements CompletionAwareInterface {
	protected $container;

	protected $rootPath;

	public function __construct() {
		$this->container    = App::container();
		$this->checkWritableDir( $this->container->getVar( 'rootPath' ) );
		$this->rootPath = $this->container->getVar( 'rootPath' );
		
		parent::__construct( static::getCommandName() );
	}

	/**
	 * Returns the command name, as it would be called by the user.
	 *
	 * E.g. `docker` or `hosts-file`.
	 *
	 * @return string
	 */
	abstract static public function getCommandName();

	protected function checkWritableDir( string $dir ) {
		if ( ! file_exists( $dir ) ) {
			throw new \RuntimeException( "$dir does not exist." );
		}

		if ( ! is_dir( $dir ) ) {
			throw new \RuntimeException( "$dir is not a directory." );
		}

		if ( ! is_readable( $dir ) ) {
			throw new \RuntimeException( "$dir is not readable." );
		}

		if ( ! is_writable( $dir ) ) {
			throw new \RuntimeException( "$dir is not writable." );
		}
	}

	/**
	 * The array of arguments accepted by this command.
	 *
	 * @return array An array of accepted arguments.
	 */
	protected function getArguments(): array {
		return [];
	}

	/**
	 * Provides autocompletion for options.
	 *
	 * @param string $optionName
	 * @param CompletionContext $context
	 *
	 * @return null
	 */
	public function completeOptionValues( $optionName, CompletionContext $context ) {
		return null;
	}

	/**
	 * Provides autocompletion for arguments.
	 *
	 * @param string $argumentName
	 * @param CompletionContext $context
	 *
	 * @return array
	 */
	public function completeArgumentValues( $argumentName, CompletionContext $context ) {
		return $this->getArguments();
	}
}
