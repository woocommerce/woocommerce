/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';

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
			<NumberControl
				label={ __( 'Usage limit per coupon', 'woocommerce' ) }
				help={ __(
					'How many times this coupon can be used before it is void. Set to 0 for unlimited.',
					'woocommerce'
				) }
				value={ attributes.usageLimit }
				min={ 0 }
				onChange={ ( value: string | undefined ) =>
					setAttributes( {
						usageLimit: Number( value ) || 0,
					} )
				}
				__next40pxDefaultSize
			/>
			<NumberControl
				label={ __( 'Usage limit per user', 'woocommerce' ) }
				help={ __(
					'How many times this coupon can be used by an individual user. Set to 0 for unlimited.',
					'woocommerce'
				) }
				value={ attributes.usageLimitPerUser }
				min={ 0 }
				onChange={ ( value: string | undefined ) =>
					setAttributes( {
						usageLimitPerUser: Number( value ) || 0,
					} )
				}
				__next40pxDefaultSize
			/>
		</PanelBody>
	);
}
