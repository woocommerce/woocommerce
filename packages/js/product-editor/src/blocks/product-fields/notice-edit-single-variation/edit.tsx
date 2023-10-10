/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Product } from '@woocommerce/data';
import { useWooBlockProps } from '@woocommerce/block-templates';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { Notice } from '../../../components/notice';
import { ProductEditorBlockEditProps } from '../../../types';
import { NoticeBlockAttributes } from './types';
import { sanitizeHTML } from '../../../utils/sanitize-html';
import { useNotice } from '../../../hooks/use-notice';

export function Edit( {
	attributes,
	context,
}: ProductEditorBlockEditProps< NoticeBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { content, isDismissible, title, type = 'info' } = attributes;
	const productId = useEntityId( 'postType', context.postType );
	const { parent_id: parentId }: Product = useSelect( ( select ) =>
		select( 'core' ).getEditedEntityRecord(
			'postType',
			context.postType || 'product',
			productId
		)
	);
	const { dismissedNotices, dismissNotice } = useNotice();

	if ( dismissedNotices.includes( parentId ) ) {
		return null;
	}

	return (
		<div { ...blockProps }>
			<Notice
				title={ title }
				type={ type }
				isDismissible={ isDismissible }
				handleDismiss={ () => {
					if ( isDismissible ) {
						dismissNotice( parentId );
					}
				} }
			>
				<p dangerouslySetInnerHTML={ sanitizeHTML( content ) } />
			</Notice>
		</div>
	);
}
