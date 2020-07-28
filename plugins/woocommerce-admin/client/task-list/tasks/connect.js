/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import apiFetch from '@wordpress/api-fetch';
import { withDispatch } from '@wordpress/data';
import { omit } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { WC_ADMIN_NAMESPACE } from 'wc-api/constants';
import withSelect from 'wc-api/with-select';

class Connect extends Component {
	componentDidMount() {
		document.body.classList.add( 'woocommerce-admin-is-loading' );
		const { query } = this.props;

		if ( query.deny === '1' ) {
			this.errorMessage(
				__(
					'You must click approve to install your extensions and connect to WooCommerce.com.',
					'woocommerce-admin'
				)
			);
			return;
		}

		if ( ! query[ 'wccom-connected' ] || ! query.request_token ) {
			this.request();
			return;
		}
		this.finish();
	}

	baseQuery() {
		const { query } = this.props;
		const baseQuery = omit( { ...query, page: 'wc-admin' }, [
			'task',
			'wccom-connected',
			'request_token',
			'deny',
		] );
		return getNewPath( {}, '/', baseQuery );
	}

	errorMessage(
		message = __(
			'There was an error connecting to WooCommerce.com. Please try again.',
			'woocommerce-admin'
		)
	) {
		document.body.classList.remove( 'woocommerce-admin-is-loading' );
		getHistory().push( this.baseQuery() );
		this.props.createNotice( 'error', message );
	}

	async request() {
		try {
			const connectResponse = await apiFetch( {
				path: `${ WC_ADMIN_NAMESPACE }/plugins/request-wccom-connect`,
				method: 'POST',
			} );
			if ( connectResponse && connectResponse.connectAction ) {
				window.location = connectResponse.connectAction;
				return;
			}
			throw new Error();
		} catch ( err ) {
			this.errorMessage();
		}
	}

	async finish() {
		const { query } = this.props;
		try {
			const connectResponse = await apiFetch( {
				path: `${ WC_ADMIN_NAMESPACE }/plugins/finish-wccom-connect`,
				method: 'POST',
				data: {
					request_token: query.request_token,
				},
			} );
			if ( connectResponse && connectResponse.success ) {
				await this.props.updateProfileItems( {
					wccom_connected: true,
				} );
				if ( ! this.props.isProfileItemsError ) {
					this.props.createNotice(
						'success',
						__(
							'Store connected to WooCommerce.com and extensions are being installed.',
							'woocommerce-admin'
						)
					);

					// @todo Show a notice for when extensions are correctly installed.

					document.body.classList.remove(
						'woocommerce-admin-is-loading'
					);
					getHistory().push( this.baseQuery() );
				} else {
					this.errorMessage();
				}

				return;
			}
			throw new Error();
		} catch ( err ) {
			this.errorMessage();
		}
	}

	render() {
		return null;
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getOnboardingError } = select( ONBOARDING_STORE_NAME );

		const isProfileItemsError = Boolean(
			getOnboardingError( 'updateProfileItems' )
		);

		return { isProfileItemsError };
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateProfileItems } = dispatch( ONBOARDING_STORE_NAME );
		return {
			createNotice,
			updateProfileItems,
		};
	} )
)( Connect );
