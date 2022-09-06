/**
 * External dependencies
 */
import { Children, createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Sortable, SortableHandle } from '../sortable';

export type ImageGalleryProps = {
	children: JSX.Element | JSX.Element[];
	columns?: number;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGallery: React.FC< ImageGalleryProps > = ( {
	children,
	columns = 4,
}: ImageGalleryProps ) => {
	return (
		<div
			className="woocommerce-image-gallery"
			style={ {
				gridTemplateColumns: 'min-content '.repeat( columns ),
			} }
		>
			<Sortable isHorizontal>
				{ Children.map( children, ( child ) => (
					<SortableHandle>{ child }</SortableHandle>
				) ) }
			</Sortable>
		</div>
	);
};
