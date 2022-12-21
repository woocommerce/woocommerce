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
import { CenteredSpinner, CardHeaderTitle } from '~/marketing/components';
import { useCampaigns } from './useCampaigns';
import { CreateNewCampaignModal } from './CreateNewCampaignModal';
import './Campaigns.scss';

const PER_PAGE = 5;

export const Campaigns = () => {
	const [ page, setPage ] = useState( 1 );
	const [ open, setOpen ] = useState( false );
	const { loading, data } = useCampaigns();

	const renderBody = () => {
		if ( loading ) {
			return (
				<CardBody>
					<CenteredSpinner />
				</CardBody>
			);
		}

		if ( data.length === 0 ) {
			return (
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
			);
		}

		const start = ( page - 1 ) * PER_PAGE;
		const pagedData = data.slice( start, start + PER_PAGE );

		return (
			<Table
				// this is `classNames`, instead of the correct `className`, due to misnaming in the Table component.
				classNames="woocommerce-marketing-campaigns-table"
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
				ids={ pagedData.map( ( el ) => el.id ) }
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
		);
	};

	const renderFooter = () => {
		if ( loading || data.length === 0 ) {
			return null;
		}

		return (
			<CardFooter className="woocommerce-marketing-campaigns-card-footer">
				<Pagination
					showPerPagePicker={ false }
					perPage={ PER_PAGE }
					page={ page }
					total={ data.length }
					onPageChange={ ( newPage: number ) => {
						setPage( newPage );
					} }
				/>
			</CardFooter>
		);
	};

	return (
		<Card className="woocommerce-marketing-campaigns-card">
			<CardHeader>
				<CardHeaderTitle>
					{ __( 'Campaigns', 'woocommerce' ) }
				</CardHeaderTitle>
				<Button variant="secondary" onClick={ () => setOpen( true ) }>
					{ __( 'Create new campaign', 'woocommerce' ) }
				</Button>
				{ open && (
					<CreateNewCampaignModal
						onRequestClose={ () => setOpen( false ) }
					/>
				) }
			</CardHeader>
			{ renderBody() }
			{ renderFooter() }
		</Card>
	);
};
