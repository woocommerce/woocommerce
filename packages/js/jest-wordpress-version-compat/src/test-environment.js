'use strict';

const { getWordPressVersionMetadata } = require( './metadata' );

function getCurrentWordPressVersion() {
	return getWordPressVersionMetadata( process.env.WP_VERSION ).target;
}

function isLatestGutenberg() {
	return getCurrentWordPressVersion() === 'gutenberg';
}

function isLatestMinusOneWordPress() {
	return getCurrentWordPressVersion() === 'latest-1';
}

function isLatestWordPress() {
	return getCurrentWordPressVersion() === 'latest';
}

module.exports = {
	getCurrentWordPressVersion,
	isLatestGutenberg,
	isLatestMinusOneWordPress,
	isLatestWordPress,
};
