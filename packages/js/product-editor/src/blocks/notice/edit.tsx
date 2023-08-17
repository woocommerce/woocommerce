/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { createElement } from '@wordpress/element';
import type { BlockAttributes, BlockEditProps } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { Product } from '@woocommerce/data';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { Notice } from '../../components/notice';
import { hasAttributesUsedForVariations } from '../../utils';

export interface NoticeBlockAttributes extends BlockAttributes {
	buttonText: string;
	content: string;
	title: string;
	type: 'error-type' | 'success' | 'warning' | 'info';
}

export function Edit( {
	attributes,
}: BlockEditProps< NoticeBlockAttributes > ) {
	const blockProps = useBlockProps();
	const { buttonText, content, title, type = 'info' } = attributes;

	const [ productAttributes ] = useEntityProp< Product[ 'attributes' ] >(
		'postType',
		'product',
		'attributes'
	);

	const isOptionsNoticeVisible =
		hasAttributesUsedForVariations( productAttributes );

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
