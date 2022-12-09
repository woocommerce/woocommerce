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
	 * we display recommended channels without collapsible list.
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
	 * TODO: Users have registered channels,
	 * display the registered channels.
	 * If there are recommended channels,
	 * display them in a collapsible list.
	 */
	return (
		<Card className="woocommerce-marketing-channels-card">
			<CardHeader>
				<CardHeaderTitle>
					{ __( 'Channels', 'woocommerce' ) }
				</CardHeaderTitle>
			</CardHeader>

			{ /* TODO: registered channels here. */ }
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

			{ /* TODO: recommended channels here. */ }
			{ recommendedChannels.length > 0 && (
				<>
					<CardDivider />
					<CardBody>recommended</CardBody>
				</>
			) }
		</Card>
	);
};
