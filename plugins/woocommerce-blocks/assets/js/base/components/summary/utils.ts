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

const wrapElementByTag = ( element: string, tag: string ) => {
	const tagLower = tag.toLowerCase();
	return `<${ tagLower }>${ element }</${ tagLower }>`;
};

const getTextFromDomElements = (
	paragraphs: HTMLCollection,
	{
		maxLength,
		countType,
	}: {
		maxLength: number;
		countType: CountType;
	},
	currentStatus: {
		text: string;
		html: string[];
		currentLength?: number;
		remainingLength?: number;
	}
) => {
	return Array.from( paragraphs ).reduce(
		(
			acc,
			paragraph
		): {
			text: string;
			html: string[];
			currentLength?: number;
			remainingLength?: number;
			isLast?: boolean;
		} => {
			const paragraphText = paragraph.textContent ?? '';
			const paragraphHtml = paragraph.innerHTML;
			const childElements = paragraph.children;
			const tag = paragraph.tagName;

			const currentLength = count( acc.text, countType );
			const remainingLength = maxLength - currentLength;

			if ( acc.isLast === true ) {
				return acc;
			}

			if (
				Array.from( childElements ).some(
					( element ) => element?.textContent?.length > 0
				)
			) {
				const childText = getTextFromDomElements(
					childElements,
					{ maxLength, countType },
					{
						text: acc.text,
						html: [],
					}
				);

				return {
					text: childText.text,
					html: acc.html.concat(
						wrapElementByTag( childText.html.join( ' ' ), tag )
					),
					isLast: childText.isLast,
				} as { text: string; html: string[] };
			}

			if (
				currentLength + count( paragraphText, countType ) >
				maxLength
			) {
				const trimmedText = paragraphText
					.split( ' ' )
					.slice( 0, remainingLength )
					.join( ' ' );

				return {
					text: acc.text + ' ' + trimmedText,
					html: acc.html.concat(
						wrapElementByTag( trimmedText + '...', tag )
					),
					isLast: true,
				} as { text: string; html: string[] };
			}

			return {
				text: acc.text + ' ' + paragraphText,
				html: acc.html.concat( wrapElementByTag( paragraphHtml, tag ) ),
			} as { text: string; html: string[] };
		},
		currentStatus as { text: string; html: string[]; isLast?: boolean }
	);
};

const getTrimmedDomElements = (
	source: string,
	maxLength: number,
	countType: CountType
) => {
	const parsedHtml = new DOMParser().parseFromString( source, 'text/html' );
	const paragraphs = parsedHtml.body.children;

	const res = {
		text: '',
		html: [] as Array< string >,
	};

	const final = getTextFromDomElements(
		paragraphs,
		{ maxLength, countType },
		res
	);

	return final.html.join( '' );
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

	return getTrimmedDomElements( sourceWithParagraphs, maxLength, countType );
};
