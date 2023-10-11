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
import { useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { Notice } from '../../../components/notice';
import { ProductEditorBlockEditProps } from '../../../types';
import { NoticeBlockAttributes } from './types';
import { useNotice } from '../../../hooks/use-notice';

export function Edit( {
	attributes,
	context,
}: ProductEditorBlockEditProps< NoticeBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const {
		content,
		isDismissible,
		noticeLink: url,
		title,
		type = 'info',
	} = attributes;
	const productId = useEntityId( 'postType', context.postType || 'product' );
	const { dismissedNotices, dismissNotice } = useNotice();
	const {
		name,
		parentId,
		isResolving,
	}: { name: string; parentId: number; isResolving: boolean } = useSelect(
		( select ) => {
			const { getEditedEntityRecord, hasFinishedResolution } =
				select( 'core' );
			const { parent_id: productIdentificator }: Product =
				getEditedEntityRecord( 'postType', 'product', productId );

			const isVariationResolving = ! hasFinishedResolution(
				'getEditedEntityRecord',
				[ 'postType', 'product', productId ]
			);

			if ( isVariationResolving ) {
				return {
					parentId: productIdentificator,
					name: '',
					isResolving: true,
				};
			}

			const { name: parentName }: Product = getEditedEntityRecord(
				'postType',
				'product',
				productIdentificator
			);
			const isParentResolving = ! hasFinishedResolution(
				'getEditedEntityRecord',
				[ 'postType', 'product', productIdentificator ]
			);

			return {
				parentId: productIdentificator || 0,
				name: parentName || '',
				isResolving: isParentResolving || isVariationResolving,
			};
		},
		[ productId ]
	);

	if ( dismissedNotices.includes( parentId ) || isResolving || name === '' ) {
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
					parentProductName: <span>{ name }</span>,
				} ) }
			</Notice>
		</div>
	);
}
