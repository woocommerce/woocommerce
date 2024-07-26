<?php

namespace WooCommerce\Dev\CLI\Commands;

use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use WooCommerce\Dev\CLI\ShellCommand;

class RunTests extends ShellCommand {
	static public function getCommandName() {
		return 'test';
	}

	protected function configure() {
		$this
			->setDescription( 'Run PHP or JS tests.' )
			->setDefinition(
				new InputDefinition(
					[
						new InputArgument( 'test_type', InputArgument::REQUIRED ),
					]
				)
			)
			->addOption(
				'watch',
				'',
				InputOption::VALUE_OPTIONAL,
				'Whether to watch the process, if it supports it.',
				false
			)
			->setHelp( <<<HELP
Usage:
 PHP Unit tests: ./woo test php
 JavaScript tests: ./woo test js
HELP
			);
	}

	protected function getArguments(): array {
		return [ 'php', 'js' ];
	}

	protected function configure_process( Process $process ) {
		$process->setTimeout( 600 );
	}

	protected function getCommand( InputInterface $input, OutputInterface $output ): array {
		$test_type = $input->getArgument( 'test_type' );

		switch ( $test_type ) {
			case 'php':
				$this->prepare_unit_tests();
				$this->wait_for_db( $output );

				return [ 'pnpm test:unit --filter=woocommerce' ];
			case 'js':
				if ( $input->getOption( 'watch' ) ) {
					return [ "pnpm test:watch --filter=woocommerce/client/admin" ];
				} else {
					return [ "pnpm test:client --filter=woocommerce/client/admin" ];
				}
			default:
				throw new \InvalidArgumentException();
		}
	}

	protected function prepare_unit_tests() {
		exec( 'rm -rf /tmp/wordpress-tests-lib' );
		exec( 'docker run --rm --name woocommerce_test_db -p 3307:3306 -e MYSQL_ROOT_PASSWORD=woocommerce_test_password -d mysql:5.7.33' );
		sleep( 5 );
		exec( $this->rootPath . '/plugins/woocommerce/tests/bin/install.sh woocommerce_tests root woocommerce_test_password 0.0.0.0:3307' );
	}

	protected function wait_for_db( OutputInterface $output ) {
		for ( $slept = 0; $slept <= 30; $slept ++ ) {
			exec( 'docker ps', $docker_ps_output );
			if ( stripos( implode( ' ', $docker_ps_output ), 'woocommerce_test_db' ) !== false ) {
				break;
			}
			$output->writeln( "Waiting for PHP unit tests environment..." );
		}
	}
}
