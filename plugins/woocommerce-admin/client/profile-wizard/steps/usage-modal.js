/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import interpolateComponents from '@automattic/interpolate-components';
import { Button, Modal } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { initializeExPlat } from '@woocommerce/explat';

class UsageModal extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isLoadingScripts: false,
			isRequestStarted: false,
			selectedAction: null,
		};
	}

	async componentDidUpdate( prevProps, prevState ) {
		const { hasErrors, isRequesting, onClose, onContinue, createNotice } =
			this.props;
		const { isLoadingScripts, isRequestStarted } = this.state;

		// We can't rely on isRequesting props only because option update might be triggered by other component.
		if ( ! isRequestStarted ) {
			return;
		}

		const isRequestSuccessful =
			! isRequesting &&
			! isLoadingScripts &&
			( prevProps.isRequesting || prevState.isLoadingScripts ) &&
			! hasErrors;
		const isRequestError =
			! isRequesting && prevProps.isRequesting && hasErrors;

		if ( isRequestSuccessful ) {
			onClose();
			onContinue();
		}

		if ( isRequestError ) {
			createNotice(
				'error',
				__(
					'There was a problem updating your preferences',
					'woocommerce'
				)
			);
			onClose();
		}
	}

	updateTracking( { allowTracking } ) {
		const { updateOptions } = this.props;

		if ( allowTracking && typeof window.wcTracks.enable === 'function' ) {
			this.setState( { isLoadingScripts: true } );
			window.wcTracks.enable( () => {
				// Don't update state if component is unmounted already
				if ( ! this._isMounted ) {
					return;
				}

				initializeExPlat();

				this.setState( { isLoadingScripts: false } );
			} );
		} else if ( ! allowTracking ) {
			window.wcTracks.isEnabled = false;
		}

		const trackingValue = allowTracking ? 'yes' : 'no';
		this.setState( { isRequestStarted: true } );
		updateOptions( {
			woocommerce_allow_tracking: trackingValue,
		} );
	}

	componentDidMount() {
		this._isMounted = true;
	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	render() {
		const { allowTracking, isResolving, onClose, onContinue } = this.props;

		if ( isResolving ) {
			return null;
		}

		// Bail if site has already opted in to tracking
		if ( allowTracking ) {
			onClose();
			onContinue();
			return null;
		}

		const {
			isRequesting,
			title = __( 'Build a better WooCommerce', 'woocommerce' ),
			message = interpolateComponents( {
				mixedString: __(
					'Get improved features and faster fixes by sharing non-sensitive data via {{link}}usage tracking{{/link}} ' +
						'that shows us how WooCommerce is used. No personal data is tracked or stored.',
					'woocommerce'
				),
				components: {
					link: (
						<Link
							href="https://woocommerce.com/usage-tracking?utm_medium=product"
							target="_blank"
							type="external"
						/>
					),
				},
			} ),
			dismissActionText = __( 'No thanks', 'woocommerce' ),
			acceptActionText = __( 'Yes, count me in!', 'woocommerce' ),
		} = this.props;

		const { isRequestStarted, selectedAction } = this.state;
		const isBusy = isRequestStarted && isRequesting;

		return (
			<Modal
				title={ title }
				isDismissible={ this.props.isDismissible }
				onRequestClose={ () => this.props.onClose() }
				className="woocommerce-usage-modal"
			>
				<div className="woocommerce-usage-modal__wrapper">
					<div className="woocommerce-usage-modal__message">
						{ message }
					</div>
					<div className="woocommerce-usage-modal__actions">
						<Button
							isSecondary
							isBusy={ isBusy && selectedAction === 'dismiss' }
							disabled={ isBusy && selectedAction === 'accept' }
							onClick={ () => {
								this.setState( { selectedAction: 'dismiss' } );
								this.updateTracking( { allowTracking: false } );
							} }
						>
							{ dismissActionText }
						</Button>
						<Button
							isPrimary
							isBusy={ isBusy && selectedAction === 'accept' }
							disabled={ isBusy && selectedAction === 'dismiss' }
							onClick={ () => {
								this.setState( { selectedAction: 'accept' } );
								this.updateTracking( { allowTracking: true } );
							} }
						>
							{ acceptActionText }
						</Button>
					</div>
				</div>
			</Modal>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const {
			getOption,
			getOptionsUpdatingError,
			isOptionsUpdating,
			hasFinishedResolution,
		} = select( OPTIONS_STORE_NAME );

		return {
			allowTracking: getOption( 'woocommerce_allow_tracking' ) === 'yes',
			isRequesting: Boolean( isOptionsUpdating() ),
			isResolving:
				! hasFinishedResolution( 'getOption', [
					'woocommerce_allow_tracking',
				] ) ||
				typeof getOption( 'woocommerce_allow_tracking' ) ===
					'undefined',
			hasErrors: Boolean( getOptionsUpdatingError() ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );

		return {
			createNotice,
			updateOptions,
		};
	} )
)( UsageModal );
