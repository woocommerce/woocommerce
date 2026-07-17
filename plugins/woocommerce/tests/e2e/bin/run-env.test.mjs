import { test } from 'node:test';
import assert from 'node:assert/strict';
import { parseArgs, isCi } from './run-env.mjs';

test( 'parseArgs extracts --rebuild and passes the rest through', () => {
	assert.deepEqual( parseArgs( [ '--rebuild', '--debug' ] ), {
		rebuild: true,
		passthrough: [ '--debug' ],
	} );
	assert.deepEqual( parseArgs( [ '--debug' ] ), {
		rebuild: false,
		passthrough: [ '--debug' ],
	} );
	assert.deepEqual( parseArgs( [] ), { rebuild: false, passthrough: [] } );
} );

test( 'isCi is true only for a non-empty CI var', () => {
	assert.equal( isCi( { CI: 'true' } ), true );
	assert.equal( isCi( { CI: '' } ), false );
	assert.equal( isCi( {} ), false );
} );
