<?php
/**
 * Tool to list whether projects have been touched so as to need a changelog entry.
 *
 * Based on the tool from the Jetpack repository:
 * https://github.com/Automattic/jetpack/blob/master/tools/check-changelogger-use.php
 */

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.PHP.DiscouragedPHPFunctions, WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.GlobalVariablesOverride

/**
 * Display usage information and exit.
 */
function usage() {
	global $argv;
	echo <<<EOH
USAGE: {$argv[0]} [--debug|-v] [--list] [-p <path>|--path=<path>] <base-ref> <head-ref>
Checks that a monorepo commit contains a Changelogger change entry for each
project touched.
  --debug, -v     Display verbose output.
  --list          Just list projects, no explanatory output.
  --path=<path>,  Project path to check for changed files.
    -p <path>
  <base-ref>      Base git ref to compare for changed files.
  <head-ref>      Head git ref to compare for changed files.
EOH;
	exit( 1 );
}

// Options followed by a single colon have a required value.
$short_options = 'vhp:';
$long_options = array(
	'debug',
	'list',
	'help',
	'path:',
);
$options = getopt( $short_options, $long_options, $remain_index );
$arg_count = count( $argv ) - $remain_index;

if ( isset( $options['h'] ) || isset( $options['help'] ) ) {
	usage();
}

$list = isset( $options['l'] ) || isset( $options['list'] );
$verbose = isset( $options['v'] ) || isset( $options['debug'] );
$path = false;
if ( isset( $options['p'] ) || isset( $options['path'] ) ) {
	$path = isset( $options['path'] ) ? $options['path'] : $options['p'];
}

if ( $arg_count > 2 ) {
	fprintf( STDERR, "\e[1;31mToo many arguments.\e[0m\n" );
	usage();
}

if ( $arg_count < 2 ) {
	fprintf( STDERR, "\e[1;31mBase and head refs are required.\e[0m\n" );
	usage();
}

$base = $argv[ count( $argv ) - 2 ];
$head = $argv[ count( $argv ) - 1 ];

if ( $verbose ) {
	/**
	 * Output debug info.
	 *
	 * @param array ...$args Arguments to printf. A newline is automatically appended.
	 */
	function debug( ...$args ) {
		if ( getenv( 'CI' ) ) {
			$args[0] = "\e[34m{$args[0]}\e[0m\n";
		} else {
			$args[0] = "\e[1;30m{$args[0]}\e[0m\n";
		}
		fprintf( STDERR, ...$args );
	}
} else {
	/**
	 * Do not output debug info.
	 */
	function debug() {
	}
}

$base_path = dirname( dirname( __DIR__ ) );

$workspace_paths = array();
$workspace_yaml = file_get_contents( $base_path . '/pnpm-workspace.yaml' );
if ( preg_match( '/^packages:((\n\s+.+)+)/', $workspace_yaml, $matches ) ) {
        $packages_config = $matches[1];
        if ( preg_match_all( "/^\s+-\s?'([^']+)'/m", $packages_config, $matches ) ) {
                $workspace_paths = $matches[1];
        }
}

$composer_files = array_map( function( $path ) {
        return glob( $path . '/composer.json' );
}, $workspace_paths );
$composer_files = array_merge( ...$composer_files );
$composer_projects = array_map( 'dirname', $composer_files );

if ( $path && ! count( $composer_projects ) ) {
	debug( sprintf( 'The provided project path, %s, did not contain a composer file.', $path ) );
	exit( 1 );
}

// Find projects that use changelogger, and read the relevant config.
$changelogger_projects = array();
foreach ( $composer_projects as $project_path ) {
	try {
		$data = json_decode( file_get_contents( $base_path . '/' . $project_path . '/composer.json' ), true, 512, JSON_THROW_ON_ERROR );
		if (
			! isset( $data['require']['automattic/jetpack-changelogger'] ) &&
			! isset( $data['require-dev']['automattic/jetpack-changelogger'] )
		) {
			continue;
		}
	} catch ( Exception $e ) {
		continue;
	}
	$data  = isset( $data['extra']['changelogger'] ) ? $data['extra']['changelogger'] : array();

	if ( ! isset( $data[ 'changelog' ] ) ) {
		$data['changelog'] = $project_path . '/CHANGELOG.md';
	}
	if ( ! isset( $data[ 'changes-dir' ] ) ) {
		$data['changes-dir'] = $project_path . '/changelog';
	}

	$changelogger_projects[ $project_path ] = $data;
}

// Support centralizing the changelogs for multiple components and validating them together.
$project_component_map = array(
	'plugins/woocommerce-admin'  => 'plugins/woocommerce',
	'plugins/woocommerce-blocks' => 'plugins/woocommerce',
);

// Process the diff.
debug( 'Checking diff from %s...%s.', $base, $head );
$pipes = null;
$p     = proc_open(
	sprintf( 'git -c core.quotepath=off diff --no-renames --name-only %s...%s', escapeshellarg( $base ), escapeshellarg( $head ) ),
	array( array( 'pipe', 'r' ), array( 'pipe', 'w' ), STDERR ),
	$pipes
);
if ( ! $p ) {
	exit( 1 );
}
fclose( $pipes[0] );

$ok_projects      = array();
$touched_projects = array();
// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
while ( ( $line = fgets( $pipes[1] ) ) ) {
	$line  = trim( $line );

	$project_match = false;
	foreach( $composer_projects as $path ) {
		if ( substr( $line, 0, strlen( $path ) + 1 ) === $path . '/' ) {
			$project_match = $path;
			break;
		}
	}

	// Support overriding the project when checking a component.
	if ( isset( $project_component_map[ $project_match ] ) ) {
		$project_match = $project_component_map[ $project_match ];
		debug( 'Mapping %s to project %s.', $line, $project_match );
	}

	if ( false === $project_match ) {
		debug( 'Ignoring non-project file %s.', $line );
		continue;
	}

	if ( ! isset( $changelogger_projects[ $project_match ] ) ) {
		debug( 'Ignoring file %s, project %s does not use changelogger.', $line, $project_match );
		continue;
	}
	if ( basename( $line ) === $changelogger_projects[ $project_match ]['changelog'] ) {
		debug( 'Ignoring changelog file %s.', $line );
		continue;
	}
	if ( dirname( $line ) === $changelogger_projects[ $project_match ]['changes-dir'] ) {
		if ( '.' === basename( $line )[0] ) {
			debug( 'Ignoring changes dir dotfile %s.', $line );
		} else {
			debug( 'PR touches file %s, marking %s as having a change file.', $line, $project_match );
			$ok_projects[ $project_match ] = true;
		}
		continue;
	}
	// Ignore dot-files: those are development related, and it makes no sense to create a changelog entry for them.
	if ( '.' === basename( $line )[0] ) {
		debug( 'Ignoring changes dot-file %s.', $line );
		continue;
	}

	debug( 'PR touches file %s, marking %s as touched.', $line, $project_match );
	if ( ! isset( $touched_projects[ $project_match ] ) ) {
		$touched_projects[ $project_match ][] = $line;
	}
}

fclose( $pipes[1] );
$status = proc_close( $p );
if ( $status ) {
	exit( $status );
}

// Output.
ksort( $touched_projects );
$exit = 0;
foreach ( $touched_projects as $slug => $files ) {
	if ( empty( $ok_projects[ $slug ] ) ) {
		if ( $list ) {
			echo "$slug\n";
		} elseif ( getenv( 'CI' ) ) {
			printf( "---\n" ); // Bracket message containing newlines for better visibility in GH's logs.
			printf(
				"::error::Project %s is being changed, but no change file in %s is touched!\n\nUse `pnpm --filter=./%s changelog add` to add a change file.\n",
				$slug,
				"$slug/{$changelogger_projects[ $slug ]['changes-dir']}/",
				$slug
			);
			printf( "---\n" );
			$exit = 1;
		} else {
			printf(
				"\e[1;31mProject %s is being changed, but no change file in %s is touched!\e[0m\n",
				$slug,
				"$slug/{$changelogger_projects[ $slug ]['changes-dir']}/"
			);
			$exit = 1;
		}
	}
}
if ( $exit && ! getenv( 'CI' ) && ! $list ) {
	printf( "\e[32mUse `pnpm --filter={project} changelog add` to add a change file for each project.\e[0m\n" );
}

exit( $exit );
