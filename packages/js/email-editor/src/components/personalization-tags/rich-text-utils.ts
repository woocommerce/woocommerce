/**
 * External dependencies
 */
import * as React from '@wordpress/element';
import { applyFormat, create, insert } from '@wordpress/rich-text';
import type { RichTextValue } from '@wordpress/rich-text/build-types/types';

/**
 * Internal dependencies
 */
import { PersonalizationTag } from '../../store';

/**
 * Read the selection offsets of a RichText field from its live editable DOM.
 * Returns null when the selection is not in the field.
 *
 * @param richTextRef - Reference to the editable element.
 */
const getCursorPosition = (
	richTextRef: React.RefObject< HTMLElement >
): { start: number; end: number } | null => {
	const element = richTextRef.current;
	const selection = element?.ownerDocument.defaultView?.getSelection();

	if ( ! element || ! selection?.rangeCount ) {
		return null;
	}

	const range = selection.getRangeAt( 0 ).cloneRange();

	if ( ! range.intersectsNode( element ) ) {
		return null;
	}

	if ( ! element.contains( range.endContainer ) ) {
		range.setEnd( element, element.childNodes.length );
	}

	// A caret inside a tag comment counts as right after it.
	const getCommentSpan = ( container: Node ) => {
		const node =
			container.nodeType === container.TEXT_NODE
				? container.parentElement
				: ( container as Element );
		const span = node?.closest( '[data-rich-text-comment]' );
		return span && element.contains( span ) ? span : null;
	};
	const startSpan = getCommentSpan( range.startContainer );
	if ( startSpan ) {
		range.setStartAfter( startSpan );
	}
	const endSpan = getCommentSpan( range.endContainer );
	if ( endSpan ) {
		range.setEndAfter( endSpan );
	}

	const { start, end } = create( {
		element,
		range,
		__unstableIsEditableTree: true,
	} );

	return {
		start: start ?? 0,
		end: Math.max( start ?? 0, end ?? start ?? 0 ),
	};
};

/**
 * Resolve a DOM element inside a RichText container to its offsets in the RichText value.
 *
 * @param container    The editable element.
 * @param element      The element to locate.
 * @param contentsOnly Only select the element's contents.
 */
const getElementRange = (
	container: HTMLElement | null | undefined,
	element: HTMLElement,
	contentsOnly = false
): { start: number; end: number } | null => {
	if ( ! container?.contains( element ) ) {
		return null;
	}
	const range = container.ownerDocument.createRange();
	if ( contentsOnly ) {
		range.selectNodeContents( element );
	} else {
		range.selectNode( element );
	}
	const { start, end } = create( {
		element: container,
		range,
		__unstableIsEditableTree: true,
	} );
	return start === undefined || end === undefined ? null : { start, end };
};

/**
 * Rename the tag comment at the given range of a RichText value.
 * Returns null when the range is not a single tag comment.
 *
 * @param value      The RichText value.
 * @param range      Offsets of the tag, from getElementRange.
 * @param updatedTag The new tag, without brackets.
 */
const renameTagComment = (
	value: RichTextValue,
	range: { start: number; end: number } | null,
	updatedTag: string
): RichTextValue | null => {
	const replacement = ( range && value.replacements[ range.start ] ) as
		{ type: string; attributes?: Record< string, string > } | undefined;
	if (
		! range ||
		! replacement ||
		range.end - range.start !== 1 ||
		value.text[ range.start ] !== '\ufffc' ||
		replacement.type !== '#comment'
	) {
		return null;
	}
	const replacements = value.replacements.slice();
	replacements[ range.start ] = {
		...replacement,
		attributes: {
			...replacement.attributes,
			// The popover removes the brackets, so add them back.
			'data-rich-text-comment': `[${ updatedTag }]`,
		},
	} as ( typeof replacements )[ number ];
	return { ...value, replacements, activeFormats: [] } as RichTextValue;
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
	getElementRange,
	insertPersonalizationTag,
	renameTagComment,
	replacePersonalizationTagsWithHTMLComments,
};
