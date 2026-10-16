import { spawnSync } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import { getThemeDirectory, isMain } from './theme-utils.mjs';

export async function buildZip( slug ) {
	const directory = await getThemeDirectory( slug );
	const { name } = JSON.parse(
		await fs.readFile( path.join( directory, 'package.json' ), 'utf8' )
	);
	const result = spawnSync(
		'pnpm',
		[ 'exec', 'wp-scripts', 'plugin-zip', `--root-folder=${ slug }` ],
		{ cwd: directory, stdio: 'inherit' }
	);
	if ( result.error ) {
		throw result.error;
	}
	if ( result.status !== 0 ) {
		throw new Error( `Could not build the ZIP for ${ slug }.` );
	}
	const outputDirectory = path.join( directory, 'artifacts' );
	await fs.mkdir( outputDirectory, { recursive: true } );
	const archive = path.join( outputDirectory, `${ slug }.zip` );
	await fs.rename( path.join( directory, `${ name }.zip` ), archive );
	return archive;
}

if ( isMain( import.meta.url ) ) {
	try {
		process.stdout.write( `${ await buildZip( process.argv[ 2 ] ) }\n` );
	} catch ( error ) {
		process.stderr.write( `${ error.message }\n` );
		process.exitCode = 1;
	}
}
