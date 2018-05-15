/** @format */

/* Returns a string with the site's wp-admin URL appended. JS version of `admin_url`.
 * TODO: Test
 *
 * @param {String} path Relative path.
 * @return {String} Full admin URL.
 */
export const getAdminLink = path => {
	return wcSettings.adminUrl + 'admin.php?page=woodash#' + path;
};
