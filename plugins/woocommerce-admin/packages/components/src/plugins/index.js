/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createElement, Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { withSelect, withDispatch } from '@wordpress/data';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';

export class Plugins extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			hasErrors: false,
		};

		this.installAndActivate = this.installAndActivate.bind( this );
		this.skipInstaller = this.skipInstaller.bind( this );
		this.handleErrors = this.handleErrors.bind( this );
		this.handleSuccess = this.handleSuccess.bind( this );
	}

	componentDidMount() {
		const { autoInstall } = this.props;

		if ( autoInstall ) {
			this.installAndActivate();
		}
	}

	async installAndActivate( event ) {
		if ( event ) {
			event.preventDefault();
		}

		const {
			installAndActivatePlugins,
			isRequesting,
			pluginSlugs,
		} = this.props;

		// Avoid double activating.
		if ( isRequesting ) {
			return false;
		}

		installAndActivatePlugins( pluginSlugs )
			.then( ( response ) => {
				this.handleSuccess( response.data.activated, response );
			} )
			.catch( ( response ) => {
				this.handleErrors( response.errors, response );
			} );
	}

	handleErrors( errors, response ) {
		const { onError } = this.props;

		this.setState( { hasErrors: true } );
		onError( errors, response );
	}

	handleSuccess( activePlugins, response ) {
		const { onComplete } = this.props;
		onComplete( activePlugins, response );
	}

	skipInstaller() {
		this.props.onSkip();
	}

	render() {
		const {
			isRequesting,
			skipText,
			autoInstall,
			pluginSlugs,
			onAbort,
			abortText,
		} = this.props;
		const { hasErrors } = this.state;

		if ( hasErrors ) {
			return (
				<Fragment>
					<Button
						isPrimary
						isBusy={ isRequesting }
						onClick={ this.installAndActivate }
					>
						{ __( 'Retry', 'woocommerce-admin' ) }
					</Button>
					<Button onClick={ this.skipInstaller }>
						{ __(
							'Continue without installing',
							'woocommerce-admin'
						) }
					</Button>
				</Fragment>
			);
		}

		if ( autoInstall ) {
			return null;
		}

		if ( pluginSlugs.length === 0 ) {
			return (
				<Fragment>
					<Button
						isPrimary
						isBusy={ isRequesting }
						onClick={ this.skipInstaller }
					>
						{ __( 'Continue', 'woocommerce-admin' ) }
					</Button>
				</Fragment>
			);
		}

		return (
			<Fragment>
				<Button
					isBusy={ isRequesting }
					isPrimary
					onClick={ this.installAndActivate }
				>
					{ __( 'Install & enable', 'woocommerce-admin' ) }
				</Button>
				<Button isTertiary onClick={ this.skipInstaller }>
					{ skipText || __( 'No thanks', 'woocommerce-admin' ) }
				</Button>
				{ onAbort && (
					<Button isTertiary onClick={ onAbort }>
						{ abortText || __( 'Abort', 'woocommerce-admin' ) }
					</Button>
				) }
			</Fragment>
		);
	}
}

Plugins.propTypes = {
	/**
	 * Called when the plugin installer is successfully completed.
	 */
	onComplete: PropTypes.func.isRequired,
	/**
	 * Called when the plugin installer completes with an error.
	 */
	onError: PropTypes.func,
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
	 * An array of plugin slugs to install.
	 */
	pluginSlugs: PropTypes.arrayOf( PropTypes.string ),
	/**
	 * Called when the plugin connection is aborted.
	 */
	onAbort: PropTypes.func,
	/**
	 * Text used for the abort connection button.
	 */
	abortText: PropTypes.string,
};

Plugins.defaultProps = {
	autoInstall: false,
	onError: () => {},
	onSkip: () => {},
	pluginSlugs: [ 'jetpack', 'woocommerce-services' ],
};

export default compose(
	withSelect( ( select ) => {
		const {
			getActivePlugins,
			getInstalledPlugins,
			isPluginsRequesting,
		} = select( PLUGINS_STORE_NAME );

		const isRequesting =
			isPluginsRequesting( 'activatePlugins' ) ||
			isPluginsRequesting( 'installPlugins' );

		return {
			isRequesting,
			activePlugins: getActivePlugins(),
			installedPlugins: getInstalledPlugins(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { installAndActivatePlugins } = dispatch( PLUGINS_STORE_NAME );

		return {
			installAndActivatePlugins,
		};
	} )
)( Plugins );
