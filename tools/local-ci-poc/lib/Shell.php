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
	 * How many processors this machine reports, or zero when it will not say.
	 */
	public static function processor_count(): int {
		return (int) self::output( 'getconf _NPROCESSORS_ONLN 2>/dev/null || sysctl -n hw.ncpu 2>/dev/null' );
	}
}
