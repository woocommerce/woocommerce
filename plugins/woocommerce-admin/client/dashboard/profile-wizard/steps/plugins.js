/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from 'newspack-components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { difference } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * Internal depdencies
 */
import { H, Stepper, Card } from '@woocommerce/components';
import { recordEvent } from 'lib/tracks';
import withSelect from 'wc-api/with-select';

const plugins = [ 'jetpack', 'woocommerce-services' ];

class Plugins extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			step: 'install',
		};

		this.activatePlugins = this.activatePlugins.bind( this );
	}

	componentDidMount() {
		this.props.installPlugins( plugins );
	}

	componentDidUpdate( prevProps ) {
		const { createNotice, errors, installedPlugins, jetpackConnectUrl } = this.props;

		if ( jetpackConnectUrl ) {
			window.location = jetpackConnectUrl;
		}

		const newErrors = difference( errors, prevProps.errors );
		newErrors.map( error => createNotice( 'error', error ) );

		if (
			prevProps.installedPlugins.length !== plugins.length &&
			installedPlugins.length === plugins.length
		) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( { step: 'activate' } );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	async activatePlugins( event ) {
		event.preventDefault();

		// Avoid double activating.
		const { isRequesting } = this.props;
		if ( isRequesting ) {
			return false;
		}

		recordEvent( 'storeprofiler_install_plugin' );

		this.props.activatePlugins( plugins );
	}

	render() {
		const { hasErrors, isRequesting } = this.props;
		const { step } = this.state;

		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'Enhance your store setup', 'woocommerce-admin' ) }
				</H>

				<Card className="woocommerce-profile-wizard__plugins-card">
					<Stepper
						isVertical={ true }
						currentStep={ step }
						isPending={ isRequesting && ! hasErrors }
						steps={ [
							{
								label: __( 'Install Jetpack and WooCommerce Services', 'woocommerce-admin' ),
								key: 'install',
							},
							{
								label: __( 'Activate Jetpack and WooCommerce Services', 'woocommerce-admin' ),
								key: 'activate',
							},
						] }
					/>

					<div className="woocommerce-profile-wizard__plugins-actions">
						{ hasErrors && (
							<Button isPrimary onClick={ () => location.reload() }>
								{ __( 'Retry', 'woocommerce-admin' ) }
							</Button>
						) }

						{ ! ( hasErrors && 'activate' === step ) && (
							<Button isPrimary isBusy={ isRequesting } onClick={ this.activatePlugins }>
								{ __( 'Activate & continue', 'woocommerce-admin' ) }
							</Button>
						) }
					</div>
				</Card>
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
			getPluginInstallations,
			getPluginInstallationErrors,
			getPluginActivations,
			getPluginActivationErrors,
			isPluginActivateRequesting,
			isPluginInstallRequesting,
		} = select( 'wc-api' );

		const isRequesting =
			isPluginActivateRequesting() || isPluginInstallRequesting() || getJetpackConnectUrlError();

		const activationErrors = getPluginActivationErrors( plugins );
		const activatedPlugins = Object.keys( getPluginActivations( plugins ) );
		const installationErrors = getPluginInstallationErrors( plugins );
		const installedPlugins = Object.keys( getPluginInstallations( plugins ) );

		const isJetpackConnectUrlRequesting = isGetJetpackConnectUrlRequesting();
		const jetpackConnectUrlError = getJetpackConnectUrlError();
		let jetpackConnectUrl = null;
		if ( activatedPlugins.includes( 'jetpack' ) ) {
			jetpackConnectUrl = getJetpackConnectUrl();
		}

		const errors = [];
		Object.keys( activationErrors ).map( plugin =>
			errors.push( activationErrors[ plugin ].message )
		);
		Object.keys( installationErrors ).map( plugin =>
			errors.push( installationErrors[ plugin ].message )
		);
		if ( jetpackConnectUrlError ) {
			errors.push( jetpackConnectUrlError );
		}
		const hasErrors = Boolean( errors.length );

		return {
			activatedPlugins,
			installedPlugins,
			jetpackConnectUrl,
			isJetpackConnectUrlRequesting,
			errors,
			hasErrors,
			isRequesting,
		};
	} ),
	withDispatch( dispatch => {
		const { createNotice } = dispatch( 'core/notices' );
		const { activatePlugins, installPlugins } = dispatch( 'wc-api' );

		return {
			activatePlugins,
			createNotice,
			installPlugins,
		};
	} )
)( Plugins );
