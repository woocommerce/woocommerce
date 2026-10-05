import { fileURLToPath } from 'node:url';
import { createTestConfig } from '../internal-js-tests/vitest.config.mjs';

export default createTestConfig(
	fileURLToPath( new URL( '.', import.meta.url ) ),
	{
		test: {
			setupFiles: [
				new URL(
					'../internal-js-tests/src/setup-window-globals.js',
					import.meta.url
				).pathname,
				new URL(
					'../internal-js-tests/src/setup-globals.js',
					import.meta.url
				).pathname,
				new URL(
					'../internal-js-tests/src/setup-react-testing-library.js',
					import.meta.url
				).pathname,
				new URL( './vitest.setup.ts', import.meta.url ).pathname,
			],
		},
	}
);
