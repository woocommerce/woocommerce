/**
 * External dependencies
 */
import {
	createElement,
	cloneElement,
	useState,
	useEffect,
	useCallback,
} from '@wordpress/element';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { Sortable, moveIndex } from '../sortable';
import { ImageGalleryToolbar } from './index';
import { ImageGalleryChild } from './types';

export type ImageGalleryProps = {
	children: ImageGalleryChild | ImageGalleryChild[];
	columns?: number;
	onRemove?: ( removeIndex: number, removedItem: ImageGalleryChild ) => void;
	onOrderChange?: ( items: ImageGalleryChild[] ) => void;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGallery: React.FC< ImageGalleryProps > = ( {
	children,
	columns = 4,
	onOrderChange = () => null,
	onRemove = () => null,
}: ImageGalleryProps ) => {
	const [ toolBarItem, setToolBarItem ] = useState< string | null >( null );
	const [ orderedChildren, setOrderedChildren ] = useState<
		ImageGalleryChild[]
	>( [] );

	useEffect( () => {
		if ( ! children ) {
			return;
		}
		setOrderedChildren(
			( Array.isArray( children ) ? children : [ children ] ).map(
				( child, index ) =>
					cloneElement( child, { key: child.key || String( index ) } )
			)
		);
	}, [ children ] );

	const updateOrderedChildren = ( items: ImageGalleryChild[] ) => {
		setOrderedChildren( items );
		onOrderChange( orderedChildren );
	};

	const moveItem = useCallback(
		( fromIndex: number, toIndex: number ) => {
			updateOrderedChildren(
				moveIndex< ImageGalleryChild >(
					fromIndex,
					toIndex,
					orderedChildren
				)
			);
		},
		[ orderedChildren ]
	);

	const removeItem = useCallback(
		( removeIndex: number ) => {
			updateOrderedChildren(
				orderedChildren.filter( ( _, index ) => index !== removeIndex )
			);
			onRemove( removeIndex, orderedChildren[ removeIndex ] );
		},
		[ orderedChildren ]
	);

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
					updateOrderedChildren( items );
				} }
				notSortableIndexes={ [ 0 ] }
			>
				{ orderedChildren.map( ( child, childIndex ) => {
					const isToolbarItem = child.key === toolBarItem;
					const isCoverItem = childIndex === 0;

					return cloneElement(
						child,
						{
							isCover: isCoverItem,
							className: classnames( {
								'is-showing-toolbar': isToolbarItem,
							} ),
							onClick: () => {
								setToolBarItem(
									isToolbarItem
										? null
										: ( child.key as string )
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
								setToolBarItem={ setToolBarItem }
							/>
						) : null
					);
				} ) }
			</Sortable>
		</div>
	);
};
