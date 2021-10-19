/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { formatPrice } from '@woocommerce/price-format';
import { RemovableChip } from '@woocommerce/base-components/chip';
import Label from '@woocommerce/base-components/label';

/**
 * Format a min/max price range to display.
 *
 * @param {number} minPrice The min price, if set.
 * @param {number} maxPrice The max price, if set.
 */
export const formatPriceRange = ( minPrice, maxPrice ) => {
	if ( Number.isFinite( minPrice ) && Number.isFinite( maxPrice ) ) {
		return sprintf(
			/* translators: %1$s min price, %2$s max price */
			__( 'Between %1$s and %2$s', 'woocommerce' ),
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
 *
 * @param {Object}   listItem                  The removable item to render.
 * @param {string}   listItem.type             Type string.
 * @param {string}   listItem.name             Name string.
 * @param {string}   listItem.prefix           Prefix shown before item name.
 * @param {Function} listItem.removeCallback   Callback to remove item.
 * @param {string}   listItem.displayStyle     Whether it's a list or chips.
 * @param {boolean}  [listItem.showLabel=true] Should the label be shown for
 *                                             this item?
 */
export const renderRemovableListItem = ( {
	type,
	name,
	prefix,
	removeCallback = () => {},
	showLabel = true,
	displayStyle,
} ) => {
	const prefixedName = prefix ? (
		<>
			{ prefix }
			&nbsp;
			{ name }
		</>
	) : (
		name
	);
	const removeText = sprintf(
		/* translators: %s attribute value used in the filter. For example: yellow, green, small, large. */
		__( 'Remove %s filter', 'woocommerce' ),
		name
	);

	return (
		<li
			className="wc-block-active-filters__list-item"
			key={ type + ':' + name }
		>
			{ showLabel && (
				<span className="wc-block-active-filters__list-item-type">
					{ type + ': ' }
				</span>
			) }
			{ displayStyle === 'chips' ? (
				<RemovableChip
					element="span"
					text={ prefixedName }
					onRemove={ removeCallback }
					radius="large"
					ariaLabel={ removeText }
				/>
			) : (
				<span className="wc-block-active-filters__list-item-name">
					{ prefixedName }
					<button
						className="wc-block-active-filters__list-item-remove"
						onClick={ removeCallback }
					>
						<svg
							width="16"
							height="16"
							viewBox="0 0 16 16"
							fill="none"
							xmlns="http://www.w3.org/2000/svg"
						>
							<ellipse
								cx="8"
								cy="8"
								rx="8"
								ry="8"
								transform="rotate(-180 8 8)"
								fill="currentColor"
								fillOpacity="0.7"
							/>
							<rect
								x="10.636"
								y="3.94983"
								width="2"
								height="9.9466"
								transform="rotate(45 10.636 3.94983)"
								fill="white"
							/>
							<rect
								x="12.0503"
								y="11.0209"
								width="2"
								height="9.9466"
								transform="rotate(135 12.0503 11.0209)"
								fill="white"
							/>
						</svg>

						<Label screenReaderLabel={ removeText } />
					</button>
				</span>
			) }
		</li>
	);
};
