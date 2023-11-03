#!/usr/bin/env node

/**
 *
 * @param {latestVersion} latestVersion
 * @param {minus} minus
 * @returns {String} the minused version.
 */
function getLatestMinusVersion( latestVersion, minus ) {
	// Convert the 1 or 2 to a decimal we can use for the logic below.
	let minusAmount = minus / 10;

	// Check if we only have a major / minor (e.g. x.x) to append a patch version
	if ( latestVersion.match( /\./g ).length < 2 ) {
		latestVersion = latestVersion.concat( '.0' )
	}

	const baseVersion = latestVersion.replace( /.[^\.]$/, '' );

	// Calculate the version we need and return.
	console.info( String( baseVersion - minusAmount ) );
	process.exit( 0 );
}

const latestVersion = process.argv[2];
const minus = process.argv[3];
if ( ! latestVersion || ! minus ) {
	console.error( 'Usage: get-previous-version.js <latestVersion> <minus>' );
	process.exit( 1 );
}

getLatestMinusVersion( latestVersion, minus );
