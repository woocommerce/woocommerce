/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { formatPrice } from '@woocommerce/base-utils';

/**
 * Format a min/max price range to display.
 * @param {number} minPrice The min price, if set.
 * @param {number} maxPrice The max price, if set.
 */
export const formatPriceRange = ( minPrice, maxPrice ) => {
	if ( Number.isFinite( minPrice ) && Number.isFinite( maxPrice ) ) {
		return sprintf(
			/* translators: %s min price, %s max price */
			__( 'Between %s and %s', 'woocommerce' ),
			formatPrice( minPrice ),
			formatPrice( maxPrice )
		);
	}

	if ( Number.isFinite( minPrice ) ) {
		return sprintf(
			/* translators: %s min price */
			__( 'From %s', 'woocommerce' ),
			formatPrice( minPrice )
		);
	}

	return sprintf(
		/* translators: %s max price */
		__( 'Up to %s', 'woocommerce' ),
		formatPrice( maxPrice )
	);
};

/**
 * Render a removable item in the active filters block list.
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
				{ __( 'Remove', 'woocommerce' ) }
			</button>
		</li>
	);
};
