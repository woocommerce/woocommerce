/**
 * External dependencies
 */
import { Button, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import sanitizeHTML from '../../../lib/sanitize-html';
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
		! wccomSettings?.wooUpdateManagerActive &&
		! wccomSettings?.wooUpdateManagerInstalled
	) {
		return (
			<section className="woocommerce-marketplace__woo-update-manager-plugin__notices">
				<Notice status="error" isDismissible={ false }>
					<span
						dangerouslySetInnerHTML={ sanitizeHTML(
							__(
								'Please install the <b>Woo.com Update Manager</b> to continue receiving the updates and streamlined support included in your Woo.com subscriptions.<br/>Alternatively, you can download and install it manually.',
								'woocommerce'
							)
						) }
					></span>
					<div className="components-notice__buttons">
						<Button
							href={ wccomSettings?.wooUpdateManagerInstallUrl }
							variant="secondary"
						>
							{ __( 'Install', 'woocommerce' ) }
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
		wccomSettings?.wooUpdateManagerInstalled &&
		! wccomSettings?.wooUpdateManagerActive
	) {
		return (
			<section className="woocommerce-marketplace__woo-update-manager-plugin__notices">
				<Notice status="error" isDismissible={ false }>
					<span
						dangerouslySetInnerHTML={ sanitizeHTML(
							__(
								'Activate the <b>Woo.com Update Manager</b> to continue receiving the updates and streamlined support included in your Woo.com subscriptions.',
								'woocommerce'
							)
						) }
					></span>
					<div className="components-notice__buttons">
						<Button
							href={ WP_ADMIN_PLUGIN_LIST_URL }
							variant="secondary"
						>
							{ __( 'Activate', 'woocommerce' ) }
						</Button>
					</div>
				</Notice>
			</section>
		);
	}

	return null;
}
