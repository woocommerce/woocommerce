/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getSetting } from '@woocommerce/settings';
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
	percent: 'Percentage discount',
	fixed_cart: 'Fixed cart discount',
	fixed_product: 'Fixed product discount',
};

function getCouponTypeOptions(): CouponType[] {
	const types =
		( getSetting< Record< string, string > >(
			'couponTypes',
			null
		) as Record< string, string > | null ) ?? DEFAULT_COUPON_TYPES;
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
			{ couponTypeOptions.length > 0 && (
				<SelectControl
					label={ __( 'Discount type', 'woocommerce' ) }
					value={ attributes.discountType }
					options={ couponTypeOptions }
					onChange={ ( value ) =>
						setAttributes( { discountType: value } )
					}
					__nextHasNoMarginBottom
				/>
			) }
			<NumberControl
				label={ __( 'Amount', 'woocommerce' ) }
				value={ attributes.amount }
				min={ 0 }
				max={ amountMax }
				onChange={ ( value: string | undefined ) =>
					setAttributes( {
						amount: Math.min( Number( value ) || 0, amountMax ),
					} )
				}
				__next40pxDefaultSize
			/>
			<NumberControl
				label={ __( 'Expires (days after send)', 'woocommerce' ) }
				help={ __( 'Set to 0 for no expiry.', 'woocommerce' ) }
				value={ attributes.expiryDay }
				min={ 0 }
				onChange={ ( value: string | undefined ) =>
					setAttributes( {
						expiryDay: Number( value ) || 0,
					} )
				}
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
