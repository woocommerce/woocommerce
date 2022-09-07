/**
 * External dependencies
 */
import { dirname } from 'path';

// Escape from ./tools/package-release/src
export const MONOREPO_ROOT = dirname( dirname( dirname( __dirname ) ) );
