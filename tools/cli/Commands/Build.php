<?php

namespace WooCommerce\Dev\CLI\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use WooCommerce\Dev\CLI\ShellCommand;

class Build extends ShellCommand {
	static public function getCommandName() {
		return 'build';
	}

	protected function configure() {
		$this
			->setDescription( 'Build a target in the monorepo.' )
			->setDefinition(
				new InputDefinition(
					[
						new InputArgument( 'target', InputArgument::OPTIONAL, 'Target in the monorepo to build.', 'woocommerce' ),
					]
				)
			)
			->setHelp( <<<HELP
Usage:
 Building WooCommerce: ./woo build woocommerce (default)
HELP
			);
	}

	protected function getArguments(): array {
		return [ 'woocommerce' ];
	}

	protected function getCommand( InputInterface $input, OutputInterface $output ): array {
		$target = $input->getArgument( 'target' );

		return [ "pnpm -- turbo run build --filter=$target" ];
	}

	protected function configure_process( Process $process ) {
		$process->setWorkingDirectory( $this->rootPath );
	}
}
