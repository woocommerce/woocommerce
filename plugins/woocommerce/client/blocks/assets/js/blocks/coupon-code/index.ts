/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import { Save, DeprecatedSave } from './save';
import metadata from './block.json';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
registerBlockType( metadata as any, {
	edit: Edit,
	save: Save,
	deprecated: [
		{
			attributes: {
				couponCode: {
					type: 'string' as const,
					default: '',
				},
			},
			save: DeprecatedSave,
			migrate( attributes: Record< string, unknown > ) {
				return {
					...attributes,
					source: 'existing' as const,
				};
			},
		},
	],
} );
