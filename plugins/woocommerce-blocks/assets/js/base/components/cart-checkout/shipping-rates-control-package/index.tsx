/**
 * External dependencies
 */
import classNames from 'classnames';
import { _n, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import type { ReactElement } from 'react';
import type { PackageRateOption } from '@woocommerce/type-defs/shipping';
import { Panel } from '@woocommerce/blocks-checkout';
import Label from '@woocommerce/base-components/label';
import { useSelectShippingRate } from '@woocommerce/base-context/hooks';
import type { CartShippingPackageShippingRate } from '@woocommerce/type-defs/cart';
import { sanitizeHTML } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import PackageRates from './package-rates';
import './style.scss';

interface PackageItem {
	name: string;
	key: string;
	quantity: number;
}

interface Destination {
	address_1: string;
	address_2: string;
	city: string;
	state: string;
	postcode: string;
	country: string;
}

export interface PackageData {
	destination: Destination;
	name: string;
	shipping_rates: CartShippingPackageShippingRate[];
	items: PackageItem[];
}

export type PackageRateRenderOption = (
	option: CartShippingPackageShippingRate
) => PackageRateOption;

interface PackageProps {
	/* PackageId can be a string, WooCommerce Subscriptions uses strings for example, but WooCommerce core uses numbers */
	packageId: string | number;
	renderOption: PackageRateRenderOption;
	collapse?: boolean;
	packageData: PackageData;
	className?: string;
	collapsible?: boolean;
	noResultsMessage: ReactElement;
	showItems?: boolean;
}

export const ShippingRatesControlPackage = ( {
	packageId,
	className = '',
	noResultsMessage,
	renderOption,
	packageData,
	collapsible = false,
	collapse = false,
	showItems = false,
}: PackageProps ): ReactElement => {
	const { selectShippingRate } = useSelectShippingRate();

	const header = (
		<>
			{ ( showItems || collapsible ) && (
				<div
					className="wc-block-components-shipping-rates-control__package-title"
					dangerouslySetInnerHTML={ {
						__html: sanitizeHTML( packageData.name ),
					} }
				/>
			) }
			{ showItems && (
				<ul className="wc-block-components-shipping-rates-control__package-items">
					{ Object.values( packageData.items ).map( ( v ) => {
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
									screenReaderLabel={ sprintf(
										/* translators: %1$s name of the product (ie: Sunglasses), %2$d number of units in the current cart package */
										_n(
											'%1$s (%2$d unit)',
											'%1$s (%2$d units)',
											quantity,
											'woo-gutenberg-products-block'
										),
										name,
										quantity
									) }
								/>
							</li>
						);
					} ) }
				</ul>
			) }
		</>
	);
	const body = (
		<PackageRates
			className={ className }
			noResultsMessage={ noResultsMessage }
			rates={ packageData.shipping_rates }
			onSelectRate={ ( newShippingRateId ) =>
				selectShippingRate( newShippingRateId, packageId )
			}
			selectedRate={ packageData.shipping_rates.find(
				( rate ) => rate.selected
			) }
			renderOption={ renderOption }
		/>
	);
	if ( collapsible ) {
		return (
			<Panel
				className="wc-block-components-shipping-rates-control__package"
				initialOpen={ ! collapse }
				title={ header }
			>
				{ body }
			</Panel>
		);
	}
	return (
		<div
			className={ classNames(
				'wc-block-components-shipping-rates-control__package',
				className
			) }
		>
			{ header }
			{ body }
		</div>
	);
};

export default ShippingRatesControlPackage;
