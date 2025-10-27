/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { getAdminLink } from '@woocommerce/settings';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
/**
 * Internal dependencies
 */
import './newsletter-panel.scss';

const NewsletterPanel = () => {
	const postId = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostId()
	);

	// Restrict the panel to only show on the coming soon page template.
	if ( typeof postId === 'string' && ! postId?.endsWith( '//coming-soon' ) ) {
		return null;
	}

	// For whatever reason,  if PluginDocumentSettingPanel is not available, abort.
	if ( ! PluginDocumentSettingPanel ) {
		return null;
	}

	// comingSoonNewsletter is set up from LaunchYourStore.php
	// eslint-disable-next-line
	const { mailpoet_connected, mailpoet_installed } =
		window.comingSoonNewsletter || {};

	// If MailPoet is connected, don't show the panel
	if ( mailpoet_connected ) {
		return null;
	}

	// If MailPoet is not installed, link to the plugin install page.
	// Otherwise, link to the MailPoet homepage to connect.
	const setupLink = ! mailpoet_installed
		? getAdminLink(
				'plugin-install.php?tab=plugin-information&plugin=mailpoet'
		  )
		: getAdminLink( 'admin.php?page=mailpoet-homepage' );

	return (
		<PluginDocumentSettingPanel
			name="coming-soon-newsletter-mailpoet-setting-panel"
			title={ __( 'Launch Newsletter', 'woocommerce' ) }
			className="coming-soon-newsletter-mailpoet-setting-panel"
		>
			<div className="coming-soon-newsletter-mailpoet-setting-panel-body">
				<h3>{ __( 'Set up email marketing', 'woocommerce' ) }</h3>
				<p>
					{ __(
						'To collect email and notify your subscribers, set up MailPoet.',
						'woocommerce'
					) }
				</p>
				<Button variant="link" href={ setupLink }>
					{ __( 'Set up MailPoet', 'woocommerce' ) }
				</Button>
			</div>
		</PluginDocumentSettingPanel>
	);
};

export default NewsletterPanel;
