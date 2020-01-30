/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { formatPrice } from '@woocommerce/base-utils';

/**
 * Format a min/max price range to display.
 *
 * @param {number} minPrice The min price, if set.
 * @param {number} maxPrice The max price, if set.
 */
export const formatPriceRange = ( minPrice, maxPrice ) => {
	if ( Number.isFinite( minPrice ) && Number.isFinite( maxPrice ) ) {
		return sprintf(
			/* translators: %s min price, %s max price */
			__( 'Between %s and %s', 'woo-gutenberg-products-block' ),
			formatPrice( minPrice ),
			formatPrice( maxPrice )
		);
	}

	if ( Number.isFinite( minPrice ) ) {
		return sprintf(
			/* translators: %s min price */
			__( 'From %s', 'woo-gutenberg-products-block' ),
			formatPrice( minPrice )
		);
	}

	return sprintf(
		/* translators: %s max price */
		__( 'Up to %s', 'woo-gutenberg-products-block' ),
		formatPrice( maxPrice )
	);
};

/**
 * Render a removable item in the active filters block list.
 *
 * @param {string} type Type string.
 * @param {string} name Name string.
 * @param {Function} removeCallback Callback to remove item.
 */
export const renderRemovableListItem = (
	type,
	name,
	removeCallback = () => {}
) => {
	return (
		<li
			className="wc-block-active-filters-list-item"
			key={ type + ':' + name }
		>
			<span className="wc-block-active-filters-list-item__type">
				{ type + ': ' }
			</span>
			<strong className="wc-block-active-filters-list-item__name">
				{ name }
			</strong>
			<button onClick={ removeCallback }>
				{ sprintf(
					/* translators: %s attribute value used in the filter. For example: yellow, green, small, large. */
					__( 'Remove %s filter', 'woo-gutenberg-products-block' ),
					name
				) }
			</button>
		</li>
	);
};
