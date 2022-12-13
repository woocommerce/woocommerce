/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardHeader, CardBody, CardDivider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	CardHeaderTitle,
	CardHeaderDescription,
	CenteredSpinner,
	SmartPluginCardBody,
} from '~/marketing/components';
import { useChannels } from './useChannels';
import './Channels.scss';
import { InstalledChannelCardBody } from './InstalledChannelCardBody';
import { CollapsibleRecommendedChannels } from './CollapsibleRecommendedChannels';

export const Channels = () => {
	const {
		loading,
		data: { registeredChannels, recommendedChannels },
	} = useChannels();

	/**
	 * TODO: we may need to filter the channels against
	 * `@woocommerce/data` installed plugins.
	 */

	if ( loading ) {
		return (
			<Card>
				<CardHeader>
					<CardHeaderTitle>
						{ __( 'Channels', 'woocommerce' ) }
					</CardHeaderTitle>
				</CardHeader>
				<CardBody>
					<CenteredSpinner />
				</CardBody>
			</Card>
		);
	}

	/*
	 * If users have no registered channels,
	 * we display recommended channels without collapsible list
	 * and with a description in the card header.
	 */
	if ( registeredChannels.length === 0 && recommendedChannels.length > 0 ) {
		return (
			<Card className="woocommerce-marketing-channels-card">
				<CardHeader>
					<CardHeaderTitle>
						{ __( 'Channels', 'woocommerce' ) }
					</CardHeaderTitle>
					<CardHeaderDescription>
						{ __(
							'Start by adding a channel to your store',
							'woocommerce'
						) }
					</CardHeaderDescription>
				</CardHeader>
				{ recommendedChannels.map( ( el, idx ) => {
					return (
						<Fragment key={ el.plugin }>
							<SmartPluginCardBody plugin={ el } />
							{ idx < recommendedChannels.length - 1 && (
								<CardDivider />
							) }
						</Fragment>
					);
				} ) }
			</Card>
		);
	}

	/*
	 * Users have registered channels,
	 * so here we display the registered channels first.
	 * If there are recommended channels,
	 * we display them next in a collapsible list.
	 */
	return (
		<Card className="woocommerce-marketing-channels-card">
			<CardHeader>
				<CardHeaderTitle>
					{ __( 'Channels', 'woocommerce' ) }
				</CardHeaderTitle>
			</CardHeader>

			{ /* Registered channels section. */ }
			{ registeredChannels.map( ( el, idx ) => {
				return (
					<Fragment key={ el.slug }>
						<InstalledChannelCardBody installedChannel={ el } />
						{ idx < registeredChannels.length - 1 && (
							<CardDivider />
						) }
					</Fragment>
				);
			} ) }

			{ /* Recommended channels section. */ }
			{ recommendedChannels.length > 0 && (
				<CollapsibleRecommendedChannels
					recommendedChannels={ recommendedChannels }
				/>
			) }
		</Card>
	);
};
