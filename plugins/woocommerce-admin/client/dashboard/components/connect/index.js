/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { withDispatch, withSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';

/**
 * Button redirecting to Jetpack auth flow.
 *
 * Only render this component when the user has accepted Jetpack's Terms of Service.
 * The API endpoint used by this component sets "jetpack_tos_agreed" to true when
 * returning the URL.
 */
export class Connect extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isAwaitingRedirect: false,
			isRedirecting: false,
		};

		this.connectJetpack = this.connectJetpack.bind( this );
		props.setIsPending( false );
	}

	componentDidUpdate( prevProps ) {
		const { createNotice, error, onError, isRequesting } = this.props;

		if ( error && error !== prevProps.error ) {
			if ( onError ) {
				onError();
			}
			createNotice( 'error', error );
		}

		if (
			this.state.isAwaitingRedirect &&
			! this.state.isRedirecting &&
			! isRequesting &&
			! error
		) {
			this.setState( { isRedirecting: true }, () => {
				window.location = this.props.jetpackAuthUrl;
			} );
		}
	}

	connectJetpack() {
		const { onConnect } = this.props;

		if ( onConnect ) {
			onConnect();
		}

		this.setState( { isAwaitingRedirect: true } );
	}

	render() {
		const { error, onSkip, skipText, onAbort, abortText } = this.props;

		return (
			<Fragment>
				{ error ? (
					<Button
						isPrimary
						onClick={ () => window.location.reload() }
					>
						{ __( 'Retry', 'woocommerce' ) }
					</Button>
				) : (
					<Button
						isBusy={ this.state.isAwaitingRedirect }
						isPrimary
						onClick={ this.connectJetpack }
					>
						{ __( 'Connect', 'woocommerce' ) }
					</Button>
				) }
				{ onSkip && (
					<Button onClick={ onSkip }>
						{ skipText || __( 'No thanks', 'woocommerce' ) }
					</Button>
				) }
				{ onAbort && (
					<Button onClick={ onAbort }>
						{ abortText || __( 'Abort', 'woocommerce' ) }
					</Button>
				) }
			</Fragment>
		);
	}
}

Connect.propTypes = {
	/**
	 * Method to create a displayed notice.
	 */
	createNotice: PropTypes.func.isRequired,
	/**
	 * Human readable error message.
	 *
	 * Also used to determine if the "Retry" button should be displayed.
	 */
	error: PropTypes.string,
	/**
	 * Bool to check if the connection URL is still being requested.
	 */
	isRequesting: PropTypes.bool,
	/**
	 * Generated Jetpack authentication URL.
	 */
	jetpackAuthUrl: PropTypes.string,
	/**
	 * Called before the redirect to Jetpack.
	 */
	onConnect: PropTypes.func,
	/**
	 * Called when the plugin has an error retrieving the jetpackAuthUrl.
	 */
	onError: PropTypes.func,
	/**
	 * Called when the plugin connection is skipped.
	 */
	onSkip: PropTypes.func,
	/**
	 * Redirect URL to encode as a URL param for the connection path.
	 */
	redirectUrl: PropTypes.string,
	/**
	 * Text used for the skip connection button.
	 */
	skipText: PropTypes.string,
	/**
	 * Control the `isPending` logic of the parent containing the Stepper.
	 */
	setIsPending: PropTypes.func,
	/**
	 * Called when the plugin connection is aborted.
	 */
	onAbort: PropTypes.func,
	/**
	 * Text used for the abort connection button.
	 */
	abortText: PropTypes.string,
};

Connect.defaultProps = {
	setIsPending: () => {},
};

export default compose(
	withSelect( ( select, props ) => {
		const { getJetpackAuthUrl, isResolving } = select(
			ONBOARDING_STORE_NAME
		);

		const queryArgs = {
			redirectUrl: props.redirectUrl || window.location.href,
			from: 'woocommerce-services',
		};

		const jetpackAuthUrlResponse = getJetpackAuthUrl( queryArgs );
		const isRequesting = isResolving( 'getJetpackAuthUrl', [ queryArgs ] );

		let error;

		if ( ! isResolving && ! jetpackAuthUrlResponse ) {
			error = __( 'Error requesting connection URL.', 'woocommerce' );
		}

		if ( jetpackAuthUrlResponse?.errors?.length ) {
			error = jetpackAuthUrlResponse?.errors[ 0 ];
		}

		return {
			error,
			isRequesting,
			jetpackAuthUrl: jetpackAuthUrlResponse.url,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
		};
	} )
)( Connect );
