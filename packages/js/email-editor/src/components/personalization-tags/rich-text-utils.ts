/**
 * External dependencies
 */
import * as React from '@wordpress/element';
import { applyFormat, create, insert } from '@wordpress/rich-text';
import type {
	RichTextFormatList,
	RichTextValue,
} from '@wordpress/rich-text/build-types/types';

/**
 * Internal dependencies
 */
import { PersonalizationTag } from '../../store';

type Replacement = {
	attributes: Record< string, string >;
	type: string;
};

function getChildElement( rootElement: HTMLElement ): HTMLElement | null {
	let currentElement: HTMLElement | null = rootElement;

	while ( currentElement && currentElement?.children?.length > 0 ) {
		// Traverse into the first child element
		currentElement = currentElement.children[ 0 ] as HTMLElement;
	}

	return currentElement;
}

function findReplacementIndex(
	element: HTMLElement,
	replacements: ( null | Replacement )[]
): number | null {
	// Iterate over the replacements array
	for ( const [ index, replacement ] of replacements.entries() ) {
		if ( ! replacement ) {
			continue;
		}

		const { attributes } = replacement;

		if (
			element.getAttribute( 'data-rich-text-comment' ) ===
			attributes[ 'data-rich-text-comment' ]
		) {
			return index;
		}
	}

	return null; // Return null if no match is found
}

/**
 * Find the latest index of the format that matches the element.
 *
 * @param element
 * @param formats
 */
function findLatestFormatIndex(
	element: HTMLElement,
	formats: RichTextFormatList[]
): number | null {
	let latestFormatIndex = null;

	for ( const [ index, formatList ] of formats.entries() ) {
		if ( ! formatList ) {
			continue;
		}

		// Check each format within the format list at the current index
		for ( const format of formatList ) {
			if (
				// @ts-expect-error attributes property is missing in build type for RichTextFormatList type
				format?.attributes &&
				element.tagName.toLowerCase() ===
					// @ts-expect-error tagName property is missing in build type for RichTextFormatList type
					format.tagName?.toLowerCase() &&
				element.getAttribute( 'data-link-href' ) ===
					// @ts-expect-error attributes property is missing in build type for RichTextFormatList type
					format?.attributes[ 'data-link-href' ]
			) {
				latestFormatIndex = index;
			}
		}
	}

	return latestFormatIndex;
}

/**
 * Retrieves the cursor position within a RichText component.
 * Calculates the offset in plain text while accounting for HTML tags and comments.
 *
 * @param {React.RefObject<HTMLElement>} richTextRef - Reference to the RichText component.
 * @param {string}                       content     - The plain text content of the block.
 * @return {{ start: number, end: number } | null} - The cursor position as start and end offsets.
 */
const getCursorPosition = (
	richTextRef: React.RefObject< HTMLElement >,
	content: string
): { start: number; end: number } => {
	const selection =
		richTextRef.current.ownerDocument.defaultView.getSelection();

	if ( ! selection.rangeCount ) {
		return {
			start: 0,
			end: 0,
		};
	}

	const range = selection.getRangeAt( 0 );

	if ( selection.anchorNode.previousSibling === null ) {
		return {
			start: selection.anchorOffset,
			end: selection.anchorOffset + range.toString().length,
		};
	}

	const richTextValue = create( { html: content } );
	let previousSibling = selection.anchorNode.previousSibling as HTMLElement;
	previousSibling = getChildElement( previousSibling );

	const formatIndex = findLatestFormatIndex(
		previousSibling,
		richTextValue.formats
	);
	if ( formatIndex !== null ) {
		return {
			start: formatIndex + selection.anchorOffset + 1, // We need to add 1 for the format length
			end: formatIndex + selection.anchorOffset + range.toString().length,
		};
	}

	const replacementIndex = findReplacementIndex(
		previousSibling,
		richTextValue.replacements as Replacement[]
	);
	if ( replacementIndex !== null ) {
		return {
			start: replacementIndex + selection.anchorOffset + 1, // We need to add 1 for the replacement length
			end:
				replacementIndex +
				selection.anchorOffset +
				range.toString().length,
		};
	}

	// fallback for placing the value at the end of the rich text
	return {
		start: richTextValue.text.length,
		end: richTextValue.text.length + range.toString().length,
	};
};

/**
 * Replace registered personalization tags with HTML comments in content.
 *
 * @param content string The content to replace the tags in.
 * @param tags    PersonalizationTag[] The tags to replace in the content.
 */
const replacePersonalizationTagsWithHTMLComments = (
	content: string,
	tags: PersonalizationTag[]
) => {
	tags.forEach( ( tag ) => {
		// Skip if the token is not in the content
		if (
			! content.includes( tag.token.slice( 0, tag.token.length - 1 ) )
		) {
			return;
		}

		// Match the token with optional attributes like [mailpoet/subscriber-firstname default="user"]
		const baseToken = tag.token
			.substring( 1, tag.token.length - 1 )
			.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ); // Escape base token and remove brackets
		const regex = new RegExp(
			`(?<!<!--)(?<!["'])\\[(${ baseToken }(\\s[^\\]]*)?)\\](?!-->)`, // Match token not inside quotes (attributes)
			'g'
		);

		content = content.replace( regex, ( match ) => {
			// Use the exact text inside the brackets for the replacement
			return `<!--${ match }-->`;
		} );
	} );
	return content;
};

/**
 * Insert a personalization tag (or a tag link when linkText is set) into a RichText value
 * at its selection, or at the end when there is no selection.
 *
 * @param value
 * @param tag
 * @param linkText
 */
const insertPersonalizationTag = (
	value: RichTextValue,
	tag: string,
	linkText?: string | null
): RichTextValue => {
	const start = value.start ?? value.text.length;
	const end = value.end ?? start;

	if ( ! linkText ) {
		return {
			// Non-breaking space avoids the WP renderer removing a block containing only a comment
			...insert(
				value,
				create( { html: `<!--${ tag }-->&nbsp;` } ),
				start,
				end
			),
			activeFormats: [],
		} as RichTextValue;
	}

	const inserted = insert( value, linkText, start, end );
	return {
		...applyFormat(
			inserted,
			{
				type: 'woocommerce-email-editor/link-shortcode',
				// @ts-expect-error attributes property is missing in build type for WPFormat type
				attributes: {
					'data-link-href': tag,
					contenteditable: 'false',
					style: 'text-decoration: underline;',
				},
			},
			start,
			start + linkText.length
		),
		activeFormats: [],
	} as RichTextValue;
};

export {
	getCursorPosition,
	insertPersonalizationTag,
	replacePersonalizationTagsWithHTMLComments,
};
