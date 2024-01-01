/**
 * External dependencies
 */
import { Template } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { Attributes } from '../types';

/**
 * For discoverability, register a variant of the Price Filter which is a template
 * that wraps it in a Collection Filter block.
 */
const EditWrapper = ( props: Attributes ) => {
	const template: Template[] = [
		[
			'woocommerce/collection-filters',
			{},
			[ [ 'woocommerce/collection-rating-filter' ] ],
		],
	];

	return (
		<div { ...props }>
			<InnerBlocks template={ template } />
		</div>
	);
};

export default EditWrapper;
