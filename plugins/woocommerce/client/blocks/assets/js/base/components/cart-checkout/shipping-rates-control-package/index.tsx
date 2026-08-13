/**
 * External dependencies
 */
import clsx from 'clsx';
import { decodeEntities } from '@wordpress/html-entities';
import { Panel } from '@woocommerce/blocks-components';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { useShippingData, useStoreCart } from '@woocommerce/base-context/hooks';
import { sanitizeHTML } from '@woocommerce/sanitize';
import { CartShippingPackageShippingRate } from '@woocommerce/types';
import {
	PackageItems,
	ShippingPackageItemIcon,
} from '@woocommerce/base-components/cart-checkout';

/**
 * Internal dependencies
 */
import PackageRates from './package-rates';
import type { PackageProps } from './types';
import './style.scss';

export const ShippingRatesControlPackage = ( {
	packageId,
	className = '',
	noResultsMessage,
	renderOption,
	packageData,
	collapsible,
	showItems,
	highlightChecked = false,
}: PackageProps ) => {
	const { selectShippingRate, shippingRates } = useShippingData();
	const { cartItems } = useStoreCart();

	const internalPackageCount = shippingRates?.length || 1;

	const packageClass = 'wc-block-components-shipping-rates-control__package';
	const [ instanceCount, setInstanceCount ] = useState( 0 );

	// We have no built-in way of checking if other extensions have added packages e.g. if subscriptions has added them.
	const multiplePackages = internalPackageCount > 1 || instanceCount > 1;

	useEffect( () => {
		const updateCount = () => {
			setInstanceCount(
				document.querySelectorAll( `.${ packageClass }` ).length
			);
		};
		updateCount();

		const observer = new MutationObserver( updateCount );
		observer.observe( document.body, { childList: true, subtree: true } );

		return () => {
			observer.disconnect();
		};
	}, [] );

	// If showItems is not set, we check if we have multiple packages.
	// We sometimes don't want to show items even if we have multiple packages.
	const shouldShowItems = showItems ?? multiplePackages;

	// If collapsible is not set, we check if we have multiple packages.
	// We sometimes don't want to collapse even if we have multiple packages.
	const shouldBeCollapsible = collapsible ?? multiplePackages;

	const selectedOption: CartShippingPackageShippingRate | undefined = useMemo(
		() => packageData?.shipping_rates?.find( ( rate ) => rate?.selected ),
		[ packageData?.shipping_rates ]
	);

	// Collapsible and non-collapsible header handling.
	let header = null;

	if ( shouldBeCollapsible || shouldShowItems ) {
		header = (
			<div className="wc-block-components-shipping-rates-control__package-header">
				<div
					className="wc-block-components-shipping-rates-control__package-title"
					dangerouslySetInnerHTML={ {
						__html: sanitizeHTML(
							String( packageData.name ?? '' )
						),
					} }
				/>
				{ shouldBeCollapsible && (
					<div className="wc-block-components-totals-shipping__via">
						{ decodeEntities( selectedOption?.name ) }
					</div>
				) }
				{ shouldShowItems && (
					<PackageItems packageData={ packageData } />
				) }
			</div>
		);

		if ( multiplePackages ) {
			const packageItems = packageData.items || [];

			header = (
				<div className="wc-block-components-shipping-rates-control__package-container">
					{ header }
					<div className="wc-block-components-shipping-rates-control__package-thumbnails">
						{ packageItems.slice( 0, 3 ).map( ( item ) => (
							<ShippingPackageItemIcon
								key={ item.key }
								packageItem={ item }
								cartItems={ cartItems }
							/>
						) ) }
					</div>
				</div>
			);
		}
	}

	const onSelectRate = useCallback(
		( newShippingRateId: string ) => {
			selectShippingRate( newShippingRateId, packageId );
		},
		[ packageId, selectShippingRate ]
	);

	const packageRatesProps = {
		className,
		noResultsMessage,
		rates: packageData.shipping_rates,
		onSelectRate,
		selectedRate: packageData.shipping_rates.find(
			( rate ) => rate.selected
		),
		renderOption,
		highlightChecked,
	};

	if ( shouldBeCollapsible ) {
		return (
			<Panel
				className={ clsx(
					'wc-block-components-shipping-rates-control__package',
					multiplePackages &&
						'wc-block-components-shipping-rates-control__package--multiple',
					className
				) }
				// initialOpen remembers only the first value provided to it, so by the
				// time we know we have several packages, initialOpen would be hardcoded to true.
				// If we're rendering a panel, we're more likely rendering several
				// packages and we want to them to be closed initially.
				initialOpen={ false }
				title={ header }
			>
				<PackageRates { ...packageRatesProps } />
			</Panel>
		);
	}

	return (
		<div
			className={ clsx(
				'wc-block-components-shipping-rates-control__package',
				multiplePackages &&
					'wc-block-components-shipping-rates-control__package--multiple',
				className
			) }
		>
			{ header }
			<PackageRates { ...packageRatesProps } />
		</div>
	);
};

export default ShippingRatesControlPackage;
