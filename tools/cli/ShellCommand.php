<?php

namespace WooCommerce\Dev\CLI;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class ShellCommand extends Command {
	// Process timeout in seconds
	protected $timeout = 120;

	/**
	 * Get the shell command to run.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return array<string,array<string,mixed>> An array specifying the command to run, its current
	 *                                           working directory and env. The array should be compatible
	 *                                           with the `Symfony\Process::__construct` input arguments.
	 */
	abstract protected function getCommand( InputInterface $input, OutputInterface $output ): array;

	/**
	 * Runs the shell command.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int The exit code after running the command.
	 */
	final protected function execute( InputInterface $input, OutputInterface $output ) {
		$process = new Process( ...$this->getCommand( $input, $output ) );

		// Color support for Shell, but not all terminals support them.
		$process->setTty( $process::isTtySupported() );
		$process->setTimeout( $this->timeout );

		$this->configure_process( $process );
		
		$process->start();
		$processReturn = $process->wait( function ( $type, $buffer ) {
			if ( $type === Process::ERR ) {
				echo $buffer;

				return Command::FAILURE;
			} else {
				echo $buffer;
			}
		} );

		if ( $processReturn === Command::FAILURE ) {
			return Command::FAILURE;
		}

		return $process->getTermSignal();
	}

	protected function configure_process( Process $process ) {
		// no-op, overridable by children classes.
		return null;
	}
}
