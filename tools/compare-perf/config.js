const path = require( 'path' );

const getPnpmPackage = ( sourceDir ) => {
	const packageJson = require( path.join( sourceDir, 'package.json' ) );
	let pnpmPackage = 'pnpm';

	if ( packageJson.engines.pnpm ) {
		pnpmPackage = `pnpm@${ packageJson.engines.pnpm }`;
	}

	return pnpmPackage;
};

const config = {
	gitRepositoryURL: 'https://github.com/woocommerce/woocommerce.git',
	pluginPath: '/plugins/woocommerce',
	testsPath: '/plugins/woocommerce/tests/metrics/specs',
	getSetupTestRunner: ( sourceDir ) => {
		const pnpmPackage = getPnpmPackage( sourceDir );

		return `npm install -g ${ pnpmPackage } && pnpm install --frozen-lockfile --filter="@woocommerce/plugin-woocommerce" &> /dev/null && cd plugins/woocommerce && pnpm exec playwright install chromium`;
	},
	getSetupCommand: ( sourceDir ) => {
		const pnpmPackage = getPnpmPackage( sourceDir );

		return `npm install -g ${ pnpmPackage } && pnpm install --frozen-lockfile &> /dev/null && pnpm build &> /dev/null`;
	},
	getTestCommand: ( sourceDir ) => {
		const pnpmPackage = getPnpmPackage( sourceDir );
		return `npm install -g ${ pnpmPackage } && cd plugins/woocommerce && pnpm test:metrics`;
	},
};

module.exports = config;
