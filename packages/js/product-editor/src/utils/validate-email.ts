/**
 * Checks if the provided email address is valid.
 *
 * @param {string} email - The email address to be tested.
 * @return {boolean} Returns true if the email address is valid.
 */
export const isValidEmail = ( email: string ) => {
	const re =
		/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test( String( email ).toLowerCase() );
};
