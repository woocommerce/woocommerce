import {
	copyFileSync,
	existsSync,
	mkdirSync,
	mkdtempSync,
	readFileSync,
	readdirSync,
	writeFileSync,
} from 'node:fs';
import { dirname, join } from 'node:path';
import { createRequire } from 'node:module';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = dirname( fileURLToPath( import.meta.url ) );
const require = createRequire( import.meta.url );

function run( executable, args, directory, logName ) {
	const child = spawnSync( executable, args, {
		cwd: directory,
		encoding: 'utf8',
		timeout: 180_000,
		maxBuffer: 16 * 1024 * 1024,
	} );
	const output = ( child.stdout ?? '' ) + ( child.stderr ?? '' );
	writeFileSync( join( directory, logName ), output );
	if ( child.error ) {
		throw child.error;
	}
	return { status: child.status, output };
}

function validate( result, violation, logPath ) {
	const expected = violation
		? result.status === 12 &&
			result.output.includes(
				'Error: Invariant ' + violation + ' is violated.'
			)
		: result.status === 0 &&
			result.output.includes(
				'Model checking completed. No error has been found.'
			);
	const match = result.output.match(
		/([\d,]+) states generated,\s*([\d,]+) distinct states found/
	);
	if ( ! expected || ! match ) {
		throw new Error( 'Unexpected checker result. Read ' + logPath );
	}
	return {
		generated: Number( match[ 1 ].replaceAll( ',', '' ) ),
		distinct: Number( match[ 2 ].replaceAll( ',', '' ) ),
	};
}

function parameters( constants, types ) {
	return [
		...Object.entries( constants ).map(
			( [ name, value ] ) =>
				'export const ' + name + ' = ' + JSON.stringify( value ) + ';'
		),
		...Object.entries( types ).map(
			( [ name, values ] ) =>
				'export type ' +
				name +
				' = ' +
				values
					.map( ( value ) => JSON.stringify( value ) )
					.join( ' | ' ) +
				';'
		),
		'',
	].join( '\n' );
}

function main() {
	const withTlc = process.argv.includes( '--tlc' );
	const args = process.argv.slice( 2 ).filter( ( arg ) => arg !== '--tlc' );
	const example = args[ 0 ] ?? 'stock-reservation';
	const examples = readdirSync( join( root, 'examples' ), {
		withFileTypes: true,
	} )
		.filter( ( entry ) => entry.isDirectory() )
		.map( ( entry ) => entry.name );
	if ( args.length > 2 || ! examples.includes( example ) ) {
		throw new Error( 'Usage: node run.mjs [--tlc] [example] [case]' );
	}
	const directory = join( root, 'examples', example );
	const definition = JSON.parse(
		readFileSync( join( directory, 'cases.json' ), 'utf8' )
	);
	if ( args[ 1 ] && ! Object.hasOwn( definition.cases, args[ 1 ] ) ) {
		throw new Error( 'Unknown case: ' + args[ 1 ] );
	}
	const cli = join(
		dirname( require.resolve( 'tla-ts/package.json' ) ),
		'dist/cli.js'
	);
	const jar = process.env.TLA2TOOLS_JAR;
	if ( withTlc && ( ! jar || ! existsSync( jar ) ) ) {
		throw new Error( 'Set TLA2TOOLS_JAR to the official tla2tools.jar.' );
	}
	const selected = args[ 1 ]
		? [ args[ 1 ] ]
		: Object.keys( definition.cases );
	for ( const name of selected ) {
		const options = definition.cases[ name ];
		const output = join( root, 'artifacts', example, name );
		mkdirSync( output, { recursive: true } );
		copyFileSync(
			join( directory, 'model.ts' ),
			join( output, 'model.ts' )
		);
		writeFileSync(
			join( output, 'parameters.ts' ),
			parameters(
				{ ...definition.constants, ...options.constants },
				{ ...definition.types, ...options.types }
			)
		);
		console.log( 'Checking ' + example + '/' + name );
		const checked = run(
			process.execPath,
			[ cli, '-workers', '1', 'model.ts' ],
			output,
			'tla-ts.log'
		);
		const states = validate(
			checked,
			options.violation,
			join( output, 'tla-ts.log' )
		);
		const result = {
			example,
			case: name,
			result: options.violation ? 'expected counterexample' : 'passed',
			states,
		};
		if ( withTlc ) {
			const meta = mkdtempSync( join( output, 'tlc-state-' ) );
			const tlc = run(
				process.env.TLA_JAVA ?? 'java',
				[
					'-XX:+UseParallelGC',
					'-Xmx512m',
					'-cp',
					jar,
					'tlc2.TLC',
					'-workers',
					'1',
					'-metadir',
					meta,
					'-config',
					join( output, 'Model.cfg' ),
					join( output, 'Model.tla' ),
				],
				output,
				'tlc.log'
			);
			result.tlc = validate(
				tlc,
				options.violation,
				join( output, 'tlc.log' )
			);
			if (
				! options.violation &&
				result.tlc.distinct !== states.distinct
			) {
				throw new Error( 'The checkers disagree on reachable states.' );
			}
		}
		writeFileSync(
			join( output, 'result.json' ),
			JSON.stringify( result, null, 2 ) + '\n'
		);
		console.log( JSON.stringify( result ) );
	}
}

try {
	main();
} catch ( error ) {
	console.error( error.message );
	process.exitCode = 1;
}
