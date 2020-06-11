/**
 * External dependencies
 */
import { Disabled } from '@wordpress/components';
import EditProductLink from '@woocommerce/block-components/edit-product-link';

/**
 * Internal dependencies
 */
import Block from './block';

export default ( { attributes } ) => {
	return (
		<>
			<EditProductLink />
			<Disabled>
				<Block { ...attributes } />
			</Disabled>
		</>
	);
};
