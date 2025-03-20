/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import desktopIcon from './icon-desktop.svg';
import desktopActiveIcon from './icon-desktop-active.svg';
import mobileIcon from './icon-mobile.svg';
import mobileActiveIcon from './icon-mobile-active.svg';

export const DEVICE_TYPE_DESKTOP = 'desktop';
export const DEVICE_TYPE_MOBILE = 'mobile';

type EmailPreviewDeviceTypeProps = {
	deviceType: string;
	setDeviceType: ( deviceType: string ) => void;
};

export const EmailPreviewDeviceType: React.FC<
	EmailPreviewDeviceTypeProps
> = ( { deviceType, setDeviceType } ) => {
	const isDesktop = deviceType === DEVICE_TYPE_DESKTOP;
	const isMobile = deviceType === DEVICE_TYPE_MOBILE;
	const setDesktop = () => setDeviceType( DEVICE_TYPE_DESKTOP );
	const setMobile = () => setDeviceType( DEVICE_TYPE_MOBILE );

	return (
		<div className="wc-settings-email-preview-device-type">
			<button
				className={ isDesktop ? 'active' : '' }
				onClick={ setDesktop }
				title={ __( 'Email preview on desktop', 'woocommerce' ) }
				type="button"
			>
				<img
					src={ isDesktop ? desktopActiveIcon : desktopIcon }
					alt={ __( 'Desktop icon', 'woocommerce' ) }
				/>
			</button>
			<button
				className={ isMobile ? 'active' : '' }
				onClick={ setMobile }
				title={ __( 'Mobile preview on desktop', 'woocommerce' ) }
				type="button"
			>
				<img
					src={ isMobile ? mobileActiveIcon : mobileIcon }
					alt={ __( 'Mobile icon', 'woocommerce' ) }
				/>
			</button>
		</div>
	);
};
