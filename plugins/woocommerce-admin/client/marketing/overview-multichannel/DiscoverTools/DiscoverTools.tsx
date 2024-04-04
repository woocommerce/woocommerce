/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Icon, trendingUp } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	CollapsibleCard,
	CardBody,
	CenteredSpinner,
} from '~/marketing/components';
import { useRecommendedPluginsWithoutChannels } from './useRecommendedPluginsWithoutChannels';
import { PluginsTabPanel } from './PluginsTabPanel';
import './DiscoverTools.scss';

export const DiscoverTools = () => {
	const { isInitializing, isLoading, data, installAndActivate } =
		useRecommendedPluginsWithoutChannels();

	/**
	 * Renders card body.
	 *
	 * - If loading is in progress, it renders a loading indicator.
	 * - If there are zero plugins, it renders an empty content.
	 * - Otherwise, it renders PluginsTabPanel.
	 */
	const renderCardContent = () => {
		if ( isInitializing ) {
			return (
				<CardBody>
					<CenteredSpinner />
				</CardBody>
			);
		}

		if ( data.length === 0 ) {
			return (
				<CardBody className="woocommerce-marketing-discover-tools-card-body-empty-content">
					<Icon icon={ trendingUp } size={ 32 } />
					<div>
						{ __(
							'Continue to reach the right audiences and promote your products in ways that matter to them with our range of marketing solutions.',
							'woocommerce'
						) }
					</div>
					<Button
						variant="tertiary"
						href="woocommerce.com/product-category/woocommerce-extensions/marketing-extensions/"
						onClick={ () => {
							recordEvent( 'marketing_explore_more_extensions' );
						} }
					>
						{ __(
							'Explore more marketing extensions',
							'woocommerce'
						) }
					</Button>
				</CardBody>
			);
		}

		return (
			<PluginsTabPanel
				plugins={ data }
				isLoading={ isLoading }
				onInstallAndActivate={ installAndActivate }
			/>
		);
	};

	return (
		<CollapsibleCard
			header={ __( 'Discover more marketing tools', 'woocommerce' ) }
		>
			{ renderCardContent() }
		</CollapsibleCard>
	);
};
