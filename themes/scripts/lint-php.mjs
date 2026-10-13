import { spawnSync } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import {
	getThemeDirectory,
	readThemeHeader,
	themesRoot,
} from './theme-utils.mjs';

try {
	const [ slug, ...args ] = process.argv.slice( 2 );
	const directory = await getThemeDirectory( slug );
	const stylesheet = await fs.readFile(
		path.join( directory, 'style.css' ),
		'utf8'
	);
	const minimum = readThemeHeader( stylesheet, 'Requires at least' );
	if ( ! /^\d+\.\d+(?:\.\d+)?$/.test( minimum ?? '' ) ) {
		throw new Error( 'Requires at least must be a WordPress version.' );
	}
	const result = spawnSync(
		'php',
		[
			path.join( themesRoot, 'vendor/bin/phpcs' ),
			`--standard=${ path.join( themesRoot, 'phpcs.xml.dist' ) }`,
			'--runtime-set',
			'text_domain',
			slug,
			'--runtime-set',
			'minimum_wp_version',
			minimum,
			'.',
			...args,
		],
		{ cwd: directory, stdio: 'inherit' }
	);
	if ( result.error ) {
		throw result.error;
	}
	process.exitCode = result.status ?? 1;
} catch ( error ) {
	console.error( error.message );
	process.exitCode = 1;
}
