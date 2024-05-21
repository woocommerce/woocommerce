const path = require( 'path' );

const getPnpmPackage = ( sourceDir ) => {
	const packageJson = require( path.join( sourceDir, 'package.json' ) );
	let pnpm_package = 'pnpm';

	if ( packageJson.engines.pnpm ) {
		pnpm_package = `pnpm@${ packageJson.engines.pnpm }`;
	}

	return pnpm_package;
};

const config = {
	gitRepositoryURL: 'https://github.com/woocommerce/woocommerce.git',
	pluginPath: '/plugins/woocommerce',
	testsPath: '/plugins/woocommerce/tests/metrics/specs',
	getSetupTestRunner: ( sourceDir ) => {
		const pnpm_package = getPnpmPackage( sourceDir );

		return `npm install -g ${ pnpm_package } && pnpm install --filter="@woocommerce/plugin-woocommerce" &> /dev/null && cd plugins/woocommerce && pnpm exec playwright install chromium`;
	},
	getSetupCommand: ( sourceDir ) => {
		const pnpm_package = getPnpmPackage( sourceDir );

		return `npm install -g ${ pnpm_package } && pnpm install &> /dev/null && pnpm build &> /dev/null`;
	},
	getTestCommand: ( sourceDir ) => {
		const pnpm_package = getPnpmPackage( sourceDir );
		return `npm install -g ${ pnpm_package } && cd plugins/woocommerce && pnpm test:metrics`;
	},
};

module.exports = config;
