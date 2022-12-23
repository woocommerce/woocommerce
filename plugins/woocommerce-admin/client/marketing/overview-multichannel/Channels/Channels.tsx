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
} from '~/marketing/components';
import { useChannels } from './useChannels';
import './Channels.scss';
import { InstalledChannelCardBody } from './InstalledChannelCardBody';
import { RecommendedChannels } from './RecommendedChannels';
import { RecommendedChannelsList } from './RecommendedChannelsList';

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
	 * we should display recommended channels without collapsible list
	 * and with a description in the card header.
	 */
	if ( registeredChannels.length === 0 ) {
		/**
		 * If for some reasons we don't have recommended channels,
		 * then we should not show the Channels card at all.
		 */
		if ( recommendedChannels.length === 0 ) {
			return null;
		}

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
						<InstalledChannelCardBody installedChannel={ el } />
						{ idx < registeredChannels.length - 1 && (
							<CardDivider />
						) }
					</Fragment>
				);
			} ) }

			{ /* Recommended channels section. */ }
			{ recommendedChannels.length > 0 && (
				<RecommendedChannels
					recommendedChannels={ recommendedChannels }
				/>
			) }
		</Card>
	);
};
