/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { withDispatch, withSelect } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { PLUGINS_STORE_NAME } from '@woocommerce/data';

class Connect extends Component {
	constructor( props ) {
		super( props );

		this.connectJetpack = this.connectJetpack.bind( this );
		props.setIsPending( true );
	}

	componentDidUpdate( prevProps ) {
		const { createNotice, error, isRequesting, setIsPending } = this.props;

		if ( prevProps.isRequesting && ! isRequesting ) {
			setIsPending( false );
		}

		if ( error && error !== prevProps.error ) {
			createNotice( 'error', error );
		}
	}

	async connectJetpack() {
		const { jetpackConnectUrl, onConnect } = this.props;
		if ( onConnect ) {
			onConnect();
		}
		window.location = jetpackConnectUrl;
	}

	render() {
		const { hasErrors, isRequesting, onSkip, skipText } = this.props;

		return (
			<Fragment>
				{ hasErrors ? (
					<Button
						isPrimary
						onClick={ () => window.location.reload() }
					>
						{ __( 'Retry', 'woocommerce-admin' ) }
					</Button>
				) : (
					<Button
						disabled={ isRequesting }
						isPrimary
						onClick={ this.connectJetpack }
					>
						{ __( 'Connect', 'woocommerce-admin' ) }
					</Button>
				) }
				{ onSkip && (
					<Button onClick={ onSkip }>
						{ skipText || __( 'No thanks', 'woocommerce-admin' ) }
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
	 */
	error: PropTypes.string,
	/**
	 * Bool to determine if the "Retry" button should be displayed.
	 */
	hasErrors: PropTypes.bool,
	/**
	 * Bool to check if the connection URL is still being requested.
	 */
	isRequesting: PropTypes.bool,
	/**
	 * Generated Jetpack connection URL.
	 */
	jetpackConnectUrl: PropTypes.string,
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
};

Connect.defaultProps = {
	setIsPending: () => {},
};

export default compose(
	withSelect( ( select, props ) => {
		const {
			getJetpackConnectUrl,
			isPluginsRequesting,
			getPluginsError,
		} = select( PLUGINS_STORE_NAME );

		const queryArgs = {
			redirect_url: props.redirectUrl || window.location.href,
		};
		const isRequesting = isPluginsRequesting( 'getJetpackConnectUrl' );
		const error = getPluginsError( 'getJetpackConnectUrl' ) || '';
		const jetpackConnectUrl = getJetpackConnectUrl( queryArgs );

		return {
			error,
			isRequesting,
			jetpackConnectUrl,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
		};
	} )
)( Connect );
