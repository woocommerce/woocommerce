/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

export const PERMALINK_PRODUCT_REGEX = /%(?:postname|pagename)%/;

export const getProductPermalinkParts = () => {
	return getAdminSetting( 'productPermalinkTemplate' ).split(
		PERMALINK_PRODUCT_REGEX
	);
};
