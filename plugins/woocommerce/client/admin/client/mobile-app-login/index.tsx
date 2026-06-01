/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { QRDirectLoginCode } from '~/homescreen/mobile-app-modal/components/QRDirectLoginCode';
import WooLogo from '~/core-profiler/components/navigation/woologo';
import './style.scss';

/**
 * URL to the mobile app login help article.
 *
 * Kept in sync with the FAQ link rendered inside `<QRDirectLoginCode />` —
 * both point at the same canonical help page so merchants land in the same
 * place regardless of which troubleshooting affordance they tap.
 */
const FAQ_URL =
	'https://woocommerce.com/document/android-ios-apps-login-help-faq/';

/**
 * Standalone wc-admin page for signing in to the Woo mobile app via QR code.
 *
 * Audience: merchants who already have the Woo mobile app installed on their
 * phone and want a quick, one-shot way to sign in without going through the
 * onboarding modal.
 *
 * This page only builds the Application Password flow. The WordPress.com
 * multi-store flow is intentionally deferred — the single-column layout
 * leaves room below the QR for a future secondary CTA without needing a
 * structural rewrite.
 */
export const MobileAppLoginPage = () => {
	useEffect( () => {
		recordEvent( 'mobile_app_qr_login_page_viewed' );
	}, [] );

	return (
		<div className="woocommerce-mobile-app-login">
			<Card className="woocommerce-mobile-app-login__card">
				<CardBody className="woocommerce-mobile-app-login__body">
					<div
						className="woocommerce-mobile-app-login__logo"
						aria-hidden="true"
					>
						<WooLogo />
					</div>
					<h1 className="woocommerce-mobile-app-login__heading">
						{ __( 'Sign in to the Woo mobile app', 'woocommerce' ) }
					</h1>
					<p className="woocommerce-mobile-app-login__intro">
						{ interpolateComponents( {
							mixedString: __(
								'Open the Woo mobile app on your phone, tap {{strong}}Scan QR code{{/strong}}, then point your camera at the code below.',
								'woocommerce'
							),
							components: {
								strong: <strong />,
							},
						} ) }
					</p>

					<div className="woocommerce-mobile-app-login__qr">
						<QRDirectLoginCode />
					</div>

					{ /*
					 * Leave room below the actions for a future "Log in with
					 * WordPress.com for multi-store access" secondary CTA
					 * (see WOOMOB-2767 plan). Do not add the UI here — a
					 * separate task owns that flow.
					 */ }

					<p className="woocommerce-mobile-app-login__faq">
						{ interpolateComponents( {
							mixedString: __(
								'Any troubles signing in? Check out the {{link}}FAQ{{/link}}.',
								'woocommerce'
							),
							components: {
								link: (
									<Link
										href={ FAQ_URL }
										target="_blank"
										type="external"
										onClick={ () => {
											recordEvent(
												'mobile_app_qr_login_page_faq_click'
											);
										} }
									/>
								),
							},
						} ) }
					</p>
				</CardBody>
			</Card>
		</div>
	);
};

export default MobileAppLoginPage;
