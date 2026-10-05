import { fileURLToPath } from 'node:url';
import { createTestConfig } from '../../../../packages/js/internal-js-tests/vitest.config.mjs';

export default createTestConfig(
	fileURLToPath( new URL( '.', import.meta.url ) ),
	{
		test: {
			include: [
				'client/**/__tests__/**/*.{js,jsx,ts,tsx}',
				'client/**/test/*.{js,jsx,ts,tsx}',
				'client/**/*.{test,spec}.{js,jsx,ts,tsx}',
			],
		},
	}
);
