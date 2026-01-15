/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { sanitizeHTML } from '@woocommerce/sanitize';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import { NoticeBlockAttributes } from './types';
import { Notice } from '../../../components/notice';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< NoticeBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	return (
		<div { ...blockProps }>
			<Notice
				content={
					<div
						dangerouslySetInnerHTML={ {
							__html: sanitizeHTML( attributes.message ),
						} }
					></div>
				}
			></Notice>
		</div>
	);
}
