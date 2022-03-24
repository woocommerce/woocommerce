<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * User interface for the changelogger tool.
 */
class Application extends SymfonyApplication {

	const VERSION = '1.1.2';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( 'Test Instruction Logger', self::VERSION );
	}

	/**
	 * Called when the application is run.
	 *
	 * @param InputInterface $input InputInterface.
	 * @param OutputInterface $output OutputInterface.
	 *
	 * @return int
	 * @throws Throwable
	 */
	public function doRun( InputInterface $input, OutputInterface $output ) {
		return parent::doRun( $input, $output );
	}

}
