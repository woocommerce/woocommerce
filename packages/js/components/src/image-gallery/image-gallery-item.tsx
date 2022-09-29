/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, cloneElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Pill from '../pill';
import { SortableHandle } from '../sortable';

export type ImageGalleryItemProps = {
	alt: string;
	isCover?: boolean;
	src: string;
	displayToolbar: boolean;
	className?: string;
	onClick: () => void;
	onDragStart: () => void;
	onDragEnd: () => void;
	children?: JSX.Element;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGalleryItem: React.FC< ImageGalleryItemProps > = ( {
	alt,
	isCover = false,
	src,
	className = '',
	onClick = () => null,
	onDragStart,
	onDragEnd,
	children,
}: ImageGalleryItemProps ) => {
	return (
		<div
			className={ `woocommerce-image-gallery__item ${ className }` }
			//TODO: add correct keyboard handler
			onKeyPress={ () => {} }
			tabIndex={ 0 }
			role="button"
			onClick={ () => onClick() }
		>
			{ children && cloneElement( children, { onDragStart, onDragEnd } ) }

			<SortableHandle onDragStart={ onDragStart } onDragEnd={ onDragEnd }>
				{ isCover && <Pill>{ __( 'Cover', 'woocommerce' ) }</Pill> }
				<img alt={ alt } src={ src } />
			</SortableHandle>
		</div>
	);
};
