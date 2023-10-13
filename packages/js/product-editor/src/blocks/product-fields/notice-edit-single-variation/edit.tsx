/**
 * External dependencies
 */
import { createElement, createInterpolateElement } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Product } from '@woocommerce/data';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { recordEvent } from '@woocommerce/tracks';
import { Link } from '@woocommerce/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { Notice } from '../../../components/notice';
import { ProductEditorBlockEditProps } from '../../../types';
import { NoticeBlockAttributes } from './types';
import { useNotice } from '../../../hooks/use-notice';
import { useConfirmUnsavedProductChanges } from '../../../hooks/use-confirm-unsaved-product-changes';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< NoticeBlockAttributes > ) {
	useConfirmUnsavedProductChanges( 'product_variation' );
	const blockProps = useWooBlockProps( attributes );
	const {
		content,
		isDismissible,
		noticeLink: url,
		title,
		type = 'info',
	} = attributes;
	const [ parentId ] = useEntityProp< number >( 'postType', 'product_variation', 'parent_id' );
	const { dismissedNotices, dismissNotice } = useNotice();
	const {
		parentName,
		isResolving,
	}: { parentName: string; isResolving: boolean } = useSelect(
		( select ) => {
			const { getEditedEntityRecord, hasFinishedResolution } =
				select( 'core' );
			const { name }: Product = getEditedEntityRecord(
				'postType',
				'product',
				parentId
			);
			const isParentResolving = ! hasFinishedResolution(
				'getEditedEntityRecord',
				[ 'postType', 'product', parentId ]
			);

			return {
				parentName: name || '',
				isResolving: isParentResolving,
			};
		}
	);

	if ( dismissedNotices.includes( parentId ) || isResolving || parentName === '' ) {
		return null;
	}

	return (
		<div { ...blockProps }>
			<Notice
				title={ title }
				type={ type }
				isDismissible={ isDismissible }
				handleDismiss={ () => {
					recordEvent( 'product_single_variation_notice_dismissed' );
					dismissNotice( parentId );
				} }
			>
				{ createInterpolateElement( content, {
					strong: <strong />,
					noticeLink: (
						<Link
							href={ url }
							onClick={ () => {
								recordEvent(
									'product_single_variation_notice_click'
								);
							} }
						/>
					),
					parentProductName: <span>{ parentName }</span>,
				} ) }
			</Notice>
		</div>
	);
}
