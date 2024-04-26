/**
 * External dependencies
 */
import removeAccents from 'remove-accents';

const ALL_UNICODE_DASH_CHARACTERS = new RegExp(
	/[\u007e\u00ad\u2053\u207b\u208b\u2212\p{Pd}]/gu
);

export const normalizeTextString = ( value: string ): string => {
	return removeAccents( value )
		.toLocaleLowerCase()
		.replace( ALL_UNICODE_DASH_CHARACTERS, '-' );
};
