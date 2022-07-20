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
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';
import interpolateComponents from '@automattic/interpolate-components';

/**
 * Internal dependencies
 */
import JetpackLogo from './images/jetpack-logo.png';
import WcsNotice from './images/wcs-notice.png';
import './style.scss';

type JetpackStatusResponse = {
	installed: boolean;
	activated: boolean;
	connected: boolean;
	user_connected: boolean;
};

type GenerateQRResponse = {
	image: string;
};

const getJetpackStatus = () => {
	return apiFetch< JetpackStatusResponse >( {
		path: `${ WC_ADMIN_NAMESPACE }/login-qr/jetpack_status`,
	} );
};

const generateQRCode = () => {
	return apiFetch< GenerateQRResponse >( {
		path: `${ WC_ADMIN_NAMESPACE }/login-qr/generate_qr`,
	} );
};

export const LoginQR = () => {
	const [ isLoadingJetpack, setIsLoadingJetpack ] = useState( true );
	const [ isLoadingQr, setIsLoadingQr ] = useState( true );
	const [ isFetchedJetpack, setIsFetchedJetpack ] = useState( false );
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );
	const [ isJetpackInstalled, setIsJetpackInstalled ] = useState( false );
	const [ isJetpackActivated, setIsJetpackActivated ] = useState( false );
	const [ qrImageSrc, setQrImageSrc ] = useState( '' );
	const [ isJetpackUserConnected, setIsJetpackUserConnected ] =
		useState( false );

	const isJetpackReady =
		isJetpackInstalled && isJetpackActivated && isJetpackUserConnected;

	const fetchQr = () => {
		setIsLoadingQr( true );
		generateQRCode()
			.then( ( response ) => {
				setQrImageSrc( response.image );
			} )
			.finally( () => setIsLoadingQr( false ) );
	};

	useEffect( () => {
		if ( isPopoverVisible && ! isFetchedJetpack ) {
			getJetpackStatus()
				.then( ( response ) => {
					setIsJetpackInstalled( response.installed );
					setIsJetpackActivated( response.activated );
					setIsJetpackUserConnected( response.user_connected );
					setIsFetchedJetpack( true );
				} )
				.finally( () => setIsLoadingJetpack( false ) );
		}
	}, [ isFetchedJetpack, isPopoverVisible ] );

	const menuClick = () => {
		setIsPopoverVisible( true );
		// Todo: recordEvent()
	};

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
							setIsJetpackInstalled( true );
							setIsJetpackActivated( true );
							setIsJetpackUserConnected( true );
							fetchQr();
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
							{ isLoadingJetpack ? (
								<Spinner />
							) : (
								<>
									{ isJetpackReady ? (
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
