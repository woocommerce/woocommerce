/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Card, Flex, FlexItem, FlexBlock, Button } from '@wordpress/components';
import { Icon, trendingUp, megaphone, closeSmall } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { CreateNewCampaignModal } from '~/marketing/components';
import './IntroductionBanner.scss';
import wooIconUrl from './woo.svg';
import illustrationUrl from './illustration.svg';
import illustrationLargeUrl from './illustration-large.svg';

type IntroductionBannerProps = {
	showButtons: boolean;
	onDismiss: () => void;
	onAddChannels: () => void;
};

export const IntroductionBanner = ( {
	showButtons,
	onDismiss,
	onAddChannels,
}: IntroductionBannerProps ) => {
	const [ open, setOpen ] = useState( false );

	return (
		<Card className="woocommerce-marketing-introduction-banner">
			<div className="woocommerce-marketing-introduction-banner-content">
				<div className="woocommerce-marketing-introduction-banner-title">
					{ __(
						'Reach new customers and increase sales without leaving WooCommerce',
						'woocommerce'
					) }
				</div>
				<Flex
					className="woocommerce-marketing-introduction-banner-features"
					direction="column"
					gap={ 1 }
					expanded={ false }
				>
					<FlexItem>
						<Flex>
							<Icon icon={ trendingUp } />
							<FlexBlock>
								{ __(
									'Reach customers on other sales channels',
									'woocommerce'
								) }
							</FlexBlock>
						</Flex>
					</FlexItem>
					<FlexItem>
						<Flex>
							<Icon icon={ megaphone } />
							<FlexBlock>
								{ __(
									'Advertise with marketing campaigns',
									'woocommerce'
								) }
							</FlexBlock>
						</Flex>
					</FlexItem>
					<FlexItem>
						<Flex>
							<Icon
								icon={
									<img
										src={ wooIconUrl }
										alt={ __(
											'WooCommerce logo',
											'woocommerce'
										) }
										width="24"
										height="24"
									/>
								}
							/>
							<FlexBlock>
								{ __( 'Built by WooCommerce', 'woocommerce' ) }
							</FlexBlock>
						</Flex>
					</FlexItem>
				</Flex>
				{ showButtons && (
					<Flex
						className="woocommerce-marketing-introduction-banner-buttons"
						justify="flex-start"
					>
						<Button
							variant="primary"
							onClick={ () => {
								setOpen( true );
							} }
						>
							{ __( 'Create a campaign', 'woocommerce' ) }
						</Button>
						<Button variant="secondary" onClick={ onAddChannels }>
							{ __( 'Add channels', 'woocommerce' ) }
						</Button>
					</Flex>
				) }
				{ open && (
					<CreateNewCampaignModal
						onRequestClose={ () => setOpen( false ) }
					/>
				) }
			</div>
			<div className="woocommerce-marketing-introduction-banner-illustration">
				<Button
					isSmall
					className="woocommerce-marketing-introduction-banner-close-button"
					onClick={ onDismiss }
				>
					<Icon icon={ closeSmall } />
				</Button>
				<img
					src={ showButtons ? illustrationLargeUrl : illustrationUrl }
					alt={ __(
						'WooCommerce Marketing introduction banner illustration',
						'woocommerce'
					) }
				/>
			</div>
		</Card>
	);
};
