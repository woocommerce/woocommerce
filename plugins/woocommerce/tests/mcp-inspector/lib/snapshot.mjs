import assert from 'node:assert/strict';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const SNAPSHOT_DIR = path.join(
	path.dirname( fileURLToPath( import.meta.url ) ),
	'../snapshots'
);

const UPDATE = Boolean( process.env.UPDATE_SNAPSHOTS );

export function snapshotPath( name ) {
	return path.join( SNAPSHOT_DIR, `${ name }.json` );
}

/**
 * A missing snapshot is written and fails the test, so a new ability cannot pass before its snapshot is reviewed.
 */
export function assertMatchesSnapshot( name, actual ) {
	const file = snapshotPath( name );

	if ( UPDATE || ! existsSync( file ) ) {
		mkdirSync( path.dirname( file ), { recursive: true } );
		writeFileSync( file, JSON.stringify( actual, null, '\t' ) + '\n' );
		if ( ! UPDATE ) {
			assert.fail(
				`Snapshot created at ${ file }, review it and re-run the suite.`
			);
		}
		return;
	}

	assert.deepEqual(
		actual,
		JSON.parse( readFileSync( file, 'utf8' ) ),
		`Output does not match snapshot ${ file }. Run with UPDATE_SNAPSHOTS=1 if the change is intended.`
	);
}
