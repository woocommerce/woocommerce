<?php
/**
 * Running commands.
 *
 * Repo-agnostic.
 */

namespace LocalCi;

/**
 * The smallest wrapper around running a command that the rest of this tool needs.
 *
 * Everything else here talks to git, gh and pnpm through this, so there is one
 * place to change if command execution ever needs to be logged, sandboxed or
 * stubbed out in a test.
 */
final class Shell {

	/**
	 * Run a command and return its trimmed standard output.
	 *
	 * @param string $command Command to run.
	 */
	public static function output( string $command ): string {
		return trim( (string) shell_exec( $command ) );
	}

	/**
	 * Run a command and report only whether it exited zero.
	 *
	 * Output is discarded: callers that use this want the verdict, and letting a
	 * command's chatter reach the terminal would break up the step-by-step output.
	 *
	 * @param string $command Command to run.
	 */
	public static function succeeds( string $command ): bool {
		exec( $command . ' 2>/dev/null', $ignored, $exit_code );

		return 0 === $exit_code;
	}

	/**
	 * Whether a program is on PATH.
	 *
	 * @param string $program Program name.
	 */
	public static function has_program( string $program ): bool {
		return '' !== self::output( sprintf( 'command -v %s 2>/dev/null', escapeshellarg( $program ) ) );
	}

	/**
	 * Open a URL in the person's browser, if that is possible and wanted.
	 *
	 * Best effort by design. Opening a window is a side effect, so it is skipped
	 * when the output is not a terminal — a scripted or CI run should never have a
	 * browser thrown at it — and when NO_BROWSER is set. Failure is silent: the URL
	 * has already been printed, so the run continues either way.
	 *
	 * @param string $url URL to open.
	 *
	 * @return bool Whether a browser was launched.
	 */
	public static function open_url( string $url ): bool {
		if ( false !== getenv( 'NO_BROWSER' ) || ! stream_isatty( STDOUT ) ) {
			return false;
		}

		foreach ( array( 'open', 'xdg-open' ) as $opener ) {
			if ( self::has_program( $opener ) ) {
				return self::succeeds( sprintf( '%s %s', $opener, escapeshellarg( $url ) ) );
			}
		}

		return false;
	}

	/**
	 * Put text on the clipboard, if this machine has one.
	 *
	 * @param string $text Text to copy.
	 *
	 * @return bool Whether it was copied.
	 */
	public static function copy_to_clipboard( string $text ): bool {
		foreach ( array( 'pbcopy', 'wl-copy', 'xclip -selection clipboard' ) as $copier ) {
			$program = strtok( $copier, ' ' );

			if ( ! self::has_program( (string) $program ) ) {
				continue;
			}

			$handle = popen( $copier, 'w' );

			if ( ! is_resource( $handle ) ) {
				continue;
			}

			fwrite( $handle, $text );

			return 0 === pclose( $handle );
		}

		return false;
	}

	/**
	 * How many processors this machine reports, or zero when it will not say.
	 */
	public static function processor_count(): int {
		return (int) self::output( 'getconf _NPROCESSORS_ONLN 2>/dev/null || sysctl -n hw.ncpu 2>/dev/null' );
	}
}
