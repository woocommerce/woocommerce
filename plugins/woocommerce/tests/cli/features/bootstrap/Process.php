<?php

namespace WP_CLI;

/**
 * Run a system process, and learn what happened.
 */
class Process {

	/**
	 * @param string $command Command to execute.
	 * @param string $cwd Directory to execute the command in.
	 * @param array $env Environment variables to set when running the command.
	 */
	public static function create( $command, $cwd = null, $env = array() ) {
		$proc = new self;

		$proc->command = $command;
		$proc->cwd = $cwd;
		$proc->env = $env;

		return $proc;
	}

	private $command, $cwd, $env;

	private function __construct() {}

	/**
	 * Run the command.
	 *
	 * @return ProcessRun
	 */
	public function run() {
		$cwd = $this->cwd;

		$descriptors = array(
			0 => STDIN,
			1 => array( 'pipe', 'w' ),
			2 => array( 'pipe', 'w' ),
		);

		$proc = proc_open( $this->command, $descriptors, $pipes, $cwd, $this->env );

		$stdout = stream_get_contents( $pipes[1] );
		fclose( $pipes[1] );

		$stderr = stream_get_contents( $pipes[2] );
		fclose( $pipes[2] );

		return new ProcessRun( array(
			'stdout' => $stdout,
			'stderr' => $stderr,
			'return_code' => proc_close( $proc ),
			'command' => $this->command,
			'cwd' => $cwd,
			'env' => $this->env
		) );
	}

	/**
	 * Run the command, but throw an Exception on error.
	 *
	 * @return ProcessRun
	 */
	public function run_check() {
		$r = $this->run();

		if ( $r->return_code || !empty( $r->STDERR ) ) {
			throw new \RuntimeException( $r );
		}

		return $r;
	}
}

/**
 * Results of an executed command.
 */
class ProcessRun {

	/**
	 * @var array $props Properties of executed command.
	 */
	public function __construct( $props ) {
		foreach ( $props as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Return properties of executed command as a string.
	 *
	 * @return string
	 */
	public function __toString() {
		$out  = "$ $this->command\n";
		$out .= "$this->stdout\n$this->stderr";
		$out .= "cwd: $this->cwd\n";
		$out .= "exit status: $this->return_code";

		return $out;
	}

}
