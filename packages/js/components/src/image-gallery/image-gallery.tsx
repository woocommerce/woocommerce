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
import { MediaUpload } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import { Sortable, moveIndex } from '../sortable';
import { ImageGalleryToolbar } from './index';
import { ImageGalleryChild, MediaUploadComponentType } from './types';

export type ImageGalleryProps = {
	children: ImageGalleryChild | ImageGalleryChild[];
	columns?: number;
	onRemove?: ( props: {
		removeIndex: number;
		removedItem: ImageGalleryChild;
	} ) => void;
	onReplace?: ( props: {
		replaceIndex: number;
		previousItem: ImageGalleryChild;
		newItem: ImageGalleryChild;
	} ) => void;
	onOrderChange?: ( items: ImageGalleryChild[] ) => void;
	MediaUploadComponent?: MediaUploadComponentType;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGallery: React.FC< ImageGalleryProps > = ( {
	children,
	columns = 4,
	onOrderChange = () => null,
	onRemove = () => null,
	onReplace = () => null,
	MediaUploadComponent = MediaUpload,
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
			onRemove( {
				removeIndex,
				removedItem: orderedChildren[ removeIndex ],
			} );
		},
		[ orderedChildren ]
	);

	const replaceItem = useCallback(
		( replaceIndex: number, newSrc: string, newAlt: string ) => {
			const newChildren = [ ...orderedChildren ];
			newChildren.splice(
				replaceIndex,
				1,
				cloneElement( orderedChildren[ replaceIndex ], {
					src: newSrc,
					alt: newAlt,
				} )
			);
			onReplace( {
				replaceIndex,
				previousItem: orderedChildren[ replaceIndex ],
				newItem: newChildren[ replaceIndex ],
			} );
			updateOrderedChildren( newChildren );
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
							onBlur: (
								event: React.FocusEvent< HTMLDivElement >
							) => {
								if (
									event.currentTarget.contains(
										event.relatedTarget
									) ||
									( event.relatedTarget &&
										(
											event.relatedTarget as Element
										 ).closest(
											'.components-modal__frame'
										) )
								) {
									return;
								}
								setToolBarItem( null );
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
								replaceItem={ replaceItem }
								setToolBarItem={ setToolBarItem }
								MediaUploadComponent={ MediaUploadComponent }
							/>
						) : null
					);
				} ) }
			</Sortable>
		</div>
	);
};
