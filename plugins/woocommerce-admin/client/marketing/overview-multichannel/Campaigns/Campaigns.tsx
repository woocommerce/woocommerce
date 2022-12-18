/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardHeader,
	CardBody,
	Flex,
	FlexItem,
	FlexBlock,
} from '@wordpress/components';
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
						{
							display: (
								<Flex gap={ 4 }>
									<FlexItem className="woocommerce-marketing-campaign-logo">
										<img
											src={ el.icon }
											alt={ el.channelName }
											width="16"
											height="16"
										/>
									</FlexItem>
									<FlexBlock>
										<Flex direction="column" gap={ 1 }>
											<FlexItem className="woocommerce-marketing-campaign-title">
												<a href={ el.manageUrl }>
													{ el.title }
												</a>
											</FlexItem>
											<FlexItem className="woocommerce-marketing-campaign-description">
												{ el.description }
											</FlexItem>
										</Flex>
									</FlexBlock>
								</Flex>
							),
						},
						{ display: el.cost },
					];
				} ) }
				rowsPerPage={ 5 }
				totalRows={ data.length }
			/>
		</Card>
	);
};
