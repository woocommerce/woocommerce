/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { generateManifestCommand } from './manifest/create';

/**
 * Internal dependencies
 */

const program = new Command( 'md-docs' )
	.description( 'Utilities for generating markdown doc manifests.' )
	.addCommand( generateManifestCommand, { isDefault: true } );

export default program;
