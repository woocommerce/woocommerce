/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import interpolateComponents from 'interpolate-components';

/**
 * WooCommerce dependencies
 */
import { WC_ADMIN_NAMESPACE } from 'wc-api/constants';
import { getQuery } from '@woocommerce/navigation';
import { Form, Link, TextControl } from '@woocommerce/components';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';

class PayPal extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			connectURL: '',
			showManualConfiguration: props.manualConfig,
		};

		this.updateSettings = this.updateSettings.bind( this );
	}

	componentDidMount() {
		const { showManualConfiguration } = this.state;

		const query = getQuery();
		// Handle redirect back from PayPal
		if ( query[ 'paypal-connect' ] ) {
			if ( '1' === query[ 'paypal-connect' ] ) {
				recordEvent( 'tasklist_payment_connect_method', { payment_method: 'paypal' } );
				this.props.markConfigured( 'paypal' );
				this.props.createNotice(
					'success',
					__( 'PayPal connected successfully.', 'woocommerce-admin' )
				);
				return;
			}

			/* eslint-disable react/no-did-mount-set-state */
			this.setState( {
				showManualConfiguration: true,
			} );
			/* eslint-enable react/no-did-mount-set-state */
			return;
		}

		if ( ! showManualConfiguration ) {
			this.fetchOAuthConnectURL();
		}
	}

	componentDidUpdate( prevProps, prevState ) {
		if (
			true === prevState.showManualConfiguration &&
			false === this.state.showManualConfiguration
		) {
			this.fetchOAuthConnectURL();
		}

		if ( false === prevProps.optionsIsRequesting && true === this.props.optionsIsRequesting ) {
			this.props.setRequestPending( true );
		}

		if ( true === prevProps.optionsIsRequesting && false === this.props.optionsIsRequesting ) {
			this.props.setRequestPending( false );
		}
	}

	async fetchOAuthConnectURL() {
		this.props.setRequestPending( true );
		try {
			const result = await apiFetch( {
				path: WC_ADMIN_NAMESPACE + '/onboarding/plugins/connect-paypal',
				method: 'POST',
			} );
			if ( ! result || ! result.connectUrl ) {
				this.props.setRequestPending( false );
				this.setState( {
					showManualConfiguration: true,
				} );
				return;
			}
			this.props.setRequestPending( false );
			this.setState( {
				connectURL: result.connectUrl,
			} );
		} catch ( error ) {
			this.props.setRequestPending( false );
			this.setState( {
				showManualConfiguration: true,
			} );
		}
	}

	renderConnectButton() {
		const { connectURL } = this.state;
		return (
			<Button isPrimary isDefault href={ connectURL }>
				{ __( 'Connect', 'woocommerce-admin' ) }
			</Button>
		);
	}

	async updateSettings( values ) {
		const { createNotice, isSettingsError, updateOptions, markConfigured } = this.props;

		this.props.setRequestPending( true );
		await updateOptions( {
			woocommerce_ppec_paypal_settings: {
				...this.props.options.woocommerce_ppec_paypal_settings,
				api_username: values.api_username,
				api_password: values.api_password,
				enabled: 'yes',
			},
		} );

		if ( ! isSettingsError ) {
			recordEvent( 'tasklist_payment_connect_method', { payment_method: 'paypal' } );
			this.props.setRequestPending( false );
			markConfigured( 'paypal' );
			this.props.createNotice(
				'success',
				__( 'PayPal connected successfully.', 'woocommerce-admin' )
			);
		} else {
			this.props.setRequestPending( false );
			createNotice(
				'error',
				__( 'There was a problem saving your payment settings.', 'woocommerce-admin' )
			);
		}
	}

	getInitialConfigValues() {
		return {
			api_username: '',
			api_password: '',
		};
	}

	validate( values ) {
		const errors = {};

		if ( ! values.api_username ) {
			errors.api_username = __( 'Please enter your API username', 'woocommerce-admin' );
		}
		if ( ! values.api_password ) {
			errors.api_password = __( 'Please enter your API password', 'woocommerce-admin' );
		}

		return errors;
	}

	renderManualConfig() {
		const { optionsIsRequesting } = this.props;
		const link = (
			<Link
				href="https://docs.woocommerce.com/document/paypal-express-checkout/#section-8"
				target="_blank"
				type="external"
			/>
		);
		const help = interpolateComponents( {
			mixedString: __(
				'Your API details can be obtained from your {{link}}PayPal account{{/link}}',
				'woocommerce-admin'
			),
			components: {
				link,
			},
		} );

		return (
			<Form
				initialValues={ this.getInitialConfigValues() }
				onSubmitCallback={ this.updateSettings }
				validate={ this.validate }
			>
				{ ( { getInputProps, handleSubmit } ) => {
					return (
						<Fragment>
							<TextControl
								label={ __( 'API Username', 'woocommerce-admin' ) }
								required
								{ ...getInputProps( 'api_username' ) }
							/>
							<TextControl
								label={ __( 'API Password', 'woocommerce-admin' ) }
								required
								{ ...getInputProps( 'api_password' ) }
							/>

							<Button onClick={ handleSubmit } isPrimary disabled={ optionsIsRequesting }>
								{ __( 'Proceed', 'woocommerce-admin' ) }
							</Button>

							<Button
								onClick={ () => {
									this.props.markConfigured( 'paypal' );
								} }
							>
								{ __( 'Skip', 'woocommerce-admin' ) }
							</Button>

							<p>{ help }</p>
						</Fragment>
					);
				} }
			</Form>
		);
	}

	render() {
		const { connectURL, showManualConfiguration } = this.state;

		if ( connectURL && ! showManualConfiguration ) {
			return this.renderConnectButton();
		}

		if ( showManualConfiguration ) {
			return this.renderManualConfig();
		}

		return null;
	}
}

PayPal.defaultProps = {
	manualConfig: false, // WCS is not required for the PayPal OAuth flow, so we can default to smooth connection.
};

export default compose(
	withSelect( select => {
		const { getOptions, isGetOptionsRequesting } = select( 'wc-api' );
		const options = getOptions( [ 'woocommerce_ppec_paypal_settings' ] );
		const optionsIsRequesting = Boolean(
			isGetOptionsRequesting( [ 'woocommerce_ppec_paypal_settings' ] )
		);

		return {
			options,
			optionsIsRequesting,
		};
	} ),
	withDispatch( dispatch => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( 'wc-api' );
		return {
			createNotice,
			updateOptions,
		};
	} )
)( PayPal );
