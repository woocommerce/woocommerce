/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Children } from '@wordpress/element';
import { Text } from '@woocommerce/experimental';
import { pluginsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	DismissableList,
	DismissableListHeading,
} from '../settings-recommendations/dismissable-list';
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
				{ __( 'Take recovery further', 'woocommerce' ) }
			</Text>
			<Text
				className="woocommerce-recommended-abandoned-cart-recovery__header-heading"
				variant="caption"
				as="p"
				size="12"
				lineHeight="16px"
			>
				{ __(
					'Pair WooCommerce’s recovery email with multi-step flows, segmentation, and ongoing marketing.',
					'woocommerce'
				) }
			</Text>
		</DismissableListHeading>
		<ul className="woocommerce-list">
			{ Children.map( children, ( item ) => (
				<li className="woocommerce-list__item">{ item }</li>
			) ) }
		</ul>
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
			{ ! hasAutomateWoo && <AutomateWooItem /> }
			{ ! hasMailPoet && <MailPoetItem /> }
		</AbandonedCartRecoveryRecommendationsList>
	);
};

export default AbandonedCartRecoveryRecommendations;
