const packageJson = require( '../../package.json' );
let pnpm_package = 'pnpm';

if ( packageJson.engines.pnpm ) {
	pnpm_package = `pnpm@${ packageJson.engines.pnpm }`;
}

const config = {
	gitRepositoryURL: 'https://github.com/woocommerce/woocommerce.git',
	setupTestRunner: `npm install -g ${ pnpm_package } && pnpm install --filter="@woocommerce/plugin-woocommerce" &> /dev/null && cd plugins/woocommerce && pnpm exec playwright install chromium`,
	setupCommand: `npm install -g ${ pnpm_package } && pnpm install &> /dev/null && pnpm build`,
	pluginPath: '/plugins/woocommerce',
	testsPath: '/plugins/woocommerce/tests/metrics/specs',
	testCommand: `npm install -g ${ pnpm_package } && cd plugins/woocommerce && pnpm test:metrics`,
};

module.exports = config;
