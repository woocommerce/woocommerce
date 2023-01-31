/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	Button,
	Card,
	CardHeader,
	CardBody,
	CardFooter,
	Flex,
	FlexItem,
	FlexBlock,
} from '@wordpress/components';
import { Icon, megaphone } from '@wordpress/icons';
import { Pagination, Table } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { CardHeaderTitle, CenteredSpinner } from '~/marketing/components';
import { useCampaigns } from './useCampaigns';
import './Campaigns.scss';

export const Campaigns = () => {
	const [ page, setPage ] = useState( 1 );
	const { loading, data } = useCampaigns();

	if ( loading ) {
		return (
			<Card>
				<CardHeader>
					<CardHeaderTitle>
						{ __( 'Campaigns', 'woocommerce' ) }
					</CardHeaderTitle>
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

	const perPage = 5;
	const total = data.length;
	const start = ( page - 1 ) * perPage;
	const pagedData = data.slice( start, start + perPage );

	return (
		<Card className="woocommerce-marketing-campaigns-card">
			<CardHeader>
				<CardHeaderTitle>
					{ __( 'Campaigns', 'woocommerce' ) }
				</CardHeaderTitle>
			</CardHeader>
			<Table
				caption={ __( 'Campaigns', 'woocommerce' ) }
				headers={ [
					{
						key: 'campaign',
						label: __( 'Campaign', 'woocommerce' ),
					},
					{
						key: 'cost',
						label: __( 'Cost', 'woocommerce' ),
						isNumeric: true,
					},
				] }
				rows={ pagedData.map( ( el ) => {
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
			/>
			<CardFooter className="woocommerce-marketing-campaigns-card-footer">
				<Pagination
					showPerPagePicker={ false }
					perPage={ 5 }
					page={ page }
					total={ total }
					onPageChange={ ( newPage: number ) => {
						setPage( newPage );
					} }
				/>
			</CardFooter>
		</Card>
	);
};
