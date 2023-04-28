/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import classnames from 'classnames';
import { createElement } from '@wordpress/element';
import type { BlockAttributes } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { TabButton } from './tab-button';

export function Edit( {
	attributes,
	context,
}: {
	attributes: BlockAttributes;
	context?: {
		selectedTab?: string | null;
	};
} ) {
	const blockProps = useBlockProps();
	const { id, title } = attributes;
	const isSelected = context?.selectedTab === id;

	const classes = classnames( 'wp-block-woocommerce-product-tab__content', {
		'is-selected': isSelected,
	} );

	return (
		<div { ...blockProps }>
			<TabButton id={ id } selected={ isSelected }>
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
