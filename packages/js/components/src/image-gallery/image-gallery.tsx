/**
 * External dependencies
 */
import { Children, createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Handle } from '../list-item';
import { Sortable } from '../sortable';

export type ImageGalleryProps = {
	children: JSX.Element | JSX.Element[];
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGallery: React.FC< ImageGalleryProps > = ( {
	children,
}: ImageGalleryProps ) => {
	return (
		<div className="woocommerce-image-gallery">
			<Sortable>
				{ Children.map( children, ( child ) => (
					<Handle>{ child }</Handle>
				) ) }
			</Sortable>
		</div>
	);
};
