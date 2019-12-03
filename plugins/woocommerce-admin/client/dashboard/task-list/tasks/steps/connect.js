/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';

class Connect extends Component {
	constructor() {
		super( ...arguments );

		this.connectJetpack = this.connectJetpack.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const { createNotice, error } = this.props;

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
		const { hasErrors, isRequesting } = this.props;

		return hasErrors ? (
			<Button isPrimary onClick={ () => location.reload() }>
				{ __( 'Retry', 'woocommerce-admin' ) }
			</Button>
		) : (
			<Fragment>
				<Button isBusy={ isRequesting } isPrimary onClick={ this.connectJetpack }>
					{ __( 'Connect', 'woocommerce-admin' ) }
				</Button>
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
	 * Redirect URL to encode as a URL param for the connection path.
	 */
	redirectUrl: PropTypes.string,
};

export default compose(
	withSelect( ( select, props ) => {
		const {
			getJetpackConnectUrl,
			isGetJetpackConnectUrlRequesting,
			getJetpackConnectUrlError,
		} = select( 'wc-api' );

		const queryArgs = {
			redirect_url: props.redirectUrl || window.location.href,
		};
		const isRequesting = isGetJetpackConnectUrlRequesting( queryArgs );
		const error = getJetpackConnectUrlError( queryArgs );
		const jetpackConnectUrl = getJetpackConnectUrl( queryArgs );

		return {
			error,
			isRequesting,
			jetpackConnectUrl,
		};
	} ),
	withDispatch( dispatch => {
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
		};
	} )
)( Connect );
