/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, createInterpolateElement } from '@wordpress/element';
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

export class CheckSubscriptionModal extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isModalOpen: true,
		};
	}

	dismiss() {
		dismissRequest( this.props, ( response ) => {
			this.setState( { isModalOpen: false } )
		} );
	}

	remindLater() {
		remindLaterRequest( this.props, ( response ) => {
			this.setState( { isModalOpen: false } )
		} );
	}

	renew() {
		this.setState( { isModalOpen: false } )
	}

	subscribe() {
		this.setState( { isModalOpen: false } )
	}

	renderBenefits() {
		const isExpired = this.props.subscriptionState.expired;
		const subtitle = isExpired
			? __(
				'Reactivate your subscription and benefit from:',
				'woocommerce'
			)
			: __(
				'Purchase a subscription to benefit from:',
				'woocommerce'
			);

		const benefits =  [
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
	}

	renderPrimaryCard() {
		const isExpired = this.props.subscriptionState.expired;

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
									this.props.productName
								)
								: sprintf(
									/* translators: %s is product name */
									__(
										'Subscribe to %s',
										'woocommerce'
									),
									this.props.productName
								)
						}
					</h2>
				</CardHeader>
				<CardBody>
					{ this.renderBenefits() }
				</CardBody>
				<CardFooter>
					<Button
						onClick={ () => this.remindLater() }
						variant="secondary"
					>
						{ __( 'Maybe later', 'woocommerce' ) }
					</Button>

					<Button
						isPrimary
						target="_blank"
						href={ isExpired ? this.props.renewUrl : this.props.subscribeUrl }
						onClick={ () => isExpired ? this.renew() : this.subscribe() }
					>
						{
							isExpired
								? sprintf(
									/* translators: %s is product price */
									__( 'Renew for $%s', 'woocommerce' ),
									this.props.productRegularPrice
								)
								: sprintf(
									/* translators: %s is product price */
									__( 'Subscribe for $%s', 'woocommerce' ),
									this.props.productRegularPrice
								)
						}
					</Button>
				</CardFooter>
			</Card>
		);
	}

	renderSecondaryCard() {
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
	}

	render() {
		const { isModalOpen } = this.state;
		if ( ! isModalOpen ) {
			return null;
		}

		return (
			<Modal
				style={ { borderRadius: '2px' } }
				onRequestClose={ () => this.dismiss() }
				className="woocommerce-check-subscription-modal"
			>
				<Flex
					gap={ 0 }
					align={ 'stretch' }
					direction={ [ 'column-reverse', 'row' ] }
				>
					<FlexItem>
						{ this.renderPrimaryCard() }
					</FlexItem>
					<FlexItem>
						{ this.renderSecondaryCard() }
					</FlexItem>
				</Flex>
			</Modal>
		);
	}
}
