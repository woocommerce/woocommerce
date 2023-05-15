/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../../utils/sanitize-html';

type ContentPreviewProps = {
	content: string;
};

const CONTENT_TAGS = [
	'a',
	'b',
	'em',
	'i',
	'strong',
	'p',
	'br',
	'img',
	'blockquote',
	'cite',
	'h1',
	'h2',
	'h3',
	'h4',
	'h5',
	'h6',
	'ul',
	'li',
	'ol',
	'div',
];

const CONTENT_ATTR = [
	'target',
	'href',
	'rel',
	'name',
	'download',
	'src',
	'style',
	'class',
];

export function ContentPreview( { content }: ContentPreviewProps ) {
	return (
		<div
			className="woocommerce-content-preview"
			dangerouslySetInnerHTML={ sanitizeHTML( content, {
				tags: CONTENT_TAGS,
				attr: CONTENT_ATTR,
			} ) }
		/>
	);
}
