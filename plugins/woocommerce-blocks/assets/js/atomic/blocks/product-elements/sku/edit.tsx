/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import EditProductLink from '@woocommerce/editor-components/edit-product-link';

/**
 * Internal dependencies
 */
import Block from './block';
import withProductSelector from '../shared/with-product-selector';
import { BLOCK_TITLE, BLOCK_ICON } from './constants';
import type { BlockAttributes } from './types';

interface Props {
	attributes: BlockAttributes;
}

const Edit = ( { attributes }: Props ): JSX.Element => {
	return (
		<>
			<EditProductLink />
			<Block { ...attributes } />
		</>
	);
};

export default withProductSelector( {
	icon: BLOCK_ICON,
	label: BLOCK_TITLE,
	description: __(
		'Choose a product to display its SKU.',
		'woo-gutenberg-products-block'
	),
} )( Edit );
