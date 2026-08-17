/**
 * External dependencies
 */
import {
	postcodeValidator,
	postcodeValidatorExistsForCountry,
} from 'postcode-validator';

/**
 * Internal dependencies
 */
import postcodeValidationData from '../../../../../../../src/Internal/Utilities/postcode-validation-rules.json';

type PostcodeValidationRule = {
	pattern: string;
	flags?: string;
	normalization?: 'removeSpaces' | 'removeSpacesAndHyphens';
};

const SHARED_RULES = postcodeValidationData.rules as Record<
	string,
	PostcodeValidationRule
>;

const normalizePostcode = (
	postcode: string,
	normalization?: PostcodeValidationRule[ 'normalization' ]
): string => {
	if ( normalization === 'removeSpaces' ) {
		return postcode.replace( / /g, '' );
	}
	if ( normalization === 'removeSpacesAndHyphens' ) {
		return postcode.trim().replace( /[\s-]/g, '' );
	}
	return postcode;
};

export interface IsPostcodeProps {
	postcode: string;
	country: string;
}

const isPostcode = ( { postcode, country }: IsPostcodeProps ): boolean => {
	const sharedRule = SHARED_RULES[ country ];
	if ( sharedRule ) {
		if ( /[^\sA-Za-z0-9-]/.test( postcode ) ) {
			return false;
		}

		const regex = new RegExp(
			`^(?:${ sharedRule.pattern })$`,
			sharedRule.flags || ''
		);
		return regex.test(
			normalizePostcode( postcode, sharedRule.normalization )
		);
	}
	// If the country is not in the upstream list, trying to validate it would throw, so we skip and assume
	// that it is valid.
	if ( postcodeValidatorExistsForCountry( country ) ) {
		return postcodeValidator( postcode, country );
	}
	return true;
};

export default isPostcode;
