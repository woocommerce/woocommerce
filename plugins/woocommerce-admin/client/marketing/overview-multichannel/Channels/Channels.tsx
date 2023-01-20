/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardHeader, CardDivider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { RecommendedChannel } from '~/marketing/data-multichannel/types';
import { CardHeaderTitle, CardHeaderDescription } from '~/marketing/components';
import { RegisteredChannel } from '~/marketing/types';
import { RegisteredChannelCardBody } from './RegisteredChannelCardBody';
import { RecommendedChannels } from './RecommendedChannels';
import { RecommendedChannelsList } from './RecommendedChannelsList';
import './Channels.scss';

type ChannelsProps = {
	registeredChannels: Array< RegisteredChannel >;
	recommendedChannels: Array< RecommendedChannel >;
	onInstalledAndActivated?: () => void;
};

export const Channels: React.FC< ChannelsProps > = ( {
	registeredChannels,
	recommendedChannels,
	onInstalledAndActivated,
} ) => {
	/*
	 * If users have no registered channels,
	 * we should display recommended channels without collapsible list
	 * and with a description in the card header.
	 */
	if ( registeredChannels.length === 0 ) {
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
				<RecommendedChannelsList
					recommendedChannels={ recommendedChannels }
					onInstalledAndActivated={ onInstalledAndActivated }
				/>
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
						<RegisteredChannelCardBody registeredChannel={ el } />
						{ idx < registeredChannels.length - 1 && (
							<CardDivider />
						) }
					</Fragment>
				);
			} ) }

			{ /* Recommended channels section. */ }
			{ recommendedChannels.length >= 1 && (
				<RecommendedChannels
					recommendedChannels={ recommendedChannels }
					onInstalledAndActivated={ onInstalledAndActivated }
				/>
			) }
		</Card>
	);
};
