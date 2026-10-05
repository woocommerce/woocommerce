/**
 * External dependencies
 */
import { afterEach, beforeEach } from 'vitest';
import { cleanup } from '@testing-library/react';
import '@testing-library/jest-dom/vitest';

/**
 * Internal dependencies
 */
import './setup-console';

beforeEach( () => {
	globalThis.IS_REACT_ACT_ENVIRONMENT = true;
} );
afterEach( cleanup );
