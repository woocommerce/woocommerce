/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardHeader,
	CardBody,
	CardDivider,
	Button,
	Icon,
} from '@wordpress/components';
import { chevronUp, chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { RecommendedChannel } from '~/marketing/data-multichannel/types';
import {
	CardHeaderTitle,
	CardHeaderDescription,
	SmartPluginCardBody,
} from '~/marketing/components';
import { RegisteredChannel } from '~/marketing/types';
import { RegisteredChannelCardBody } from './RegisteredChannelCardBody';
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
	/**
	 * State to collapse / expand the recommended channels.
	 */
	const [ expanded, setExpanded ] = useState(
		registeredChannels.length === 0
	);

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
				{ recommendedChannels.map( ( el, idx ) => {
					return (
						<Fragment key={ el.plugin }>
							<SmartPluginCardBody
								plugin={ el }
								onInstalledAndActivated={
									onInstalledAndActivated
								}
							/>
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
						<RegisteredChannelCardBody registeredChannel={ el } />
						{ idx < registeredChannels.length - 1 && (
							<CardDivider />
						) }
					</Fragment>
				);
			} ) }

			{ /* Recommended channels section. */ }
			{ recommendedChannels.length >= 1 && (
				<div className="woocommerce-marketing-recommended-channels">
					<CardDivider />
					<CardBody>
						<Button
							variant="link"
							onClick={ () => setExpanded( ! expanded ) }
						>
							{ __( 'Add channels', 'woocommerce' ) }
							<Icon
								icon={ expanded ? chevronUp : chevronDown }
								size={ 24 }
							/>
						</Button>
					</CardBody>
					{ expanded &&
						recommendedChannels.map( ( el, idx ) => {
							return (
								<Fragment key={ el.plugin }>
									<SmartPluginCardBody
										plugin={ el }
										onInstalledAndActivated={
											onInstalledAndActivated
										}
									/>
									{ idx < recommendedChannels.length - 1 && (
										<CardDivider />
									) }
								</Fragment>
							);
						} ) }
				</div>
			) }
		</Card>
	);
};
