/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, Flex, FlexItem, FlexBlock } from '@wordpress/components';
import { Icon, trendingUp, megaphone } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './IntroductionBanner.scss';
import wooIconUrl from './woo.svg';
import illustrationUrl from './illustration.svg';

export const IntroductionBanner = () => {
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
			</div>
			<div className="woocommerce-marketing-introduction-banner-illustration">
				<img
					src={ illustrationUrl }
					alt={ __(
						'WooCommerce Marketing introduction banner illustration',
						'woocommerce'
					) }
				/>
			</div>
		</Card>
	);
};
