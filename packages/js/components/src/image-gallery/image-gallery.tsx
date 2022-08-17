/**
 * External dependencies
 */
import { Children, createElement, Fragment } from '@wordpress/element';
import { DragEventHandler } from 'react';

/**
 * Internal dependencies
 */
import { Sortable } from '../sortable';

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
			<Sortable>
				{ Children.map( children, ( child ) => (
					<CustomListItem>{ child }</CustomListItem>
				) ) }
			</Sortable>
		</div>
	);
};
