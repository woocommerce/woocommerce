'use strict';

const { withWordPressDependencyCompat } = require( './jest' );
const {
	getCurrentWordPressVersion,
	isLatestGutenberg,
	isLatestMinusOneWordPress,
	isLatestWordPress,
} = require( './test-environment' );

module.exports = {
	getCurrentWordPressVersion,
	isLatestGutenberg,
	isLatestMinusOneWordPress,
	isLatestWordPress,
	withWordPressDependencyCompat,
};
