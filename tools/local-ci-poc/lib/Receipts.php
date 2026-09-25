<?php
/**
 * The receipt contract.
 *
 * Repo-agnostic.
 */

namespace LocalCi;

/**
 * What a receipt is, how it is published, and how CI finds one.
 *
 * A receipt is a check run created by this tool's GitHub App. Three things follow
 * from that choice, and all three are the reason for it:
 *
 * - Only a GitHub App may create a check run. A personal access token cannot, so
 *   a receipt cannot be hand-rolled with curl by anyone who happens to have write
 *   access — it has to come through the App.
 * - The run carries its own output, so the log a reviewer needs is in the check
 *   itself rather than parked somewhere under an individual's account.
 * - The App is the author, so the person is recorded deliberately in `external_id`
 *   rather than being visible by accident. A check run cannot be deleted, only
 *   updated, so that identity has to be written at creation or it is lost.
 *
 * This file is the contract: CI rebuilds the same name and verifies the same App,
 * so the rule here and the rule in the workflow have to agree exactly.
 */
final class Receipts {

	/**
	 * Prefix for every check run this tool creates.
	 *
	 * Versioned so the naming rule can change later without a running CI mistaking
	 * an old receipt for one it understands, and prefixed so a receipt is obvious
	 * in a checks list full of real jobs.
	 */
	public const PREFIX = 'local-ci/v1: ';

	/**
	 * The App that is allowed to publish receipts.
	 *
	 * Matched by numeric id rather than slug: the slug follows the App's display
	 * name and would change if it were ever renamed, and a trust check must not
	 * hinge on a name someone can edit.
	 */
	public const APP_ID = 4830646;

	/**
	 * How much of a log to carry, in bytes.
	 *
	 * The check run output field accepts 65535; this leaves room for the rest of
	 * the payload. The tail is kept because that is where failures and the summary
	 * are.
	 */
	private const MAX_LOG_BYTES = 55000;

	/**
	 * GitHub API client.
	 *
	 * @var GitHubApi
	 */
	private $api;

	/**
	 * Login of the person who authorised this machine.
	 *
	 * @var string
	 */
	private $author;

	/**
	 * @param GitHubApi $api    GitHub API client.
	 * @param string    $author Login of the person who authorised this machine.
	 */
	public function __construct( GitHubApi $api, string $author ) {
		$this->api    = $api;
		$this->author = $author;
	}

	/**
	 * The receipt name for a planned job.
	 *
	 * The job's own name is used verbatim after the prefix, because it already
	 * identifies the job uniquely — it carries the project and the test type — and
	 * CI rebuilds the same string from its matrix entry.
	 *
	 * @param array{name: string} $job Planned job.
	 */
	public static function name_for( array $job ): string {
		return self::PREFIX . $job['name'];
	}

	/**
	 * Publish one receipt.
	 *
	 * @param string              $sha    Commit to attach to.
	 * @param array{name: string, projectName: string} $job Job the receipt vouches for.
	 * @param string              $output The check's output, for a reviewer to read.
	 *
	 * @return array{status: int, body: mixed}
	 */
	public function publish( string $sha, array $job, string $output = '' ): array {
		return $this->api->create_check_run(
			$sha,
			self::name_for( $job ),
			// Recorded here because the check run's author is the App. Without it
			// there is no way back to a person, and check runs cannot be deleted
			// and rewritten later.
			'authorized-by:' . $this->author,
			array(
				'title'   => sprintf( '%s passed locally', $job['projectName'] ),
				'summary' => sprintf(
					"Run on a contributor's machine by @%s and substituted for the CI job of the same name.",
					$this->author
				),
				'text'    => self::as_log( $output ),
			)
		);
	}

	/**
	 * The receipts this tool published on a commit.
	 *
	 * Only check runs created by this App count. Anything else sharing the naming
	 * convention is somebody else's check run and is ignored.
	 *
	 * @param string $sha Commit to read.
	 *
	 * @return array<int, array{name: string, conclusion: string, author: string, url: string}>
	 */
	public function read( string $sha ): array {
		$receipts = array();

		foreach ( $this->api->list_check_runs( $sha ) as $run ) {
			$name = (string) ( $run['name'] ?? '' );

			if ( ! str_starts_with( $name, self::PREFIX ) ) {
				continue;
			}

			if ( self::APP_ID !== (int) ( $run['app']['id'] ?? 0 ) ) {
				continue;
			}

			$receipts[] = array(
				'name'       => $name,
				'conclusion' => (string) ( $run['conclusion'] ?? '' ),
				'author'     => str_replace( 'authorized-by:', '', (string) ( $run['external_id'] ?? '' ) ),
				'url'        => (string) ( $run['html_url'] ?? '' ),
			);
		}

		return $receipts;
	}

	/**
	 * Wrap a log so it renders as preformatted text rather than markdown.
	 *
	 * @param string $output Raw output.
	 */
	private static function as_log( string $output ): string {
		if ( '' === trim( $output ) ) {
			return '_No output captured._';
		}

		if ( strlen( $output ) > self::MAX_LOG_BYTES ) {
			$output = "[earlier output trimmed]\n\n" . substr( $output, -self::MAX_LOG_BYTES );
		}

		// Absolute paths from the machine that ran this are noise to a reviewer and
		// needless detail about somebody's laptop.
		$home = (string) getenv( 'HOME' );

		if ( '' !== $home ) {
			$output = str_replace( $home, '~', $output );
		}

		return "```\n" . $output . "\n```";
	}
}
