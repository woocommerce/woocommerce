/**
 * External dependencies
 */
import {
	createElement,
	cloneElement,
	useState,
	useEffect,
} from '@wordpress/element';
import { DragEventHandler } from 'react';
import classnames from 'classnames';
import { MediaUpload } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import { Sortable, moveIndex } from '../sortable';
import { ImageGalleryToolbar } from './index';
import { ImageGalleryChild, MediaUploadComponentType } from './types';
import { removeItem, replaceItem } from './utils';

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
	} ) => void;
	onSelectAsCover?: ( itemId: string | null ) => void;
	onOrderChange?: ( items: ImageGalleryChild[] ) => void;
	MediaUploadComponent?: MediaUploadComponentType;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGallery: React.FC< ImageGalleryProps > = ( {
	children,
	columns = 4,
	onSelectAsCover = () => null,
	onOrderChange = () => null,
	onRemove = () => null,
	onReplace = () => null,
	MediaUploadComponent = MediaUpload,
	onDragStart = () => null,
	onDragEnd = () => null,
	onDragOver = () => null,
}: ImageGalleryProps ) => {
	const [ activeToolbarKey, setActiveToolbarKey ] = useState< string | null >(
		null
	);
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
		onOrderChange( items );
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
					updateOrderedChildren( items );
				} }
				onDragStart={ onDragStart }
				onDragEnd={ onDragEnd }
				onDragOver={ onDragOver }
			>
				{ orderedChildren.map( ( child, childIndex ) => {
					const isToolbarVisible = child.key === activeToolbarKey;
					const isCoverItem = childIndex === 0;

					return cloneElement(
						child,
						{
							isCover: isCoverItem,
							className: classnames( {
								'is-toolbar-visible': isToolbarVisible,
							} ),
							onClick: () => {
								setActiveToolbarKey(
									isToolbarVisible
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
										 ).closest( '.media-modal' ) )
								) {
									return;
								}
								setActiveToolbarKey( null );
							},
						},
						isToolbarVisible ? (
							<ImageGalleryToolbar
								childIndex={ childIndex }
								lastChild={
									childIndex === orderedChildren.length - 1
								}
								moveItem={ (
									fromIndex: number,
									toIndex: number
								) => {
									updateOrderedChildren(
										moveIndex< ImageGalleryChild >(
											fromIndex,
											toIndex,
											orderedChildren
										)
									);
								} }
								removeItem={ ( removeIndex: number ) => {
									onRemove( {
										removeIndex,
										removedItem:
											orderedChildren[ removeIndex ],
									} );
									updateOrderedChildren(
										removeItem(
											orderedChildren,
											removeIndex
										)
									);
								} }
								replaceItem={ (
									replaceIndex: number,
									newSrc: string,
									newAlt: string
								) => {
									onReplace( {
										replaceIndex,
										previousItem:
											orderedChildren[ replaceIndex ],
									} );
									updateOrderedChildren(
										replaceItem< {
											src: string;
											alt: string;
										} >( orderedChildren, replaceIndex, {
											src: newSrc,
											alt: newAlt,
										} )
									);
								} }
								setToolBarItem={ ( toolBarItem )=> {
									onSelectAsCover( activeToolbarKey );
									setActiveToolbarKey( toolBarItem );
								} }
								MediaUploadComponent={ MediaUploadComponent }
							/>
						) : null
					);
				} ) }
			</Sortable>
		</div>
	);
};
