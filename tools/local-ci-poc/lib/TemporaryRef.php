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
	 * Whether cleanup handlers have been registered.
	 *
	 * @var bool
	 */
	private static $handlers_registered = false;

	/**
	 * Publish HEAD under a ref named after the commit.
	 *
	 * @param string $sha Commit to publish.
	 *
	 * @return bool Whether the push succeeded.
	 */
	public static function publish( string $sha ): bool {
		self::register_handlers();

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

	/**
	 * Arrange for cleanup however the process ends.
	 *
	 * Anything this tool started elsewhere is stopped here too, so an interrupted
	 * run does not leave test processes holding the CPU.
	 */
	private static function register_handlers(): void {
		if ( self::$handlers_registered ) {
			return;
		}

		self::$handlers_registered = true;

		register_shutdown_function(
			static function (): void {
				CheckRunner::stop_everything();
				self::remove();
			}
		);

		if ( ! function_exists( 'pcntl_signal' ) ) {
			return;
		}

		pcntl_async_signals( true );

		foreach ( array( SIGINT, SIGTERM, SIGHUP ) as $signal ) {
			pcntl_signal(
				$signal,
				static function (): void {
					CheckRunner::stop_everything();
					self::remove();
					exit( 130 );
				}
			);
		}
	}
}
