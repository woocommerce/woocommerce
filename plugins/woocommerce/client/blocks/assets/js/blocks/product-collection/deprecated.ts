/**
 * External dependencies
 */
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { attributes } from './block.json';
import save from './save';

const v1 = [
	{
		save,
		attributes: {
			...attributes,
			displayLayout: {
				type: 'object',
				properties: {
					type: {
						type: 'string',
						enum: [ 'flex', 'list', 'carousel' ],
					},
					columns: {
						type: 'number',
					},
					shrinkColumns: {
						type: 'boolean',
					},
				},
			},
		},
		migrate: ( attributes, innerBlocks ) => {
			// Stack layout
			if ( attributes.displayLayout.type === 'list' ) {
			}
			// Grid layout
			if ( attributes.displayLayout.type === 'flex' ) {
			}
			// Carousel layout
			if ( attributes.displayLayout.type === 'carousel' ) {
			}
		},
	},
];

export default [ v1 ];
