/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useShippingData } from '@woocommerce/base-context/hooks';
import { ShippingRatesControl } from '@woocommerce/base-components/cart-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import { decodeEntities } from '@wordpress/html-entities';
import { Notice } from 'wordpress-components';
import classnames from 'classnames';
import { getSetting } from '@woocommerce/settings';
import type { PackageRateOption } from '@woocommerce/type-defs/shipping';
import type { CartShippingPackageShippingRate } from '@woocommerce/type-defs/cart';
import { Icon, mapMarker } from '@wordpress/icons';
import { MetaKeyValue } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import './style.scss';

const getPickupLocation = (
	option: CartShippingPackageShippingRate
): string => {
	if ( option?.meta_data ) {
		const match = option.meta_data.find(
			( meta: MetaKeyValue ) => meta.key === 'pickup_location'
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
			( meta: MetaKeyValue ) => meta.key === 'pickup_address'
		);
		return match ? match.value : '';
	}
	return '';
};

/**
 * Renders a shipping rate control option.
 *
 * @param {Object} option Shipping Rate.
 */
const renderShippingRatesControlOption = (
	option: CartShippingPackageShippingRate
): PackageRateOption => {
	const priceWithTaxes = getSetting( 'displayCartPricesIncludingTax', false )
		? parseInt( option.price, 10 ) + parseInt( option.taxes, 10 )
		: parseInt( option.price, 10 );
	const location = getPickupLocation( option );
	const address = getPickupAddress( option );
	return {
		value: option.rate_id,
		label: location
			? decodeEntities( location )
			: decodeEntities( option.name ),
		secondaryLabel: (
			<FormattedMonetaryAmount
				currency={ getCurrencyFromPriceResponse( option ) }
				value={ priceWithTaxes }
			/>
		),
		description: decodeEntities( option.description ),
		secondaryDescription: address ? (
			<>
				<Icon
					icon={ mapMarker }
					className="wc-block-editor-components-block-icon"
				/>
				{ decodeEntities( address ) }
			</>
		) : undefined,
	};
};

const Block = (): JSX.Element | null => {
	const { shippingRates, needsShipping, isLoadingRates } = useShippingData();

	if ( ! needsShipping ) {
		return null;
	}

	const filteredShippingRates = shippingRates.map(
		( shippingRatesPackage ) => {
			return {
				...shippingRatesPackage,
				shipping_rates: shippingRatesPackage.shipping_rates.filter(
					( shippingRatesPackageRate ) =>
						shippingRatesPackageRate.method_id === 'pickup_location'
				),
			};
		}
	);

	return (
		<ShippingRatesControl
			noResultsMessage={
				<Notice
					isDismissible={ false }
					className={ classnames(
						'wc-block-components-shipping-rates-control__no-results-notice',
						'woocommerce-error'
					) }
				>
					{ __(
						'There are no pickup options available.',
						'woo-gutenberg-products-block'
					) }
				</Notice>
			}
			renderOption={ renderShippingRatesControlOption }
			shippingRates={ filteredShippingRates }
			isLoadingRates={ isLoadingRates }
			context="woocommerce/checkout"
		/>
	);
};

export default Block;
