/**
 * External dependencies
 */
import {
	createSlotFill,
	ToggleControl,
	RadioControl,
	Button,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { useCopyToClipboard } from '@wordpress/compose';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { SETTINGS_SLOT_FILL_CONSTANT } from '../../settings/settings-slots';
import './style.scss';

const { Fill } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );

const SiteVisibility = () => {
	const shareKey =
		window?.wcSettings?.admin?.siteVisibilitySettings
			?.woocommerce_share_key;

	const [ comingSoon, setComingSoon ] = useState(
		window?.wcSettings?.admin?.siteVisibilitySettings
			?.woocommerce_coming_soon || 'no'
	);
	const [ storePagesOnly, setStorePagesOnly ] = useState(
		window?.wcSettings?.admin?.siteVisibilitySettings
			?.woocommerce_store_pages_only
	);
	const [ privateLink, setPrivateLink ] = useState(
		window?.wcSettings?.admin?.siteVisibilitySettings
			?.woocommerce_private_link
	);

	const copyLink = __( 'Copy link', 'woocommerce' );
	const copied = __( 'Copied!', 'woocommerce' );
	const [ copyLinkText, setCopyLinkText ] = useState( copyLink );

	const getPrivateLink = () => {
		if ( storePagesOnly === 'yes' ) {
			return (
				window?.wcSettings?.admin?.siteVisibilitySettings
					?.shop_permalink +
				'?woo-share=' +
				shareKey
			);
		}

		return window?.wcSettings?.homeUrl + '?woo-share=' + shareKey;
	};

	const copyClipboardRef = useCopyToClipboard( getPrivateLink, () => {
		setCopyLinkText( copied );
		setTimeout( () => {
			setCopyLinkText( copyLink );
		}, 2000 );
	} );

	return (
		<div className="site-visibility-settings-slotfill">
			<input
				type="hidden"
				value={ comingSoon }
				name="woocommerce_coming_soon"
			/>
			<input
				type="hidden"
				value={ storePagesOnly }
				name="woocommerce_store_pages_only"
			/>
			<input
				type="hidden"
				value={ privateLink }
				name="woocommerce_private_link"
			/>
			<h2>{ __( 'Site visibility', 'woocommerce' ) }</h2>
			<p className="site-visibility-settings-slotfill-description">
				{ __(
					'Manage how your site appears to visitors.',
					'woocommerce'
				) }
			</p>
			<div className="site-visibility-settings-slotfill-section">
				<RadioControl
					onChange={ () => {
						setComingSoon( 'yes' );
						recordEvent( 'site_visibility_toggle', {
							status: 'coming_soon',
						} );
					} }
					options={ [
						{
							label: 'Coming soon',
							value: 'yes',
						},
					] }
					selected={ comingSoon }
				/>
				<p className="site-visibility-settings-slotfill-section-description">
					{ __(
						'Your site is hidden from visitors behind a “Coming soon” landing page until it’s ready for viewing. You can customize your “Coming soon” landing page via the Editor.',
						'woocommerce'
					) }
				</p>
				<div
					className={ classNames(
						'site-visibility-settings-slotfill-section-content',
						{
							'is-hidden': comingSoon !== 'yes',
						}
					) }
				>
					<ToggleControl
						label={
							<>
								{ __(
									'Restrict to store pages only',
									'woocommerce'
								) }
								<p>
									{ __(
										'Hide store pages only behind a “Coming soon” page. The rest of your site will remain public.',
										'woocommerce'
									) }
								</p>
							</>
						}
						checked={ storePagesOnly === 'yes' }
						onChange={ ( enabled ) => {
							setStorePagesOnly(
								storePagesOnly === 'yes' ? 'no' : 'yes'
							);
							recordEvent(
								'site_visibility_restrict_store_pages_only_toggle',
								{
									enabled,
								}
							);
						} }
					/>
					<ToggleControl
						label={
							<>
								{ __(
									'Share your site with a private link',
									'woocommerce'
								) }
								<p>
									{ __(
										'“Coming soon” sites are only visible to Admins and Shop managers. Enable “Share site” to let other users view your site.',
										'woocommerce'
									) }
								</p>
							</>
						}
						checked={ privateLink === 'yes' }
						onChange={ ( enabled ) => {
							setPrivateLink(
								privateLink === 'yes' ? 'no' : 'yes'
							);
							recordEvent(
								'site_visibility_share_private_link_toggle',
								{
									enabled,
								}
							);
						} }
					/>
				</div>
				{ privateLink === 'yes' && (
					<div className="site-visibility-settings-slotfill-private-link">
						{ getPrivateLink() }
						<Button
							ref={ copyClipboardRef }
							variant="link"
							onClick={ () => {
								recordEvent(
									'site_visibility_private_link_copy'
								);
							} }
						>
							{ copyLinkText }
						</Button>
					</div>
				) }
			</div>
			<div className="site-visibility-settings-slotfill-section">
				<RadioControl
					onChange={ () => {
						setComingSoon( 'no' );
						recordEvent( 'site_visibility_toggle', {
							status: 'live',
						} );
					} }
					options={ [
						{
							label: 'Live',
							value: 'no',
						},
					] }
					selected={ comingSoon }
				/>
				<p className="site-visibility-settings-slotfill-section-description">
					{ __(
						'Your entire site is visible to everyone.',
						'woocommerce'
					) }
				</p>
			</div>
		</div>
	);
};

const SiteVisibilitySlotFill = () => {
	return (
		<Fill>
			<SiteVisibility />
		</Fill>
	);
};

export const registerSiteVisibilitySlotFill = () => {
	registerPlugin( 'woocommerce-admin-site-visibility-settings-slotfill', {
		scope: 'woocommerce-site-visibility-settings',
		render: SiteVisibilitySlotFill,
	} );
};
