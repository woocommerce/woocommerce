/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useCustomerData,
	useShippingData,
} from '@woocommerce/base-context/hooks';
import { ShippingRatesControl } from '@woocommerce/base-components/cart-checkout';
import {
	getShippingRatesPackageCount,
	hasCollectableRate,
	isAddressComplete,
} from '@woocommerce/base-utils';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	FormattedMonetaryAmount,
	StoreNoticesContainer,
} from '@woocommerce/blocks-components';
import { useEditorContext, noticeContexts } from '@woocommerce/base-context';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import type {
	PackageRateOption,
	CartShippingPackageShippingRate,
} from '@woocommerce/types';
import NoticeBanner from '@woocommerce/base-components/notice-banner';
import type { ReactElement } from 'react';

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
	return {
		label: decodeEntities( option.name ),
		value: option.rate_id,
		description: decodeEntities( option.description ),
		secondaryLabel: (
			<FormattedMonetaryAmount
				currency={ getCurrencyFromPriceResponse( option ) }
				value={ priceWithTaxes }
			/>
		),
		secondaryDescription: decodeEntities( option.delivery_time ),
	};
};

const Block = ( { noShippingPlaceholder = null } ): ReactElement | null => {
	const { isEditor } = useEditorContext();

	const {
		shippingRates,
		needsShipping,
		isLoadingRates,
		hasCalculatedShipping,
		isCollectable,
	} = useShippingData();

	const { shippingAddress } = useCustomerData();

	const filteredShippingRates = isCollectable
		? shippingRates.map( ( shippingRatesPackage ) => {
				return {
					...shippingRatesPackage,
					shipping_rates: shippingRatesPackage.shipping_rates.filter(
						( shippingRatesPackageRate ) =>
							! hasCollectableRate(
								shippingRatesPackageRate.method_id
							)
					),
				};
		  } )
		: shippingRates;

	if ( ! needsShipping ) {
		return null;
	}

	const shippingRatesPackageCount =
		getShippingRatesPackageCount( shippingRates );

	if ( ! hasCalculatedShipping && ! shippingRatesPackageCount ) {
		return (
			<p>
				{ __(
					'Shipping options will be displayed here after entering your full shipping address.',
					'woocommerce'
				) }
			</p>
		);
	}
	const addressComplete = isAddressComplete( shippingAddress );

	return (
		<>
			<StoreNoticesContainer
				context={ noticeContexts.SHIPPING_METHODS }
			/>
			{ isEditor && ! shippingRatesPackageCount ? (
				noShippingPlaceholder
			) : (
				<ShippingRatesControl
					noResultsMessage={
						<>
							{ addressComplete ? (
								<NoticeBanner
									isDismissible={ false }
									className="wc-block-components-shipping-rates-control__no-results-notice"
									status="warning"
								>
									{ __(
										'There are no shipping options available. Please check your shipping address.',
										'woocommerce'
									) }
								</NoticeBanner>
							) : (
								__(
									'Add a shipping address to view shipping options.',
									'woocommerce'
								)
							) }
						</>
					}
					renderOption={ renderShippingRatesControlOption }
					collapsible={ false }
					shippingRates={ filteredShippingRates }
					isLoadingRates={ isLoadingRates }
					context="woocommerce/checkout"
				/>
			) }
		</>
	);
};

export default Block;
