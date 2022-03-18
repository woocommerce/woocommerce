/**
 * External dependencies.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const readlineSync = require( 'readline-sync' );

/**
 * Internal dependencies.
 */
const { resolveLocalE2ePath, resolvePackagePath } = require( './test-config' );

/**
 * Create a path relative to the local `tests/e2e` folder.
 * @param relativePath
 * @return {string}
 */
const createLocalE2ePath = ( relativePath ) => {
	let specFolderPath = '';
	const folders = [ `..${path.sep}..${path.sep}tests`, `..${path.sep}e2e`, relativePath ];
	folders.forEach( ( folder ) => {
		specFolderPath = resolveLocalE2ePath( folder );
		if ( ! fs.existsSync( specFolderPath ) ) {
			console.log( `Creating folder ${specFolderPath}` );
			fs.mkdirSync( specFolderPath );
		}
	} );

	return specFolderPath;
};

/**
 * Prompt the console for confirmation.
 *
 * @param {string} prompt Prompt for the user.
 * @param {string} choices valid responses.
 * @return {string}
 */
const confirm = ( prompt, choices ) => {
	const answer = readlineSync.keyIn( prompt, choices );
	return answer;
};

/**
 *
 * @param {string} localE2ePath Destination path
 * @param {string} packageE2ePath Source path
 * @param {string} packageName Source package. Default @woocommerce/e2e-environment package.
 * @param {boolean} force Whether to override files if they exist without confirmation.
 * @return {boolean}
 */
const confirmLocalCopy = ( localE2ePath, packageE2ePath, packageName = '', force = false ) => {
	const localPath = resolveLocalE2ePath( localE2ePath );
	const packagePath = resolvePackagePath( packageE2ePath, packageName );
	const confirmPrompt = `${localE2ePath} already exists. Overwrite? [Y]es/[n]o: `;

	let overwriteFiles;
	if ( ! force && fs.existsSync( localPath ) ) {
		overwriteFiles = confirm( confirmPrompt, 'ny' );
		overwriteFiles = overwriteFiles.toLowerCase();
	} else {
		overwriteFiles = 'y';
	}
	if ( overwriteFiles == 'y' ) {
		fs.copyFileSync( packagePath, localPath );
		return true;
	}

	return false;
};

/**
 * Prompt for confirmation before deleting a local E2E file.
 *
 * @param {string} localE2ePath Relative path to local E2E file.
 */
const confirmLocalDelete = ( localE2ePath ) => {
	const localPath = resolveLocalE2ePath( localE2ePath );
	if ( ! fs.existsSync( localPath ) ) {
		return;
	}

	const confirmPrompt = `${localE2ePath} exists. Delete? [y]es/[n]o: `;
	const deleteFile = confirm( confirmPrompt, 'ny' );
	if ( deleteFile == 'y' ) {
		fs.unlinkSync( localPath );
	}
};

/**
 * Get the install data for a tests package.
 *
 * @param {string} packageName npm package name
 * @return {string}
 */
const getPackageData = ( packageName ) => {
	const packageSlug = packageName.replace( '@', '' ).replace( /\//g, '.' );
	const installFiles = require( `${packageName}${path.sep}installFiles` );

	return { packageSlug, ...installFiles };
};

/**
 * Install test runner and test container defaults
 */
const installDefaults = ( force ) => {
	createLocalE2ePath( 'docker' );
	console.log( 'Writing tests/e2e/docker/initialize.sh' );
	confirmLocalCopy( `docker${path.sep}initialize.sh`, `installFiles${path.sep}initialize.sh`, '', force );

	createLocalE2ePath( 'config' );
	console.log( 'Writing tests/e2e/config/jest.config.js' );
	confirmLocalCopy( `config${path.sep}jest.config.js`, `installFiles${path.sep}jest.config.js.default`, '', force );
	console.log( 'Writing tests/e2e/config/jest.setup.js' );
	confirmLocalCopy( `config${path.sep}jest.setup.js`, `installFiles${path.sep}jest.setup.js.default`, '', force );
};

module.exports = {
	createLocalE2ePath,
	confirm,
	confirmLocalCopy,
	confirmLocalDelete,
	getPackageData,
	installDefaults,
};
