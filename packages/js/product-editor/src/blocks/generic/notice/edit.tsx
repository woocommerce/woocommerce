/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import { NoticeBlockAttributes } from './types';
import { Notice } from '../../../components/notice';
import { sanitizeHTML } from '../../../utils/sanitize-html';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< NoticeBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	return (
		<div { ...blockProps }>
			<Notice
				content={
					<div
						dangerouslySetInnerHTML={ sanitizeHTML(
							attributes.message
						) }
					></div>
				}
			></Notice>
		</div>
	);
}
