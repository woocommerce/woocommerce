/**
 * External dependencies
 */
import { getAdminLink } from '@woocommerce/settings';

export const COMING_SOON_PAGE_EDITOR_LINK = getAdminLink(
	'site-editor.php?postType=wp_template&postId=woocommerce/woocommerce//coming-soon&canvas=edit'
);

export const SITE_VISIBILITY_DOC_LINK =
	'https://woocommerce.com/document/configuring-woocommerce-settings/coming-soon-mode/';

export const LAUNCH_YOUR_STORE_DOC_LINK =
	'https://woocommerce.com/document/configuring-woocommerce-settings/#site-visibility';
