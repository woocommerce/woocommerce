/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import classnames from 'classnames';
import { createElement } from '@wordpress/element';
import type { BlockAttributes } from '@wordpress/blocks';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { TabButton } from './tab-button';
import { ProductEditorBlockEditProps } from '../../../types';

export interface TabBlockAttributes extends BlockAttributes {
	id: string;
	title: string;
	isSelected?: boolean;
}

export function TabBlockEdit( {
	setAttributes,
	attributes,
	context,
	// @ts-ignore
	children,
}: ProductEditorBlockEditProps< TabBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const {
		id,
		title,
		_templateBlockOrder: order,
		isSelected: contextIsSelected,
	} = attributes;
	const isSelected = context.selectedTab === id;
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
				{ children }
			</div>
		</div>
	);
}
