/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Card, CardHeader, CardBody } from '@wordpress/components';
import { Icon, megaphone } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { CardHeaderTitle, CenteredSpinner } from '~/marketing/components';
import { useCampaigns } from './useCampaigns';
import './Campaigns.scss';

export const Campaigns = () => {
	const { loading, data } = useCampaigns();

	if ( loading ) {
		return (
			<Card>
				<CardHeader>
					<CardHeaderTitle>
						{ __( 'Campaigns', 'woocommerce' ) }
					</CardHeaderTitle>
					<Button variant="secondary">
						{ __( 'Create new campaign', 'woocommerce' ) }
					</Button>
				</CardHeader>
				<CardBody>
					<CenteredSpinner />
				</CardBody>
			</Card>
		);
	}

	if ( data.length === 0 ) {
		return (
			<Card className="woocommerce-marketing-campaigns-card">
				<CardHeader>
					<CardHeaderTitle>
						{ __( 'Campaigns', 'woocommerce' ) }
					</CardHeaderTitle>
					<Button variant="secondary">
						{ __( 'Create new campaign', 'woocommerce' ) }
					</Button>
				</CardHeader>
				<CardBody className="woocommerce-marketing-campaigns-card-body-empty-content">
					<Icon icon={ megaphone } size={ 32 } />
					<div className="woocommerce-marketing-campaigns-card-body-empty-content-title">
						{ __(
							'Advertise with marketing campaigns',
							'woocommerce'
						) }
					</div>
					<div>
						{ __(
							'Easily create and manage marketing campaigns without leaving WooCommerce.',
							'woocommerce'
						) }
					</div>
				</CardBody>
			</Card>
		);
	}

	return <div>TODO: campaigns here.</div>;
};
