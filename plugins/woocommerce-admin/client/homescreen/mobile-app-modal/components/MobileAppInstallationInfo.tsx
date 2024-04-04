/**
 * External dependencies
 */
import { QRCodeSVG } from 'qrcode.react';

export const MobileAppInstallationInfo = () => {
	return (
		<div>
			<QRCodeSVG
				value={
					'woocommerce.com/mobile/?utm_source=wc_onboarding_mobile_task'
				}
				size={ 140 }
			/>
		</div>
	);
};
