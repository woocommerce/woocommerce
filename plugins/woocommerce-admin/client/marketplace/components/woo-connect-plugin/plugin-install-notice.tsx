/**
 * External dependencies
 */
import { Button, Card, CardBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import { getAdminSetting } from '../../../utils/admin-settings';
import { WP_ADMIN_PLUGIN_LIST_URL } from '../constants';
import './woo-connect-plugin.scss';

export default function PluginInstallNotice() {
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	if (
		! wccomSettings?.wooConnectActive &&
		! wccomSettings?.wooConnectInstalled
	) {
		const message = __(
			'Please install the Woo Connect plugin to keep getting updates and streamlined support for your Woo.com subscriptions. You can also download and install it manually in your stores.',
			'woocommerce'
		);
		return (
			<section className="woocommerce-marketplace__woo-connect-plugin__notices">
				<Card
					className={
						'woocommerce-marketplace__notice--error components-notice is-error'
					}
				>
					<CardBody>
						<p>{ message }</p>
						<br></br>
						<Button
							href={ 'https://woo.com/woocom-plugin/install/' }
							variant="secondary"
						>
							Install Woo Connect
						</Button>
						<Button
							href={ 'https://woo.com/woocom-plugin/download/' }
							variant="link"
						>
							Download
						</Button>
					</CardBody>
				</Card>
			</section>
		);
	} else if (
		wccomSettings?.wooConnectInstalled &&
		! wccomSettings?.wooConnectActive
	) {
		const message = __(
			'Please activate the Woo Connect plugin to keep getting updates and streamlined support for your Woo.com subscriptions.',
			'woocommerce'
		);
		return (
			<section className="woocommerce-marketplace__woo-connect-plugin__notices">
				<Card
					className={
						'woocommerce-marketplace__notice--error components-notice is-error'
					}
				>
					<CardBody>
						<p>{ message }</p>
						<br></br>
						<Button
							href={ WP_ADMIN_PLUGIN_LIST_URL }
							variant="secondary"
						>
							Activate Woo Connect
						</Button>
					</CardBody>
				</Card>
			</section>
		);
	}

	return null;
}
