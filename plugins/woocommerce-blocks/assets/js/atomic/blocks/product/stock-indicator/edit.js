/**
 * External dependencies
 */
import EditProductLink from '@woocommerce/block-components/edit-product-link';

/**
 * Internal dependencies
 */
import Block from './block';

export default ( { attributes } ) => {
	return (
		<>
			<EditProductLink />
			<Block { ...attributes } />
		</>
	);
};
