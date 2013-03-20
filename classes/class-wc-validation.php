<?php
/**
 * Contains Validation functions
 *
 * @class 		WC_Validation
 * @version		1.6.4
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Validation {

	/**
	 * Validates an email using wordpress native is_email function
	 *
	 * @param   string	email address
	 * @return  bool
	 */
	public function is_email( $email ) {
		return is_email( $email );
	}


	/**
	 * Validates a phone number using a regular expression
	 *
	 * @param   string	phone number
	 * @return  bool
	 */
	public function is_phone( $phone ) {
		if ( strlen( trim( preg_replace( '/[\s\#0-9_\-\+\(\)]/', '', $phone ) ) ) > 0 )
			return false;

		return true;
	}


	/**
	 * Checks for a valid postcode (UK)
	 *
	 * @param   string	postcode
	 * @param	string	country
	 * @return  bool
	 */
	public function is_postcode( $postcode, $country ) {
		if ( strlen( trim( preg_replace( '/[\s\-A-Za-z0-9]/', '', $postcode ) ) ) > 0 )
			return false;

		switch ( $country ) {
			case "GB" :
				return $this->is_GB_postcode( $postcode );
			case "US" :
				 if ( preg_match( "/^([0-9]{5})(-[0-9]{4})?$/i", $postcode ) )
				 	return true;
				 else
				 	return false;
		}

		return true;
	}


	/**
	 * is_GB_postcode function.
	 *
	 * @author John Gardner
	 * @access public
	 * @param mixed $toCheck A postcode
	 * @return bool
	 */
	public function is_GB_postcode( $toCheck ) {

		// Permitted letters depend upon their position in the postcode.
		$alpha1 = "[abcdefghijklmnoprstuwyz]";                          // Character 1
		$alpha2 = "[abcdefghklmnopqrstuvwxy]";                          // Character 2
		$alpha3 = "[abcdefghjkstuw]";                                   // Character 3
		$alpha4 = "[abehmnprvwxy]";                                     // Character 4
		$alpha5 = "[abdefghjlnpqrstuwxyz]";                             // Character 5

		// Expression for postcodes: AN NAA, ANN NAA, AAN NAA, and AANN NAA
		$pcexp[0] = '^('.$alpha1.'{1}'.$alpha2.'{0,1}[0-9]{1,2})([0-9]{1}'.$alpha5.'{2})$';

		// Expression for postcodes: ANA NAA
		$pcexp[1] =  '^('.$alpha1.'{1}[0-9]{1}'.$alpha3.'{1})([0-9]{1}'.$alpha5.'{2})$';

		// Expression for postcodes: AANA NAA
		$pcexp[2] =  '^('.$alpha1.'{1}'.$alpha2.'[0-9]{1}'.$alpha4.')([0-9]{1}'.$alpha5.'{2})$';

		// Exception for the special postcode GIR 0AA
		$pcexp[3] =  '^(gir)(0aa)$';

		// Standard BFPO numbers
		$pcexp[4] = '^(bfpo)([0-9]{1,4})$';

		// c/o BFPO numbers
		$pcexp[5] = '^(bfpo)(c\/o[0-9]{1,3})$';

		// Load up the string to check, converting into lowercase and removing spaces
		$postcode = strtolower($toCheck);
		$postcode = str_replace (' ', '', $postcode);

		// Assume we are not going to find a valid postcode
		$valid = false;

		// Check the string against the six types of postcodes
		foreach ($pcexp as $regexp) {

			if (ereg($regexp,$postcode, $matches)) {

				// Load new postcode back into the form element
				$toCheck = strtoupper ($matches[1] . ' ' . $matches [2]);

				// Take account of the special BFPO c/o format
				$toCheck = str_replace( 'C/O', 'c/o ', $toCheck );

				// Remember that we have found that the code is valid and break from loop
				$valid = true;
				break;
			}
		}

		if ($valid){return true;} else {return false;};
	}

	/**
	 * Format the postcode according to the country and length of the postcode
	 *
	 * @param   string	postcode
	 * @param	string	country
	 * @return  string	formatted postcode
	 */
	public function format_postcode( $postcode, $country ) {
		$postcode = strtoupper(trim($postcode));
		$postcode = trim(preg_replace('/[\s]/', '', $postcode));

		if ( in_array( $country, array('GB', 'CA') ) ) :
			$postcode = trim( substr_replace( $postcode, ' ', -3, 0 ) );
		endif;

		return $postcode;
	}

	/**
	 * format_phone function.
	 *
	 * @access public
	 * @param mixed $tel
	 * @return string
	 */
	public function format_phone( $tel ) {
		$tel = str_replace( '.', '-', $tel );
		return $tel;
	}

}