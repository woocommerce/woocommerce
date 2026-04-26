/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, TextControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { CouponCodeAttributes } from '../types';

interface UsageLimitsProps {
	attributes: CouponCodeAttributes;
	setAttributes: ( attrs: Partial< CouponCodeAttributes > ) => void;
}

export function UsageLimits( {
	attributes,
	setAttributes,
}: UsageLimitsProps ): JSX.Element {
	return (
		<PanelBody
			title={ __( 'Usage limits', 'woocommerce' ) }
			initialOpen={ false }
		>
			<TextControl
				label={ __( 'Usage limit per coupon', 'woocommerce' ) }
				help={ __(
					'How many times this coupon can be used before it is void. Set to 0 for unlimited.',
					'woocommerce'
				) }
				value={ String( attributes.usageLimit ) }
				type="number"
				min={ 0 }
				onChange={ ( value ) =>
					setAttributes( {
						usageLimit: Number( value ) || 0,
					} )
				}
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<TextControl
				label={ __( 'Usage limit per user', 'woocommerce' ) }
				help={ __(
					'How many times this coupon can be used by an individual user. Set to 0 for unlimited.',
					'woocommerce'
				) }
				value={ String( attributes.usageLimitPerUser ) }
				type="number"
				min={ 0 }
				onChange={ ( value ) =>
					setAttributes( {
						usageLimitPerUser: Number( value ) || 0,
					} )
				}
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
		</PanelBody>
	);
}
