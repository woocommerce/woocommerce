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
import { recordEvent } from '@woocommerce/tracks';
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

type GenerateQRResponse = {
	image: string;
};

// i'd rather not add another API endpoint since I'm pretty sure this can be queried elsewhere but I haven't gone and found out where
// const getJetpackStatus = () => {
// 	return apiFetch< JetpackStatusResponse >( {
// 		path: `${ WC_ADMIN_NAMESPACE }/login-qr/jetpack_status`,
// 	} );
// };

const generateQRCode = () => {
	return apiFetch< GenerateQRResponse >( {
		path: `${ WC_ADMIN_NAMESPACE }/login-qr/generate_qr`,
	} );
};

export const AppLogin: FunctionComponent< AppLoginProps > = ( {
	jetpackInstallState,
	onClickInstall,
	isBusy,
} ) => {
	const [ isLoadingQr, setIsLoadingQr ] = useState( true );

	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );

	const [ qrImageSrc, setQrImageSrc ] = useState( '' );

	// wrapper currently doesn't check for jetpack user connected since it's not populated in any WP data stores that we can reach from here
	// const [ isJetpackUserConnected, setIsJetpackUserConnected ] =
	// 	useState( false );

	const fetchQr = () => {
		setIsLoadingQr( true );
		generateQRCode()
			.then( ( response ) => {
				setQrImageSrc( response.image );
			} )
			.finally( () => setIsLoadingQr( false ) );
	};

	const menuClick = () => {
		setIsPopoverVisible( true );
		// Todo: recordEvent()
	};

	useEffect( () => {
		if ( jetpackInstallState === 'activated' ) {
			fetchQr();
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

	const QRCode = () => {
		return (
			<>
				<img alt="QRCode" src={ qrImageSrc }></img>
				<div
					style={ {
						textAlign: 'center',
					} }
				>
					You should scan this to get free WooCommerce store points!
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
											{ isLoadingQr ? (
												<Spinner />
											) : (
												<QRCode></QRCode>
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
	const { canUserInstallPlugins, jetpackInstallState, isBusy } = useSelect(
		( select ) => {
			const { getPluginInstallState, isPluginsRequesting } =
				select( PLUGINS_STORE_NAME );
			const installState = getPluginInstallState( 'jetpack' );
			const busyState =
				isPluginsRequesting( 'getJetpackConnectUrl' ) ||
				isPluginsRequesting( 'installPlugins' ) ||
				isPluginsRequesting( 'activatePlugins' );

			return {
				isBusy: busyState,
				jetpackInstallState: installState,
				canUserInstallPlugins: currentUserCan( 'install_plugins' ),
			};
		}
	);

	const { installJetpackAndConnect } = useDispatch( PLUGINS_STORE_NAME );

	if ( ! canUserInstallPlugins ) {
		return null;
	}

	const onClickInstall = () => {
		installJetpackAndConnect( createErrorNotice, getAdminLink );
	};

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
