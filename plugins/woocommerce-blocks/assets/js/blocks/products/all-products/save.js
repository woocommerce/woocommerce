/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { getBlockClassName } from '../utils.js';

export default function save( { attributes } ) {
	return (
		<div
			className={ getBlockClassName(
				'wc-block-all-products',
				attributes
			) }
		>
			<InnerBlocks.Content />
		</div>
	);
}
