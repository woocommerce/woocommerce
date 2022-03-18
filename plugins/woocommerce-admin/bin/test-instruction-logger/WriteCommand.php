<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase

use Automattic\Jetpack\Changelog\ChangeEntry;
use Automattic\Jetpack\Changelog\Changelog;
use Automattic\Jetpack\Changelog\ChangelogEntry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * "Add" command for the changelogger tool CLI.
 * @property Config config
 */
class WriteCommand extends Command {


	/**
	 * The default command name
	 *
	 * @var string|null
	 */
	protected static $defaultName = 'write';

	/**
	 * Bullet for changes.
	 *
	 * @var string
	 */
	protected $bullet = '-';

	/**
	 * Github username and personal token.
	 * @var array
	 */
	protected $githubCredentials;

	/**
	 * Jetpack Changelog formatter for WCA changelog.
	 *
	 * @var WCAdminFormatter
	 */
	protected $changeloggerFormatter;

	/**
	 * WriteCommand constructor.
	 *
	 * @param Config $config
	 */
	public function __construct(Config $config) {
	    $this->config = $config;
	    $this->changeloggerFormatter = new WCAdminFormatter();
		parent::__construct();
	}

	/**
	 * Configures the command.
	 */
	protected function configure() {
		$this->setDescription( 'Generate test instructions from a given version.' )
			->addArgument( 'version', InputArgument::REQUIRED, 'Release version from changelog.txt.' )
			->addOption( 'save-to', null, InputOption::VALUE_REQUIRED, 'Specificity a file path to save the output.' )
			->addOption( 'types', null, InputOption::VALUE_REQUIRED, 'List of changelog types to use for testing instructions.' );
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
		$version = $input->getArgument( 'version' );
		$saveTo = $input->getOption( 'save-to' );
		if ( null === $saveTo ) {
			$saveTo = $this->config->getOutputFilePath();
		}
		$types = $input->getOption( 'types');
		$changelog_types = array();
		if ( null !== $types ) {
			$changelog_types = explode( ',', strtolower( $types ) );
		}

		$changelog = $this->changeloggerFormatter->parse( file_get_contents( $this->config->getChangelogFilepath() ) );
		$this->githubCredentials = $this->config->getGithubCredentials();

		if ( null === $this->githubCredentials['token'] ) {
			$output->writeln("
				<error>Github token is missing. Please refer to the README.md to set Github credentials.</>
			");
			return 1;
		}

		if ( null === $this->githubCredentials['username'] ) {
			$output->writeln("
				<error>Github username is missing. Please refer to the README.md to set Github credentials.</>
			");
			return 1;
		}

		$entry = $this->getChangelogEntryByVersion( $changelog, $version );

		if ( null === $entry ) {
			$output->writeln( "<error>{$version} does not exist.</>" );
			return 1;
		}

		$prs = $this->extractPrNumbers( $entry->getChanges(), $changelog_types );
		$prContents = $this->getPrContents( $prs );

		if ( count($prContents) === 0 ) {
			$output->writeln("<error>PRs in version {$version} doesn't have any test instructions</>");
			return 0;
		}

		$testingInstructionsPath = $this->config->getOutputFilePath();
		$testingInstructions = $this->buildTestInstructions( $testingInstructionsPath, $prContents, $version );


		file_put_contents( $saveTo, $testingInstructions );
		$output->writeln( "Successfully saved to {$saveTo}" );

		return 0;
	}

	/**
	 * Build new TESTING-INSTRUCTIONS.md content by inserting $prContents.
	 *
	 * @param string $testingInstructionsPath Path of TESTING-INSTRUCTIONS.md
	 * @param array $prContents Array of PR content
	 * @param string $version Target version
	 *
	 * @return string
	 */
	protected function buildTestInstructions( $testingInstructionsPath, $prContents, $version ) {
		// Get the content of the existing TESTING-INSTRUCTIONS.md.
		$testingInstructions = file( $testingInstructionsPath );

		$output = array();

		// Get the first line (heading) from the TESTING-INSTRUCTIONS.md.
		$output[] = array_shift( $testingInstructions );

		// Put the version.
		$output[] = '## '.$version;

		foreach ( $prContents as $pr => $prContent ) {
			if ( empty( $prContent['testInstructions'] ) ) {
				continue;
			}
			$output[] = "\n### ".$prContent[ 'title' ].' #'.$pr."\n";
			$output[] = rtrim( $prContent[ 'testInstructions' ] );
		}

		// Put the remaining contents from TESTING-INSTRUCTIONS.md.
		$output[] = implode( '', $testingInstructions );

		return implode( "\n", $output);
	}

	/**
	 * Get ChangelogEntry by the given version.
	 *
	 * @param Changelog $changelog
	 * @param $version
	 *
	 * @return ChangelogEntry|null
	 */
	protected function getChangelogEntryByVersion( Changelog $changelog, $version ) {
		foreach ( $changelog->getEntries() as $entry ) {
			if ( $entry->getVersion() === $version ) {
				return $entry;
			}
		}

		return null;
	}

	/**
	 * Extract testing instructions from the PR description.
	 *
	 * @param string $body PR description body.
	 *
	 * @return string Testing instructions.
	 */
	protected function getTestInstructions( $body ) {
		// add additinal ### to make the regex working.
		$body   .= "\n###";
		$pattern = '/### (Detailed test instructions:)\R+(.+?)(?=\R+###)/s';
		preg_match( $pattern, $body, $matches );

		if ( 3 === count( $matches ) ) {
			$matches[2] = strtr($matches[2], array(
				'No changelog required.' => '',
				'No changelog required' => '',
				'- [x] Include test instructions in the release' => '',
				'- [ ] Include test instructions in the release' => ''
			));

			//Remove <!-- --> or <!--- ---> comments.
			return preg_replace('/(?=<!--)([\s\S]*?)-->/', '', $matches[2]);
		}

		return '';
	}

	/**
	 * Extract PR #s from the change entries.
	 *
	 * @param ChangeEntry[] $changeEntires
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function extractPrNumbers( $changeEntires = [], $changelogSubheadings = [] ) {
		$prs = [];
		foreach ( $changeEntires as $changeEntry ) {
			$subheading = strtolower( $changeEntry->getSubheading() );
			if ( 0 !== count( $changelogSubheadings ) && false === in_array( $subheading, $changelogSubheadings ) ) {
				continue;
			}
			preg_match( "/#[0-9]+/", $changeEntry->getContent(), $matches );
			if ( count( $matches ) ) {
				array_push( $prs, str_replace( "#", "", $matches[0] ) );
			} else {
				throw new Exception( "Unable to find a PR # in '{$changeEntry->getContent()}'" );
			}
		}

		return $prs;
	}

	/**
	 * Get the PR contents.
	 *
	 * @param array $prs PR numbers.
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function getPrContents( array $prs ) {
		$authorization = 'Basic '. base64_encode(
			$this->githubCredentials['username'].':'.$this->githubCredentials['token']
		);

		$prs = array_map( function( $pr ) {
			return "https://api.github.com/repos/" . $this->config->getRepositoryPath() . "/pulls/{$pr}";
		}, $prs );

		$header = array(
			"Authorization: {$authorization}"
		);

		$responses = array();
		foreach ( array_chunk( $prs, 5 ) as $chunk ) {
			$chunk_responses = $this->multiRequest($chunk, array(
				CURLOPT_HTTPHEADER => $header,
				CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0'
			));
			foreach ( $chunk_responses as $chunk_response ) {
				array_push( $responses, json_decode( $chunk_response, false ) );
			}
		}

		$contents = array();

		foreach ( $responses as $response ) {
			if ( false === isset( $response->url ) ) {
				throw new Exception("Unable to retrieve content for the PR from {$response->url}");
			}

			if ( false === $this->shouldIncludeTestInstructions( $response->labels ) ) {
				continue;
			}

			$testInstruction = $this->getTestInstructions( $response->body );
			if ( '' == $testInstruction ) {
				continue;
			}

			$contents[ $response->number ] = array(
				'title' => $response->title,
				'number' => $response->number,
				'testInstructions' => $testInstruction
			);
		}

		return $contents;
	}

	/**
	 * Determine if the given PR's test instructions should be included.
	 *
	 * @param Array $labels Labels from a PR.
	 *
	 * @return bool
	 */
	protected function shouldIncludeTestInstructions( $labels ) {
		foreach ( $labels as $label ) {
			if ( 'no release testing instructions' === $label->name ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Make multiple curl requests.
	 *
	 * @param array $data
	 * @param array $options
	 *
	 * @return array
	 */
	protected function multiRequest( array $data, $options = array() ) {
		// array of curl handles
		$curly = array();
		// data to be returned
		$result = array();

		// multi handle
		$mh = curl_multi_init();

		// loop through $data and create curl handles
		// then add them to the multi-handle
		foreach ( $data as $id => $d ) {

			$curly[$id] = curl_init();

			$url = ( is_array( $d ) && ! empty( $d['url'] ) ) ? $d['url'] : $d;
			curl_setopt( $curly[$id], CURLOPT_URL, $url );
			curl_setopt( $curly[$id], CURLOPT_HEADER, 0 );
			curl_setopt( $curly[$id], CURLOPT_RETURNTRANSFER, 1 );

			// post?
			if ( is_array( $d ) ) {
				if ( ! empty( $d['post'] ) ) {
					curl_setopt( $curly[$id], CURLOPT_POST, 1 );
					curl_setopt( $curly[$id], CURLOPT_POSTFIELDS, $d['post'] );
				}
			}

			// extra options?
			if ( ! empty( $options ) ) {
				curl_setopt_array( $curly[$id], $options );
			}

			curl_multi_add_handle( $mh, $curly[$id] );
		}

		// execute the handles
		$running = null;
		do {
			curl_multi_exec( $mh, $running );
		} while( $running > 0 );


		// get content and remove handles
		foreach( $curly as $id => $c ) {
			$result[$id] = curl_multi_getcontent( $c );
			curl_multi_remove_handle( $mh, $c );
		}

		// all done
		curl_multi_close( $mh );

		return $result;
	}
}
