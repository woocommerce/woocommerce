/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
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
	const {
		content,
		id: blockId,
		isDismissible,
		title,
		type = 'info',
	} = attributes;
	const productId = useEntityId( 'postType', context.postType || 'product' );
	const { dismissedNotices, dismissNotice } = useNotice();
	const isDismissed = Object.values( dismissedNotices ).some( ( notice ) =>
		notice.some( ( product ) => product === productId )
	);
	if ( isDismissed ) {
		return null;
	}

	return (
		<div { ...blockProps }>
			<Notice
				title={ title }
				type={ type }
				isDismissible={ isDismissible }
				handleDismiss={ () => {
					if ( isDismissible && blockId !== '' ) {
						dismissNotice( blockId, productId );
					}
				} }
			>
				<p dangerouslySetInnerHTML={ sanitizeHTML( content ) } />
			</Notice>
		</div>
	);
}
