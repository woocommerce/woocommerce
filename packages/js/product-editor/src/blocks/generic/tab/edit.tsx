/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import classnames from 'classnames';
import { createElement, useEffect, useState } from '@wordpress/element';
import type { BlockAttributes } from '@wordpress/blocks';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { __experimentalErrorBoundary as ErrorBoundary } from '@woocommerce/components';

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
}: ProductEditorBlockEditProps< TabBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { id, title, _templateBlockOrder: order, isSelected } = attributes;

	const classes = classnames( 'wp-block-woocommerce-product-tab__content', {
		'is-selected': isSelected,
	} );

	const [ canRenderChildren, setCanRenderChildren ] = useState( false );

	useEffect( () => {
		if ( ! context.selectedTab ) return;

		const isSelectedInContext = context.selectedTab === id;

		setAttributes( { isSelected: isSelectedInContext } );

		if ( isSelectedInContext ) {
			setCanRenderChildren( true );
			return;
		}

		const timeoutId = setTimeout( setCanRenderChildren, 300, true );
		return () => clearTimeout( timeoutId );
	}, [ context.selectedTab, id, setAttributes ] );

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
				<ErrorBoundary
					errorMessage={ __(
						'An unexpected error occurred in this tab. Make sure any unsaved changes are saved and then try reloading the page to see if the error recurs.',
						'woocommerce'
					) }
					onError={ ( error, errorInfo ) => {
						// eslint-disable-next-line no-console
						console.error(
							`Error caught in tab '${ id }'`,
							error,
							errorInfo
						);
					} }
				>
					{ canRenderChildren && (
						/* eslint-disable-next-line @typescript-eslint/ban-ts-comment */
						/* @ts-ignore Content only template locking does exist for this property. */
						<InnerBlocks templateLock="contentOnly" />
					) }
				</ErrorBoundary>
			</div>
		</div>
	);
}
