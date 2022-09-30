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

export type ImageGalleryProps = {
	children: JSX.Element | JSX.Element[];
	columns?: number;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGallery: React.FC< ImageGalleryProps > = ( {
	children,
	columns = 4,
}: ImageGalleryProps ) => {
	const [ toolBarItem, setToolBarItem ] = useState< string | null >( null );
	const [ orderedChildren, setOrderedChildren ] = useState< JSX.Element[] >(
		[]
	);

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

	const moveItem = useCallback(
		( fromIndex: number, toIndex: number ) => {
			setOrderedChildren(
				moveIndex< JSX.Element >( fromIndex, toIndex, orderedChildren )
			);
		},
		[ orderedChildren ]
	);

	const removeItem = useCallback(
		( removeIndex: number ) => {
			setOrderedChildren(
				orderedChildren.filter( ( _, index ) => index !== removeIndex )
			);
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
					setOrderedChildren( items );
				} }
			>
				{ orderedChildren.map( ( child, childIndex ) => {
					const isToolbarItem = child.key === toolBarItem;
					const isCoverItem = childIndex === 0;

					return cloneElement(
						child,
						{
							isCover: isCoverItem,
							className: classnames( {
								'not-sortable': isCoverItem,
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
