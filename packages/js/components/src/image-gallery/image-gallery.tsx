/**
 * External dependencies
 */
import {
	Children,
	createElement,
	cloneElement,
	useState,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ImageGalleryToolbar } from './index';
import { Sortable, SortableHandle } from '../sortable';

export type ImageGalleryProps = {
	children: JSX.Element | JSX.Element[];
	columns?: number;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGallery: React.FC< ImageGalleryProps > = ( {
	children,
	columns = 4,
}: ImageGalleryProps ) => {
	const [ toolbarItem, setToolbarItem ] = useState< number | null >( null );

	return (
		<div
			className="woocommerce-image-gallery"
			style={ {
				gridTemplateColumns: 'min-content '.repeat( columns ),
			} }
		>
			<Sortable isHorizontal>
				{ Children.map( children, ( child, index ) => (
					<SortableHandle>
						<div
							className="woocommerce-image-gallery__item-wrapper"
							onClick={ () => setToolbarItem( index ) }
						>
							{ toolbarItem === index && <ImageGalleryToolbar /> }
							{ child }
						</div>
					</SortableHandle>
				) ) }
			</Sortable>
		</div>
	);
};
