<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * "Add" command for the changelogger tool CLI.
 */
class SetGithubCredentialsCommand extends Command {

	/**
	 * The default command name
	 *
	 * @var string|null
	 */
	protected static $defaultName = 'github-credentials';
	private $config;

	public function __construct(Config $config) {
	    $this->config = $config;
		parent::__construct();
	}

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->setDescription( 'Save Github username and personal token.' );
	}


	/**
	 * Executes the command.
	 *
	 * @param InputInterface $input InputInterface.
	 * @param OutputInterface $output OutputInterface.
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		$question = new Question( "Github username: ");
		$username = $this->getHelper( 'question' )->ask( $input, $output, $question );
		if ( null === $username ) { // non-interactive.
			$output->writeln( 'Got EOF when attempting to query user, aborting.', OutputInterface::VERBOSITY_VERBOSE ); // @codeCoverageIgnore
			return 1;
		}

		$question = new Question( "Github personal token: ");
		$token = $this->getHelper( 'question' )->ask( $input, $output, $question );
		if ( null === $token ) { // non-interactive.
			$output->writeln( 'Got EOF when attempting to query user, aborting.', OutputInterface::VERBOSITY_VERBOSE ); // @codeCoverageIgnore
			return 1;
		}

		$this->config->saveGithubToken( $username, $token );
		$output->writeln( "Successfully updated!" );

		return 0;
	}
}
