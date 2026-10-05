import { fileURLToPath } from 'node:url';
import { createTestConfig } from '../../../../packages/js/internal-js-tests/vitest.config.mjs';

export default createTestConfig(
	fileURLToPath( new URL( '.', import.meta.url ) ),
	{
		test: {
			include: [ 'js/**/test/*.{js,jsx,ts,tsx}' ],
			setupFiles: [
				new URL(
					'../../../../packages/js/internal-js-tests/src/setup-console.js',
					import.meta.url
				).pathname,
			],
		},
	}
);
