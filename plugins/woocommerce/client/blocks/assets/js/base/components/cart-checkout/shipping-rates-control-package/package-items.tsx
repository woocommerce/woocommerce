/**
 * External dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { Label } from '@woocommerce/blocks-components';

/**
 * Internal dependencies
 */
import type { PackageData, PackageItem } from './types';

export const PackageItems = ( {
	packageData,
}: {
	packageData: PackageData;
} ): JSX.Element => {
	return (
		<ul className="wc-block-components-shipping-rates-control__package-items">
			{ Object.values( packageData.items ).map( ( v: PackageItem ) => {
				const name = decodeEntities( v.name );
				const quantity = v.quantity;
				return (
					<li
						key={ v.key }
						className="wc-block-components-shipping-rates-control__package-item"
					>
						<Label
							label={
								quantity > 1
									? `${ name } × ${ quantity }`
									: `${ name }`
							}
							allowHTML
							screenReaderLabel={ sprintf(
								/* translators: %1$s name of the product (ie: Sunglasses), %2$d number of units in the current cart package */
								_n(
									'%1$s (%2$d unit)',
									'%1$s (%2$d units)',
									quantity,
									'woocommerce'
								),
								name,
								quantity
							) }
						/>
					</li>
				);
			} ) }
		</ul>
	);
};

export default PackageItems;
