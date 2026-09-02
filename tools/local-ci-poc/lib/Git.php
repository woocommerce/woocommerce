<?php
/**
 * The git side of publishing a receipt.
 *
 * Repo-agnostic apart from the trunk branch name, which the caller supplies.
 */

namespace LocalCi;

/**
 * Reads and writes the local repository.
 *
 * The methods here answer questions rather than print anything, so the calling
 * script keeps the whole narrative and this file keeps the plumbing.
 */
final class Git {

	/**
	 * The commit a receipt would name.
	 */
	public static function head_sha(): string {
		return self::output( 'rev-parse HEAD' );
	}

	/**
	 * The current branch name.
	 */
	public static function branch_name(): string {
		return self::output( 'branch --show-current' );
	}

	/**
	 * Tracked files with staged or unstaged changes.
	 *
	 * These are what make the working tree differ from HEAD, so they are what
	 * would make a receipt naming HEAD dishonest.
	 *
	 * @return string[] Paths.
	 */
	public static function modified_tracked_files(): array {
		return self::status_paths( false );
	}

	/**
	 * Files git does not track. Respects .gitignore, so build output is excluded.
	 *
	 * @return string[] Paths.
	 */
	public static function untracked_files(): array {
		return self::status_paths( true );
	}

	/**
	 * Fetch the trunk branch so a staleness check reads current data.
	 *
	 * A stale local ref would happily report "up to date" against a trunk that
	 * moved days ago, which is the exact thing that check exists to catch.
	 *
	 * @param string $branch Trunk branch name.
	 */
	public static function fetch_trunk( string $branch ): bool {
		return self::succeeds( sprintf( 'fetch -q origin %s', escapeshellarg( $branch ) ) );
	}

	/**
	 * How many commits trunk has that this branch does not.
	 *
	 * Reads FETCH_HEAD rather than a remote-tracking ref, because that is what
	 * the fetch above is guaranteed to have just written.
	 */
	public static function commits_behind_trunk(): int {
		return (int) self::output( 'rev-list --count HEAD..FETCH_HEAD' );
	}

	/**
	 * Push the current branch to origin.
	 *
	 * Deliberately without --no-verify, unlike the temporary ref: this is a real
	 * branch push, so the repository's own pre-push hook should run exactly as it
	 * would for a hand-typed `git push`.
	 */
	public static function push_current_branch(): bool {
		return self::succeeds( 'push origin HEAD' );
	}

	/**
	 * Point a ref at HEAD on the remote.
	 *
	 * --no-verify because a pre-push hook has no useful opinion about a ref that
	 * exists only to make a SHA reachable, and some hooks refuse the push outright.
	 *
	 * @param string $ref Fully qualified ref name.
	 */
	public static function push_ref( string $ref ): bool {
		return self::succeeds( sprintf( 'push --no-verify -q origin HEAD:%s', escapeshellarg( $ref ) ) );
	}

	/**
	 * Delete a ref from the remote.
	 *
	 * @param string $ref Fully qualified ref name.
	 */
	public static function delete_ref( string $ref ): bool {
		return self::succeeds( sprintf( 'push --no-verify -q origin --delete %s', escapeshellarg( $ref ) ) );
	}

	/**
	 * Read `git status --porcelain` and return one side of it.
	 *
	 * @param bool $want_untracked True for untracked paths, false for tracked changes.
	 *
	 * @return string[] Paths.
	 */
	private static function status_paths( bool $want_untracked ): array {
		$paths = array();

		foreach ( explode( "\n", self::output( 'status --porcelain' ) ) as $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}

			if ( str_starts_with( $line, '?? ' ) === $want_untracked ) {
				$paths[] = trim( substr( $line, 3 ) );
			}
		}

		return $paths;
	}

	/**
	 * Run a git command and return its trimmed output.
	 *
	 * @param string $arguments Arguments after `git`.
	 */
	private static function output( string $arguments ): string {
		return Shell::output( 'git ' . $arguments );
	}

	/**
	 * Run a git command and report only whether it succeeded.
	 *
	 * @param string $arguments Arguments after `git`.
	 */
	private static function succeeds( string $arguments ): bool {
		return Shell::succeeds( 'git ' . $arguments );
	}
}
