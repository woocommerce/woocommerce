import { fileURLToPath } from 'node:url';
import { createTestConfig } from '../../js/internal-js-tests/vitest.config.mjs';

export default createTestConfig(
	fileURLToPath( new URL( '.', import.meta.url ) ),
	{ test: { environment: 'node', setupFiles: [] } }
);
