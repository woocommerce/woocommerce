/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import Block from './block';
import withProductSelector from '../shared/with-product-selector';
import { BLOCK_TITLE, BLOCK_ICON } from './constants';

const Edit = ( { attributes } ) => {
	const blockProps = useBlockProps( {
		className: 'wp-block-woocommerce-product-rating',
	} );

	return (
		<div { ...blockProps }>
			<Block { ...attributes } />
		</div>
	);
};
export default withProductSelector( {
	icon: BLOCK_ICON,
	label: BLOCK_TITLE,
	description: __(
		'Choose a product to display its rating.',
		'woo-gutenberg-products-block'
	),
} )( Edit );
