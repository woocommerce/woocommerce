/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import {
	createElement,
	Fragment,
	useState,
	useEffect,
} from '@wordpress/element';
import { SyntheticEvent } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { PLUGINS_STORE_NAME, InstallPluginsResponse } from '@woocommerce/data';

type PluginsProps = {
	onComplete: (
		activePlugins: string[],
		response: InstallPluginsResponse
	) => void;
	onError: ( errors: unknown, response: InstallPluginsResponse ) => void;
	onSkip?: () => void;
	skipText?: string;
	autoInstall?: boolean;
	pluginSlugs?: string[];
	onAbort?: () => void;
	abortText?: string;
};

export const Plugins = ( {
	autoInstall = false,
	onAbort,
	onComplete,
	onError = () => null,
	pluginSlugs = [ 'jetpack', 'woocommerce-services' ],
	onSkip,
	skipText = __( 'No thanks', 'woocommerce' ),
	abortText = __( 'Abort', 'woocommerce' ),
}: PluginsProps ) => {
	const [ hasErrors, setHasErrors ] = useState( false );
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );
	const { isRequesting } = useSelect( ( select ) => {
		const { getActivePlugins, getInstalledPlugins, isPluginsRequesting } =
			select( PLUGINS_STORE_NAME );

		return {
			isRequesting:
				isPluginsRequesting( 'activatePlugins' ) ||
				isPluginsRequesting( 'installPlugins' ),
			activePlugins: getActivePlugins(),
			installedPlugins: getInstalledPlugins(),
		};
	} );

	const handleErrors = (
		errors: unknown,
		response: InstallPluginsResponse
	) => {
		setHasErrors( true );

		onError( errors, response );
	};

	const handleSuccess = (
		plugins: string[],
		response: InstallPluginsResponse
	) => {
		onComplete( plugins, response );
	};

	const installAndActivate = async (
		event?: SyntheticEvent< HTMLAnchorElement >
	) => {
		if ( event ) {
			event.preventDefault();
		}

		// Avoid double activating.
		if ( isRequesting ) {
			return false;
		}

		installAndActivatePlugins( pluginSlugs )
			.then( ( response ) => {
				handleSuccess( response.data.activated, response );
			} )
			.catch( ( response ) => {
				handleErrors( response.errors, response );
			} );
	};

	useEffect( () => {
		if ( autoInstall ) {
			installAndActivate();
		}
	}, [] );

	if ( hasErrors ) {
		return (
			<>
				<Button
					isPrimary
					isBusy={ isRequesting }
					onClick={ installAndActivate }
				>
					{ __( 'Retry', 'woocommerce' ) }
				</Button>
				{ onSkip && (
					<Button onClick={ onSkip }>
						{ __( 'Continue without installing', 'woocommerce' ) }
					</Button>
				) }
			</>
		);
	}

	if ( autoInstall ) {
		return null;
	}

	if ( ! pluginSlugs.length ) {
		return (
			<Fragment>
				<Button isPrimary isBusy={ isRequesting } onClick={ onSkip }>
					{ __( 'Continue', 'woocommerce' ) }
				</Button>
			</Fragment>
		);
	}

	return (
		<>
			<Button
				isBusy={ isRequesting }
				isPrimary
				onClick={ installAndActivate }
			>
				{ __( 'Install & enable', 'woocommerce' ) }
			</Button>
			{ onSkip && (
				<Button isTertiary onClick={ onSkip }>
					{ skipText }
				</Button>
			) }
			{ onAbort && (
				<Button isTertiary onClick={ onAbort }>
					{ abortText }
				</Button>
			) }
		</>
	);
};

export default Plugins;
