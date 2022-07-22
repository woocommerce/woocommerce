/**
 * External dependencies
 */
import {
	Popover,
	Card,
	CardBody,
	Spinner,
	Button,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Phone from 'gridicons/dist/phone';
import classNames from 'classnames';
import apiFetch from '@wordpress/api-fetch';
import interpolateComponents from '@automattic/interpolate-components';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	WC_ADMIN_NAMESPACE,
	PLUGINS_STORE_NAME,
	useUser,
} from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';
import { createErrorNotice } from '@woocommerce/data/src/plugins/actions';
import type { FunctionComponent } from 'react';

/**
 * Internal dependencies
 */
import JetpackLogo from './images/jetpack-logo.png';
import WcsNotice from './images/wcs-notice.png';
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

export const AppLogin: FunctionComponent< AppLoginProps > = ( {
	jetpackInstallState,
	onClickInstall,
	isBusy,
} ) => {
	const [ isLoadingQr, setIsLoadingQr ] = useState( false );

	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );

	const [ isSuccess, setIsSuccess ] = useState( false );

	const { createNotice } = useDispatch( 'core/notices' );

	// wrapper currently doesn't check for jetpack user connected since it's not populated in any WP data stores that we can reach from here
	// const [ isJetpackUserConnected, setIsJetpackUserConnected ] =
	// 	useState( false );

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

	const menuClick = () => {
		setIsPopoverVisible( true );
		// Todo: recordEvent()
	};

	useEffect( () => {
		if ( jetpackInstallState === 'activated' ) {
		}
	}, [ jetpackInstallState ] );

	const JetpackStatus = () => {
		return (
			<div className="jetpack-container">
				<div>
					<div className="">
						<img
							className="jetpack-logo"
							src={ JetpackLogo }
							alt=""
						/>
						<img className="wcs-notice" src={ WcsNotice } alt="" />
					</div>
				</div>
				<div>
					<p className="install-header">
						{ __(
							'Install and connect your store to have access to a magic link QR code!',
							'woocommerce'
						) }
					</p>
					<p className="install-footer">
						{ interpolateComponents( {
							mixedString: __(
								'By clicking "Install Jetpack and connect", you agree to the {{ tosLink /}}',
								'woocommerce'
							),
							components: {
								tosLink: (
									<a
										href="https://wordpress.com/tos/"
										target="_blank"
										rel="noreferrer"
									>
										{ __(
											'Terms of Service',
											'woocommerce'
										) }
									</a>
								),
							},
						} ) }
					</p>
					<Button
						variant="primary"
						onClick={ () => {
							onClickInstall();
						} }
					>
						{ __( 'Install Jetpack and connect', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		);
	};

	const Success = () => {
		return (
			<>
				<div
					style={ {
						textAlign: 'center',
					} }
				>
					{ __( 'Login link is sent to your email.', 'woocommerce' ) }
				</div>
			</>
		);
	};

	return (
		<div>
			<Button
				className={ classNames(
					'woocommerce-layout__activity-panel-tab',
					isPopoverVisible ? 'is-opened' : ''
				) }
				onClick={ menuClick }
			>
				<Phone />
				{ __( 'App login', 'woocommerce' ) }
			</Button>
			{ isPopoverVisible && (
				<Popover
					focusOnMount="container"
					position="bottom center"
					onClose={ () => setIsPopoverVisible( false ) }
				>
					<Card className="qrcode-container">
						<CardBody>
							{ isBusy ? (
								<Spinner />
							) : (
								<>
									{ jetpackInstallState === 'activated' ? (
										<>
											{ isSuccess ? (
												<Success />
											) : (
												<Button
													variant="primary"
													isBusy={ isLoadingQr }
													disabled={ isLoadingQr }
													onClick={ fetchQr }
												>
													Get magic link
												</Button>
											) }
										</>
									) : (
										<JetpackStatus></JetpackStatus>
									) }
								</>
							) }
						</CardBody>
					</Card>
				</Popover>
			) }
		</div>
	);
};

// below largely copied from install-jetpack-cta.js, we should refactor this component to be reusable since this pattern is used a lot
// just need to extract out the part where we render the child and make it composable
export const AppLoginWrapper = () => {
	const { currentUserCan } = useUser();
	const {
		canUserInstallPlugins,
		jetpackInstallState,
		isBusy,
		jetpackConnectionData,
	} = useSelect( ( select ) => {
		const {
			getPluginInstallState,
			isPluginsRequesting,
			getJetpackConnectionData,
		} = select( PLUGINS_STORE_NAME );
		const installState = getPluginInstallState( 'jetpack' );
		const busyState =
			isPluginsRequesting( 'getJetpackConnectUrl' ) ||
			isPluginsRequesting( 'installPlugins' ) ||
			isPluginsRequesting( 'activatePlugins' );

		return {
			jetpackConnectionData: getJetpackConnectionData(),
			isBusy: busyState,
			jetpackInstallState: installState,
			canUserInstallPlugins: currentUserCan( 'install_plugins' ),
		};
	} );

	const { installJetpackAndConnect } = useDispatch( PLUGINS_STORE_NAME );

	if ( ! canUserInstallPlugins ) {
		return null;
	}

	const onClickInstall = () => {
		installJetpackAndConnect( createErrorNotice, getAdminLink );
	};

	// eslint-disable-next-line no-console
	console.log( 'jetpack connection data', jetpackConnectionData );
	return (
		<AppLogin
			jetpackInstallState={ jetpackInstallState }
			isBusy={ isBusy }
			onClickInstall={ onClickInstall }
		/>
	);
};

interface AppLoginProps {
	jetpackInstallState: 'activated' | 'installed' | 'unavailable';
	isBusy: boolean;
	onClickInstall: () => void;
}
