/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';

export class NavigationOptOutContainer extends Component {
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

		if ( ! window.surveyData || ! window.surveyData.url ) {
			return null;
		}

		return (
			<Modal
				title={ __( 'Help us improve', 'woocommerce' ) }
				onRequestClose={ () => this.setState( { isModalOpen: false } ) }
				className="woocommerce-navigation-opt-out-modal"
			>
				<p>
					{ __(
						"Take this 2-minute survey to share why you're opting out of the new navigation",
						'woocommerce'
					) }
				</p>

				<div className="woocommerce-navigation-opt-out-modal__actions">
					<Button
						isDefault
						onClick={ () =>
							this.setState( { isModalOpen: false } )
						}
					>
						{ __( 'No thanks', 'woocommerce' ) }
					</Button>

					<Button
						isPrimary
						target="_blank"
						href={ window.surveyData.url }
						onClick={ () =>
							this.setState( { isModalOpen: false } )
						}
					>
						{ __( 'Share feedback', 'woocommerce' ) }
					</Button>
				</div>
			</Modal>
		);
	}
}
