/**
 * External dependencies
 */
import { Children, createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Sortable, SortableHandle } from '../sortable';

export type ImageGalleryProps = {
	children: JSX.Element | JSX.Element[];
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGallery: React.FC< ImageGalleryProps > = ( {
	children,
}: ImageGalleryProps ) => {
	return (
		<div className="woocommerce-image-gallery">
			<Sortable isHorizontal>
				{ Children.map( children, ( child ) => (
					<SortableHandle>{ child }</SortableHandle>
				) ) }
			</Sortable>
		</div>
	);
};
