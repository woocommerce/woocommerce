/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { Children, useState } from '@wordpress/element';
import { CardFooter } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { pluginsStore, PluginNames } from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import {
	DismissableList,
	DismissableListHeading,
} from '../settings-recommendations/dismissable-list';
import { TrackedLink } from '~/components/tracked-link/tracked-link';
import { useOptionDismiss } from '~/hooks/use-option-dismiss';
import { createNoticesFromResponse } from '../lib/notices';
import AutomateWooItem from './automatewoo-item';
import MailPoetItem from './mailpoet-item';
import './abandoned-cart-recovery-recommendations.scss';

/**
 * Install-and-activate hook used by the recommendation card.
 *
 * Tracks which plugin slugs are currently being set up so item buttons can show
 * a busy state and be disabled while any install is in flight. Mirrors the
 * shipping recommendation utilities, with a narrower surface — we only need
 * the combined install+activate path here.
 */
export const useInstallPlugin = () => {
	const [ pluginsBeingSetup, setPluginsBeingSetup ] = useState<
		Array< string >
	>( [] );

	const { installAndActivatePlugins } = useDispatch( pluginsStore );

	const handleSetup = ( slugs: string[] ): Promise< void > => {
		if ( pluginsBeingSetup.length > 0 ) {
			return Promise.resolve();
		}

		setPluginsBeingSetup( slugs );

		return installAndActivatePlugins( slugs as Partial< PluginNames >[] )
			.then( () => {
				setPluginsBeingSetup( [] );
			} )
			.catch( ( response: { errors: Record< string, string > } ) => {
				createNoticesFromResponse( response );
				setPluginsBeingSetup( [] );

				return Promise.reject();
			} );
	};

	return [ pluginsBeingSetup, handleSetup ] as const;
};

export const AbandonedCartRecoveryRecommendationsList = ( {
	children,
}: {
	children: React.ReactNode;
} ) => {
	const { isDismissed, onDismiss } = useOptionDismiss(
		'woocommerce_abandoned_cart_recovery_recommendations_hidden'
	);

	return (
		<DismissableList
			className="woocommerce-recommended-abandoned-cart-recovery-extensions"
			isDismissed={ isDismissed }
		>
			<DismissableListHeading onDismiss={ onDismiss }>
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
};

const AbandonedCartRecoveryRecommendations = () => {
	const activePlugins = useSelect(
		( select ) => select( pluginsStore ).getActivePlugins() ?? [],
		[]
	);

	const [ pluginsBeingSetup, setupPlugin ] = useInstallPlugin();

	const hasAutomateWoo = activePlugins.includes( 'automatewoo' );
	const hasMailPoet = activePlugins.includes( 'mailpoet' );

	// Both already installed — nothing to recommend.
	if ( hasAutomateWoo && hasMailPoet ) {
		return null;
	}

	return (
		<AbandonedCartRecoveryRecommendationsList>
			{ ! hasMailPoet && (
				<MailPoetItem
					pluginsBeingSetup={ pluginsBeingSetup }
					onSetupClick={ setupPlugin }
				/>
			) }
			{ ! hasAutomateWoo && <AutomateWooItem /> }
		</AbandonedCartRecoveryRecommendationsList>
	);
};

export default AbandonedCartRecoveryRecommendations;
