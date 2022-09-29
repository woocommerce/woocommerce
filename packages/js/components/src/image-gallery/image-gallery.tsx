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
import { Sortable, moveIndex } from '../sortable';
import { ImageGalleryToolbar } from './index';

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

	useEffect( () => {
		if ( ! children ) {
			return;
		}
		setOrderedChildren(
			Array.isArray( children ) ? children : [ children ]
		);
	}, [ children ] );

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
					return cloneElement(
						child,
						{
							isCover: childIndex === 0,
							onItemClick: () => {
								setToolBarItem(
									Boolean( child ) && toolBarItem === child
										? null
										: child
								);
							},
						},
						child === toolBarItem ? (
							<ImageGalleryToolbar
								childIndex={ childIndex }
								lastChild={
									childIndex === orderedChildren.length - 1
								}
								moveItem={ moveItem }
								removeItem={ removeItem }
								setAsCoverImage={ setAsCoverImage }
							/>
						) : null
					);
				} ) }
			</Sortable>
		</div>
	);
};
