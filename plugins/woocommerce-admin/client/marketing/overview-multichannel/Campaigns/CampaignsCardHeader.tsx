/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	Button,
	CardHeader,
	Modal,
	Icon,
	Flex,
	FlexBlock,
	FlexItem,
} from '@wordpress/components';
import { chevronUp, chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	CardHeaderTitle,
	RecommendedChannelsList,
} from '~/marketing/components';
import { useRecommendedChannels } from '~/marketing/hooks';
import { useNewCampaignTypes } from './useNewCampaignTypes';

export const CampaignsCardHeader = () => {
	const [ open, setOpen ] = useState( false );
	const [ collapsed, setCollapsed ] = useState( true );
	const { loading, data: newCampaignTypes } = useNewCampaignTypes();
	const { data: recommendedChannels } = useRecommendedChannels();

	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );

	return (
		<CardHeader>
			<CardHeaderTitle>
				{ __( 'Campaigns', 'woocommerce' ) }
			</CardHeaderTitle>
			<Button variant="secondary" onClick={ openModal }>
				{ __( 'Create new campaign', 'woocommerce' ) }
			</Button>
			{ open && (
				<Modal
					className="woocommerce-marketing-create-campaign-modal"
					title={ __( 'Create a new campaign', 'woocommerce' ) }
					onRequestClose={ closeModal }
				>
					<div className="woocommerce-marketing-new-campaigns">
						<div className="woocommerce-marketing-new-campaigns__question-label">
							{ __(
								'Where would you like to promote your products?',
								'woocommerce'
							) }
						</div>
						<div>
							{ newCampaignTypes.map( ( el ) => {
								return (
									<Flex
										key={ el.id }
										className="woocommerce-marketing-new-campaign-type"
										gap={ 4 }
									>
										<FlexItem>
											<img
												src={ el.icon }
												alt={ el.name }
												width="32"
												height="32"
											/>
										</FlexItem>
										<FlexBlock>
											<Flex direction="column" gap={ 1 }>
												<FlexItem className="woocommerce-marketing-new-campaign-type__name">
													{ el.name }
												</FlexItem>
												<FlexItem className="woocommerce-marketing-new-campaign-type__description">
													{ el.description }
												</FlexItem>
											</Flex>
										</FlexBlock>
										<FlexItem>
											<Button
												variant="secondary"
												href={ el.createUrl }
											>
												{ __(
													'Create',
													'woocommerce'
												) }
											</Button>
										</FlexItem>
									</Flex>
								);
							} ) }
						</div>
					</div>
					{ recommendedChannels.length > 0 && (
						<div className="woocommerce-marketing-add-channels">
							<Flex direction="column">
								<FlexItem>
									<Button
										variant="link"
										onClick={ () =>
											setCollapsed( ! collapsed )
										}
									>
										{ __(
											'Add channels for other campaign types',
											'woocommerce'
										) }
										<Icon
											icon={
												collapsed
													? chevronDown
													: chevronUp
											}
											size={ 24 }
										/>
									</Button>
								</FlexItem>
								{ ! collapsed && (
									<FlexItem>
										<RecommendedChannelsList
											recommendedChannels={
												recommendedChannels
											}
										/>
									</FlexItem>
								) }
							</Flex>
						</div>
					) }
				</Modal>
			) }
		</CardHeader>
	);
};
