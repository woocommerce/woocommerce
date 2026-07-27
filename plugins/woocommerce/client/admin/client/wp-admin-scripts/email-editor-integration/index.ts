// This file acts as a way of adding JS integration support for the email editor package

/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { addFilter, addAction } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import {
	initializeEditor,
	registerEntityAction,
} from '@woocommerce/email-editor';

/**
 * Internal dependencies
 */
import { NAME_SPACE } from './constants';
import { modifyTemplateSidebar } from './templates';
import { modifySidebar } from './sidebar_settings';
import { registerEmailValidationRules } from './email-validation';
import getResetNotificationEmailContentAction from './reset-notification-email-content';
import { ReviewUpdatePlugin } from './review-update-plugin';
import { UpdateBannerPlugin } from './update-banner-plugin';
import {
	registerStore as registerIntegrationStore,
	STORE_NAME as INTEGRATION_STORE_NAME,
} from './store';

import './style.scss';
import './update-banner.scss';

addFilter( 'woocommerce_email_editor_send_button_label', NAME_SPACE, () =>
	__( 'Save email', 'woocommerce' )
);

addFilter(
	'woocommerce_email_editor_check_sending_method_configuration_link',
	NAME_SPACE,
	() => 'https://woocommerce.com/document/email-faq/'
);

// Add filter to permanently delete emails.
// This is used to delete email posts from the database instead of moving them to the trash.
// The email posts can be recreated from the WooCommerce settings email listing page.
addFilter(
	'woocommerce_email_editor_trash_modal_should_permanently_delete',
	NAME_SPACE,
	() => true
);

/**
 * Register default handler for creating coupons in WooCommerce.
 * Uses the localized admin URL from PHP to support subdirectory installations.
 * Integrators can override this filter to customize behavior (e.g., SPA routing).
 */
addFilter( 'woocommerce_email_editor_create_coupon_handler', NAME_SPACE, () => {
	// Get the create coupon URL from localized data (provided by PHP)
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const editorStore = ( window as any ).wp?.data?.select(
		'woocommerce/email-editor'
	);
	const urls = editorStore?.getUrls?.();
	const createCouponUrl = urls?.createCoupon;

	// Return the handler function
	return () => {
		if ( createCouponUrl ) {
			// Use the localized URL from PHP (supports subdirectory installations)
			window.open( createCouponUrl, '_blank' );
		} else {
			// Fallback: relative path (may not work in subdirectory installations)
			window.open(
				'/wp-admin/post-new.php?post_type=shop_coupon',
				'_blank'
			);
		}
	};
} );

// Register the integration's @wordpress/data store before any plugin
// renders, so consumers (review drawer, future RSM-141 banner, etc.)
// can dispatch into it from anywhere.
registerIntegrationStore();

modifySidebar();
modifyTemplateSidebar();
registerEmailValidationRules();

// Register the review-update plugin (RSM-143). Mounts the review drawer
// into the email editor — its open / close state is driven by the
// `woocommerce/email-editor-integration` store, so any other surface
// (RSM-141 banner, list-page row action, browser console) can open it
// via `wp.data.dispatch( 'woocommerce/email-editor-integration' )
// .openReviewDrawer()`.
registerPlugin( 'woocommerce-email-editor-review-update', {
	scope: 'woocommerce-email-editor',
	render: ReviewUpdatePlugin,
} );

// Register the update banner plugin (RSM-141). Mounts a floating banner
// over the editor canvas when the open email post is classified
// `core_updated_customized`. Reads dismiss + viewed-dedup state from the
// integration store; consumes useChangeSummary (RSM-142) and
// useApplyUpdate (RSM-143) for content + apply.
registerPlugin( 'woocommerce-email-editor-update-banner', {
	scope: 'woocommerce-email-editor',
	render: UpdateBannerPlugin,
} );

// Deep-link contract: opens the review drawer when arriving with
// `?wc_email_review_drawer=1` (set by the email list page's update indicator).
if (
	new URLSearchParams( window.location.search ).get(
		'wc_email_review_drawer'
	) === '1'
) {
	void dispatch( INTEGRATION_STORE_NAME ).openReviewDrawer();

	// Strip the param from the URL so a refresh doesn't re-trigger the
	// drawer auto-open. RSM-141 §5.2.
	const url = new URL( window.location.href );
	url.searchParams.delete( 'wc_email_review_drawer' );
	window.history.replaceState( {}, '', url.pathname + url.search + url.hash );
}

/**
 * Register the reset notification email content entity action for the woo_email post type.
 * This action allows users to reset the email content to the original state as distributed by the plugin.
 */
const registerResetNotificationEmailContentAction = ( postType: string ) => {
	if ( postType !== 'woo_email' ) {
		return;
	}
	registerEntityAction(
		'postType',
		postType,
		getResetNotificationEmailContentAction()
	);
};

addAction(
	'core.registerPostTypeSchema',
	`${ NAME_SPACE }/reset-notification-email-content`,
	registerResetNotificationEmailContentAction
);

initializeEditor( 'woocommerce-email-editor' );
