/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import classnames from 'classnames';
import { createElement } from '@wordpress/element';
import type { BlockAttributes, BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { TabButton } from './tab-button';

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
				{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
				{ /* @ts-ignore Content only template locking does exist for this property. */ }
				<InnerBlocks templateLock="contentOnly" />
			</div>
		</div>
	);
}
