/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type { CouponCodeAttributes } from '../types';

interface GeneralSettingsProps {
	attributes: CouponCodeAttributes;
	setAttributes: ( attrs: Partial< CouponCodeAttributes > ) => void;
}

interface CouponType {
	value: string;
	label: string;
}

const DEFAULT_COUPON_TYPES: Record< string, string > = {
	percent: __( 'Percentage discount', 'woocommerce' ),
	fixed_cart: __( 'Fixed cart discount', 'woocommerce' ),
	fixed_product: __( 'Fixed product discount', 'woocommerce' ),
};

function getCouponTypeOptions(): CouponType[] {
	const types = getSetting( 'couponTypes', DEFAULT_COUPON_TYPES ) as Record<
		string,
		string
	>;
	return Object.entries( types ).map( ( [ value, label ] ) => ( {
		value,
		label,
	} ) );
}

function getAmountMax( discountType: string ): number {
	return discountType === 'percent' ? 100 : 1000000;
}

export function GeneralSettings( {
	attributes,
	setAttributes,
}: GeneralSettingsProps ): JSX.Element {
	const couponTypeOptions = getCouponTypeOptions();
	const amountMax = getAmountMax( attributes.discountType );

	return (
		<PanelBody
			title={ __( 'General', 'woocommerce' ) }
			initialOpen={ true }
		>
			<SelectControl
				label={ __( 'Discount type', 'woocommerce' ) }
				value={ attributes.discountType }
				options={ couponTypeOptions }
				onChange={ ( value ) => {
					const newMax = getAmountMax( value );
					setAttributes( {
						discountType: value,
						amount: Math.min( attributes.amount, newMax ),
					} );
				} }
				__nextHasNoMarginBottom
			/>
			<TextControl
				label={ __( 'Amount', 'woocommerce' ) }
				value={ String( attributes.amount ) }
				type="number"
				min={ 0 }
				max={ amountMax }
				onChange={ ( value ) =>
					setAttributes( {
						amount: Math.min( Number( value ) || 0, amountMax ),
					} )
				}
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<TextControl
				label={ __( 'Expires (days after send)', 'woocommerce' ) }
				help={ __( 'Set to 0 for no expiry.', 'woocommerce' ) }
				value={ String( attributes.expiryDay ) }
				type="number"
				min={ 0 }
				onChange={ ( value ) =>
					setAttributes( {
						expiryDay: Number( value ) || 0,
					} )
				}
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<ToggleControl
				label={ __( 'Free shipping', 'woocommerce' ) }
				checked={ attributes.freeShipping }
				onChange={ ( value ) =>
					setAttributes( { freeShipping: value } )
				}
				__nextHasNoMarginBottom
			/>
		</PanelBody>
	);
}
