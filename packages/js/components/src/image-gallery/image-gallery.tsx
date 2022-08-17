/**
 * External dependencies
 */
import { Children, createElement, Fragment } from '@wordpress/element';
import { DragEventHandler } from 'react';

/**
 * Internal dependencies
 */
import { SortableList } from '../sortable-list';

export type ImageGalleryProps = {
	children: JSX.Element | JSX.Element[];
} & React.HTMLAttributes< HTMLDivElement >;

type CustomListItemProps = {
	children: React.ReactNode;
	onDraggableEnd?: DragEventHandler< Element >;
	onDraggableStart?: DragEventHandler< Element >;
};

const CustomListItem = ( {
	children,
	onDraggableStart,
	onDraggableEnd,
}: CustomListItemProps ) => {
	return <>{ children }</>;
};

export const ImageGallery: React.FC< ImageGalleryProps > = ( {
	children,
}: ImageGalleryProps ) => {
	return (
		<div className="woocommerce-image-gallery">
			<SortableList>
				{ Children.map( children, ( child ) => (
					<CustomListItem>{ child }</CustomListItem>
				) ) }
			</SortableList>
		</div>
	);
};
