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
import { Sortable, SortableHandle, moveIndex } from '../sortable';

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
	const [ orderedChildren, setOrderedChildren ] = useState< JSX.Element[] >(
		Array.isArray( children ) ? children : [ children ]
	);

	const moveItem = ( fromIndex: number, toIndex: number ) => {
		setOrderedChildren(
			moveIndex< JSX.Element >( fromIndex, toIndex, orderedChildren )
		);
	};

	const removeItem = ( removeIndex: number ) => {
		setOrderedChildren(
			orderedChildren.filter( ( _, index ) => index !== removeIndex )
		);
	};

	const setAsCoverImage = ( coverIndex: number ) => {
		moveItem( coverIndex, 0 );
		setToolBarItem( null );
	};

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
				{ Children.map( orderedChildren, ( child, childIndex ) => (
					<div
						className="woocommerce-image-gallery__item-wrapper"
						//TODO: add correct keyboard handler
						onKeyPress={ () => {} }
						tabIndex={ 0 }
						role="button"
						onClick={ ( event ) => {
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
						{ toolBarItem === child && (
							<ImageGalleryToolbar
								childIndex={ childIndex }
								moveItem={ moveItem }
								removeItem={ removeItem }
								setAsCoverImage={ setAsCoverImage }
							/>
						) }
						<SortableHandle>
							{ cloneElement( child, {
								isCover: childIndex === 0 ? true : false,
							} ) }
						</SortableHandle>
					</div>
				) ) }
			</Sortable>
		</div>
	);
};
