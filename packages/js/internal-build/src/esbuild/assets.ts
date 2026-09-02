/**
 * External dependencies
 */
import { copyFile, mkdir } from 'node:fs/promises';
import { dirname, join, relative } from 'node:path';
import { glob } from 'glob';

export async function copyAssets(
	patterns: readonly string[],
	outdir: string
): Promise< void > {
	for ( const pattern of patterns ) {
		for ( const src of await glob( pattern ) ) {
			const dest = join( outdir, relative( 'src', src ) );
			await mkdir( dirname( dest ), { recursive: true } );
			await copyFile( src, dest );
		}
	}
}
