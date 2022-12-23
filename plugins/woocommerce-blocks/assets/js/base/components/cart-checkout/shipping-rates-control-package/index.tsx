/**
 * External dependencies
 */
import classNames from 'classnames';
import { _n, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import type { ReactElement } from 'react';
import type {
	PackageRateOption,
	CartShippingPackageShippingRate,
} from '@woocommerce/types';
import { Panel } from '@woocommerce/blocks-checkout';
import Label from '@woocommerce/base-components/label';
import { useSelectShippingRate } from '@woocommerce/base-context/hooks';
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

// A flag can be ternary if true, false, and undefined are all valid options.
// In our case, we use this for collapsible and showItems, having a boolean will force that
// option, having undefined will let the component decide the logic based on other factors.
export type TernaryFlag = boolean | undefined;
interface PackageProps {
	/* PackageId can be a string, WooCommerce Subscriptions uses strings for example, but WooCommerce core uses numbers */
	packageId: string | number;
	renderOption?: PackageRateRenderOption | undefined;
	collapse?: boolean;
	packageData: PackageData;
	className?: string;
	collapsible?: TernaryFlag;
	noResultsMessage: ReactElement;
	showItems?: TernaryFlag;
}

export const ShippingRatesControlPackage = ( {
	packageId,
	className = '',
	noResultsMessage,
	renderOption,
	packageData,
	collapsible,
	showItems,
}: PackageProps ): ReactElement => {
	const { selectShippingRate } = useSelectShippingRate();
	const multiplePackages =
		document.querySelectorAll(
			'.wc-block-components-shipping-rates-control__package'
		).length > 1;

	// If showItems is not set, we check if we have multiple packages.
	// We sometimes don't want to show items even if we have multiple packages.
	const shouldShowItems = showItems ?? multiplePackages;

	// If collapsible is not set, we check if we have multiple packages.
	// We sometimes don't want to collapse even if we have multiple packages.
	const shouldBeCollapsible = collapsible ?? multiplePackages;

	const header = (
		<>
			{ ( shouldBeCollapsible || shouldShowItems ) && (
				<div
					className="wc-block-components-shipping-rates-control__package-title"
					dangerouslySetInnerHTML={ {
						__html: sanitizeHTML( packageData.name ),
					} }
				/>
			) }
			{ shouldShowItems && (
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
	if ( shouldBeCollapsible ) {
		return (
			<Panel
				className="wc-block-components-shipping-rates-control__package"
				// initialOpen remembers only the first value provided to it, so by the
				// time we know we have several packages, initialOpen would be hardcoded to true.
				// If we're rendering a panel, we're more likely rendering several
				// packages and we want to them to be closed initially.
				initialOpen={ false }
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
