'use strict';

const SUPPORTED_WORDPRESS_VERSION_TARGETS = [
	'latest',
	'latest-1',
	'gutenberg',
];

const BUNDLED_PACKAGES = [
	'@wordpress/admin-ui',
	'@wordpress/dataviews',
	'@wordpress/dataviews/wp',
	'@wordpress/fields',
	'@wordpress/grid',
	'@wordpress/icons',
	'@wordpress/interface',
	'@wordpress/style-runtime',
	'@wordpress/ui',
	'@wordpress/undo-manager',
	'@wordpress/views',
];

const BUNDLED_PACKAGE_SET = new Set( BUNDLED_PACKAGES );

function isWordPressPackage( packageName ) {
	return /^@wordpress\/[a-z0-9-]+$/.test( packageName );
}

function isBundledPackage( packageName ) {
	return BUNDLED_PACKAGE_SET.has( packageName );
}

function getSupportedWordPressVersions() {
	return [ ...SUPPORTED_WORDPRESS_VERSION_TARGETS ];
}

function getUnsupportedVersionMessage( wpVersion ) {
	return `Unsupported WordPress version "${ wpVersion }". Supported targets: ${ getSupportedWordPressVersions().join(
		', '
	) }`;
}

function getWordPressVersionMetadata( wpVersion ) {
	const version = String( wpVersion || 'latest' ).trim();

	if ( version === 'gutenberg' ) {
		return { distTag: 'latest', target: version };
	}

	if ( version === 'latest' ) {
		return { distTag: 'wp-latest', target: version };
	}

	if ( version === 'latest-1' ) {
		return { distTag: 'wp-latest-1', target: version };
	}

	throw new Error( getUnsupportedVersionMessage( wpVersion ) );
}

function getNpmDistTagForWordPressVersion( wpVersion ) {
	return getWordPressVersionMetadata( wpVersion ).distTag;
}

function resolveWordPressPackageSpec( packageName, wpVersion ) {
	if ( ! isWordPressPackage( packageName ) ) {
		throw new Error( `"${ packageName }" is not an @wordpress package.` );
	}

	return `${ packageName }@${ getNpmDistTagForWordPressVersion(
		wpVersion
	) }`;
}

function getPackagesForWordPressVersion( wpVersion ) {
	getWordPressVersionMetadata( wpVersion );
	return {};
}

module.exports = {
	BUNDLED_PACKAGES,
	SUPPORTED_WORDPRESS_VERSION_TARGETS,
	getPackagesForWordPressVersion,
	getNpmDistTagForWordPressVersion,
	getSupportedWordPressVersions,
	getWordPressVersionMetadata,
	isBundledPackage,
	isWordPressPackage,
	resolveWordPressPackageSpec,
};
