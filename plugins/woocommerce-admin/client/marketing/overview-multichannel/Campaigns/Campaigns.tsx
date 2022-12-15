/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Card, CardHeader, CardBody } from '@wordpress/components';
import { Icon, megaphone } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { CardHeaderTitle } from '~/marketing/components';
import './Campaigns.scss';

export const Campaigns = () => {
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
};
