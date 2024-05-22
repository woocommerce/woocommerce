/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';

export class CheckSubscriptionModal extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isModalOpen: true,
		};
	}

	render() {
		const { isModalOpen } = this.state;
		if ( ! isModalOpen ) {
			return null;
		}

		if ( ! window.wooCheckSubscriptionData ) {
			return null;
		}

		return (
			<Modal
				title={ __( 'Missing subscription', 'woocommerce' ) }
				onRequestClose={ () => this.setState( { isModalOpen: false } ) }
				className="woocommerce-navigation-opt-out-modal"
			>
				<p>
					{
						sprintf(
							__(
								"We couldn't find active subscription for %s on your store.",
								'woocommerce'
							),
							window.wooCheckSubscriptionData.productName
						)
					}
				</p>

				<div className="woocommerce-navigation-opt-out-modal__actions">
					<Button
						isDefault
						onClick={ () =>
							this.setState( { isModalOpen: false } )
						}
					>
						{ __( 'Remind me later', 'woocommerce' ) }
					</Button>

					<Button
						isPrimary
						target="_blank"
						href={ window.wooCheckSubscriptionData.url }
						onClick={ () =>
							this.setState( { isModalOpen: false } )
						}
					>
						{ __( 'Manage subscriptions', 'woocommerce' ) }
					</Button>
				</div>
			</Modal>
		);
	}
}
