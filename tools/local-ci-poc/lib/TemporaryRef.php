<?php
/**
 * Making a commit known to GitHub without triggering anything.
 *
 * Repo-agnostic.
 */

namespace LocalCi;

/**
 * A ref that exists only long enough for a status to be attached to a commit.
 *
 * A commit status can only be attached to a commit GitHub knows about, and the
 * whole point is to publish before the branch is pushed. `refs/local-ci/<sha>`
 * solves that: it is outside `refs/heads/*` and `refs/tags/*`, so Actions cannot
 * trigger on it, and it stays out of the branch list and out of everyone's fetch.
 *
 * The ref is temporary and this class owns its lifetime. An earlier version of
 * this tool was interrupted mid-run and left one behind on the remote, so removal
 * is registered for every way the process can end rather than being left to the
 * happy path.
 */
final class TemporaryRef {

	/**
	 * The ref currently published, or null when there is none.
	 *
	 * Static because the shutdown and signal handlers need to reach it without
	 * being handed an instance.
	 *
	 * @var string|null
	 */
	private static $published;

	/**
	 * Publish HEAD under a ref named after the commit.
	 *
	 * @param string $sha Commit to publish.
	 *
	 * @return bool Whether the push succeeded.
	 */
	public static function publish( string $sha ): bool {
		$ref = 'refs/local-ci/' . $sha;

		if ( ! Git::push_ref( $ref ) ) {
			return false;
		}

		self::$published = $ref;

		return true;
	}

	/**
	 * The ref name currently published, for output.
	 */
	public static function name(): ?string {
		return self::$published;
	}

	/**
	 * Remove the ref if one is published. Safe to call more than once.
	 */
	public static function remove(): void {
		if ( null === self::$published ) {
			return;
		}

		$ref             = self::$published;
		self::$published = null;

		Git::delete_ref( $ref );
	}
}
