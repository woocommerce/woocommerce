<?php

namespace WooCommerce\Dev\CLI\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use WooCommerce\Dev\CLI\Command;

class Autocomplete extends Command {
	static public function getCommandName() {
		return 'autocomplete';
	}

	protected function configure() {
		$this
			->setDescription( 'Add autocompletion support to your terminal session.' )
			->setHelp( <<<HELP
Usage:
 You can either run this command as ./woo autocomplete, or add this line to your ~/.bashrc or ~/.zhsrc file:
 eval \$({$this->rootPath}/woo _completion --generate-hook)
HELP
			);
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$home = rtrim( getenv( "HOME" ), '/' );

		if ( ! file_exists( $home ) ) {
			$output->writeln( "Could not find Home directory in HOME environment variable (Unix)." );

			return 1;
		}

		$options = [];

		if ( file_exists( "$home/.zshrc" ) ) {
			$writable = is_writable( "$home/.zshrc" ) ? 'yes' : 'no';
			$output->writeln( "Found valid option: \"$home/.zhsrc\" Writable? $writable" );
			$options[] = 'zshrc';
		} elseif ( file_exists( "$home/.bashrc" ) ) {
			$writable = is_writable( "$home/.bashrc" ) ? 'yes' : 'no';
			$output->writeln( "Found valid option: \"$home/.bashrc\" Writable? $writable" );
			$options[] = 'bashrc';
		}

		if ( empty( $options ) ) {
			$output->writeln( 'Could not find a valid bash terminal to add the autocomplete.' );

			return 1;
		}

		$helper   = $this->getHelper( 'question' );
		$question = new ChoiceQuestion( "Where do you want to add the autocompletion bash script?", $options );

		$choice = $helper->ask( $input, $output, $question );

		switch ( $choice ) {
			case 'zshrc':
				$path = "$home/.zshrc";
				break;
			case 'bashrc':
				$path = "$home/.bashrc";
				break;
		}

		$file = new \SplFileObject( $path );
		while ( $file->valid() ) {
			$line = $file->fgets();
			if ( stripos( $line, '/woo _completion --generate-hook' ) !== false ) {
				$output->writeln( 'Your bash terminal already has the autocomplete support.' );

				return 0;
			}
		}

		unset( $file );

		$resource = fopen( $path, 'a' );
		// Write the autocomplete fix to the bash terminal.
		fwrite( $resource, "\neval \$({$this->rootPath}/woo _completion --generate-hook)\n" );
		fclose( $resource );

		return 0;
	}
}
