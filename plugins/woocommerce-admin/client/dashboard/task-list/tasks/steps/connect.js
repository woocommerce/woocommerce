/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
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

export default compose(
	withSelect( select => {
		const {
			getJetpackConnectUrl,
			isGetJetpackConnectUrlRequesting,
			getJetpackConnectUrlError,
		} = select( 'wc-api' );

		const queryArgs = {
			redirect_url: window.location.href,
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
