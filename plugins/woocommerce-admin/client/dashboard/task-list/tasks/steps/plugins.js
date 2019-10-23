/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from 'newspack-components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { difference } from 'lodash';
import PropTypes from 'prop-types';
import { withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';

class Plugins extends Component {
	constructor() {
		super( ...arguments );

		this.installAndActivatePlugins = this.installAndActivatePlugins.bind( this );
		this.skipInstaller = this.skipInstaller.bind( this );
	}

	componentDidMount() {
		const { autoInstall } = this.props;

		if ( autoInstall ) {
			this.installAndActivatePlugins();
		}
	}

	componentDidUpdate( prevProps ) {
		const {
			activatedPlugins,
			activatePlugins,
			onComplete,
			createNotice,
			errors,
			installedPlugins,
			isRequesting,
			pluginSlugs,
			onError,
			hasErrors,
		} = this.props;

		const newErrors = difference( errors, prevProps.errors );
		newErrors.map( error => createNotice( 'error', error ) );

		if (
			! isRequesting &&
			installedPlugins.length === pluginSlugs.length &&
			activatedPlugins.length !== pluginSlugs.length &&
			prevProps.installedPlugins.length !== installedPlugins.length
		) {
			activatePlugins( pluginSlugs );
		}

		if ( activatedPlugins.length === pluginSlugs.length ) {
			createNotice(
				'success',
				__( 'Plugins were successfully installed and activated.', 'woocommerce-admin' )
			);
			onComplete();
		}

		if ( ! prevProps.hasErrors && hasErrors ) {
			onError();
		}
	}

	async installAndActivatePlugins( event ) {
		if ( event ) {
			event.preventDefault();
		}

		// Avoid double activating.
		const { isRequesting, installPlugins, pluginSlugs } = this.props;
		if ( isRequesting ) {
			return false;
		}

		installPlugins( pluginSlugs );
	}

	skipInstaller() {
		this.props.onSkip();
	}

	render() {
		const { hasErrors, isRequesting, skipText, autoInstall } = this.props;

		if ( hasErrors ) {
			return (
				<Fragment>
					<Button isPrimary isBusy={ isRequesting } onClick={ this.installAndActivatePlugins }>
						{ __( 'Retry', 'woocommerce-admin' ) }
					</Button>
					<Button onClick={ this.skipInstaller }>
						{ __( 'Continue without installing', 'woocommerce-admin' ) }
					</Button>
				</Fragment>
			);
		}

		if ( autoInstall ) {
			return null;
		}

		return (
			<Fragment>
				<Button isBusy={ isRequesting } isPrimary onClick={ this.installAndActivatePlugins }>
					{ __( 'Install & enable', 'woocommerce-admin' ) }
				</Button>
				<Button onClick={ this.skipInstaller }>
					{ skipText || __( 'No thanks', 'woocommerce-admin' ) }
				</Button>
			</Fragment>
		);
	}
}

Plugins.propTypes = {
	/**
	 * Called when the plugin installer is completed.
	 */
	onComplete: PropTypes.func.isRequired,
	/**
	 * Called when the plugin installer is skipped.
	 */
	onSkip: PropTypes.func,
	/**
	 * Text used for the skip installer button.
	 */
	skipText: PropTypes.string,
	/**
	 * If installation should happen automatically, or require user confirmation.
	 */
	autoInstall: PropTypes.bool,
	/**
	 * An array of plugin slugs to install. Must be supported by the onboarding plugins API.
	 */
	pluginSlugs: PropTypes.arrayOf( PropTypes.string ),
};

Plugins.defaultProps = {
	autoInstall: false,
	pluginSlugs: [ 'jetpack', 'woocommerce-services' ],
};

export default compose(
	withSelect( ( select, props ) => {
		const {
			getPluginInstallations,
			getPluginInstallationErrors,
			getPluginActivations,
			getPluginActivationErrors,
			isPluginActivateRequesting,
			isPluginInstallRequesting,
		} = select( 'wc-api' );
		const pluginSlugs = props.pluginSlugs || Plugins.defaultProps.pluginSlugs;

		const isRequesting = isPluginActivateRequesting() || isPluginInstallRequesting();

		const activationErrors = getPluginActivationErrors( pluginSlugs );
		const activatedPlugins = Object.keys( getPluginActivations( pluginSlugs ) );
		const installationErrors = getPluginInstallationErrors( pluginSlugs );
		const installedPlugins = Object.keys( getPluginInstallations( pluginSlugs ) );

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
)( Plugins );
