import { fileURLToPath } from 'node:url';
import { createTestConfig } from './packages/js/internal-js-tests/vitest.config.mjs';

const config = createTestConfig(
	fileURLToPath( new URL( '.', import.meta.url ) ),
	{
		test: {
			environment: 'node',
			setupFiles: [],
			include: [
				'.github/workflows/scripts/**/*.test.js',
				'plugins/woocommerce/tests/e2e/utils/blocks/*.test.mjs',
			],
		},
	}
);

config.test.exclude = [ '**/node_modules/**' ];

export default config;
