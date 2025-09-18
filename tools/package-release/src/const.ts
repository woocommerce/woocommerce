/**
 * External dependencies
 */
import { dirname, join } from 'path';

// Escape from ./tools/package-release/src
export const MONOREPO_ROOT = dirname( dirname( dirname( __dirname ) ) );

export const PLUGINS_ROOT = join( MONOREPO_ROOT, 'plugins' );

export const WOOCOMMERCE_PLUGIN_ROOT = join( PLUGINS_ROOT, 'woocommerce' );

// Packages that are not meant to be released by monorepo team for whatever reason.
export const excludedPackages: string[] = [];
