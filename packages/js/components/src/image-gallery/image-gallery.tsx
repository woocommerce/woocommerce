/**
 * External dependencies
 */
import {
	Children,
	createElement,
	cloneElement,
	useState,
	useEffect,
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
		[]
	);
	const [ coverImage, setCoverImage ] = useState< JSX.Element | undefined >(
		orderedChildren[ 0 ]
	);

	useEffect( () => {
		if ( ! children ) {
			return;
		}
		setOrderedChildren(
			Array.isArray( children ) ? children : [ children ]
		);
	}, [ children ] );

	useEffect( () => {
		setCoverImage( orderedChildren[ 0 ] );
	}, [ orderedChildren ] );

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
				{ Children.map( orderedChildren, ( child, childIndex ) => {
					const isCoverImage = coverImage === child;

					return (
						<div
							className={ `woocommerce-image-gallery__item-wrapper ${
								isCoverImage ? 'not-sortable ' : ''
							}` }
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
							{ isCoverImage &&
								cloneElement( child, {
									isCover: true,
								} ) }

							{ ! isCoverImage && (
								<SortableHandle>{ child }</SortableHandle>
							) }
						</div>
					);
				} ) }
			</Sortable>
		</div>
	);
};
