/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { SHOP_URL } from '@woocommerce/block-settings';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import iconDataUri from './icon-data-uri.js';
import './style.scss';

/**
 * Component to handle edit mode for the Cart block when cart is empty.
 */
const EmptyCartEdit = ( { hidden } ) => {
	// We need to always render `<InnerBlocks>` in the editor.
	// Otherwise, if the user saves the page without having
	// triggered the 'Empty Cart' view, inner blocks would not
	// be saved and they wouldn't be visible in the frontend.
	// We wrap them in a hidden `<div>` if the user is in
	// the editor 'Full Cart' view so they are not visible.
	return (
		<div hidden={ hidden }>
			<InnerBlocks
				templateInsertUpdatesSelection={ false }
				template={ [
					[
						'core/image',
						{
							align: 'center',
							url: iconDataUri,
							sizeSlug: 'small',
						},
					],
					[
						'core/heading',
						{
							align: 'center',
							content: __(
								'Your cart is currently empty!',
								'woo-gutenberg-products-block'
							),
							level: 2,
							className: 'wc-block-cart__empty-cart__title',
						},
					],
					[
						'core/paragraph',
						{
							align: 'center',
							content: sprintf(
								__(
									'<a href="%s">Browse store</a>.',
									'woo-gutenberg-products-block'
								),
								SHOP_URL
							),
							dropCap: false,
						},
					],
					[
						'core/separator',
						{
							className: 'is-style-dots',
						},
					],
					[
						'core/heading',
						{
							align: 'center',
							content: __(
								'New in store',
								'woo-gutenberg-products-block'
							),
							level: 2,
						},
					],
					[
						'woocommerce/product-new',
						{
							columns: 3,
							rows: 1,
						},
					],
				] }
			/>
		</div>
	);
};

EmptyCartEdit.propTypes = {
	hidden: PropTypes.bool,
};

export default EmptyCartEdit;
