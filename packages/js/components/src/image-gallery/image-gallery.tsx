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
import classnames from 'classnames';

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
					const isToolbarItem = child === toolBarItem;

					return cloneElement(
						child,
						{
							isCover: childIndex === 0,
							className: classnames( {
								'not-sortable': childIndex === 0,
								'is-showing-toolbar': isToolbarItem,
							} ),
							onClick: () => {
								setToolBarItem(
									Boolean( child ) && isToolbarItem
										? null
										: child
								);
							},
						},
						isToolbarItem ? (
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
