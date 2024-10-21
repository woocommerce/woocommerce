/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	CardMedia,
	Flex,
	FlexItem,
	Icon,
	Modal,
	ResponsiveWrapper,
} from '@wordpress/components';
import { commentContent, people, reusableBlock } from '@wordpress/icons';
import { Text } from '@woocommerce/experimental';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import extensionsSvg from './illustration.svg';
import { dismissRequest, remindLaterRequest } from './actions';

export default function ProductUsageNoticeModal( {
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
	screenId,
} ) {
	const [ isModalOpen, setIsModalOpen ] = useState( true );

	useEffect( () => {
		if ( isModalOpen ) {
			recordEvent( 'product_usage_notice_opened', {
				product_id: productId,
				screen_id: screenId,
			} );
		}
	}, [ isModalOpen, productId, screenId ] );

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
				recordEvent( 'product_usage_notice_dismissed', {
					product_id: productId,
					screen_id: screenId,
				} );
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
				recordEvent( 'product_usage_notice_maybe_later_clicked', {
					product_id: productId,
					screen_id: screenId,
				} );
			}
		);
	};
	const renew = () => {
		setIsModalOpen( false );
		recordEvent( 'product_usage_notice_renew_clicked', {
			product_id: productId,
			screen_id: screenId,
		} );
	};
	const subscribe = () => {
		setIsModalOpen( false );
		recordEvent( 'product_usage_notice_subscribe_clicked', {
			product_id: productId,
			screen_id: screenId,
		} );
	};

	const renderBenefits = () => {
		const subtitle = isExpired
			? __(
					'Reactivate your subscription and benefit from:',
					'woocommerce'
			  )
			: __( 'Purchase a subscription to benefit from:', 'woocommerce' );

		const benefits = [
			{
				key: 'get-updates',
				icon: reusableBlock,
				title: __( 'Improvements and security updates', 'woocommerce' ),
				content: __(
					'Access the latest features and product updates.',
					'woocommerce'
				),
			},
			{
				key: 'get-supports',
				icon: commentContent,
				title: __( 'Help when you need it', 'woocommerce' ),
				content: __(
					'Get streamlined support from our global support team.',
					'woocommerce'
				),
			},
			{
				key: 'supporting-ecosystem',
				icon: people,
				title: __( 'Supporting the ecosystem', 'woocommerce' ),
				content: __(
					'A subscription helps us to continuously improve your extensions, themes, and WooCommerce experience.',
					'woocommerce'
				),
			},
		];

		return (
			<div className="woocommerce-subscription-benefits">
				<h3>{ subtitle }</h3>

				{ benefits.map( ( { key, icon, title, content } ) => (
					<div
						className="woocommerce-subscription-benefits__item"
						key={ key }
					>
						<div className="woocommerce-subscription-benefits__icon">
							<Icon icon={ icon } />
						</div>

						<div className="woocommerce-subscription-benefits__content">
							<Text as="h4" lineHeight={ '20px' }>
								{ title }
							</Text>
							<Text as="p">{ content }</Text>
						</div>
					</div>
				) ) }
			</div>
		);
	};

	const renderPrimaryCard = () => {
		const status = isExpired
			? __( 'Expired', 'woocommerce' )
			: __( 'Unregistered', 'woocommerce' );

		const title = isExpired
			? sprintf(
					/* translators: %s is product name */
					__( 'Renew %s', 'woocommerce' ),
					productName
			  )
			: sprintf(
					/* translators: %s is product name */
					__( 'Subscribe to %s', 'woocommerce' ),
					productName
			  );

		const buttonLabel = isExpired
			? sprintf(
					/* translators: %s is product price */
					__( 'Renew for $%s', 'woocommerce' ),
					productRegularPrice
			  )
			: sprintf(
					/* translators: %s is product price */
					__( 'Subscribe for $%s', 'woocommerce' ),
					productRegularPrice
			  );

		return (
			<Card className="primary">
				<CardHeader>
					<div>
						<Text className="subscription-status subscription-status__expired">
							{ status }
						</Text>
					</div>
					<h2>{ title }</h2>
				</CardHeader>
				<CardBody>{ renderBenefits() }</CardBody>
				<CardFooter>
					<Button onClick={ remindLater } variant="secondary">
						{ __( 'Maybe later', 'woocommerce' ) }
					</Button>

					<Button
						isPrimary
						target="_blank"
						href={ isExpired ? renewUrl : subscribeUrl }
						onClick={ () => ( isExpired ? renew() : subscribe() ) }
					>
						{ buttonLabel }
					</Button>
				</CardFooter>
			</Card>
		);
	};

	const renderSecondaryCard = () => {
		return (
			<Card className="secondary">
				<CardMedia>
					<ResponsiveWrapper
						naturalWidth={ 240 }
						naturalHeight={ 240 }
					>
						<img src={ extensionsSvg } alt="" />
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
			className="woocommerce-product-usage-notice"
		>
			<Flex
				gap={ 0 }
				align={ 'stretch' }
				direction={ [ 'column-reverse', 'row' ] }
			>
				<FlexItem>{ renderPrimaryCard() }</FlexItem>
				<FlexItem>{ renderSecondaryCard() }</FlexItem>
			</Flex>
		</Modal>
	);
}
