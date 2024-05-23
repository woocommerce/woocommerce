/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';
import { getSetting } from '@woocommerce/settings';
import apiFetch from '@wordpress/api-fetch';

export class CheckSubscriptionModal extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isModalOpen: true,
		};
	}

	dismiss() {
		const url = addQueryArgs(
			new URL(
				'admin-ajax.php',
				getSetting( 'adminUrl' )
			).toString(),
			{
				action: this.props.actionName,
				product_id: this.props.productId,
				_ajax_nonce: this.props.dismissNonce,
			},
		);

		apiFetch( {
			url,
			method: 'GET',
		} );


		this.setState( { isModalOpen: false } )
	}

	render() {
		const { isModalOpen } = this.state;
		if ( ! isModalOpen ) {
			return null;
		}

		return (
			<Modal
				title={ __( 'Missing subscription', 'woocommerce' ) }
				onRequestClose={ () => this.dismiss() }
				className="woocommerce-navigation-opt-out-modal"
			>
				<p>
					{
						sprintf(
							__(
								"We couldn't find active subscription for %s on your store.",
								'woocommerce'
							),
							this.props.productName
						)
					}
				</p>

				<div className="woocommerce-navigation-opt-out-modal__actions">
					<Button
						isDefault
						onClick={ () => this.dismiss() }
					>
						{ __( 'Remind me later', 'woocommerce' ) }
					</Button>

					<Button
						isPrimary
						target="_blank"
						href={ this.props.manageSubscriptionsUrl }
						onClick={ () => this.dismiss() }
					>
						{ __( 'Manage subscriptions', 'woocommerce' ) }
					</Button>
				</div>
			</Modal>
		);
	}
}
