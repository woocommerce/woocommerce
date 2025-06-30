/**
 * External dependencies
 */
import { autop } from '@wordpress/autop';

/**
 * Remove HTML tags from a string.
 *
 * @param {string} htmlString String to remove tags from.
 * @return {string} Plain text string.
 */
export const removeTags = ( htmlString: string ) => {
	const tagsRegExp = /<\/?[a-z][^>]*?>/gi;
	return htmlString.replace( tagsRegExp, '' );
};

/**
 * Remove trailing punctuation and append some characters to a string.
 *
 * @param {string} text     Text to append to.
 * @param {string} moreText Text to append.
 * @return {string} String with appended characters.
 */
export const appendMoreText = ( text: string, moreText: string ) => {
	return text.replace( /[\s|\.\,]+$/i, '' ) + moreText;
};

/**
 * Limit words in string and returned trimmed version.
 *
 * @param {string} text      Text to trim.
 * @param {number} maxLength Number of countType to limit to.
 * @param {string} moreText  Appended to the trimmed string.
 * @param {string} useAutop  Whether to format with autop before returning.
 * @return {string} Trimmed string.
 */
export const trimWords = (
	text: string,
	maxLength: number,
	moreText = '&hellip;',
	useAutop = true
) => {
	const textToTrim = removeTags( text );
	const trimmedText = textToTrim
		.split( ' ' )
		.splice( 0, maxLength )
		.join( ' ' );

	if ( trimmedText === textToTrim ) {
		return useAutop ? autop( textToTrim ) : textToTrim;
	}

	if ( ! useAutop ) {
		return appendMoreText( trimmedText, moreText );
	}

	return autop( appendMoreText( trimmedText, moreText ) );
};

/**
 * Limit characters in string and returned trimmed version.
 *
 * @param {string}  text          Text to trim.
 * @param {number}  maxLength     Number of countType to limit to.
 * @param {boolean} includeSpaces Should spaces be included in the count.
 * @param {string}  moreText      Appended to the trimmed string.
 * @param {string}  useAutop      Whether to format with autop before returning.
 * @return {string} Trimmed string.
 */
export const trimCharacters = (
	text: string,
	maxLength: number,
	includeSpaces = true,
	moreText = '&hellip;',
	useAutop = true
) => {
	const textToTrim = removeTags( text );
	const trimmedText = textToTrim.slice( 0, maxLength );

	if ( trimmedText === textToTrim ) {
		return useAutop ? autop( textToTrim ) : textToTrim;
	}

	if ( includeSpaces ) {
		return autop( appendMoreText( trimmedText, moreText ) );
	}

	const matchSpaces = trimmedText.match( /([\s]+)/g );
	const spaceCount = matchSpaces ? matchSpaces.length : 0;
	const trimmedTextExcludingSpaces = textToTrim.slice(
		0,
		maxLength + spaceCount
	);

	if ( ! useAutop ) {
		return appendMoreText( trimmedTextExcludingSpaces, moreText );
	}

	return autop( appendMoreText( trimmedTextExcludingSpaces, moreText ) );
};
