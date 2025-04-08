/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { createElement, Fragment } from '@wordpress/element';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	__unstableIframe as Iframe,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	__unstableEditorStyles as EditorStyles,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	store as blockEditorStore,
} from '@wordpress/block-editor';

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
	const parentEditorSettings = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		return select( blockEditorStore ).getSettings();
	}, [] );

	return (
		<div className="woocommerce-content-preview">
			<Iframe
				className="woocommerce-content-preview__iframe"
				tabIndex={ -1 }
			>
				<>
					<EditorStyles styles={ parentEditorSettings?.styles } />
					<style>
						{ `body {
									overflow: hidden;
								}` }
					</style>
					<div
						className="woocommerce-content-preview__content"
						dangerouslySetInnerHTML={ sanitizeHTML( content, {
							tags: CONTENT_TAGS,
							attr: CONTENT_ATTR,
						} ) }
					/>
				</>
			</Iframe>
		</div>
	);
}
