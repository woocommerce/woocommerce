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
	const [ toolBarItem, setToolBarItem ] = useState<
		JSX.Element | null | undefined
	>( null );
	const [ orderedChildren, setOrderedChildren ] = useState( children );

	return (
		<div
			className="woocommerce-image-gallery"
			style={ {
				gridTemplateColumns: 'min-content '.repeat( columns ),
			} }
		>
			<Sortable
				isHorizontal
				onOrderChange={ ( items ) => {
					setOrderedChildren( items );
				} }
				onDragStart={ () => setToolBarItem( null ) }
			>
				{ Children.map( orderedChildren, ( child ) => (
					<SortableHandle>
						<div
							className="woocommerce-image-gallery__item-wrapper"
							//TODO: add correct keyboard handler
							onKeyPress={ () => {} }
							tabIndex={ 0 }
							role="button"
							onClick={ ( event ) => {
								console.debug( event.target );
								if (
									( event.target as Element ).closest(
										'.woocommerce-image-gallery__toolbar'
									) !== null
								) {
									return;
								}
								setToolBarItem(
									Boolean( child ) && toolBarItem === child
										? null
										: child
								);
							} }
						>
							{ toolBarItem === child && <ImageGalleryToolbar /> }
							{ child }
						</div>
					</SortableHandle>
				) ) }
			</Sortable>
		</div>
	);
};
