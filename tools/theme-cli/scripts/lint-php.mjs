import { spawnSync } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import { readThemeHeader, toolingRoot } from './theme-utils.mjs';

export async function lintPHP( directory ) {
	const slug = path.basename( directory );
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
			path.join( toolingRoot, 'vendor/bin/phpcs' ),
			`--standard=${ path.join( toolingRoot, 'phpcs.xml.dist' ) }`,
			'--runtime-set',
			'text_domain',
			slug,
			'--runtime-set',
			'minimum_wp_version',
			minimum,
			'.',
		],
		{ cwd: directory, stdio: 'inherit' }
	);
	if ( result.error ) {
		throw result.error;
	}
	return result.status ?? 1;
}
