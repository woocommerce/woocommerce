/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	Card,
	CardHeader,
	CardBody,
	CardFooter,
	Flex,
	FlexItem,
	FlexBlock,
} from '@wordpress/components';
import { Icon, megaphone, cancelCircleFilled } from '@wordpress/icons';
import { Pagination, Table, Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { CardHeaderTitle, CenteredSpinner } from '~/marketing/components';
import { useCampaigns } from './useCampaigns';
import './Campaigns.scss';

export const Campaigns = () => {
	const [ page, setPage ] = useState( 1 );
	const { loading, data } = useCampaigns();

	const getContent = () => {
		if ( loading ) {
			return (
				<CardBody>
					<CenteredSpinner />
				</CardBody>
			);
		}

		if ( ! data ) {
			return (
				<CardBody className="woocommerce-marketing-campaigns-card__content">
					<Icon
						className="woocommerce-marketing-campaigns-card__content-icon woocommerce-marketing-campaigns-card__content-icon--error"
						icon={ cancelCircleFilled }
						size={ 32 }
					/>
					<div className="woocommerce-marketing-campaigns-card__content-title">
						{ __( 'An unexpected error occurred.', 'woocommerce' ) }
					</div>
					<div className="woocommerce-marketing-campaigns-card-body__content-description">
						{ __(
							'Please try again later. Check the logs if the problem persists. ',
							'woocommerce'
						) }
					</div>
				</CardBody>
			);
		}

		if ( data.length === 0 ) {
			return (
				<CardBody className="woocommerce-marketing-campaigns-card__content">
					<Icon
						className="woocommerce-marketing-campaigns-card__content-icon woocommerce-marketing-campaigns-card__content-icon--empty"
						icon={ megaphone }
						size={ 32 }
					/>
					<div className="woocommerce-marketing-campaigns-card__content-title">
						{ __(
							'Advertise with marketing campaigns',
							'woocommerce'
						) }
					</div>
					<div className="woocommerce-marketing-campaigns-card__content-description">
						{ __(
							'Easily create and manage marketing campaigns without leaving WooCommerce.',
							'woocommerce'
						) }
					</div>
				</CardBody>
			);
		}

		const perPage = 5;
		const total = data.length;
		const start = ( page - 1 ) * perPage;
		const pagedData = data.slice( start, start + perPage );

		return (
			<>
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
										<FlexItem className="woocommerce-marketing-campaigns-card__campaign-logo">
											<img
												src={ el.icon }
												alt={ el.channelName }
												width="16"
												height="16"
											/>
										</FlexItem>
										<FlexBlock>
											<Flex direction="column" gap={ 1 }>
												<FlexItem className="woocommerce-marketing-campaigns-card__campaign-title">
													<Link href={ el.manageUrl }>
														{ el.title }
													</Link>
												</FlexItem>
												{ el.description && (
													<FlexItem className="woocommerce-marketing-campaigns-card__campaign-description">
														{ el.description }
													</FlexItem>
												) }
											</Flex>
										</FlexBlock>
									</Flex>
								),
							},
							{ display: el.cost },
						];
					} ) }
				/>
				{ total > perPage && (
					<CardFooter className="woocommerce-marketing-campaigns-card__footer">
						<Pagination
							showPerPagePicker={ false }
							perPage={ perPage }
							page={ page }
							total={ total }
							onPageChange={ ( newPage: number ) => {
								setPage( newPage );
							} }
						/>
					</CardFooter>
				) }
			</>
		);
	};

	return (
		<Card className="woocommerce-marketing-campaigns-card">
			<CardHeader>
				<CardHeaderTitle>
					{ __( 'Campaigns', 'woocommerce' ) }
				</CardHeaderTitle>
			</CardHeader>
			{ getContent() }
		</Card>
	);
};
