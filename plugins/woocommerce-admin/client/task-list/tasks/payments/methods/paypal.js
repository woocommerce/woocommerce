/* global ppcp_onboarding */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { Component, Fragment, useEffect } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import interpolateComponents from 'interpolate-components';
import { withDispatch, withSelect } from '@wordpress/data';
import { isEmail } from '@wordpress/url';
import { Form, Link, TextControl, Stepper } from '@woocommerce/components';
import { getQuery } from '@woocommerce/navigation';
import { PLUGINS_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';

const PAYPAL_PLUGIN = 'woocommerce-paypal-payments';
const WC_PAYPAL_NAMESPACE = '/wc-paypal/v1';

/**
 * Loads the onboarding script file into the dom on the fly.
 *
 * @param {string} url of the onboarding js file.
 * @param {Object} data required for the onboarding script, labeled as PayPalCommerceGatewayOnboarding
 * @param {Function} onLoad callback for when the script is loaded.
 */
function loadOnboardingScript( url, data, onLoad ) {
	try {
		// eslint-disable-next-line camelcase
		if ( ppcp_onboarding ) {
			onLoad();
		}
	} catch ( e ) {
		const script = document.createElement( 'script' );
		script.src = url;
		document.body.append( script );

		// Callback after scripts have loaded.
		script.onload = function () {
			onLoad();
		};
		window.PayPalCommerceGatewayOnboarding = data;
	}
}

function PaypalConnectButton( { connectUrl, recordConnectStartEvent } ) {
	useEffect( () => {
		// eslint-disable-next-line camelcase
		if ( ppcp_onboarding ) {
			// Makes sure the onboarding is hooked up to the Connect button rendered.
			ppcp_onboarding.reload();
		}
	}, [] );

	return (
		<a
			className="button-primary"
			target="_blank"
			rel="noreferrer"
			href={ connectUrl }
			data-paypal-onboard-button="true"
			data-paypal-button="true"
			data-paypal-onboard-complete="ppcp_onboarding_productionCallback"
			onClick={ () => recordConnectStartEvent( 'paypal' ) }
		>
			{ __( 'Connect', 'woocommerce-admin' ) }
		</a>
	);
}

class PayPal extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			autoConnectFailed: false,
			connectURL: '',
		};

		this.enablePaypalPlugin = this.enablePaypalPlugin.bind( this );
		this.setCredentials = this.setCredentials.bind( this );
		this.validate = this.validate.bind( this );
	}

	componentDidMount() {
		const { createNotice } = this.props;
		const query = getQuery();
		// Handle redirect back from PayPal
		if ( query.onboarding ) {
			if (
				query.onboarding === 'complete' &&
				! query[ 'ppcp-onboarding-error' ]
			) {
				this.enablePaypalPlugin();
				return;
			}

			if ( query[ 'ppcp-onboarding-error' ] ) {
				/* eslint-disable react/no-did-mount-set-state */
				this.setState( {
					autoConnectFailed: true,
				} );
				createNotice(
					'error',
					__(
						'There was a problem saving your payment settings through the onboarding, please fill the fields in manually.',
						'woocommerce-admin'
					)
				);
			}
			return;
		}
		this.fetchOAuthConnectURLAndOnboardingSetup();
	}

	componentDidUpdate( prevProps ) {
		const { activePlugins } = this.props;

		if (
			! prevProps.activePlugins.includes( PAYPAL_PLUGIN ) &&
			activePlugins.includes( PAYPAL_PLUGIN )
		) {
			this.fetchOAuthConnectURLAndOnboardingSetup();
		}
	}

	async fetchOAuthConnectURLAndOnboardingSetup() {
		const { activePlugins, createNotice } = this.props;

		if ( ! activePlugins.includes( PAYPAL_PLUGIN ) ) {
			return;
		}

		this.setState( { isPending: true } );
		try {
			const result = await apiFetch( {
				path: WC_PAYPAL_NAMESPACE + '/onboarding/get-params',
				method: 'POST',
				data: {
					environment: 'production',
					returnUrlArgs: {
						ppcpobw: '1',
					},
				},
			} );
			if ( ! result || ! result.signupLink ) {
				this.setState( {
					autoConnectFailed: true,
					isPending: false,
				} );
				return;
			}
			loadOnboardingScript( result.scriptURL, result.scriptData, () => {
				this.setState( {
					connectURL: result.signupLink,
					isPending: false,
				} );
			} );
		} catch ( error ) {
			if ( error && error.data && error.data.status === 500 ) {
				createNotice(
					'error',
					__(
						'There was a problem with the Paypal onboarding setup, please fill the fields in manually.',
						'woocommerce-admin'
					)
				);
			}
			this.setState( {
				autoConnectFailed: true,
				isPending: false,
			} );
		}
	}

	async enablePaypalPlugin( skipPpcpSettingsUpdate ) {
		const {
			createNotice,
			updateOptions,
			markConfigured,
			options,
		} = this.props;
		const updatedOptions = {
			'woocommerce_ppcp-gateway_settings': {
				enabled: 'yes',
			},
		};
		if ( ! skipPpcpSettingsUpdate ) {
			updatedOptions[ 'woocommerce-ppcp-settings' ] = {
				...options,
				enabled: true,
			};
		}

		const update = await updateOptions( updatedOptions );

		if ( update.success ) {
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

	async setCredentials( values ) {
		const { createNotice } = this.props;
		try {
			const result = await apiFetch( {
				path: WC_PAYPAL_NAMESPACE + '/onboarding/set-credentials',
				method: 'POST',
				data: {
					environment: 'production',
					...values,
				},
			} );
			if ( result && result.data ) {
				createNotice(
					'error',
					__(
						'There was a problem updating the credentials.',
						'woocommerce-admin'
					)
				);
			} else {
				await this.enablePaypalPlugin( true );
			}
		} catch ( error ) {
			if ( error && error.data && error.data.status === 404 ) {
				await this.updateManualSettings( values );
			}
		}
	}

	async updateManualSettings( values ) {
		const {
			createNotice,
			options,
			updateOptions,
			markConfigured,
		} = this.props;

		const productionValues = Object.keys( values ).reduce(
			( vals, key ) => {
				const prodKey = key + '_production';
				return {
					...vals,
					[ prodKey ]: values[ key ],
				};
			},
			{}
		);

		/**
		 * merchant data can be the same across sandbox and production, that's why we set it as
		 * standalone as well.
		 */
		const optionValues = {
			...options,
			enabled: true,
			sandbox_on: false,
			merchant_email: values.merchant_email,
			merchant_id: values.merchant_id,
			...productionValues,
		};

		const update = await updateOptions( {
			'woocommerce-ppcp-settings': optionValues,
			'woocommerce_ppcp-gateway_settings': {
				enabled: 'yes',
			},
		} );

		if ( update.success ) {
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
		const { options } = this.props;
		return [
			'merchant_email',
			'merchant_id',
			'client_id',
			'client_secret',
		].reduce( ( initialVals, key ) => {
			return {
				...initialVals,
				[ key ]:
					options && options[ key + '_production' ]
						? options[ key + '_production' ]
						: '',
			};
		}, {} );
	}

	validate( values ) {
		const errors = {};

		if ( ! values.merchant_email ) {
			errors.merchant_email = __(
				'Please enter your Merchant email',
				'woocommerce-admin'
			);
		}
		if ( ! isEmail( values.merchant_email ) ) {
			errors.merchant_email = __(
				'Please enter a valid email address',
				'woocommerce-admin'
			);
		}
		if ( ! values.merchant_id ) {
			errors.merchant_id = __(
				'Please enter your Merchant Id',
				'woocommerce-admin'
			);
		}
		if ( ! values.client_id ) {
			errors.client_id = __(
				'Please enter your Client Id',
				'woocommerce-admin'
			);
		}
		if ( ! values.client_secret ) {
			errors.client_secret = __(
				'Please enter your Client Secret',
				'woocommerce-admin'
			);
		}

		return errors;
	}

	renderManualConfig() {
		const { isOptionsUpdating } = this.props;
		const stripeHelp = interpolateComponents( {
			mixedString: __(
				'Your API details can be obtained from your {{docsLink}}Paypal developer account{{/docsLink}}, and your Merchant Id from your {{merchantLink}}Paypal Business account{{/merchantLink}}. Don’t have a Paypal account? {{registerLink}}Create one.{{/registerLink}}',
				'woocommerce-admin'
			),
			components: {
				docsLink: (
					<Link
						href="https://developer.paypal.com/docs/api-basics/manage-apps/#create-or-edit-sandbox-and-live-apps"
						target="_blank"
						type="external"
					/>
				),
				merchantLink: (
					<Link
						href="https://www.paypal.com/ca/smarthelp/article/FAQ3850"
						target="_blank"
						type="external"
					/>
				),
				registerLink: (
					<Link
						href="https://www.paypal.com/us/business"
						target="_blank"
						type="external"
					/>
				),
			},
		} );

		return (
			<Form
				initialValues={ this.getInitialConfigValues() }
				onSubmitCallback={ this.setCredentials }
				validate={ this.validate }
			>
				{ ( { getInputProps, handleSubmit } ) => {
					return (
						<Fragment>
							<TextControl
								label={ __(
									'Email address',
									'woocommerce-admin'
								) }
								required
								{ ...getInputProps( 'merchant_email' ) }
							/>
							<TextControl
								label={ __(
									'Merchant Id',
									'woocommerce-admin'
								) }
								required
								{ ...getInputProps( 'merchant_id' ) }
							/>
							<TextControl
								label={ __( 'Client Id', 'woocommerce-admin' ) }
								required
								{ ...getInputProps( 'client_id' ) }
							/>
							<TextControl
								label={ __(
									'Secret Key',
									'woocommerce-admin'
								) }
								required
								{ ...getInputProps( 'client_secret' ) }
							/>
							<Button
								isPrimary
								isBusy={ isOptionsUpdating }
								onClick={ handleSubmit }
							>
								{ __( 'Proceed', 'woocommerce-admin' ) }
							</Button>

							<p>{ stripeHelp }</p>
						</Fragment>
					);
				} }
			</Form>
		);
	}

	renderConnectFields() {
		const { autoConnectFailed, connectURL } = this.state;
		const { recordConnectStartEvent } = this.props;

		if ( ! autoConnectFailed && connectURL ) {
			return (
				<>
					<PaypalConnectButton
						connectUrl={ connectURL }
						recordConnectStartEvent={ recordConnectStartEvent }
					/>
					<p>
						{ __(
							'You will be redirected to the PayPal website to create the connection.',
							'woocommerce-admin'
						) }
					</p>
				</>
			);
		}
		if ( autoConnectFailed ) {
			return this.renderManualConfig();
		}
	}

	getConnectStep() {
		const { isRequestingOptions } = this.props;
		return {
			key: 'connect',
			label: __( 'Connect your PayPal account', 'woocommerce-admin' ),
			description: __(
				'A PayPal account is required to process payments. Connect your store to your PayPal account.',
				'woocommerce-admin'
			),
			content: isRequestingOptions ? null : this.renderConnectFields(),
		};
	}

	render() {
		const {
			installStep,
			isRequestingOptions,
			isOptionsUpdating,
		} = this.props;
		const { isPending } = this.state;

		return (
			<Stepper
				isVertical
				isPending={
					! installStep.isComplete ||
					isPending ||
					isRequestingOptions ||
					isOptionsUpdating
				}
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
		const { getOption, isOptionsUpdating, hasFinishedResolution } = select(
			OPTIONS_STORE_NAME
		);
		const { getActivePlugins } = select( PLUGINS_STORE_NAME );
		const paypalOptions = getOption( 'woocommerce-ppcp-settings' );
		const isRequestingOptions = ! hasFinishedResolution( 'getOption', [
			'woocommerce-ppcp-settings',
		] );
		const activePlugins = getActivePlugins();

		return {
			activePlugins,
			isOptionsUpdating: isOptionsUpdating(),
			options: paypalOptions,
			isRequestingOptions,
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
)( PayPal );

export { PayPal, PAYPAL_PLUGIN };
