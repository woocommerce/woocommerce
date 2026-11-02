/**
 * External dependencies
 */
import { globSync } from 'node:fs';
import { copyFile, mkdir } from 'node:fs/promises';
import { dirname, join, relative } from 'node:path';

export async function copyAssets(
	patterns: readonly string[],
	outdir: string
): Promise< void > {
	for ( const pattern of patterns ) {
		for ( const src of globSync( pattern ) ) {
			const dest = join( outdir, relative( 'src', src ) );
			await mkdir( dirname( dest ), { recursive: true } );
			await copyFile( src, dest );
		}
	}
}
