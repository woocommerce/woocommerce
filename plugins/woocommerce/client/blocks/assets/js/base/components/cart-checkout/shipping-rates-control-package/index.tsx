/**
 * External dependencies
 */
import clsx from 'clsx';
import { decodeEntities } from '@wordpress/html-entities';
import { Panel } from '@woocommerce/blocks-components';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { useShippingData } from '@woocommerce/base-context/hooks';
import { sanitizeHTML } from '@woocommerce/utils';
import type { ReactElement } from 'react';
import { useSelect } from '@wordpress/data';
import { cartStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import PackageRates from './package-rates';
import PackageItems from './package-items';
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
}: PackageProps ): ReactElement => {
	const { selectShippingRate, isSelectingRate } = useShippingData();

	const internalPackageCount = useSelect(
		( select ) => select( cartStore )?.getCartData()?.shippingRates?.length
	);

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

	const { selectedOptionNumber, selectedOption } = useMemo( () => {
		return {
			selectedOptionNumber: packageData?.shipping_rates?.findIndex(
				( rate ) => rate?.selected
			),
			selectedOption: packageData?.shipping_rates?.find(
				( rate ) => rate?.selected
			),
		};
	}, [ packageData?.shipping_rates ] );

	// Collapsible and non-collapsible header handling.
	const header =
		shouldBeCollapsible || shouldShowItems ? (
			<div className="wc-block-components-shipping-rates-control__package-header">
				<div
					className="wc-block-components-shipping-rates-control__package-title"
					dangerouslySetInnerHTML={ {
						__html: sanitizeHTML( packageData.name ),
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
		) : null;

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
		disabled: isSelectingRate,
		highlightChecked,
	};

	if ( shouldBeCollapsible ) {
		return (
			<Panel
				className={ clsx(
					'wc-block-components-shipping-rates-control__package',
					className,
					{
						'wc-block-components-shipping-rates-control__package--disabled':
							isSelectingRate,
					}
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
				className,
				{
					'wc-block-components-shipping-rates-control__package--disabled':
						isSelectingRate,
					'wc-block-components-shipping-rates-control__package--first-selected':
						! isSelectingRate && selectedOptionNumber === 0,
					'wc-block-components-shipping-rates-control__package--last-selected':
						! isSelectingRate &&
						selectedOptionNumber ===
							packageData?.shipping_rates?.length - 1,
				}
			) }
		>
			{ header }
			<PackageRates { ...packageRatesProps } />
		</div>
	);
};

export default ShippingRatesControlPackage;
