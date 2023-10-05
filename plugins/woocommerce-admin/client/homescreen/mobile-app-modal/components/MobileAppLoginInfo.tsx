/**
 * External dependencies
 */
import { QRCodeSVG } from 'qrcode.react';
import React from '@wordpress/element';

export const MobileAppLoginInfo = ( {
	siteUrl,
	username,
}: {
	siteUrl: string | undefined;
	username: string;
} ) => {
	const canShowLoginUrl =
		siteUrl !== undefined && siteUrl.length > 0 && username.length > 0;
	return (
		<div>
			{ canShowLoginUrl ? (
				<QRCodeSVG
					value={ `woocommerce://app-login?siteUrl=${ siteUrl }&username=${ username }` }
					size={ 140 }
				/>
			) : (
				<p>
					Follow the instructions in the app to sign in. Any troubles
					signing in? Check out the
					[FAQ](https://woocommerce.com/document/android-ios-apps-login-help-faq/).
				</p>
			) }
		</div>
	);
};
