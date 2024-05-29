/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { isObject, objectHasProp } from '@woocommerce/types';
import { isPackageRateCollectable } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import ShippingCalculator from '../shipping-calculator';

export interface PickupLocationProps {
	isShippingCalculatorOpen: boolean;
	setIsShippingCalculatorOpen: React.Dispatch<
		React.SetStateAction< boolean >
	>;
}
/**
 * Shows a formatted pickup location.
 */
const PickupLocation = ( {
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
}: PickupLocationProps ): JSX.Element | null => {
	const { pickupAddress } = useSelect( ( select ) => {
		const cartShippingRates = select( 'wc/store/cart' ).getShippingRates();

		const flattenedRates = cartShippingRates.flatMap(
			( cartShippingRate ) => cartShippingRate.shipping_rates
		);
		const selectedCollectableRate = flattenedRates.find(
			( rate ) => rate.selected && isPackageRateCollectable( rate )
		);

		// If the rate has an address specified in its metadata.
		if (
			isObject( selectedCollectableRate ) &&
			objectHasProp( selectedCollectableRate, 'meta_data' )
		) {
			const selectedRateMetaData = selectedCollectableRate.meta_data.find(
				( meta ) => meta.key === 'pickup_address'
			);
			if (
				isObject( selectedRateMetaData ) &&
				objectHasProp( selectedRateMetaData, 'value' ) &&
				selectedRateMetaData.value
			) {
				const selectedRatePickupAddress = selectedRateMetaData.value;
				return {
					pickupAddress: selectedRatePickupAddress,
				};
			}
		}

		if ( isObject( selectedCollectableRate ) ) {
			return {
				pickupAddress: undefined,
			};
		}
		return {
			pickupAddress: undefined,
		};
	} );

	// If the method does not contain an address, or the method supporting collection was not found, return early.
	if ( typeof pickupAddress === 'undefined' ) {
		return null;
	}
	const pickupLabel = (
		<>
			{ __( 'Collection from ', 'woocommerce' ) }
			<strong>{ pickupAddress }</strong>
		</>
	);

	// Show the pickup method's name if we don't have an address to show.
	return (
		<ShippingCalculator
			isShippingCalculatorOpen={ isShippingCalculatorOpen }
			setIsShippingCalculatorOpen={ setIsShippingCalculatorOpen }
			label={ pickupLabel }
			onUpdate={ () => {
				setIsShippingCalculatorOpen( false );
			} }
			onCancel={ () => {
				setIsShippingCalculatorOpen( false );
			} }
		/>
	);
};

export default PickupLocation;
