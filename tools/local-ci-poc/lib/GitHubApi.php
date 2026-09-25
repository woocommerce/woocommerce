<?php
/**
 * Talking to GitHub.
 *
 * Repo-agnostic: the repository is a constructor argument.
 */

namespace LocalCi;

/**
 * The GitHub REST calls this tool makes, and the one way it gets a token.
 *
 * Authentication is a user-to-server token from the tool's GitHub App, obtained
 * by device flow. That is not a preference: only a GitHub App may create a check
 * run, so no other credential can publish a receipt at all.
 */
final class GitHubApi {

	/**
	 * Authentication token.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Repository in owner/name form.
	 *
	 * @var string
	 */
	private $repository;

	/**
	 * @param string $token      Authentication token.
	 * @param string $repository Repository in owner/name form.
	 */
	public function __construct( string $token, string $repository ) {
		$this->token      = $token;
		$this->repository = $repository;
	}

	/**
	 * The login of whoever this token belongs to.
	 *
	 * @return string Empty when the token cannot identify anyone.
	 */
	public function viewer_login(): string {
		$response = $this->request( 'GET', '/user' );

		return (string) ( $response['body']['login'] ?? '' );
	}

	/**
	 * The HTTP status of asking GitHub about a commit.
	 *
	 * 422 means GitHub has never seen this SHA; 200 means it has. Watching that
	 * flip across a push is what proves a receipt can precede the branch.
	 *
	 * @param string $sha Commit to ask about.
	 */
	public function commit_status_code( string $sha ): int {
		return $this->request( 'GET', sprintf( '/repos/%s/commits/%s', $this->repository, $sha ) )['status'];
	}

	/**
	 * How many Actions runs exist for a SHA.
	 *
	 * @param string $sha Commit to count runs for.
	 */
	public function workflow_run_count( string $sha ): int {
		$response = $this->request(
			'GET',
			sprintf( '/repos/%s/actions/runs?head_sha=%s', $this->repository, $sha )
		);

		return (int) ( $response['body']['total_count'] ?? 0 );
	}

	/**
	 * Create a completed, successful check run.
	 *
	 * @param string               $sha         Commit to attach to.
	 * @param string               $name        Check run name; this is what CI looks up.
	 * @param string               $external_id Caller's own identifier, carried verbatim.
	 * @param array<string,string> $output      title, summary and text.
	 *
	 * @return array{status: int, body: mixed}
	 */
	public function create_check_run( string $sha, string $name, string $external_id, array $output ): array {
		return $this->request(
			'POST',
			sprintf( '/repos/%s/check-runs', $this->repository ),
			array(
				'name'        => $name,
				'head_sha'    => $sha,
				'status'      => 'completed',
				'conclusion'  => 'success',
				'external_id' => $external_id,
				'output'      => $output,
			)
		);
	}

	/**
	 * Every check run on a SHA.
	 *
	 * @param string $sha Commit to read.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function list_check_runs( string $sha ): array {
		$response = $this->request(
			'GET',
			sprintf( '/repos/%s/commits/%s/check-runs?per_page=100', $this->repository, $sha )
		);

		return (array) ( $response['body']['check_runs'] ?? array() );
	}

	/**
	 * One REST call.
	 *
	 * @param string                    $method HTTP method.
	 * @param string                    $path   Path beginning with a slash.
	 * @param array<string, mixed>|null $body   Optional JSON body.
	 *
	 * @return array{status: int, body: mixed}
	 */
	private function request( string $method, string $path, ?array $body = null ): array {
		$curl = curl_init( 'https://api.github.com' . $path );

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST  => $method,
				CURLOPT_USERAGENT      => 'local-ci-poc',
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_HTTPHEADER     => array(
					'Authorization: Bearer ' . $this->token,
					'Accept: application/vnd.github+json',
					'Content-Type: application/json',
				),
			)
		);

		if ( null !== $body ) {
			curl_setopt( $curl, CURLOPT_POSTFIELDS, (string) json_encode( $body ) );
		}

		$raw    = curl_exec( $curl );
		$status = (int) curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		curl_close( $curl );

		return array(
			'status' => $status,
			'body'   => is_string( $raw ) ? json_decode( $raw, true ) : null,
		);
	}
}
