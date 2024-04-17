const config = {
	gitRepositoryURL: 'https://github.com/woocommerce/woocommerce.git',
	setupTestRunner:
		'pnpm install --filter="@woocommerce/plugin-woocommerce" &> /dev/null && cd plugins/woocommerce && pnpm exec playwright install chromium',
	setupCommand: 'pnpm install &> /dev/null && pnpm build &> /dev/null',
	pluginPath: '/plugins/woocommerce',
	testsPath: '/plugins/woocommerce/tests/metrics/specs',
	testCommand: 'cd plugins/woocommerce && pnpm test:metrics',
};

module.exports = config;
