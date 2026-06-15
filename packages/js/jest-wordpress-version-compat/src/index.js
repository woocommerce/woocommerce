'use strict';

const { withWordPressDependencyCompat } = require( './jest' );
const { getWordPressVersionMetadata } = require( './metadata' );

function getWordPressVersionTarget( env = process.env ) {
	const wpVersion = env.WP_VERSION;

	if ( ! wpVersion ) {
		return undefined;
	}

	return getWordPressVersionMetadata( wpVersion ).target;
}

function isWordPressVersionTarget( targets, env = process.env ) {
	const targetList = Array.isArray( targets ) ? targets : [ targets ];
	const selectedTarget = getWordPressVersionTarget( env );

	for ( const target of targetList ) {
		getWordPressVersionMetadata( target );
	}

	return Boolean( selectedTarget && targetList.includes( selectedTarget ) );
}

module.exports = {
	getWordPressVersionTarget,
	isWordPressVersionTarget,
	withWordPressDependencyCompat,
};
