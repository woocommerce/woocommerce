/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Children } from '@wordpress/element';
import type { ReactNode } from 'react';
import { Text } from '@woocommerce/experimental';
// Keeps the existing Settings > Shipping card layout; migrating it away from CardFooter is a separate change.
// eslint-disable-next-line @wordpress/use-recommended-components
import { CardFooter } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	DismissableList,
	DismissableListHeading,
} from '../settings-recommendations/dismissable-list';
import { type DismissState } from '~/hooks/use-option-dismiss';
import { ShippingRecommendationsMarketplaceLink } from './shipping-recommendations-marketplace-link';

export { useInstallPlugin } from './use-install-plugin';
export { ShippingRecommendationsMarketplaceLink };

export const SHIPPING_RECOMMENDATIONS_DISMISS_OPTION =
	'woocommerce_settings_shipping_recommendations_hidden';

export const ShippingRecommendationsList = ( {
	children,
	dismissState,
}: {
	children: ReactNode;
	dismissState: DismissState;
} ) => {
	const { isDismissed, onDismiss } = dismissState;

	return (
		<DismissableList
			className="woocommerce-recommended-shipping-extensions"
			isDismissed={ isDismissed }
		>
			<DismissableListHeading onDismiss={ onDismiss }>
				<Text variant="title.small" as="p" size="20" lineHeight="28px">
					{ __( 'Recommended shipping solutions', 'woocommerce' ) }
				</Text>
				<Text
					className="woocommerce-recommended-shipping__header-heading"
					variant="caption"
					as="p"
					size="12"
					lineHeight="16px"
				>
					{ __(
						'We recommend adding one of the following shipping extensions to your store.',
						'woocommerce'
					) }
				</Text>
			</DismissableListHeading>
			<ul className="woocommerce-list">
				{ Children.map( children, ( item ) => (
					<li className="woocommerce-list__item">{ item }</li>
				) ) }
			</ul>
			<CardFooter>
				<ShippingRecommendationsMarketplaceLink />
			</CardFooter>
		</DismissableList>
	);
};
