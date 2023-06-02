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
 *
 * @param {Object} props Incoming props for the component.
 * @param {boolean} props.hidden Whether this component is hidden or not.
 */
const EmptyCartEdit = ( { hidden = false } ) => {
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
								'woocommerce'
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
								// Translators: %s is the link to the store product directory.
								__(
									'<a href="%s">Browse store</a>.',
									'woocommerce'
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
								'woocommerce'
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
