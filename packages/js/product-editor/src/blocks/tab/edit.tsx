/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import classnames from 'classnames';
import { createElement } from '@wordpress/element';
import type { BlockAttributes, BlockEditProps } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { Product } from '@woocommerce/data';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { TabButton } from './tab-button';
import { Notice } from '../../components/notice';
import {
	hasAttributesUsedForVariations,
	isSelectedTabApplicableForOptionsNotice,
} from '../../utils';

export interface TabBlockAttributes extends BlockAttributes {
	id: string;
	title: string;
	order: number;
	isSelected?: boolean;
}

export function Edit( {
	setAttributes,
	attributes,
	context,
}: BlockEditProps< TabBlockAttributes > & {
	context?: {
		selectedTab?: string | null;
	};
} ) {
	const blockProps = useBlockProps();
	const { id, title, order, isSelected: contextIsSelected } = attributes;
	const isSelected = context?.selectedTab === id;
	if ( isSelected !== contextIsSelected ) {
		setAttributes( { isSelected } );
	}

	const classes = classnames( 'wp-block-woocommerce-product-tab__content', {
		'is-selected': isSelected,
	} );

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
			<TabButton id={ id } selected={ isSelected } order={ order }>
				{ title }
			</TabButton>
			<div
				id={ `woocommerce-product-tab__${ id }-content` }
				aria-labelledby={ `woocommerce-product-tab__${ id }` }
				role="tabpanel"
				className={ classes }
			>
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
							{ __( 'Go to Options', 'woocommerce' ) }
						</Button>
					</Notice>
				) }
				{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
				{ /* @ts-ignore Content only template locking does exist for this property. */ }
				<InnerBlocks templateLock="contentOnly" />
			</div>
		</div>
	);
}
