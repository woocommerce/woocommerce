/**
 * Encode basic auth username and password to be used in HTTP Authorization header.
 *
 * @param {string} username
 * @param {string} password
 * @return {string} Base64-encoded string
 */
export const encodeCredentials = ( username: string, password: string ) => {
	return Buffer.from( `${ username }:${ password }` ).toString( 'base64' );
};
