/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Toolbar, ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { chevronRight, chevronLeft, trash } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { CoverImageIcon } from './icons';
import { SortableHandle } from '../sortable';

export type ImageGalleryToolbarProps = {
	childIndex: number;
	moveItem: ( fromIndex: number, toIndex: number ) => void;
	removeItem: ( removeIndex: number ) => void;
	setToolBarItem: ( key: string | null ) => void;
	lastChild: boolean;
	onDragStart?: () => void;
	onDragEnd?: () => void;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGalleryToolbar: React.FC< ImageGalleryToolbarProps > = ( {
	childIndex,
	moveItem,
	removeItem,
	setToolBarItem,
	lastChild,
	onDragStart,
	onDragEnd,
}: ImageGalleryToolbarProps ) => {
	const moveNext = () => {
		moveItem( childIndex, childIndex + 1 );
	};

	const movePrevious = () => {
		moveItem( childIndex, childIndex - 1 );
	};

	const setAsCoverImage = ( coverIndex: number ) => {
		moveItem( coverIndex, 0 );
		setToolBarItem( null );
	};

	return (
		<div className="woocommerce-image-gallery__toolbar">
			<Toolbar
				onClick={ ( e ) => e.stopPropagation() }
				label="Options"
				id="options-toolbar"
			>
				{ childIndex !== 0 && (
					<ToolbarGroup>
						<ToolbarButton
							icon={ () => (
								<SortableHandle
									onDragStart={ onDragStart }
									onDragEnd={ onDragEnd }
								/>
							) }
							label="Drag"
						/>
						<ToolbarButton
							className="woocommerce-image-gallery__toolbar-previous"
							disabled={ childIndex < 2 }
							onClick={ () => movePrevious() }
							icon={ chevronLeft }
							label="Move previous"
						/>
						<ToolbarButton
							className="woocommerce-image-gallery__toolbar-next"
							onClick={ () => moveNext() }
							icon={ chevronRight }
							label="Move next"
							disabled={ lastChild }
						/>
					</ToolbarGroup>
				) }
				{ childIndex !== 0 && (
					<ToolbarGroup>
						<ToolbarButton
							onClick={ () => setAsCoverImage( childIndex ) }
							icon={ CoverImageIcon }
							label="Set as cover image"
						/>
					</ToolbarGroup>
				) }
				<ToolbarGroup>
					<ToolbarButton>Replace</ToolbarButton>
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton
						onClick={ () => removeItem( childIndex ) }
						icon={ trash }
						label="Delete"
					/>
				</ToolbarGroup>
			</Toolbar>
		</div>
	);
};
