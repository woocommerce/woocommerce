/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
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
import {
	hasAttributesUsedForVariations,
	isSelectedTabApplicableForOptionsNotice,
} from '../../utils';

export interface NoticeBlockAttributes extends BlockAttributes {
	id: string;
	title: string;
	content: string;
	buttonText: string;
	type: string;
	isSelected?: boolean;
}

export function Edit( {
	setAttributes,
	attributes,
	context,
}: BlockEditProps< NoticeBlockAttributes > & {
	context?: {
		selectedTab?: string | null;
	};
} ) {
	const blockProps = useBlockProps();
	const { id, isSelected: contextIsSelected } = attributes;
	const isSelected = context?.selectedTab === id;
	if ( isSelected !== contextIsSelected ) {
		setAttributes( { isSelected } );
	}

	const [ productAttributes ] = useEntityProp< Product[ 'attributes' ] >(
		'postType',
		'product',
		'attributes'
	);

	const isOptionsNoticeVisible =
		hasAttributesUsedForVariations( productAttributes ) &&
		isSelectedTabApplicableForOptionsNotice( context?.selectedTab );

	return (
		<div { ...blockProps }>
			{ isOptionsNoticeVisible && (
				<Notice
					description={ __(
						"This product has options, such as size or color. You can now manage each variation's price and other details individually.",
						'woocommerce'
					) }
					status="info"
					className="woocommerce-product-tab__notice"
				>
					<Button
						isSecondary={ true }
						onClick={ () =>
							navigateTo( {
								url: getNewPath(
									{ tab: 'variations' },
									'/add-product',
									{}
								),
							} )
						}
					>
						{ __( 'Go to Variations', 'woocommerce' ) }
					</Button>
				</Notice>
			) }
		</div>
	);
}
