/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { getBlockClassName } from '../utils.js';

const { attributes: attributeDefinitions } = metadata;

const v1 = {
	attributes: Object.assign( {}, attributeDefinitions, {
		rows: { type: 'number', default: 1 },
	} ),
	save( { attributes } ) {
		const data = {
			'data-attributes': JSON.stringify( attributes ),
		};
		return (
			<div
				className={ getBlockClassName(
					'wc-block-all-products',
					attributes
				) }
				{ ...data }
			>
				<InnerBlocks.Content />
			</div>
		);
	},
};

const v2 = {
	attributes: Object.assign( {}, attributeDefinitions, {
		rows: { type: 'number', default: 1 },
	} ),
	save( { attributes } ) {
		const dataAttributes = {};
		Object.keys( attributes )
			.sort()
			.forEach( ( key ) => {
				dataAttributes[ key ] = attributes[ key ];
			} );
		const data = {
			'data-attributes': JSON.stringify( dataAttributes ),
		};
		return (
			<div
				className={ getBlockClassName(
					'wc-block-all-products',
					attributes
				) }
				{ ...data }
			>
				<InnerBlocks.Content />
			</div>
		);
	},
};

export default [ v2, v1 ];
