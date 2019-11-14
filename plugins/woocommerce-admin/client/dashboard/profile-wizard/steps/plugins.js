/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from 'newspack-components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { difference } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { H, Stepper, Card } from '@woocommerce/components';
import { getNewPath, updateQueryString, getAdminLink } from '@woocommerce/navigation';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { recordEvent } from 'lib/tracks';
import withSelect from 'wc-api/with-select';
import { pluginNames } from 'wc-api/onboarding/constants';

const pluginsToInstall = [ 'jetpack', 'woocommerce-services' ];
const { activePlugins = [] } = getSetting( 'onboarding', {} );
// We want to use the cached version of activePlugins here, otherwise the list we are dealing with could update as plugins are activated.
const plugins = difference( pluginsToInstall, activePlugins );

class Plugins extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			step: 'install',
		};

		this.activatePlugins = this.activatePlugins.bind( this );
	}

	componentDidMount() {
		const { isJetpackConnected } = this.props;
		if ( 0 === plugins.length && isJetpackConnected ) {
			return updateQueryString( { step: 'store-details' } );
		}

		this.props.installPlugins( plugins );
	}

	componentDidUpdate( prevProps ) {
		const {
			createNotice,
			errors,
			installedPlugins,
			activatedPlugins,
			jetpackConnectUrl,
			isJetpackConnected,
		} = this.props;

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

		// If Jetpack was already connected, we can go to store details after WCS is activated.
		if (
			! plugins.includes( 'jetpack' ) &&
			prevProps.activatedPlugins.length !== plugins.length &&
			activatedPlugins.length === plugins.length &&
			isJetpackConnected
		) {
			/* eslint-disable react/no-did-update-set-state */
			return updateQueryString( { step: 'store-details' } );
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
		const { hasErrors, isRequesting, isJetpackConnected, jetpackConnectUrl } = this.props;
		const { step } = this.state;

		if ( 0 === plugins.length && ! isJetpackConnected ) {
			return (
				<Fragment>
					<H className="woocommerce-profile-wizard__header-title">
						{ __( 'Connecting your store', 'woocommerce-admin' ) }
					</H>

					<p>
						{ __(
							'You will be redirected to WordPress.com to continue connecting your site.',
							'woocommerce-admin'
						) }{' '}
					</p>
				</Fragment>
			);
		}

		const pluginLabel = plugins.map( pluginSlug => pluginNames[ pluginSlug ] ).join( ' & ' );

		// Shows the "Activate & continue" button as busy during the request and redirect, so users see feedback.
		const activateButtonBusy =
			isRequesting || ( ! isJetpackConnected && null !== jetpackConnectUrl );

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
								label: sprintf( __( 'Install %s', 'woocommerce-admin' ), pluginLabel ),
								key: 'install',
							},
							{
								label: sprintf( __( 'Activate %s', 'woocommerce-admin' ), pluginLabel ),
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
							<Button isPrimary isBusy={ activateButtonBusy } onClick={ this.activatePlugins }>
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
			isJetpackConnected,
		} = select( 'wc-api' );
		const activationErrors = getPluginActivationErrors( plugins );
		const activatedPlugins = Object.keys( getPluginActivations( plugins ) );
		const installationErrors = getPluginInstallationErrors( plugins );
		const installedPlugins = Object.keys( getPluginInstallations( plugins ) );

		const queryArgs = {
			redirect_url: getAdminLink( getNewPath( { step: 'store-details' } ) ),
		};

		let jetpackConnectUrl = null;
		let isJetpackConnectUrlRequesting = false;
		let jetpackConnectUrlError = null;
		if (
			activatedPlugins.includes( 'jetpack' ) ||
			( 0 === plugins.length && ! isJetpackConnected() ) ||
			( activatedPlugins.includes( 'woocommerce-services' ) && ! isJetpackConnected() )
		) {
			isJetpackConnectUrlRequesting = isGetJetpackConnectUrlRequesting( queryArgs );
			jetpackConnectUrl = getJetpackConnectUrl( queryArgs );
			jetpackConnectUrlError = getJetpackConnectUrlError( queryArgs );
		}

		const errors = [];
		Object.keys( activationErrors ).map( plugin =>
			errors.push( activationErrors[ plugin ].message )
		);
		Object.keys( installationErrors ).map( plugin =>
			errors.push( installationErrors[ plugin ].message )
		);
		if ( jetpackConnectUrlError ) {
			errors.push( jetpackConnectUrlError.message );
		}
		const hasErrors = Boolean( errors.length );

		const isRequesting =
			isPluginActivateRequesting() || isPluginInstallRequesting() || isJetpackConnectUrlRequesting;

		return {
			activatedPlugins,
			installedPlugins,
			jetpackConnectUrl,
			isJetpackConnectUrlRequesting,
			errors,
			hasErrors,
			isRequesting,
			isJetpackConnected: isJetpackConnected(),
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
