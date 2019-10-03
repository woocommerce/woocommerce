/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import apiFetch from '@wordpress/api-fetch';
import { withDispatch } from '@wordpress/data';
import interpolateComponents from 'interpolate-components';
import { Modal } from '@wordpress/components';
import { Button, TextControl } from 'newspack-components';
import { getQuery } from '@woocommerce/navigation';
import { get } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Form, Link } from '@woocommerce/components';
import withSelect from 'wc-api/with-select';
import { WCS_NAMESPACE } from 'wc-api/constants';

class Stripe extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			errorMessage: '',
			connectURL: '',
			showConnectionButtons: ! props.manualConfig && ! props.createAccount,
			showManualConfiguration: props.manualConfig,
		};

		this.updateSettings = this.updateSettings.bind( this );
	}

	componentDidMount() {
		const { createAccount, options } = this.props;
		const { showConnectionButtons } = this.state;

		const query = getQuery();

		// Handle redirect back from Stripe.
		if ( query[ 'stripe-connect' ] && '1' === query[ 'stripe-connect' ] ) {
			const stripeSettings = get( options, [ 'woocommerce_stripe_settings' ], [] );
			const isStripeConnected = stripeSettings.publishable_key && stripeSettings.secret_key;

			if ( isStripeConnected ) {
				this.props.markConfigured( 'stripe' );
				this.props.createNotice(
					'success',
					__( 'Stripe connected successfully.', 'woocommerce-admin' )
				);
				return;
			}

			/* eslint-disable react/no-did-mount-set-state */
			this.setState( {
				showConnectionButtons: false,
				showManualConfiguration: true,
			} );
			/* eslint-enable react/no-did-mount-set-state */
			return;
		}

		if ( createAccount ) {
			this.autoCreateAccount();
		}

		if ( showConnectionButtons ) {
			this.fetchOAuthConnectURL();
		}
	}

	componentDidUpdate( prevProps, prevState ) {
		if ( false === prevState.showConnectionButtons && this.state.showConnectionButtons ) {
			this.fetchOAuthConnectURL();
		}
	}

	async fetchOAuthConnectURL() {
		const { returnUrl } = this.props;
		try {
			this.props.setRequestPending( true );
			const result = await apiFetch( {
				path: WCS_NAMESPACE + '/connect/stripe/oauth/init',
				method: 'POST',
				data: {
					returnUrl,
				},
			} );
			if ( ! result || ! result.oauthUrl ) {
				this.props.setRequestPending( false );
				this.setState( {
					showConnectionButtons: false,
					showManualConfiguration: true,
				} );
				return;
			}
			this.props.setRequestPending( false );
			this.setState( {
				connectURL: result.oauthUrl,
			} );
		} catch ( error ) {
			this.props.setRequestPending( false );
			// Fallback to manual configuration if the OAuth URL cannot be grabbed.
			this.setState( {
				showConnectionButtons: false,
				showManualConfiguration: true,
			} );
		}
	}

	async autoCreateAccount() {
		const { email, countryCode } = this.props;
		try {
			this.props.setRequestPending( true );
			const result = await apiFetch( {
				path: WCS_NAMESPACE + '/connect/stripe/account',
				method: 'POST',
				data: {
					email,
					country: countryCode,
				},
			} );

			if ( result ) {
				this.props.setRequestPending( false );
				this.props.markConfigured( 'stripe' );
				this.props.createNotice(
					'success',
					__( 'Stripe connected successfully.', 'woocommerce-admin' )
				);
				return;
			}
		} catch ( error ) {
			this.props.setRequestPending( false );
			let errorTitle, errorMessage;
			// This seems to be the best way to handle this.
			// github.com/Automattic/woocommerce-services/blob/cfb6173deb3c72897ee1d35b8fdcf29c5a93dea2/woocommerce-services.php#L563-L570
			if ( -1 === error.message.indexOf( 'Account already exists for the provided email' ) ) {
				errorTitle = __( 'Stripe', 'woocommerce-admin' );
				errorMessage = interpolateComponents( {
					mixedString: sprintf(
						__(
							'We tried to create a Stripe account automatically for {{strong}}%s{{/strong}}, but an error occured. ' +
								'Please try connecting manually to continue.',
							'woocommerce-admin'
						),
						email
					),
					components: {
						strong: <strong />,
					},
				} );
			} else {
				errorTitle = __( 'You already have a Stripe account', 'woocommerce-admin' );
				errorMessage = interpolateComponents( {
					mixedString: sprintf(
						__(
							'We tried to create a Stripe account automatically for {{strong}}%s{{/strong}}, but one already exists. ' +
								'Please sign in and connect to continue.',
							'woocommerce-admin'
						),
						email
					),
					components: {
						strong: <strong />,
					},
				} );
			}

			this.setState( {
				showConnectionButtons: true,
				errorTitle,
				errorMessage,
			} );
		}
	}

	renderErrorModal() {
		const { errorTitle, errorMessage } = this.state;
		return (
			<Modal
				title={ errorTitle }
				onRequestClose={ () => this.setState( { errorMessage: '', errorTitle: '' } ) }
				className="woocommerce-task-payments__stripe-error-modal"
			>
				<div className="woocommerce-task-payments__stripe-error-wrapper">
					<div className="woocommerce-task-payments__stripe-error-message">{ errorMessage }</div>
					<Button
						isPrimary
						isDefault
						onClick={ () => this.setState( { errorMessage: '', errorTitle: '' } ) }
					>
						{ __( 'OK', 'woocommerce-admin' ) }
					</Button>
				</div>
			</Modal>
		);
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
			woocommerce_stripe_settings: {
				...this.props.options.woocommerce_stripe_settings,
				publishable_key: values.publishable_key,
				secret_key: values.secret_key,
				enabled: 'yes',
			},
		} );

		if ( ! isSettingsError ) {
			this.props.setRequestPending( false );
			markConfigured( 'stripe' );
			this.props.createNotice(
				'success',
				__( 'Stripe connected successfully.', 'woocommerce-admin' )
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
			publishable_key: '',
			secret_key: '',
		};
	}

	validate( values ) {
		const errors = {};

		if ( ! values.publishable_key ) {
			errors.publishable_key = __( 'Please enter your publishable key', 'woocommerce-admin' );
		}
		if ( ! values.secret_key ) {
			errors.secret_key = __( 'Please enter your secret key', 'woocommerce-admin' );
		}

		return errors;
	}

	renderManualConfig() {
		const stripeHelp = interpolateComponents( {
			mixedString: __(
				'Your API details can be obtained from your {{link}}Stripe account{{/link}}',
				'woocommerce-admin'
			),
			components: {
				link: <Link href="https://stripe.com/docs/account" target="_blank" type="external" />,
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
								label={ __( 'Live Publishable Key', 'woocommerce-admin' ) }
								required
								{ ...getInputProps( 'publishable_key' ) }
							/>
							<TextControl
								label={ __( 'Live Secret Key', 'woocommerce-admin' ) }
								required
								{ ...getInputProps( 'secret_key' ) }
							/>

							<Button onClick={ handleSubmit } isPrimary>
								{ __( 'Proceed', 'woocommerce-admin' ) }
							</Button>

							<Button
								onClick={ () => {
									this.props.markConfigured( 'stripe' );
								} }
							>
								{ __( 'Skip', 'woocommerce-admin' ) }
							</Button>

							<p>{ stripeHelp }</p>
						</Fragment>
					);
				} }
			</Form>
		);
	}

	render() {
		const { errorMessage, showConnectionButtons, connectURL, showManualConfiguration } = this.state;

		if ( errorMessage ) {
			return this.renderErrorModal();
		}

		if ( showConnectionButtons && connectURL ) {
			return this.renderConnectButton();
		}

		if ( showManualConfiguration ) {
			return this.renderManualConfig();
		}

		return null;
	}
}

export default compose(
	withSelect( select => {
		const { getOptions } = select( 'wc-api' );
		const options = getOptions( [ 'woocommerce_stripe_settings' ] );
		return {
			options,
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
)( Stripe );
