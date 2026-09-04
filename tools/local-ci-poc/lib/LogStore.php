<?php
/**
 * Putting local run output somewhere a reviewer can read it.
 *
 * Repo-agnostic.
 */

namespace LocalCi;

/**
 * Uploads the output of a local run to a secret gist and hands back per-job links.
 *
 * A receipt says a job passed. On its own that is an assertion with nothing behind
 * it — a reviewer cannot see what ran. Attaching the output gives the status a
 * `target_url`, so the receipt in the checks list is clickable and lands on the
 * actual log.
 *
 * A gist is used because it needs no infrastructure and the GitHub CLI already
 * has the scope for it. The richer option is a check run, whose output renders
 * natively in the Checks tab, but that endpoint refuses anything other than a
 * GitHub App — so it is only available once this is fronted by one.
 */
final class LogStore {

	/**
	 * Largest slice of a single log to upload, in bytes.
	 *
	 * Test output can run to hundreds of kilobytes and the useful part is the end,
	 * where the failures and the summary are. The head is dropped, not the tail.
	 */
	private const MAX_LOG_BYTES = 40000;

	/**
	 * Gist description, so these are identifiable in a gist list.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * @param string $description Gist description.
	 */
	public function __construct( string $description ) {
		$this->description = $description;
	}

	/**
	 * Upload one file per job and return the anchored URL for each.
	 *
	 * @param array<string, string> $logs_by_job Job name => log contents.
	 *
	 * @return array<string, string> Job name => URL, empty when nothing could be uploaded.
	 */
	public function upload( array $logs_by_job ): array {
		if ( array() === $logs_by_job || ! Shell::has_program( 'gh' ) ) {
			return array();
		}

		$directory = sprintf( '%s/local-ci-logs-%d', sys_get_temp_dir(), getmypid() );

		if ( ! is_dir( $directory ) && ! @mkdir( $directory, 0700, true ) ) {
			return array();
		}

		$files       = array();
		$file_by_job = array();

		foreach ( $logs_by_job as $job_name => $contents ) {
			$file_name = self::file_name_for( $job_name );
			$path      = $directory . '/' . $file_name;

			if ( false === @file_put_contents( $path, self::prepare( $contents ) ) ) {
				continue;
			}

			$files[]                 = $path;
			$file_by_job[ $job_name ] = $file_name;
		}

		if ( array() === $files ) {
			self::remove_directory( $directory );

			return array();
		}

		// Secret rather than public: the output is a working artefact, not a
		// publication. Secret still means anyone with the link can read it, which
		// is why the contents are scrubbed before they get here.
		$output = Shell::output(
			sprintf(
				'gh gist create %s --desc %s 2>/dev/null',
				implode( ' ', array_map( 'escapeshellarg', $files ) ),
				escapeshellarg( $this->description )
			)
		);

		self::remove_directory( $directory );

		if ( ! preg_match( '#https://gist\.github\.com/\S+#', $output, $matches ) ) {
			return array();
		}

		$gist_url = rtrim( $matches[0] );
		$urls     = array();

		foreach ( $file_by_job as $job_name => $file_name ) {
			$urls[ $job_name ] = $gist_url . '#file-' . self::anchor_for( $file_name );
		}

		return $urls;
	}

	/**
	 * Trim a log to its tail and remove anything local to this machine.
	 *
	 * The home directory appears throughout test output as absolute paths. A
	 * secret gist is readable by anyone holding the link, so the account name
	 * should not travel with it.
	 *
	 * @param string $contents Raw log.
	 */
	private static function prepare( string $contents ): string {
		if ( strlen( $contents ) > self::MAX_LOG_BYTES ) {
			$contents = "[earlier output trimmed]\n\n" . substr( $contents, -self::MAX_LOG_BYTES );
		}

		$home = (string) getenv( 'HOME' );

		if ( '' !== $home ) {
			$contents = str_replace( $home, '~', $contents );
		}

		return $contents;
	}

	/**
	 * A file name for a job, safe for a gist and readable in one.
	 *
	 * @param string $job_name Job name.
	 */
	private static function file_name_for( string $job_name ): string {
		$slug = strtolower( (string) preg_replace( '/[^A-Za-z0-9]+/', '-', $job_name ) );

		return trim( $slug, '-' ) . '.log';
	}

	/**
	 * The anchor GitHub gives a gist file, so a link lands on the right one.
	 *
	 * @param string $file_name File name.
	 */
	private static function anchor_for( string $file_name ): string {
		return strtolower( (string) preg_replace( '/[^A-Za-z0-9]+/', '-', $file_name ) );
	}

	/**
	 * Remove the staging directory and everything in it.
	 *
	 * @param string $directory Directory to remove.
	 */
	private static function remove_directory( string $directory ): void {
		foreach ( (array) glob( $directory . '/*' ) as $file ) {
			@unlink( (string) $file );
		}

		@rmdir( $directory );
	}
}
