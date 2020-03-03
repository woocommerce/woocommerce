/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { difference } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { H, Stepper, Card } from '@woocommerce/components';
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
		const { goToNextStep } = this.props;
		if ( plugins.length === 0 ) {
			goToNextStep();
			return;
		}

		this.props.installPlugins( plugins );
	}

	componentDidUpdate( prevProps ) {
		const {
			createNotice,
			errors,
			goToNextStep,
			installedPlugins,
			activatedPlugins,
		} = this.props;

		const newErrors = difference( errors, prevProps.errors );
		newErrors.map( ( error ) => createNotice( 'error', error ) );

		if (
			prevProps.installedPlugins.length !== plugins.length &&
			installedPlugins.length === plugins.length
		) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( { step: 'activate' } );
			/* eslint-enable react/no-did-update-set-state */
		}

		// Complete this step if all plugins are active.
		if (
			prevProps.activatedPlugins.length !== plugins.length &&
			activatedPlugins.length === plugins.length
		) {
			goToNextStep();
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

		const pluginLabel = plugins
			.map( ( pluginSlug ) => pluginNames[ pluginSlug ] )
			.join( ' & ' );

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
								label: sprintf(
									__( 'Install %s', 'woocommerce-admin' ),
									pluginLabel
								),
								key: 'install',
							},
							{
								label: sprintf(
									__( 'Activate %s', 'woocommerce-admin' ),
									pluginLabel
								),
								key: 'activate',
							},
						] }
					/>

					<div className="woocommerce-profile-wizard__plugins-actions">
						{ hasErrors && (
							<Button
								isPrimary
								onClick={ () => window.location.reload() }
							>
								{ __( 'Retry', 'woocommerce-admin' ) }
							</Button>
						) }

						{ ! hasErrors && step === 'activate' && (
							<Button
								isPrimary
								isBusy={ isRequesting }
								onClick={ this.activatePlugins }
							>
								{ __(
									'Activate & continue',
									'woocommerce-admin'
								) }
							</Button>
						) }
					</div>
				</Card>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const {
			getPluginInstallations,
			getPluginInstallationErrors,
			getPluginActivations,
			getPluginActivationErrors,
			isPluginActivateRequesting,
			isPluginInstallRequesting,
		} = select( 'wc-api' );
		const activationErrors = getPluginActivationErrors( plugins );
		const activatedPlugins = Object.keys( getPluginActivations( plugins ) );
		const installationErrors = getPluginInstallationErrors( plugins );
		const installedPlugins = Object.keys(
			getPluginInstallations( plugins )
		);

		const errors = [];
		Object.keys( activationErrors ).map( ( plugin ) =>
			errors.push( activationErrors[ plugin ].message )
		);
		Object.keys( installationErrors ).map( ( plugin ) =>
			errors.push( installationErrors[ plugin ].message )
		);
		const hasErrors = Boolean( errors.length );

		const isRequesting =
			isPluginActivateRequesting() || isPluginInstallRequesting();

		return {
			activatedPlugins,
			installedPlugins,
			errors,
			hasErrors,
			isRequesting,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { activatePlugins, installPlugins } = dispatch( 'wc-api' );

		return {
			activatePlugins,
			createNotice,
			installPlugins,
		};
	} )
)( Plugins );
