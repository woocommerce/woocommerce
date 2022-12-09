/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardHeader, CardBody } from '@wordpress/components';

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

export const Channels = () => {
	const {
		loading,
		data: { registeredChannels, recommendedChannels },
	} = useChannels();

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

	const description =
		registeredChannels.length === 0 &&
		recommendedChannels.length > 0 &&
		__( 'Start by adding a channel to your store', 'woocommerce' );

	return (
		<Card className="woocommerce-marketing-channels-card">
			<CardHeader>
				<CardHeaderTitle>
					{ __( 'Channels', 'woocommerce' ) }
				</CardHeaderTitle>
				<CardHeaderDescription>{ description }</CardHeaderDescription>
			</CardHeader>
			{ /* TODO: */ }
			<CardBody>Body</CardBody>
		</Card>
	);
};
