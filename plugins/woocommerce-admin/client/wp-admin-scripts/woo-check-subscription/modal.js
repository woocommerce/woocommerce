/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	CardMedia,
	ExternalLink,
	Flex,
	FlexItem,
	Icon,
	Modal,
	ResponsiveWrapper,
} from '@wordpress/components';
import { commentContent, people, reusableBlock } from '@wordpress/icons';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import extensionsSvg from './illustration.svg';
import { dismissRequest, remindLaterRequest } from './actions';

export default function CheckSubscriptionModal( {
	renewUrl,
	subscribeUrl,
	productId,
	productName,
	productRegularPrice,
	dismissAction,
	dismissNonce,
	remindLaterAction,
	remindLaterNonce,
	subscriptionState,
} ) {
	const [ isModalOpen, setIsModalOpen ] = useState( true );
	const isExpired = subscriptionState.expired;

	const dismiss = () => {
		dismissRequest(
			{
				dismissAction,
				productId,
				dismissNonce,
			},
			() => {
				setIsModalOpen( false );
			}
		);
	};
	const remindLater = () => {
		remindLaterRequest(
			{
				remindLaterAction,
				productId,
				remindLaterNonce,
			},
			() => {
				setIsModalOpen( false );
			}
		);
	};
	const renew = () => {
		setIsModalOpen( false );
	};
	const subscribe = () => {
		setIsModalOpen( false );
	};

	const renderBenefits = () => {
		const subtitle = isExpired
			? __( 'Reactivate your subscription and benefit from:', 'woocommerce' )
			: __( 'Purchase a subscription to benefit from:', 'woocommerce' );

		const benefits = [
			{
				key: 'get-updates',
				icon: reusableBlock,
				title: __( 'Improvements and security updates', 'woocommerce' ),
				content: __( 'Access the latest features and product updates.', 'woocommerce' ),
			},
			{
				key: 'get-supports',
				icon: commentContent,
				title: __( 'Help when you need it', 'woocommerce' ),
				content: __( 'Get streamlined support from our global support team.', 'woocommerce' ),
			},
			{
				key: 'supporting-ecosystem',
				icon: people,
				title: __( 'Supporting the ecosystem', 'woocommerce' ),
				content: createInterpolateElement(
					__( 'A subscription helps us to continuously improve your extensions, themes, and WooCommerce experience. <readMore />', 'woocommerce' ),
					{
						readMore: (
						<ExternalLink href="#">{ __( 'Read more', 'woocommerce' ) }</ExternalLink>
					),
					}
				),
			},
		];

		return (
			<div className="woocommerce-subscription-benefits">
				<h3>{ subtitle }</h3>

				{ benefits.map( ( { key, icon, title, content } ) => (
					<div className="woocommerce-subscription-benefits__item" key={ key }>
						<div className="woocommerce-subscription-benefits__icon">
							<Icon icon={ icon } />
						</div>

						<div className="woocommerce-subscription-benefits__content">
							<Text
								as="h4"
								lineHeight={ "20px" }
							>
								{ title }
							</Text>
							<Text as="p">
								{ content }
							</Text>
						</div>
					</div>
				) ) }
			</div>
		)
	};

	const renderPrimaryCard = () => {
		return (
			<Card className="primary">
				<CardHeader>
					<div>
						<Text
							className="subscription-status subscription-status__expired"
						>
							{
								isExpired
									? __( 'Expired', 'woocommerce' )
									: __( 'Unregistered', 'woocommerce' )
							}
						</Text>
					</div>
					<h2>
						{
							isExpired
								? sprintf(
									/* translators: %s is product name */
									__(
										'Renew %s',
										'woocommerce'
									),
									productName
								)
								: sprintf(
									/* translators: %s is product name */
									__(
										'Subscribe to %s',
										'woocommerce'
									),
									productName
								)
						}
					</h2>
				</CardHeader>
				<CardBody>
					{ renderBenefits() }
				</CardBody>
				<CardFooter>
					<Button
						onClick={ remindLater }
						variant="secondary"
					>
						{ __( 'Maybe later', 'woocommerce' ) }
					</Button>

					<Button
						isPrimary
						target="_blank"
						href={ isExpired ? renewUrl : subscribeUrl }
						onClick={ () => isExpired ? renew() : subscribe() }
					>
						{
							isExpired
								? sprintf(
									/* translators: %s is product price */
									__( 'Renew for $%s', 'woocommerce' ),
									productRegularPrice
								)
								: sprintf(
									/* translators: %s is product price */
									__( 'Subscribe for $%s', 'woocommerce' ),
									productRegularPrice
								)
						}
					</Button>
				</CardFooter>
			</Card>
		);
	};

	const renderSecondaryCard = () => {
		return (
			<Card className="secondary">
				<CardMedia>
					<ResponsiveWrapper naturalWidth={ 240 } naturalHeight={ 240 }>
						<img
							src={ extensionsSvg }
							alt=""
						/>
					</ResponsiveWrapper>
				</CardMedia>
			</Card>
		);
	};

	if ( ! isModalOpen ) {
		return null;
	}

	return (
		<Modal
			style={ { borderRadius: '2px' } }
			onRequestClose={ dismiss }
			className="woocommerce-check-subscription-modal"
		>
			<Flex
				gap={ 0 }
				align={ 'stretch' }
				direction={ [ 'column-reverse', 'row' ] }
			>
				<FlexItem>
					{ renderPrimaryCard() }
				</FlexItem>
				<FlexItem>
					{ renderSecondaryCard() }
				</FlexItem>
			</Flex>
		</Modal>
	);
}
