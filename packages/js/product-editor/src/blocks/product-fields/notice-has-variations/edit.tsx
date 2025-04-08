/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type { BlockAttributes } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { Product } from '@woocommerce/data';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { Notice } from '../../../components/notice';
import { hasAttributesUsedForVariations } from '../../../utils';
import { ProductEditorBlockEditProps } from '../../../types';

export interface NoticeBlockAttributes extends BlockAttributes {
	buttonText: string;
	content: string;
	title: string;
	type: 'error-type' | 'success' | 'warning' | 'info';
}

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< NoticeBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { buttonText, content, title, type = 'info' } = attributes;

	const [ productAttributes ] = useEntityProp< Product[ 'attributes' ] >(
		'postType',
		'product',
		'attributes'
	);

	const [ productType ] = useEntityProp( 'postType', 'product', 'type' );

	const isOptionsNoticeVisible =
		hasAttributesUsedForVariations( productAttributes ) &&
		productType === 'variable';

	return (
		<div { ...blockProps }>
			{ isOptionsNoticeVisible && (
				<Notice content={ content } title={ title } type={ type }>
					<Button
						isSecondary={ true }
						onClick={ () =>
							navigateTo( {
								url: getNewPath( { tab: 'variations' } ),
							} )
						}
					>
						{ buttonText }
					</Button>
				</Notice>
			) }
		</div>
	);
}
