/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Children } from '@wordpress/element';
import { CardFooter } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { pluginsStore } from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import {
	DismissableList,
	DismissableListHeading,
} from '../settings-recommendations/dismissable-list';
import { TrackedLink } from '~/components/tracked-link/tracked-link';
import AutomateWooItem from './automatewoo-item';
import MailPoetItem from './mailpoet-item';
import './abandoned-cart-recovery-recommendations.scss';

export const AbandonedCartRecoveryRecommendationsList = ( {
	children,
}: {
	children: React.ReactNode;
} ) => (
	<DismissableList
		className="woocommerce-recommended-abandoned-cart-recovery-extensions"
		dismissOptionName="woocommerce_abandoned_cart_recovery_recommendations_hidden"
	>
		<DismissableListHeading>
			<Text variant="title.small" as="p" size="20" lineHeight="28px">
				{ __( 'Recover more abandoned carts', 'woocommerce' ) }
			</Text>
			<Text
				className="woocommerce-recommended-abandoned-cart-recovery__header-heading"
				variant="caption"
				as="p"
				size="12"
				lineHeight="16px"
			>
				{ __(
					'Add multi-step recovery flows, customer segmentation, and ongoing email marketing to win back more shoppers.',
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
			<TrackedLink
				message={ __(
					// translators: {{Link}} is a placeholder for a html element.
					'Visit {{Link}}the WooCommerce Marketplace{{/Link}} to find more email marketing and customer engagement solutions.',
					'woocommerce'
				) }
				targetUrl={ getAdminLink(
					'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=marketing'
				) }
				linkType="wc-admin"
				eventName="abandoned_cart_recovery_visit_marketplace_click"
			/>
		</CardFooter>
	</DismissableList>
);

const AbandonedCartRecoveryRecommendations = () => {
	const activePlugins = useSelect(
		( select ) => select( pluginsStore ).getActivePlugins() ?? [],
		[]
	);

	const hasAutomateWoo = activePlugins.includes( 'automatewoo' );
	const hasMailPoet = activePlugins.includes( 'mailpoet' );

	// Both already installed — nothing to recommend.
	if ( hasAutomateWoo && hasMailPoet ) {
		return null;
	}

	return (
		<AbandonedCartRecoveryRecommendationsList>
			{ ! hasMailPoet && <MailPoetItem /> }
			{ ! hasAutomateWoo && <AutomateWooItem /> }
		</AbandonedCartRecoveryRecommendationsList>
	);
};

export default AbandonedCartRecoveryRecommendations;
