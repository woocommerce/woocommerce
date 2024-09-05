/**
 * External dependencies
 */
import { createElement, createInterpolateElement } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Product } from '@woocommerce/data';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { recordEvent } from '@woocommerce/tracks';
import { Link } from '@woocommerce/components';
import { getNewPath } from '@woocommerce/navigation';
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

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< NoticeBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { content, isDismissible, title, type = 'info' } = attributes;
	const [ parentId ] = useEntityProp< number >(
		'postType',
		'product_variation',
		'parent_id'
	);
	const { dismissedNotices, dismissNotice, isResolving } = useNotice();
	const {
		parentName,
		isParentResolving,
	}: { parentName: string; isParentResolving: boolean } = useSelect(
		( select ) => {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			const { getEditedEntityRecord, hasFinishedResolution } =
				select( 'core' );
			const { name }: Product = getEditedEntityRecord(
				'postType',
				'product',
				parentId
			);
			const isResolutionFinished = ! hasFinishedResolution(
				'getEditedEntityRecord',
				[ 'postType', 'product', parentId ]
			);

			return {
				parentName: name || '',
				isParentResolving: isResolutionFinished,
			};
		}
	);

	if (
		dismissedNotices.includes( parentId ) ||
		isResolving ||
		isParentResolving ||
		parentName === ''
	) {
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
							href={ getNewPath(
								{ tab: 'variations' },
								`/product/${ parentId }`
							) }
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
