/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { DragEventHandler } from 'react';

/**
 * Internal dependencies
 */
import { Sortable } from '../sortable';
import { ImageGalleryChild } from './types';

export type ImageGalleryWrapperProps = {
	children: JSX.Element[];
	allowDragging?: boolean;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
	updateOrderedChildren?: ( items: ImageGalleryChild[] ) => void;
};

export const ImageGalleryWrapper: React.FC< ImageGalleryWrapperProps > = ( {
	children,
	allowDragging = true,
	onDragStart = () => null,
	onDragEnd = () => null,
	onDragOver = () => null,
	updateOrderedChildren = () => null,
} ) => {
	if ( allowDragging ) {
		return (
			<Sortable
				isHorizontal
				onOrderChange={ ( items ) => {
					updateOrderedChildren( items );
				} }
				onDragStart={ ( event ) => {
					onDragStart( event );
				} }
				onDragEnd={ ( event ) => {
					onDragEnd( event );
				} }
				onDragOver={ onDragOver }
			>
				{ children }
			</Sortable>
		);
	}
	return (
		<div className="woocommerce-image-gallery__wrapper">{ children }</div>
	);
};
