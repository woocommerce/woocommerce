import { fileURLToPath } from 'node:url';
import { createTestConfig } from '../../packages/js/internal-js-tests/vitest.config.mjs';

export default createTestConfig(
	fileURLToPath( new URL( '.', import.meta.url ) ),
	{
		test: {
			environment: 'node',
			setupFiles: [],
			include: [ 'test/**/*.test.mjs' ],
		},
	}
);
