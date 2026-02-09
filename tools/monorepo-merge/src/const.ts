/**
 * External dependencies
 */
import { dirname } from 'path';

// Escape from ./tools/monorepo-merge/src
export const MONOREPO_ROOT = dirname( dirname( dirname( __dirname ) ) );
