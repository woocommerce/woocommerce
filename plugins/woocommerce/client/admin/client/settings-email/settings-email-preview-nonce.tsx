/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

export const emailPreviewNonce = () => {
	return getAdminSetting( 'email_preview_nonce' );
};
