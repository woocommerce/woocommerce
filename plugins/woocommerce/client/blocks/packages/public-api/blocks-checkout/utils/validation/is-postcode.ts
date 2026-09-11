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
import postcodeValidationData from '../../../../../../../i18n/postcode-validation-rules.json';

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
	if ( typeof postcode !== 'string' || typeof country !== 'string' ) {
		return false;
	}

	// Mirror WC_Validation::is_postcode(): only ASCII whitespace, letters,
	// digits, and hyphens may reach country-specific validation.
	if ( /[^ \t\n\r\f\vA-Za-z0-9-]/.test( postcode ) ) {
		return false;
	}

	if ( Object.hasOwn( SHARED_RULES, country ) ) {
		const sharedRule = SHARED_RULES[ country ];
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
