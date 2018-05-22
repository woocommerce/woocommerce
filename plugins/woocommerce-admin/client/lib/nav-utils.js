/** @format */

/* Returns a string with the site's wp-admin URL appended. JS version of `admin_url`.
 *
 * @param {String} path Relative path.
 * @return {String} Full admin URL.
 */
export const getAdminLink = path => {
	return wcSettings.adminUrl + path;
};
