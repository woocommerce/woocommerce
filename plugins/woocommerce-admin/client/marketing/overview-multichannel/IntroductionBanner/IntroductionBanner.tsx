/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, Flex, FlexItem, FlexBlock, Button } from '@wordpress/components';
import { Icon, trendingUp, megaphone, closeSmall } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './IntroductionBanner.scss';
import wooIconUrl from './woo.svg';
import illustrationUrl from './illustration.svg';
import illustrationLargeUrl from './illustration-large.svg';

type IntroductionBannerProps = {
	showButtons: boolean;
	onDismiss: () => void;
};

export const IntroductionBanner = ( {
	showButtons,
	onDismiss,
}: IntroductionBannerProps ) => {
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
								// TODO: display create a campaign modal.
							} }
						>
							{ __( 'Create a campaign', 'woocommerce' ) }
						</Button>
						<Button
							variant="secondary"
							onClick={ () => {
								// TODO: scroll down to add channels area.
							} }
						>
							{ __( 'Add channels', 'woocommerce' ) }
						</Button>
					</Flex>
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
