/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import '../style.scss';

export class DismissModal extends Component {
	setDismissed = ( timestamp ) => {
		this.props.updateOptions( {
			woocommerce_shipping_dismissed_timestamp: timestamp,
		} );
	};

	hideBanner = () => {
		document.getElementById(
			'woocommerce-admin-print-label'
		).style.display = 'none';
	};

	remindMeLaterClicked = () => {
		const { onCloseAll, trackElementClicked } = this.props;
		this.setDismissed( Date.now() );
		onCloseAll();
		this.hideBanner();
		trackElementClicked( 'shipping_banner_dismiss_modal_remind_me_later' );
	};

	closeForeverClicked = () => {
		const { onCloseAll, trackElementClicked } = this.props;
		this.setDismissed( -1 );
		onCloseAll();
		this.hideBanner();
		trackElementClicked( 'shipping_banner_dismiss_modal_close_forever' );
	};

	render() {
		const { onClose, visible } = this.props;

		if ( ! visible ) {
			return null;
		}

		return (
			<Modal
				title={ __( 'Are you sure?', 'woocommerce' ) }
				onRequestClose={ onClose }
				className="wc-admin-shipping-banner__dismiss-modal"
			>
				<p className="wc-admin-shipping-banner__dismiss-modal-help-text">
					{ __(
						'With WooCommerce Shipping you can Print shipping labels from your WooCommerce dashboard at the lowest USPS rates.',
						'woocommerce'
					) }
				</p>
				<div className="wc-admin-shipping-banner__dismiss-modal-actions">
					<Button isSecondary onClick={ this.remindMeLaterClicked }>
						{ __( 'Remind me later', 'woocommerce' ) }
					</Button>
					<Button isPrimary onClick={ this.closeForeverClicked }>
						{ __( "I don't need this", 'woocommerce' ) }
					</Button>
				</div>
			</Modal>
		);
	}
}

export default compose(
	withDispatch( ( dispatch ) => {
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		return { updateOptions };
	} )
)( DismissModal );
