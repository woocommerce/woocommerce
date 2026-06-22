/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { FormattedMonetaryAmount } from '@woocommerce/blocks-components';
import type { PackageRateOption } from '@woocommerce/types';
import { getSetting } from '@woocommerce/settings';
import { CartShippingPackageShippingRate } from '@woocommerce/types';
import { __ } from '@wordpress/i18n';

/**
 * Default render function for package rate options.
 *
 * @param {Object} rate Rate data.
 */
export const renderPackageRateOption = (
	rate: CartShippingPackageShippingRate
): PackageRateOption => {
	const displayPricesIncludingTax = getSetting(
		'displayCartPricesIncludingTax',
		false
	);
	const priceWithTaxes: number = displayPricesIncludingTax
		? parseInt( rate.price, 10 ) + parseInt( rate.taxes, 10 )
		: parseInt( rate.price, 10 );
	const priceBeforeDiscount = parseInt(
		rate.price_before_discount || '',
		10
	);
	const taxesBeforeDiscount = parseInt(
		rate.taxes_before_discount || '',
		10
	);
	const priceBeforeDiscountWithTaxes: number = displayPricesIncludingTax
		? priceBeforeDiscount + taxesBeforeDiscount
		: priceBeforeDiscount;
	const showDiscountedPrice =
		Number.isFinite( priceBeforeDiscountWithTaxes ) &&
		priceBeforeDiscountWithTaxes > priceWithTaxes;

	let description = (
		<>
			{ Number.isFinite( priceWithTaxes ) && (
				<span className="wc-block-components-shipping-rates-control__package__price">
					{ showDiscountedPrice && (
						<del className="wc-block-components-shipping-rates-control__package__price--original">
							<FormattedMonetaryAmount
								currency={ getCurrencyFromPriceResponse( rate ) }
								value={ priceBeforeDiscountWithTaxes }
							/>
						</del>
					) }
					<FormattedMonetaryAmount
						currency={ getCurrencyFromPriceResponse( rate ) }
						value={ priceWithTaxes }
					/>
				</span>
			) }
			<span className="wc-block-components-shipping-rates-control__package__delivery_time">
				{ Number.isFinite( priceWithTaxes ) && rate.delivery_time
					? ' — '
					: null }
				{ decodeEntities( rate.delivery_time ) }
			</span>
		</>
	);

	if ( priceWithTaxes === 0 ) {
		description = (
			<>
				{ showDiscountedPrice && (
					<del className="wc-block-components-shipping-rates-control__package__price--original">
						<FormattedMonetaryAmount
							currency={ getCurrencyFromPriceResponse( rate ) }
							value={ priceBeforeDiscountWithTaxes }
						/>
					</del>
				) }
				<span className="wc-block-components-shipping-rates-control__package__description--free">
					{ __( 'Free', 'woocommerce' ) }
					<span className="wc-block-components-shipping-rates-control__package__delivery_time">
						{ rate.delivery_time &&
							' — ' + decodeEntities( rate.delivery_time ) }
					</span>
				</span>
			</>
		);
	}

	return {
		label: decodeEntities( rate.name ),
		value: rate.rate_id,
		description,
		secondaryDescription: rate.description
			? decodeEntities( rate.description )
			: undefined,
	};
};

export default renderPackageRateOption;
