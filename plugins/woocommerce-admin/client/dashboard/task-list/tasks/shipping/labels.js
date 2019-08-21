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
 * WooCommerce dependencies
 */
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';

const plugins = [ 'jetpack', 'woocommerce-services' ];

class ShippingLabels extends Component {
	constructor() {
		super( ...arguments );

		this.installAndActivatePlugins = this.installAndActivatePlugins.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const {
			activatedPlugins,
			activatePlugins,
			completeStep,
			createNotice,
			errors,
			installedPlugins,
			isRequesting,
		} = this.props;

		const newErrors = difference( errors, prevProps.errors );
		newErrors.map( error => createNotice( 'error', error ) );

		if (
			! isRequesting &&
			installedPlugins.length === plugins.length &&
			activatedPlugins.length !== plugins.length &&
			prevProps.installedPlugins.length !== installedPlugins.length
		) {
			activatePlugins( plugins );
		}

		if ( activatedPlugins.length === plugins.length ) {
			createNotice(
				'success',
				__( 'Plugins were successfully installed and activated.', 'woocommerce-admin' )
			);
			completeStep();
		}
	}

	async installAndActivatePlugins( event ) {
		event.preventDefault();

		// Avoid double activating.
		const { isRequesting, installPlugins } = this.props;
		if ( isRequesting ) {
			return false;
		}

		installPlugins( plugins );
	}

	skipInstaller() {
		getHistory().push( getNewPath( {}, '/', {} ) );
	}

	render() {
		const { hasErrors, isRequesting } = this.props;

		return hasErrors ? (
			<Button isPrimary onClick={ () => location.reload() }>
				{ __( 'Retry', 'woocommerce-admin' ) }
			</Button>
		) : (
			<Fragment>
				<Button isBusy={ isRequesting } isPrimary onClick={ this.installAndActivatePlugins }>
					{ __( 'Install & enable', 'woocommerce-admin' ) }
				</Button>
				<Button onClick={ this.skipInstaller }>{ __( 'No thanks', 'woocommerce-admin' ) }</Button>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
		const {
			getPluginInstallations,
			getPluginInstallationErrors,
			getPluginActivations,
			getPluginActivationErrors,
			isPluginActivateRequesting,
			isPluginInstallRequesting,
		} = select( 'wc-api' );

		const isRequesting = isPluginActivateRequesting() || isPluginInstallRequesting();

		const activationErrors = getPluginActivationErrors( plugins );
		const activatedPlugins = Object.keys( getPluginActivations( plugins ) );
		const installationErrors = getPluginInstallationErrors( plugins );
		const installedPlugins = Object.keys( getPluginInstallations( plugins ) );

		const errors = [];
		Object.keys( activationErrors ).map( plugin =>
			errors.push( activationErrors[ plugin ].message )
		);
		Object.keys( installationErrors ).map( plugin =>
			errors.push( installationErrors[ plugin ].message )
		);
		const hasErrors = Boolean( errors.length );

		return {
			activatedPlugins,
			installedPlugins,
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
)( ShippingLabels );
