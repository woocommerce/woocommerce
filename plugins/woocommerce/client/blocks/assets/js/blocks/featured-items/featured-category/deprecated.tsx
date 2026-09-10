/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { migrateToCover } from './migrate';

export default [
	{
		attributes: {
			...metadata.attributes,
			editMode: { type: 'boolean' },
			showDesc: { type: 'boolean', default: true },
			height: { type: 'number' },
			style: { type: 'object' },
			textColor: { type: 'string' },
			fontSize: { type: 'string' },
			lineHeight: { type: 'string' },
		},
		supports: metadata.supports,
		save: () => <InnerBlocks.Content />,
		isEligible: ( attributes: Record< string, unknown > ) =>
			attributes.layout !== 'cover',
		migrate: migrateToCover,
	},
];
