/**
 * External dependencies
 */
import { QRCodeSVG } from 'qrcode.react';
import React from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';

export const MobileAppLoginInfo = ( {
	loginUrl,
}: {
	loginUrl: string | undefined;
} ) => {
	// TODO: update the minimum app version
	return (
		<div>
			{ loginUrl && (
				<div>
					<QRCodeSVG value={ loginUrl } size={ 140 } />
					<p>
						{ __(
							'The app version needs to be 15.8 or above to sign in with this link.',
							'woocommerce'
						) }
					</p>
				</div>
			) }
			<div>
				{ interpolateComponents( {
					mixedString: __(
						'Any troubles signing in? Check out the {{link}}FAQ{{/link}}.',
						'woocommerce'
					),
					components: {
						link: (
							<Link
								href="https://woocommerce.com/document/android-ios-apps-login-help-faq/"
								target="_blank"
								type="external"
								onClick={ () => {
									// TODO: track event
								} }
							/>
						),
						strong: <strong />,
					},
				} ) }
			</div>
		</div>
	);
};
