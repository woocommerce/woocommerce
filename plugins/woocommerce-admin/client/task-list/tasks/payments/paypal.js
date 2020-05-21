/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import interpolateComponents from 'interpolate-components';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { Form, Link, Stepper, TextControl } from '@woocommerce/components';
import { getQuery } from '@woocommerce/navigation';
import { WC_ADMIN_NAMESPACE } from 'wc-api/constants';
import withSelect from 'wc-api/with-select';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';

class PayPal extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			autoConnectFailed: false,
			connectURL: '',
			isPending: false,
		};

		this.updateSettings = this.updateSettings.bind( this );
	}

	componentDidMount() {
		const { createNotice, markConfigured } = this.props;

		const query = getQuery();
		// Handle redirect back from PayPal
		if ( query[ 'paypal-connect' ] ) {
			if ( query[ 'paypal-connect' ] === '1' ) {
				createNotice(
					'success',
					__( 'PayPal connected successfully.', 'woocommerce-admin' )
				);
				markConfigured( 'paypal' );
				return;
			}

			/* eslint-disable react/no-did-mount-set-state */
			this.setState( {
				autoConnectFailed: true,
			} );
			/* eslint-enable react/no-did-mount-set-state */
			return;
		}

		this.fetchOAuthConnectURL();
	}

	componentDidUpdate( prevProps ) {
		const { activePlugins } = this.props;

		if (
			! prevProps.activePlugins.includes(
				'woocommerce-gateway-paypal-express-checkout'
			) &&
			activePlugins.includes(
				'woocommerce-gateway-paypal-express-checkout'
			)
		) {
			this.fetchOAuthConnectURL();
		}
	}

	async fetchOAuthConnectURL() {
		const { activePlugins } = this.props;

		if (
			! activePlugins.includes(
				'woocommerce-gateway-paypal-express-checkout'
			)
		) {
			return;
		}

		this.setState( { isPending: true } );
		try {
			const result = await apiFetch( {
				path: WC_ADMIN_NAMESPACE + '/plugins/connect-paypal',
				method: 'POST',
			} );
			if ( ! result || ! result.connectUrl ) {
				this.setState( {
					autoConnectFailed: true,
				} );
				return;
			}
			this.setState( {
				connectURL: result.connectUrl,
				isPending: false,
			} );
		} catch ( error ) {
			this.setState( {
				autoConnectFailed: true,
				isPending: false,
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
		const {
			createNotice,
			isSettingsError,
			options,
			updateOptions,
			markConfigured,
		} = this.props;

		await updateOptions( {
			woocommerce_ppec_paypal_settings: {
				...options.woocommerce_ppec_paypal_settings,
				api_username: values.api_username,
				api_password: values.api_password,
				enabled: 'yes',
			},
		} );

		if ( ! isSettingsError ) {
			createNotice(
				'success',
				__( 'PayPal connected successfully.', 'woocommerce-admin' )
			);
			markConfigured( 'paypal' );
		} else {
			createNotice(
				'error',
				__(
					'There was a problem saving your payment settings.',
					'woocommerce-admin'
				)
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
			errors.api_username = __(
				'Please enter your API username',
				'woocommerce-admin'
			);
		}
		if ( ! values.api_password ) {
			errors.api_password = __(
				'Please enter your API password',
				'woocommerce-admin'
			);
		}

		return errors;
	}

	renderManualConfig() {
		const { isOptionsRequesting } = this.props;
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
								label={ __(
									'API Username',
									'woocommerce-admin'
								) }
								required
								{ ...getInputProps( 'api_username' ) }
							/>
							<TextControl
								label={ __(
									'API Password',
									'woocommerce-admin'
								) }
								required
								{ ...getInputProps( 'api_password' ) }
							/>

							<Button
								onClick={ handleSubmit }
								isPrimary
								disabled={ isOptionsRequesting }
							>
								{ __( 'Proceed', 'woocommerce-admin' ) }
							</Button>

							<p>{ help }</p>
						</Fragment>
					);
				} }
			</Form>
		);
	}

	getConnectStep() {
		const { autoConnectFailed, connectURL, isPending } = this.state;
		const connectStep = {
			key: 'connect',
			label: __( 'Connect your PayPal account', 'woocommerce-admin' ),
		};

		if ( isPending ) {
			return connectStep;
		}

		if ( ! autoConnectFailed && connectURL ) {
			return {
				...connectStep,
				description: __(
					'A Paypal account is required to process payments. You will be redirected to the Paypal website to create the connection.',
					'woocommerce-admin'
				),
				content: this.renderConnectButton(),
			};
		}

		return {
			...connectStep,
			description: __(
				'Connect your store to your PayPal account. Don’t have a PayPal account? Create one.',
				'woocommerce-admin'
			),
			content: this.renderManualConfig(),
		};
	}

	render() {
		const { installStep } = this.props;
		const { isPending } = this.state;

		return (
			<Stepper
				isVertical
				isPending={ ! installStep.isComplete || isPending }
				currentStep={ installStep.isComplete ? 'connect' : 'install' }
				steps={ [ installStep, this.getConnectStep() ] }
			/>
		);
	}
}

PayPal.defaultProps = {
	manualConfig: false, // WCS is not required for the PayPal OAuth flow, so we can default to smooth connection.
};

export default compose(
	withSelect( ( select ) => {
		const { getOptions, isGetOptionsRequesting } = select( 'wc-api' );
		const { getActivePlugins } = select( PLUGINS_STORE_NAME );
		const options = getOptions( [ 'woocommerce_ppec_paypal_settings' ] );
		const isOptionsRequesting = Boolean(
			isGetOptionsRequesting( [ 'woocommerce_ppec_paypal_settings' ] )
		);
		const activePlugins = getActivePlugins();

		return {
			activePlugins,
			options,
			isOptionsRequesting,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( 'wc-api' );
		return {
			createNotice,
			updateOptions,
		};
	} )
)( PayPal );
