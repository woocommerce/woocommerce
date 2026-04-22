/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { createElement, Fragment } from '@wordpress/element';
import { sanitizeHTML } from '@woocommerce/sanitize';
import {
	// @ts-expect-error __unstableIframe is not exported from @wordpress/block-editor's public types.
	__unstableIframe as Iframe,
	// @ts-expect-error __unstableEditorStyles is not exported from @wordpress/block-editor's public types.
	__unstableEditorStyles as EditorStyles,
	store as blockEditorStore,
} from '@wordpress/block-editor';

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
						dangerouslySetInnerHTML={ {
							__html: sanitizeHTML( content, {
								tags: CONTENT_TAGS,
								attr: CONTENT_ATTR,
							} ),
						} }
					/>
				</>
			</Iframe>
		</div>
	);
}
