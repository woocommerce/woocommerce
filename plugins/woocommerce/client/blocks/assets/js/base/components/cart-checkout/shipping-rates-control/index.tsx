/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import { usePrevious } from '@woocommerce/base-hooks';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import { ExperimentalOrderShippingPackages } from '@woocommerce/blocks-checkout';
import {
	getSelectedOrFirstRateId,
	getShippingRatesPackageCount,
	getShippingRatesRateCount,
} from '@woocommerce/base-utils';
import {
	useStoreCart,
	useEditorContext,
	useShippingData,
} from '@woocommerce/base-context';
import NoticeBanner from '@woocommerce/base-components/notice-banner';
import { isObject } from '@woocommerce/types';
import { CheckoutShippingSkeleton } from '@woocommerce/base-components/skeleton/patterns/checkout-shipping';

/**
 * Internal dependencies
 */
import ShippingRatesControlPackage from '../shipping-rates-control-package';
import { speakFoundShippingOptions } from './utils';
import type { PackagesProps, ShippingRatesControlProps } from './types';

/**
 * Renders multiple packages within the slotfill.
 */
const Packages = ( {
	packages,
	showItems,
	collapsible,
	noResultsMessage,
	renderOption,
	context = '',
}: PackagesProps ): JSX.Element | null => {
	const { selectShippingRate } = useShippingData();
	// In the editor the cart store never changes (the thunk returns early), so the rate
	// lists have to keep owning their selection or clicking a rate would do nothing.
	const { isEditor } = useEditorContext();
	// Attempt initialization once; failed rates remain unchecked so shoppers can retry them.
	const initializedPackageIds = useRef< Set< string | number > >( new Set() );

	// Initial selection is coordinated here because the rate lists render the store's
	// selected rate rather than their own state, so they no longer choose one on mount.
	// Running it from the parent also reaches packages whose list is not mounted, such
	// as a consumer that renders them in collapsed panels.
	useEffect( () => {
		// The rate lists own their selection in the editor, so leave it to them.
		if ( isEditor ) {
			return;
		}

		const currentPackageIds = new Set(
			packages.map( ( shippingPackage ) => shippingPackage.package_id )
		);

		initializedPackageIds.current.forEach( ( packageId ) => {
			if ( ! currentPackageIds.has( packageId ) ) {
				initializedPackageIds.current.delete( packageId );
			}
		} );

		packages.forEach( ( shippingPackage ) => {
			const rateId = getSelectedOrFirstRateId(
				shippingPackage.shipping_rates
			);

			if ( ! rateId ) {
				initializedPackageIds.current.delete(
					shippingPackage.package_id
				);
				return;
			}

			if (
				initializedPackageIds.current.has( shippingPackage.package_id )
			) {
				return;
			}

			initializedPackageIds.current.add( shippingPackage.package_id );
			selectShippingRate( rateId, shippingPackage.package_id );
		} );
	}, [ packages, selectShippingRate, isEditor ] );

	// If there are no packages, return nothing.
	if ( ! packages.length ) {
		return null;
	}
	return (
		<>
			{ packages.map( ( { package_id: packageId, ...packageData } ) => (
				<ShippingRatesControlPackage
					highlightChecked={ context !== 'woocommerce/cart' }
					key={ packageId }
					packageId={ packageId }
					packageData={ packageData }
					collapsible={ collapsible }
					showItems={ showItems }
					noResultsMessage={ noResultsMessage }
					renderOption={ renderOption }
					manageSelectionLocally={ isEditor }
				/>
			) ) }
		</>
	);
};

/**
 * Renders the shipping rates control element.
 */
const ShippingRatesControl = ( {
	shippingRates,
	isLoadingRates,
	className,
	collapsible,
	showItems,
	noResultsMessage = <></>,
	renderOption,
	context,
}: ShippingRatesControlProps ): JSX.Element => {
	const shippingRatesRateCount = getShippingRatesRateCount( shippingRates );
	const shippingRatesPackageCount =
		getShippingRatesPackageCount( shippingRates );
	const previousShippingRatesRateCount = usePrevious(
		shippingRatesRateCount
	);
	const previousShippingRatesPackageCount = usePrevious(
		shippingRatesPackageCount
	);

	useEffect( () => {
		if ( isLoadingRates ) {
			return;
		}

		if (
			previousShippingRatesRateCount === shippingRatesRateCount &&
			previousShippingRatesPackageCount === shippingRatesPackageCount
		) {
			return;
		}

		speakFoundShippingOptions(
			shippingRatesPackageCount,
			shippingRatesRateCount
		);
	}, [
		isLoadingRates,
		shippingRatesRateCount,
		shippingRatesPackageCount,
		previousShippingRatesRateCount,
		previousShippingRatesPackageCount,
	] );

	// Prepare props to pass to the ExperimentalOrderShippingPackages slot fill.
	// We need to pluck out receiveCart.
	// eslint-disable-next-line no-unused-vars
	const { extensions, receiveCart, ...cart } = useStoreCart();
	const slotFillProps = {
		className,
		collapsible,
		showItems,
		noResultsMessage,
		renderOption,
		extensions,
		cart,
		components: {
			ShippingRatesControlPackage,
		},
		context,
	};
	const { isEditor } = useEditorContext();
	const { hasSelectedLocalPickup, selectedRates } = useShippingData();

	// Check if all rates selected are the same.
	const selectedRateIds = isObject( selectedRates )
		? ( Object.values( selectedRates ) as string[] )
		: [];
	const allPackagesHaveSameRate = selectedRateIds.every( ( rate: string ) => {
		return rate === selectedRateIds[ 0 ];
	} );

	if ( isLoadingRates ) {
		return <CheckoutShippingSkeleton />;
	}

	return (
		<LoadingMask
			isLoading={ isLoadingRates }
			screenReaderLabel={ __( 'Loading shipping rates…', 'woocommerce' ) }
			showSpinner={ true }
		>
			{ hasSelectedLocalPickup &&
				context === 'woocommerce/cart' &&
				shippingRates.length > 1 &&
				! allPackagesHaveSameRate &&
				! isEditor && (
					<NoticeBanner
						className="wc-block-components-notice"
						isDismissible={ false }
						status="warning"
					>
						{ __(
							'Multiple shipments must have the same pickup location',
							'woocommerce'
						) }
					</NoticeBanner>
				) }
			<ExperimentalOrderShippingPackages.Slot { ...slotFillProps } />
			<ExperimentalOrderShippingPackages>
				<Packages
					packages={ shippingRates }
					noResultsMessage={ noResultsMessage }
					renderOption={ renderOption }
				/>
			</ExperimentalOrderShippingPackages>
		</LoadingMask>
	);
};

export default ShippingRatesControl;
