/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { getBlockClassName } from '../utils.js';

export default function save( { attributes } ) {
	const dataAttributes = {};
	Object.keys( attributes )
		.sort()
		.forEach( ( key ) => {
			dataAttributes[ key ] = attributes[ key ];
		} );
	const blockProps = useBlockProps.save( {
		className: getBlockClassName( 'wc-block-all-products', attributes ),
		'data-attributes': JSON.stringify( dataAttributes ),
	} );
	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
		</div>
	);
}
