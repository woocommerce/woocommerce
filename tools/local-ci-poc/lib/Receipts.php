<?php
/**
 * The receipt contract.
 *
 * Repo-agnostic.
 */

namespace LocalCi;

/**
 * What a receipt is called, and how to publish and read one.
 *
 * This file is the contract. CI builds the same context string from its own
 * matrix entry, so the naming rule here and the one in the workflow have to
 * agree exactly — that is why it lives in one named place rather than being
 * spelled out wherever a status happens to be posted.
 */
final class Receipts {

	/**
	 * Prefix for every context this tool writes.
	 *
	 * Versioned so the naming rule can change later without a running CI
	 * mistaking an old receipt for one it understands.
	 */
	public const PREFIX = 'local-ci/v1/';

	/**
	 * GitHub API client.
	 *
	 * @var GitHubApi
	 */
	private $api;

	/**
	 * @param GitHubApi $api GitHub API client.
	 */
	public function __construct( GitHubApi $api ) {
		$this->api = $api;
	}

	/**
	 * The receipt name for a planned job.
	 *
	 * The job's own name is used verbatim because it already identifies the job
	 * uniquely — it carries the project and the test type — and CI reads the same
	 * string from its matrix. Deriving anything here would risk the two drifting.
	 *
	 * @param array{name: string} $job Planned job.
	 */
	public static function context_for( array $job ): string {
		return self::PREFIX . $job['name'];
	}

	/**
	 * Publish one receipt.
	 *
	 * @param string               $sha Commit to attach to.
	 * @param array{name: string}  $job Job the receipt vouches for.
	 *
	 * @return array{status: int, body: mixed}
	 */
	public function publish( string $sha, array $job ): array {
		return $this->api->post_status( $sha, self::context_for( $job ), 'passed locally' );
	}

	/**
	 * The receipts on a commit, most recent first, one entry per context.
	 *
	 * The API returns every status ever posted, so the same context appears once
	 * per run. Newest is first, so the first one seen for a context is current —
	 * and a later failure must never be masked by an earlier success.
	 *
	 * @param string $sha Commit to read.
	 *
	 * @return array<int, array{context: string, state: string, creator: string}>
	 */
	public function read( string $sha ): array {
		$seen     = array();
		$receipts = array();

		foreach ( $this->api->list_statuses( $sha ) as $status ) {
			$context = (string) ( $status['context'] ?? '' );

			if ( ! str_starts_with( $context, self::PREFIX ) || isset( $seen[ $context ] ) ) {
				continue;
			}

			$seen[ $context ] = true;

			$receipts[] = array(
				'context' => $context,
				'state'   => (string) ( $status['state'] ?? '' ),
				'creator' => (string) ( $status['creator']['login'] ?? '<none>' ),
			);
		}

		return $receipts;
	}
}
