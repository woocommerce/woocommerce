/**
 * External dependencies
 */
import { dirname } from 'path';

// Escape from ./tools/package-release/src
export const MONOREPO_ROOT = dirname( dirname( dirname( __dirname ) ) );

// Packages that are not meant to be released by monorepo team for whatever reason.
export const excludedPackages = [
	'@woocommerce/admin-e2e-tests',
	'@woocommerce/api',
	'@woocommerce/api-core-tests',
	'@woocommerce/e2e-core-tests',
	'@woocommerce/e2e-environment',
	'@woocommerce/e2e-utils',
];
