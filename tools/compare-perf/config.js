const packageJson = require( '../../package.json' );
const pnpm_version = `@${ packageJson.engines.pnpm }` || '';

const config = {
	gitRepositoryURL: 'https://github.com/woocommerce/woocommerce.git',
	setupTestRunner: `npm install -g pnpm${ pnpm_version } && pnpm install --filter="@woocommerce/plugin-woocommerce" &> /dev/null && cd plugins/woocommerce && pnpm exec playwright install chromium`,
	setupCommand: `npm install -g pnpm${ pnpm_version } && pnpm install &> /dev/null && pnpm build &> /dev/null`,
	pluginPath: '/plugins/woocommerce',
	testsPath: '/plugins/woocommerce/tests/metrics/specs',
	testCommand: `npm install -g pnpm${ pnpm_version } && cd plugins/woocommerce && pnpm test:metrics`,
};

module.exports = config;
