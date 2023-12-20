/**
 * External dependencies
 */
import { autop } from '@wordpress/autop';
import { trimCharacters, trimWords } from '@woocommerce/utils';
import { count, CountType } from '@wordpress/wordcount';

/**
 * Get first paragraph from some HTML text, or return whole string.
 *
 * @param {string} source Source text.
 * @return {string} First paragraph found in string.
 */
const getFirstParagraph = ( source: string ) => {
	const pIndex = source.indexOf( '</p>' );

	if ( pIndex === -1 ) {
		return source;
	}

	return source.substr( 0, pIndex + 4 );
};

/**
 * Generates the summary text from a string of text.
 *
 * @param {string} source    Source text.
 * @param {number} maxLength Limit number of countType returned if text has multiple paragraphs.
 * @param {string} countType What is being counted. One of words, characters_excluding_spaces, or characters_including_spaces.
 * @return {string} Generated summary.
 */
export const generateSummary = (
	source: string,
	maxLength = 15,
	countType: CountType = 'words'
) => {
	const sourceWithParagraphs = autop( source );
	const sourceWordCount = count( sourceWithParagraphs, countType );

	if ( sourceWordCount <= maxLength ) {
		return sourceWithParagraphs;
	}

	const firstParagraph = getFirstParagraph( sourceWithParagraphs );
	const firstParagraphWordCount = count( firstParagraph, countType );

	if ( firstParagraphWordCount <= maxLength ) {
		return firstParagraph;
	}

	if ( countType === 'words' ) {
		return trimWords( firstParagraph, maxLength );
	}

	return trimCharacters(
		firstParagraph,
		maxLength,
		countType === 'characters_including_spaces'
	);
};
