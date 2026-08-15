import { readdir, rm, stat } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const outDir = path.join(
	path.dirname( path.dirname( fileURLToPath( import.meta.url ) ) ),
	'out'
);

async function main() {
	const args = process.argv.slice( 2 );
	const all = args.includes( '--all' );
	const keepArg = args.find( ( a ) => a.startsWith( '--keep=' ) );
	const keep = all ? 0 : keepArg ? Number( keepArg.split( '=' )[ 1 ] ) : 1;
	if ( ! Number.isInteger( keep ) || keep < 0 ) {
		throw new Error( `Invalid --keep value: ${ keepArg }` );
	}

	let entries;
	try {
		entries = await readdir( outDir, { withFileTypes: true } );
	} catch {
		console.log( 'Nothing to clean — no out/ directory yet.' );
		return;
	}

	// Run directories are named by timestamp, so a name sort is a date sort.
	const runs = entries
		.filter( ( e ) => e.isDirectory() )
		.map( ( e ) => e.name )
		.sort()
		.reverse();

	const toDelete = runs.slice( keep );
	for ( const name of toDelete ) {
		await rm( path.join( outDir, name ), { recursive: true, force: true } );
		console.log( `deleted ${ name }` );
	}

	const kept = runs.slice( 0, keep );
	if ( toDelete.length === 0 ) {
		console.log(
			`Nothing to delete (${ runs.length } run(s), keeping ${ keep }).`
		);
	} else {
		console.log(
			`Deleted ${ toDelete.length } run(s)${
				kept.length ? `, kept: ${ kept.join( ', ' ) }` : ''
			}.`
		);
	}

	const sizeOf = async ( dir ) => {
		let total = 0;
		for ( const entry of await readdir( dir, { withFileTypes: true } ) ) {
			const p = path.join( dir, entry.name );
			total += entry.isDirectory()
				? await sizeOf( p )
				: ( await stat( p ) ).size;
		}
		return total;
	};
	const remaining = ( await sizeOf( outDir ) ) / ( 1024 * 1024 );
	console.log( `out/ now uses ${ remaining.toFixed( 1 ) } MB.` );
}

main().catch( ( err ) => {
	console.error( err.message );
	process.exitCode = 1;
} );
