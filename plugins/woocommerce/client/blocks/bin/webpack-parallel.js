/**
 * Runs the blocks webpack build in several processes.
 *
 * webpack builds all configs of a multi-config build on one thread, so the
 * blocks build used to keep only one CPU core busy. This starts one webpack
 * process per share of the configs (see `getShard` in webpack.config.js) and
 * passes extra arguments, such as `--watch`, to each process.
 *
 * Set WC_BLOCKS_BUILD_PROCESSES to choose the number of processes.
 */
const { spawn } = require( 'child_process' );
const os = require( 'os' );

// Leave one core for the other builds that often run at the same time, and
// allow one process per 3 GB of memory, so 8 GB machines get 2 processes.
// More than 4 processes gains little, since the 4 largest configs are about
// the same size.
const count =
	Number( process.env.WC_BLOCKS_BUILD_PROCESSES ) ||
	Math.max(
		1,
		Math.min(
			4,
			os.availableParallelism() - 1,
			Math.floor( os.totalmem() / ( 3 * 2 ** 30 ) )
		)
	);

// Start the `webpack` command from the package scripts' PATH, as the scripts
// did before, so it gets the same environment.
const children = Array.from( { length: count }, ( _, index ) =>
	spawn( 'webpack', process.argv.slice( 2 ), {
		env: { ...process.env, WC_BLOCKS_BUILD_SHARD: `${ index }/${ count }` },
		stdio: 'inherit',
	} )
);

let exitCode = 0;
let running = children.length;
for ( const child of children ) {
	child.on( 'exit', ( code, signal ) => {
		running--;
		if ( code !== 0 ) {
			exitCode = code || 1;
			// Stop the others so a failed build or watcher does not look healthy.
			children.forEach( ( other ) => other.kill( signal || 'SIGTERM' ) );
		}
		if ( running === 0 ) {
			process.exit( exitCode );
		}
	} );
}

for ( const signal of [ 'SIGINT', 'SIGTERM' ] ) {
	process.on( signal, () =>
		children.forEach( ( child ) => child.kill( signal ) )
	);
}
