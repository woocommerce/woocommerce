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
import { SyntheticEvent, useCallback } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';
import type {
	InstallPluginsResponse,
	PluginSelectors,
	PluginActions,
} from '@woocommerce/data';

type PluginsProps = {
	onComplete: (
		activePlugins: string[],
		response: InstallPluginsResponse
	) => void;
	onError: ( errors: unknown, response: InstallPluginsResponse ) => void;
	onClick?: () => void;
	onSkip?: () => void;
	skipText?: string;
	autoInstall?: boolean;
	pluginSlugs?: string[];
	onAbort?: () => void;
	abortText?: string;
	installText?: string;
	installButtonVariant?: Button.BaseProps[ 'variant' ];
	learnMoreLink?: string;
	learnMoreText?: string;
	onLearnMore?: () => void;
};

export const Plugins = ( {
	autoInstall = false,
	onAbort,
	onComplete,
	onError = () => null,
	onClick = () => null,
	pluginSlugs = [ 'woocommerce-services' ],
	onSkip,
	installText = __( 'Install & enable', 'woocommerce' ),
	skipText = __( 'No thanks', 'woocommerce' ),
	abortText = __( 'Abort', 'woocommerce' ),
	installButtonVariant = 'primary',
	learnMoreLink,
	learnMoreText = __( 'Learn more', 'woocommerce' ),
	onLearnMore,
}: PluginsProps ) => {
	const [ hasErrors, setHasErrors ] = useState( false );
	// Tracks action so that multiple instances of this button don't all light up when one is clicked
	const [ hasBeenClicked, setHasBeenClicked ] = useState( false );
	const { installAndActivatePlugins }: PluginActions =
		useDispatch( PLUGINS_STORE_NAME );
	const { isRequesting } = useSelect( ( select ) => {
		const {
			getActivePlugins,
			getInstalledPlugins,
			isPluginsRequesting,
		}: PluginSelectors = select( PLUGINS_STORE_NAME );

		return {
			isRequesting:
				isPluginsRequesting( 'activatePlugins' ) ||
				isPluginsRequesting( 'installPlugins' ),
			activePlugins: getActivePlugins(),
			installedPlugins: getInstalledPlugins(),
		};
	}, [] );

	const handleErrors = useCallback(
		( errors: unknown, response: InstallPluginsResponse ) => {
			setHasErrors( true );

			onError( errors, response );
		},
		[ onError ]
	);

	const handleSuccess = useCallback(
		( plugins: string[], response: InstallPluginsResponse ) => {
			onComplete( plugins, response );
		},
		[ onComplete ]
	);

	const installAndActivate = useCallback(
		async ( event?: SyntheticEvent< HTMLAnchorElement > ) => {
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
					setHasBeenClicked( false );
					handleErrors( response.errors, response );
				} );
		},
		[
			handleErrors,
			handleSuccess,
			installAndActivatePlugins,
			isRequesting,
			pluginSlugs,
		]
	);

	useEffect( () => {
		if ( autoInstall ) {
			installAndActivate();
		}
	}, [ autoInstall, installAndActivate ] );

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
				isBusy={ isRequesting && hasBeenClicked }
				variant={
					isRequesting && hasBeenClicked
						? 'primary' // set to primary when busy, the other variants look weird when combined with isBusy
						: installButtonVariant
				}
				disabled={ isRequesting && hasBeenClicked }
				onClick={ () => {
					onClick();
					setHasBeenClicked( true );
					installAndActivate();
				} }
			>
				{ installText }
			</Button>
			{ onSkip && (
				<Button isTertiary onClick={ onSkip }>
					{ skipText }
				</Button>
			) }
			{ learnMoreLink && (
				<a href={ learnMoreLink } target="_blank" rel="noreferrer">
					<Button isTertiary onClick={ onLearnMore }>
						{ learnMoreText }
					</Button>
				</a>
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
