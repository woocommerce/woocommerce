/**
 * External dependencies
 */
const fs = require( 'fs' );
const { execSync } = require( 'child_process' );
const path = require( 'path' );
const chalk = require( 'chalk' );
const watch = require( 'node-watch' );

const BUILD_CMD = `node ${ path
	.resolve( __dirname, './build.js' )
	.replace( /(\s+)/g, '\\$1' ) }`;

let filesToBuild = new Map();

const exists = ( filename ) => {
	try {
		return fs.statSync( filename ).isFile();
	} catch ( e ) {}
	return false;
};

// Exclude deceitful source-like files, such as editor swap files.
const isSourceFile = ( filename ) => {
	return ! /\/node_modules\//.test( filename ) && /\.scss$/.test( filename );
};

const rebuild = ( filename ) => filesToBuild.set( filename, true );

const dir = process.argv.slice( 2 );

if ( ! dir.length ) {
	// eslint-disable-next-line no-console
	console.log(
		chalk.inverse.bold.red( ' ERROR ' ) +
			chalk.bold.red( ' no build path specified' )
	);
	process.exit( 1 );
}

const srcDir = path.resolve( dir[ 0 ] );

try {
	fs.accessSync( srcDir, fs.F_OK );
	watch( srcDir, { recursive: true }, ( event, filename ) => {
		const filePath = path.resolve( srcDir, filename );

		if ( ! isSourceFile( filename ) ) {
			return;
		}

		if (
			[ 'update', 'change', 'rename' ].includes( event ) &&
			exists( filePath )
		) {
			// eslint-disable-next-line no-console
			console.log( chalk.green( '->' ), `${ event }: ${ filename }` );
			rebuild( filePath );
		} else {
			const buildFile = path.resolve( srcDir, '..', 'build', filename );
			try {
				fs.unlinkSync( buildFile );
				process.stdout.write(
					chalk.red( '  \u2022 ' ) +
						path.relative(
							path.resolve( srcDir, '..', '..' ),
							buildFile
						) +
						' (deleted)' +
						'\n'
				);
			} catch ( e ) {}
		}
	} );
} catch ( e ) {
	// doesn't exist
}

setInterval( () => {
	const files = Array.from( filesToBuild.keys() );
	if ( files.length ) {
		filesToBuild = new Map();
		try {
			execSync(
				`${ BUILD_CMD } ${ files
					.map( ( file ) => file.replace( /(\s+)/g, '\\$1' ) )
					.join( ' ' ) }`,
				{ stdio: [ 0, 1, 2 ] }
			);
		} catch ( e ) {}
	}
}, 100 );

// eslint-disable-next-line no-console
console.log( chalk.red( '->' ), chalk.cyan( 'Watching for changes...' ) );
