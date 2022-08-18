/**
 * External dependencies
 */
import {
	Card,
	CardBody,
	CardFooter,
	Spinner,
	Button,
	CardHeader,
	Flex,
	FlexItem,
	CardMedia,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import interpolateComponents from '@automattic/interpolate-components';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	WC_ADMIN_NAMESPACE,
	PLUGINS_STORE_NAME,
	useUser,
} from '@woocommerce/data';
import { createErrorNotice } from '@woocommerce/data/src/plugins/actions';
import type { ReactNode, FunctionComponent } from 'react';

/**
 * Internal dependencies
 */
import WooMobileAppIlustration from './images/woo-mobile-app-illustration-phone.png';
import './style.scss';

export type MagicLinkResponse = {
	data: unknown;
	code: string;
	message: string;
} & Response;

const sendMagicLink = () => {
	return apiFetch< MagicLinkResponse >( {
		path: `${ WC_ADMIN_NAMESPACE }/login-qr/send-magic-link`,
	} );
};

const WooMobileAppWelcomePage = ( { children }: { children: ReactNode } ) => (
	<div className="woo-mobile-app-page is-narrow">
		<Card>
			<CardHeader>
				<h1>{ __( 'WooCommerce Mobile App', 'woocommerce' ) }</h1>
			</CardHeader>
			<CardBody className="woo-mobile-app-page-body">
				<Flex>
					<FlexItem>
						<h2 className="woo-mobile-app-page-body-title-text">
							{ __(
								'Run your store from anywhere',
								'woocommerce'
							) }
						</h2>
						<div className="woo-mobile-app-page-blurb">
							<p>
								{ __(
									'Manage your business on the go with the WooCommerce Mobile App.',
									'woocommerce'
								) }
							</p>
							<p>
								{ __(
									'Create products, process orders, and keep an eye on key stats in real-time.',
									'woocommerce'
								) }
							</p>
							<p>
								{ interpolateComponents( {
									mixedString: __(
										'The WooCommerce Mobile App is free and powered by {{ jetpackDocs /}}.',
										'woocommerce'
									),
									components: {
										jetpackDocs: (
											<a
												target="_blank"
												rel="noreferrer"
												href="https://docs.woocommerce.com/document/jetpack-setup-instructions-for-the-woocommerce-mobile-app/"
											>
												{ __(
													'Jetpack',
													'woocommerce'
												) }
											</a>
										),
									},
								} ) }
							</p>
						</div>
					</FlexItem>
					<div className="woo-mobile-app-illustration-wrapper">
						<CardMedia>
							<img
								src={ WooMobileAppIlustration }
								alt="WooCommerce Mobile App Illustration"
							/>
						</CardMedia>
					</div>
				</Flex>
			</CardBody>
			<CardFooter>{ children }</CardFooter>
		</Card>
	</div>
);

const MagicLinkDialog = () => {
	const [ isLoadingQr, setIsLoadingQr ] = useState( false );

	const [ isSuccess, setIsSuccess ] = useState( false );

	const { createNotice } = useDispatch( 'core/notices' );

	const fetchQr = () => {
		setIsLoadingQr( true );
		sendMagicLink()
			.then( ( response ) => {
				if ( response.code === 'success' ) {
					setIsSuccess( true );
				} else {
					createNotice(
						'error',
						__( 'Sorry, an unknown error occured.', 'woocommerce' )
					);
				}
			} )
			.catch( ( response ) => {
				if ( response.code === 'error_sending_mobile_magic_link' ) {
					createNotice(
						'error',
						__(
							'Sorry, there was an error trying to request for a magic link',
							'woocommerce'
						)
					);
				} else if (
					response.code === 'invalid_user_permission_view_admin'
				) {
					createNotice(
						'error',
						__(
							"Sorry, your account don't have sufficient permission.",
							'woocommerce'
						)
					);
				} else if ( response.code === 'jetpack_not_connected' ) {
					createNotice( 'error', response.message );
				} else {
					createNotice( 'error', response.message );
				}
			} )
			.finally( () => setIsLoadingQr( false ) );
	};

	if ( isSuccess ) {
		return (
			<p>
				{ __(
					'The magic link has been sent to the email address associated with your WordPress.com account.',
					'woocommerce'
				) }
				<br />
				{ __(
					'Please open the email on your smartphone.',
					'woocommerce'
				) }
			</p>
		);
	}
	return (
		<Flex direction="column" gap={ 4 }>
			<FlexItem>
				<p>
					{ __(
						'To help you get started with WooCommerce Mobile App right away, we can send you an email with a magic link that will log you in on your smartphone.',
						'woocommerce'
					) }
				</p>
			</FlexItem>
			<FlexItem>
				<Button
					isBusy={ isLoadingQr }
					variant="primary"
					onClick={ fetchQr }
				>
					Send magic link
				</Button>
			</FlexItem>
		</Flex>
	);
};

const JetpackInstallationInProgress = () => {
	return (
		<>
			<p>
				{ __(
					'Please wait while we install Jetpack and connect to your WordPress Account …',
					'woocommerce'
				) }
			</p>
			<Spinner />
		</>
	);
};

const JetpackNotAvailable = () => {
	return (
		<div>
			<p>
				{ __(
					'Sorry, it seems like you do not have permission to install Jetpack on this installation of WordPress.',
					'woocommerce'
				) }
				<br />
				{ __(
					'WooCommerce Mobile App relies on Jetpack in order to communicate with your store, so please contact a site administrator to assist with this.',
					'woocommerce'
				) }
			</p>
		</div>
	);
};

const JetpackInstalledButNotActivated: FunctionComponent< {
	installJetpackHandler: () => void;
} > = ( { installJetpackHandler } ) => {
	return (
		<>
			<p>
				{ __(
					'Jetpack is installed but not activated. Please activate Jetpack in order to use WooCommerce Mobile App',
					'woocommerce'
				) }
			</p>
			<Button variant="primary" onClick={ installJetpackHandler }>
				{ __( 'Activate', 'woocommerce' ) }
			</Button>
		</>
	);
};

const JetpackNotInstalled: FunctionComponent< {
	installJetpackHandler: () => void;
} > = ( { installJetpackHandler } ) => {
	return (
		<>
			<p>
				{ __(
					'Jetpack is not installed. Please install Jetpack in order to use WooCommerce Mobile App',
					'woocommerce'
				) }
			</p>
			<Button variant="primary" onClick={ installJetpackHandler }>
				{ __( 'Install Jetpack', 'woocommerce' ) }
			</Button>
		</>
	);
};

const JetpackActivatedButNotConnected: FunctionComponent< {
	onClickInstall: () => void;
} > = ( { onClickInstall } ) => {
	return (
		<div>
			<p>
				{ __(
					'Jetpack is not connected to a WordPress.com account. Please connect to your WordPress.com account in order to use WooCommerce Mobile App',
					'woocommerce'
				) }
			</p>
			<Button variant="primary" onClick={ onClickInstall }>
				{ __( 'Connect Jetpack', 'woocommerce' ) }
			</Button>
		</div>
	);
};

export const AppLoginLogic = () => {
	const { currentUserCan } = useUser();
	const {
		canUserInstallPlugins,
		jetpackInstallState,
		jetpackConnectionData,
	} = useSelect( ( select ) => {
		const { getPluginInstallState, getJetpackConnectionData } =
			select( PLUGINS_STORE_NAME );
		const installState = getPluginInstallState( 'jetpack' );

		return {
			jetpackConnectionData: getJetpackConnectionData(),
			jetpackInstallState: installState,
			canUserInstallPlugins: currentUserCan( 'install_plugins' ),
		};
	} );

	const { installJetpackAndConnect } = useDispatch( PLUGINS_STORE_NAME );
	const [ isSettingUpJetpack, setIsSettingUpJetpack ] = useState( false );

	/**
	 * Installs, Activates, and Connects Jetpack - starting wherever hasn't been completed
	 */
	const onClickInstall = () => {
		const thisUrl = window.location.href;
		installJetpackAndConnect( createErrorNotice, () => thisUrl );
		// Navigates to localhost/wp-admin/admin.php?page=jetpack&action=register&_wpnonce=1111111111&redirect=https%3A%2F%2Flocalhost%2Fwp-admin%2Fadmin.php%3Fpage%3Dwc-admin&from=woocommerce-onboarding&calypso_env=production
		setIsSettingUpJetpack( true );
	};

	if ( isSettingUpJetpack ) {
		return <JetpackInstallationInProgress />;
	}

	if ( ! canUserInstallPlugins ) {
		return <JetpackNotAvailable />;
	}

	if ( jetpackInstallState === 'unavailable' ) {
		// Most users should see this if they don't have Jetpack set up
		return <JetpackNotInstalled installJetpackHandler={ onClickInstall } />;
	}

	if ( jetpackInstallState === 'installed' ) {
		// Weird edge case where Jetpack is installed but not activated
		return (
			<JetpackInstalledButNotActivated
				installJetpackHandler={ onClickInstall }
			/>
		);
	}

	if ( jetpackInstallState === 'activated' ) {
		if (
			// Jetpack can be installed and activated but not connected to a WordPress.com user account, this handles that
			jetpackConnectionData &&
			! jetpackConnectionData.currentUser.isConnected
		) {
			return (
				<JetpackActivatedButNotConnected
					onClickInstall={ onClickInstall }
				/>
			);
		}
		// Happy path if Jetpack prerequisites are met
		return <MagicLinkDialog />;
	}

	return null; // never gets here and Typescript knows that but still complains if this isn't here
};

export const AppLoginWrapper = () => {
	return (
		<WooMobileAppWelcomePage>
			<AppLoginLogic />
		</WooMobileAppWelcomePage>
	);
};

export default AppLoginWrapper;
