/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Card, CardHeader, CardBody } from '@wordpress/components';
import { Icon, megaphone } from '@wordpress/icons';
import { Table } from '@woocommerce/components';

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
			<Table
				title={ __( 'Campaigns', 'woocommerce' ) }
				showMenu={ false }
				headers={ [
					{
						key: 'campaign',
						label: __( 'Campaign', 'woocommerce' ),
					},
					{
						key: 'cost',
						label: __( 'Cost', 'woocommerce' ),
					},
				] }
				ids={ data.map( ( el ) => el.id ) }
				rows={ data.map( ( el ) => {
					return [
						{ display: <div>{ el.title }</div> },
						{ display: el.cost },
					];
				} ) }
				rowsPerPage={ 5 }
				totalRows={ data.length }
			/>
		</Card>
	);
};
