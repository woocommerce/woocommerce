/**
 * External dependencies
 */
import { _n, __ } from '@wordpress/i18n';
import {
	useState,
	useEffect,
	useCallback,
	useMemo,
	createInterpolateElement,
} from '@wordpress/element';
import { useShippingData, useStoreCart } from '@woocommerce/base-context/hooks';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	FormattedMonetaryAmount,
	RadioControlOptionType,
} from '@woocommerce/blocks-components';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { Icon, mapMarker } from '@wordpress/icons';
import { CartShippingPackageShippingRate } from '@woocommerce/types';
import {
	isPackageRateCollectable,
	getShippingRatesPackageCount,
} from '@woocommerce/base-utils';
import { ExperimentalOrderLocalPickupPackages } from '@woocommerce/blocks-checkout';
import { LocalPickupSelect } from '@woocommerce/base-components/cart-checkout/local-pickup-select';
import ReadMore from '@woocommerce/base-components/read-more';

/**
 * Internal dependencies
 */
import ShippingRatesControlPackage from '../../../../base/components/cart-checkout/shipping-rates-control-package';

const getPickupLocation = (
	option: CartShippingPackageShippingRate
): string => {
	if ( option?.meta_data ) {
		const match = option.meta_data.find(
			( meta ) => meta.key === 'pickup_location'
		);
		return match ? match.value : '';
	}
	return '';
};

const getPickupAddress = (
	option: CartShippingPackageShippingRate
): string => {
	if ( option?.meta_data ) {
		const match = option.meta_data.find(
			( meta ) => meta.key === 'pickup_address'
		);
		return match ? match.value : '';
	}
	return '';
};

const getPickupDetails = (
	option: CartShippingPackageShippingRate
): string => {
	if ( option?.meta_data ) {
		const match = option.meta_data.find(
			( meta ) => meta.key === 'pickup_details'
		);
		return match ? match.value : '';
	}
	return '';
};

const renderPickupLocation = (
	option: CartShippingPackageShippingRate,
	packageCount: number
): RadioControlOptionType => {
	const priceWithTaxes = getSetting( 'displayCartPricesIncludingTax', false )
		? parseInt( option.price, 10 ) + parseInt( option.taxes, 10 )
		: parseInt( option.price, 10 );
	const location = getPickupLocation( option );
	const address = getPickupAddress( option );
	const details = getPickupDetails( option );

	const isSelected = option?.selected;

	// Default to showing "free" as the secondary label. Price checks below will update it if needed.
	let secondaryLabel = <em>{ __( 'free', 'woocommerce' ) }</em>;

	// If there is a cost for local pickup, show the cost per package.
	if ( priceWithTaxes > 0 ) {
		// If only one package, show the price and not the package count.
		if ( packageCount === 1 ) {
			secondaryLabel = (
				<FormattedMonetaryAmount
					currency={ getCurrencyFromPriceResponse( option ) }
					value={ priceWithTaxes }
				/>
			);
		} else {
			secondaryLabel = createInterpolateElement(
				/* translators: <price/> is the price of the package, <packageCount/> is the number of packages. These must appear in the translated string. */
				_n(
					'<price/> x <packageCount/> package',
					'<price/> x <packageCount/> packages',
					packageCount,
					'woocommerce'
				),
				{
					price: (
						<FormattedMonetaryAmount
							currency={ getCurrencyFromPriceResponse( option ) }
							value={ priceWithTaxes }
						/>
					),
					packageCount: <>{ packageCount }</>,
				}
			);
		}
	}

	return {
		value: option.rate_id,
		label: location
			? decodeEntities( location )
			: decodeEntities( option.name ),
		secondaryLabel,
		description: address ? (
			<>
				<Icon
					icon={ mapMarker }
					className="wc-block-editor-components-block-icon"
				/>
				{ decodeEntities( address ) }
			</>
		) : undefined,
		secondaryDescription:
			isSelected && details ? (
				<ReadMore maxLines={ 2 }>
					{ decodeEntities( details ) }
				</ReadMore>
			) : undefined,
	};
};

const Block = () => {
	const { shippingRates, selectShippingRate } = useShippingData();

	// Memoize pickup locations to prevent re-rendering when the shipping rates change.
	const pickupLocations: CartShippingPackageShippingRate[] = useMemo( () => {
		return ( shippingRates[ 0 ]?.shipping_rates || [] ).filter(
			isPackageRateCollectable
		);
	}, [ shippingRates ] );

	const [ selectedOption, setSelectedOption ] = useState<
		string | undefined
	>(
		() =>
			pickupLocations.find( ( rate ) => rate.selected )?.rate_id ??
			pickupLocations[ 0 ]?.rate_id
	);

	const handleShippingRateChange = useCallback(
		( rateId: string ) => {
			setSelectedOption( rateId );
			selectShippingRate( rateId );
		},
		[ setSelectedOption, selectShippingRate ]
	);

	// Update on mount, we do it every time to:
	// - sync the initial value with the server
	// - or reset pending request to change shipping rate that might be coming
	//   from other components (e.g. shipping options), selectShippingRate thunk
	//   in the cart store properly handles aborting the previous request if needed
	useEffect( () => {
		if ( selectedOption ) {
			selectShippingRate( selectedOption );
		}
		// We want this to run on mount only, beware of updating it as it may cause
		// shipping rate selection to end up in inifite loop
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Update the selected option if cart state changes in the data store.
	useEffect( () => {
		const selectedRate = pickupLocations.find( ( rate ) => rate.selected );
		const selectedRateId = selectedRate?.rate_id;

		if ( selectedRateId && selectedRateId !== selectedOption ) {
			setSelectedOption( selectedRateId );
		}
		// We want to explicitly react to changes in the data store only here, local state is managed
		// through different code path.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ pickupLocations ] );

	// Prepare props to pass to the ExperimentalOrderLocalPickupPackages slot fill.
	// We need to pluck out receiveCart.
	// eslint-disable-next-line no-unused-vars
	const { extensions, receiveCart, ...cart } = useStoreCart();
	const slotFillProps = {
		extensions,
		cart,
		components: {
			ShippingRatesControlPackage,
			LocalPickupSelect,
		},
		renderPickupLocation,
	};

	return (
		<>
			<ExperimentalOrderLocalPickupPackages.Slot { ...slotFillProps } />
			<ExperimentalOrderLocalPickupPackages>
				<LocalPickupSelect
					title={ shippingRates[ 0 ].name }
					selectedOption={ selectedOption ?? '' }
					renderPickupLocation={ renderPickupLocation }
					pickupLocations={ pickupLocations }
					packageCount={ getShippingRatesPackageCount(
						shippingRates
					) }
					onChange={ ( value ) => handleShippingRateChange( value ) }
				/>
			</ExperimentalOrderLocalPickupPackages>
		</>
	);
};

export default Block;
