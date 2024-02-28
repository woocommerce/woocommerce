/**
 * External dependencies
 */
import { Button, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import { getAdminSetting } from '../../../utils/admin-settings';
import {
	WP_ADMIN_PLUGIN_LIST_URL,
	WOO_CONNECT_PLUGIN_DOWNLOAD_URL,
} from '../constants';
import './woo-update-manager-plugin.scss';

export default function PluginInstallNotice() {
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	if ( ! wccomSettings?.isConnected ) {
		return null;
	}

	if (
		! wccomSettings?.wooConnectActive &&
		! wccomSettings?.wooConnectInstalled
	) {
		const message = __(
			'Please install the Woo Update Manager plugin to keep getting updates and streamlined support for your Woo.com subscriptions. You can also download and install it manually in your stores.',
			'woocommerce'
		);
		return (
			<section className="woocommerce-marketplace__woo-update-manager-plugin__notices">
				<Notice status="error" isDismissible={ false }>
					{ message }
					<div className="components-notice__buttons">
						<Button
							href={ wccomSettings?.wooConnectInstallUrl }
							variant="secondary"
						>
							{ __( 'Install Woo Update Manager', 'woocommerce' ) }
						</Button>
						<Button
							href={ WOO_CONNECT_PLUGIN_DOWNLOAD_URL }
							variant="link"
						>
							{ __( 'Download', 'woocommerce' ) }
						</Button>
					</div>
				</Notice>
			</section>
		);
	} else if (
		wccomSettings?.wooConnectInstalled &&
		! wccomSettings?.wooConnectActive
	) {
		const message = __(
			'Please activate the Woo Update Manager plugin to keep getting updates and streamlined support for your Woo.com subscriptions.',
			'woocommerce'
		);
		return (
			<section className="woocommerce-marketplace__woo-update-manager-plugin__notices">
				<Notice status="error" isDismissible={ false }>
					{ message }
					<div className="components-notice__buttons">
						<Button
							href={ WP_ADMIN_PLUGIN_LIST_URL }
							variant="secondary"
						>
							{ __( 'Activate Woo Update Manager', 'woocommerce' ) }
						</Button>
					</div>
				</Notice>
			</section>
		);
	}

	return null;
}
