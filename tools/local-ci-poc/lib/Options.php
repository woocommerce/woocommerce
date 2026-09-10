<?php
/**
 * Command line arguments.
 *
 * Repo-agnostic.
 */

namespace LocalCi;

/**
 * What the caller asked for.
 */
final class Options {

	/**
	 * Whether to push the branch once receipts are published.
	 *
	 * Off by default. Publishing a receipt is a local act with no visible effect,
	 * but pushing a branch starts CI and notifies reviewers, so it stays something
	 * the contributor asks for rather than something running the checks does to them.
	 *
	 * @var bool
	 */
	public $push = false;

	/**
	 * Substrings limiting which projects to run.
	 *
	 * @var string[]
	 */
	public $only = array();

	/**
	 * How many checks to run at once.
	 *
	 * @var int
	 */
	public $concurrency;

	/**
	 * Whether the arguments asked for help.
	 *
	 * @var bool
	 */
	public $wants_help = false;

	/**
	 * The first argument that made no sense, or null when they all did.
	 *
	 * @var string|null
	 */
	public $unknown_argument;

	/**
	 * Parse arguments.
	 *
	 * @param string[] $argv Full argument vector, including the script name.
	 */
	public static function from_argv( array $argv ): self {
		$options              = new self();
		$options->concurrency = CheckRunner::default_concurrency();

		foreach ( array_slice( $argv, 1 ) as $argument ) {
			if ( '--push' === $argument ) {
				$options->push = true;
				continue;
			}

			if ( str_starts_with( $argument, '--only=' ) ) {
				$options->only = array_values(
					array_filter( array_map( 'trim', explode( ',', substr( $argument, 7 ) ) ) )
				);
				continue;
			}

			if ( str_starts_with( $argument, '--jobs=' ) ) {
				$options->concurrency = max( 1, (int) substr( $argument, 7 ) );
				continue;
			}

			if ( '--help' === $argument || '-h' === $argument ) {
				$options->wants_help = true;
				continue;
			}

			$options->unknown_argument = $argument;

			return $options;
		}

		return $options;
	}

	/**
	 * The usage text.
	 *
	 * @param string $script Script name, for the first line.
	 */
	public static function usage( string $script ): string {
		return sprintf(
			"usage: php %s [--push] [--only=SUBSTRING[,...]] [--jobs=N]\n\n"
				. "  --push   push the current branch after the receipts are published, so\n"
				. "           the SHA that reaches GitHub is the one they name\n"
				. "  --only   only run jobs whose project name contains one of these\n"
				. "           substrings. Whatever is left out gets no receipt, so CI runs it\n"
				. "  --jobs   how many checks to run at once (default: half this machine's\n"
				. "           cores, capped at 8)",
			$script
		);
	}
}
