/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

interface LoginAppPageProps {
	siteURL?: string | undefined;
	userEmail?: string | undefined;
}

export const LoginAppQRPage: React.FC< LoginAppPageProps > = () => {
	return (
		<div className="login-into-apps-modal-body">
			<div className="wrong-user-connected-title">
				<h1>{ __( 'Sign into the app', 'woocommerce' ) }</h1>
			</div>
			<div className="login-into-apps-subheader-spacer">
				<div className="login-into-apps-subheader">
					{ __(
						'Please scan the QR end enter your credentials into the apps.',
						'woocommerce'
					) }
				</div>
				<br />
				<div className="login-into-apps-qr">
					{ __(
						'Here the page will display the QR.',
						'woocommerce'
					) }
					<div itemID="app-qr-code" />
				</div>
			</div>
		</div>
	);
};
