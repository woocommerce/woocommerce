/**
 * External dependencies
 */
import { Button, ButtonGroup, Modal } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import sanitizeHTML from '../../../lib/sanitize-html';
import { Subscription } from '../my-subscriptions/types';
import {
	WOO_CONNECT_PLUGIN_DOWNLOAD_URL,
	WP_ADMIN_PLUGIN_LIST_URL,
} from '../constants';
import { getAdminSetting } from '../../../utils/admin-settings';

interface ConnectProps {
	subscription: Subscription;
	onClose: () => void;
}

export default function InstallWooConnectModal( props: ConnectProps ) {
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	if ( ! wccomSettings?.wooUpdateManagerInstalled ) {
		return (
			<Modal
				title={ __( 'Access your updates', 'woocommerce' ) }
				onRequestClose={ props.onClose }
				focusOnMount={ true }
				className="woocommerce-marketplace__header-account-modal"
				style={ { borderRadius: 4 } }
				overlayClassName="woocommerce-marketplace__header-account-modal-overlay"
			>
				<p className="woocommerce-marketplace__header-account-modal-text">
					<span
						dangerouslySetInnerHTML={ sanitizeHTML(
							sprintf(
								// translators: %s is the product version number (e.g. 1.0.2).
								__(
									'Version %s is available. To access this update, please first <b>install the Woo.com Update Manager</b> extension. Alternatively, you can download and install it manually.',
									'woocommerce'
								),
								props.subscription.version
							)
						) }
					/>
				</p>
				<ButtonGroup className="woocommerce-marketplace__header-account-modal-button-group">
					<Button
						href={ WOO_CONNECT_PLUGIN_DOWNLOAD_URL }
						variant="secondary"
					>
						{ __( 'Download', 'woocommerce' ) }
					</Button>
					<Button
						href={ wccomSettings?.wooUpdateManagerInstallUrl }
						variant="primary"
					>
						{ __( 'Install', 'woocommerce' ) }
					</Button>
				</ButtonGroup>
			</Modal>
		);
	}

	if ( ! wccomSettings?.wooUpdateManagerActive ) {
		return (
			<Modal
				title={ __( 'Access your updates', 'woocommerce' ) }
				onRequestClose={ props.onClose }
				focusOnMount={ true }
				className="woocommerce-marketplace__header-account-modal"
				style={ { borderRadius: 4 } }
				overlayClassName="woocommerce-marketplace__header-account-modal-overlay"
			>
				<p className="woocommerce-marketplace__header-account-modal-text">
					<span
						dangerouslySetInnerHTML={ sanitizeHTML(
							sprintf(
								// translators: %s is the product version number (e.g. 1.0.2).
								__(
									'Version %s is available. To access this update, please <b>activate the Woo.com Update Manager</b> extension.',
									'woocommerce'
								),
								props.subscription.version
							)
						) }
					/>
				</p>
				<ButtonGroup className="woocommerce-marketplace__header-account-modal-button-group">
					<Button onClick={ props.onClose } variant="link">
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button href={ WP_ADMIN_PLUGIN_LIST_URL } variant="primary">
						{ __( 'Activate', 'woocommerce' ) }
					</Button>
				</ButtonGroup>
			</Modal>
		);
	}

	return null;
}
