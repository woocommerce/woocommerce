/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, Card, CardBody, CardFooter } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import interpolateComponents from 'interpolate-components';
import { Link } from '@woocommerce/components';
import {
	pluginNames,
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	OPTIONS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import { Benefits } from './benefits';
import { createNoticesFromResponse } from '../../../lib/notices';
import Logo from './logo';

export const BenefitsLayout = ( { goToNextStep } ) => {
	const {
		activePlugins,
		isJetpackConnected,
		isProfileItemsError,
		isUpdatingProfileItems,
	} = useSelect( ( select ) => {
		const { getOnboardingError, isOnboardingRequesting } = select(
			ONBOARDING_STORE_NAME
		);
		const { getActivePlugins } = select( PLUGINS_STORE_NAME );

		return {
			activePlugins: getActivePlugins(),
			isJetpackConnected: select(
				PLUGINS_STORE_NAME
			).isJetpackConnected(),
			isProfileItemsError: Boolean(
				getOnboardingError( 'updateProfileItems' )
			),
			isUpdatingProfileItems: isOnboardingRequesting(
				'updateProfileItems'
			),
		};
	} );

	const { createNotice } = useDispatch( 'core/notices' );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { updateProfileItems } = useDispatch( ONBOARDING_STORE_NAME );
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );

	const pluginsRemaining = [ 'jetpack', 'woocommerce-services' ].filter(
		( plugin ) => ! activePlugins.includes( plugin )
	);

	// Cache the initial plugin list so we don't change benefits midway through activation.
	const [ pluginsToInstall ] = useState( pluginsRemaining );
	const [ isInstalling, setIsInstalling ] = useState( false );

	const isJetpackActive = ! pluginsToInstall.includes( 'jetpack' );
	const isWcsActive = ! pluginsToInstall.includes( 'woocommerce-services' );
	const isComplete = isWcsActive && isJetpackActive && isJetpackConnected;

	useEffect( () => {
		// Skip this step if already complete.
		if ( isComplete ) {
			goToNextStep();
			return;
		}

		recordEvent( 'storeprofiler_plugins_to_install', {
			plugins: pluginsToInstall,
		} );
	}, [] );

	if ( isComplete ) {
		return null;
	}

	const skipPluginInstall = async () => {
		const plugins = isJetpackActive ? 'skipped-wcs' : 'skipped';
		await updateProfileItems( { plugins } );

		if ( isProfileItemsError ) {
			createNotice(
				'error',
				__(
					'There was a problem updating your preferences',
					'woocommerce-admin'
				)
			);
		} else {
			recordEvent( 'storeprofiler_install_plugins', {
				install: false,
				plugins,
			} );
		}

		goToNextStep();
	};

	const startPluginInstall = () => {
		const plugins = isJetpackActive ? 'installed-wcs' : 'installed';

		setIsInstalling( true );

		recordEvent( 'storeprofiler_install_plugins', {
			install: true,
			plugins,
		} );

		Promise.all( [
			pluginsToInstall.length
				? installAndActivatePlugins( pluginsToInstall )
				: null,
			updateProfileItems( { plugins } ),
			updateOptions( {
				woocommerce_setup_jetpack_opted_in: true,
			} ),
		] )
			.then( () => {
				setIsInstalling( false );
				goToNextStep();
			} )
			.catch( ( pluginError, profileError ) => {
				if ( pluginError ) {
					createNoticesFromResponse( pluginError );
				}
				if ( profileError ) {
					createNotice(
						'error',
						__(
							'There was a problem updating your preferences',
							'woocommerce-admin'
						)
					);
				}
				setIsInstalling( false );
				goToNextStep();
			} );
	};

	const pluginNamesString = pluginsToInstall
		.map( ( pluginSlug ) => pluginNames[ pluginSlug ] )
		.join( ' ' + __( 'and', 'woocommerce-admin' ) + ' ' );
	const isAcceptingTos = ! isWcsActive;
	const pluralizedPlugins = _n(
		'plugin',
		'plugins',
		pluginsToInstall.length,
		'woocommerce-admin'
	);

	return (
		<Card className="woocommerce-profile-wizard__benefits-card">
			<CardBody justify="center">
				<Logo />
				<div className="woocommerce-profile-wizard__step-header">
					<Text variant="title.small" as="h2">
						{ sprintf(
							/* translators: %s = names of plugins to install, e.g. Jetpack and Woocommerce Services */
							__(
								'Enhance your store with %s',
								'woocommerce-admin'
							),
							pluginNamesString
						) }
					</Text>
				</div>
				<Benefits
					isJetpackSetup={ isJetpackActive && isJetpackConnected }
					isWcsSetup={ isWcsActive }
				/>
			</CardBody>
			<CardFooter isBorderless justify="center">
				<Button
					isPrimary
					isBusy={ isInstalling }
					disabled={ isUpdatingProfileItems || isInstalling }
					onClick={ startPluginInstall }
				>
					{ __( 'Yes please!', 'woocommerce-admin' ) }
				</Button>
				<Button
					isSecondary
					isBusy={ isUpdatingProfileItems && ! isInstalling }
					disabled={ isUpdatingProfileItems || isInstalling }
					className="woocommerce-profile-wizard__skip"
					onClick={ skipPluginInstall }
				>
					{ __( 'No thanks', 'woocommerce-admin' ) }
				</Button>
			</CardFooter>

			{ !! pluginsToInstall.length && (
				<CardFooter isBorderless justify="center">
					<p className="woocommerce-profile-wizard__benefits-install-notice">
						{ isAcceptingTos
							? interpolateComponents( {
									mixedString: sprintf(
										/* translators: %1$s: names of plugins to install, e.g. Jetpack and Woocommerce Services, %2$s: singular or plural form of 'plugins'   */
										__(
											'%1$s %2$s will be installed & activated for free, and you agree to our {{link}}Terms of Service{{/link}}.',
											'woocommerce-admin'
										),
										pluginNamesString,
										pluralizedPlugins
									),
									components: {
										link: (
											<Link
												href="https://wordpress.com/tos/"
												target="_blank"
												type="external"
											/>
										),
									},
							  } )
							: sprintf(
									/* translators: %1$s: names of plugins to install, e.g. Jetpack and Woocommerce Services, %2$s: singular or plural form of 'plugins'   */
									__(
										'%1$s %2$s will be installed & activated for free.',
										'woocommerce-admin'
									),
									pluginNamesString,
									pluralizedPlugins
							  ) }
					</p>
				</CardFooter>
			) }
		</Card>
	);
};
