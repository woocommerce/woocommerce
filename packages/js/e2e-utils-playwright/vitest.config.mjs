import { fileURLToPath } from 'node:url';
import { createTestConfig } from '../internal-js-tests/vitest.config.mjs';

export default createTestConfig(
	fileURLToPath( new URL( '.', import.meta.url ) ),
	{
		test: {
			include: [
				'src/**/__tests__/**/*.{js,jsx,ts,tsx}',
				'src/**/test/*.{js,jsx,ts,tsx}',
				'src/**/*.{test,spec}.{js,jsx,ts,tsx}',
			],
		},
	}
);
