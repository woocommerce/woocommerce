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
 * The GitHub CLI is the single supported credential source. Reading tokens out
 * of the environment or the git credential store as well would mean this tool
 * could authenticate as one identity while `gh` reports another, and a receipt's
 * creator is the whole basis for trusting it. One source keeps that unambiguous.
 *
 * `gh auth token` honours GH_TOKEN and GITHUB_TOKEN itself, so exporting either
 * still works — it goes through the CLI rather than around it.
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
	 * Build a client from the GitHub CLI's token.
	 *
	 * @param string $repository Repository in owner/name form.
	 *
	 * @return self|null Null when the CLI is missing or logged out; the caller
	 *                   decides what to say about it.
	 */
	public static function from_github_cli( string $repository ): ?self {
		if ( ! Shell::has_program( 'gh' ) ) {
			return null;
		}

		$token = Shell::output( 'gh auth token 2>/dev/null' );

		return '' === $token ? null : new self( $token, $repository );
	}

	/**
	 * Whether the GitHub CLI is installed, to tell "missing" from "logged out".
	 */
	public static function github_cli_is_installed(): bool {
		return Shell::has_program( 'gh' );
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
	 * Attach a commit status.
	 *
	 * @param string $sha         Commit to attach to.
	 * @param string $context     Status context.
	 * @param string $description Short human-readable description.
	 * @param string $target_url  Optional link; this is what the status links to.
	 *
	 * @return array{status: int, body: mixed}
	 */
	public function post_status( string $sha, string $context, string $description, string $target_url = '' ): array {
		$body = array(
			'state'       => 'success',
			'context'     => $context,
			'description' => $description,
		);

		if ( '' !== $target_url ) {
			$body['target_url'] = $target_url;
		}

		return $this->request(
			'POST',
			sprintf( '/repos/%s/statuses/%s', $this->repository, $sha ),
			$body
		);
	}

	/**
	 * Every commit status on a SHA, newest first.
	 *
	 * Deliberately the list endpoint and not the combined one at
	 * /commits/:sha/status. Only this shape includes `creator`, and creator is
	 * the identity a trust check would validate — the combined endpoint omits it.
	 *
	 * @param string $sha Commit to read.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function list_statuses( string $sha ): array {
		$response = $this->request(
			'GET',
			sprintf( '/repos/%s/commits/%s/statuses', $this->repository, $sha )
		);

		return (array) ( $response['body'] ?? array() );
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
