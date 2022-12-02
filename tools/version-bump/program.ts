/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

export const program = new Command();

program
	.name( 'version-bump' )
	.description( 'CLI to automate version bumping for WooCommerce plugins.' );
