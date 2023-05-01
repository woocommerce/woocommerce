/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Pill from '../pill';
import { SortableHandle, NonSortableItem } from '../sortable';
import { ConditionalWrapper } from '../conditional-wrapper';

export type ImageGalleryItemProps = {
	id?: string;
	alt: string;
	isCover?: boolean;
	src: string;
	displayToolbar?: boolean;
	className?: string;
	onClick?: () => void;
	children?: JSX.Element;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGalleryItem: React.FC< ImageGalleryItemProps > = ( {
	id,
	alt,
	isCover = false,
	src,
	className = '',
	onClick = () => null,
	onBlur = () => null,
	children,
}: ImageGalleryItemProps ) => (
	<ConditionalWrapper
		condition={ isCover }
		wrapper={ ( wrappedChildren ) => (
			<NonSortableItem>{ wrappedChildren }</NonSortableItem>
		) }
	>
		<div
			className={ `woocommerce-image-gallery__item ${ className }` }
			onKeyPress={ () => {} }
			tabIndex={ 0 }
			role="button"
			onClick={ ( event ) => onClick( event ) }
			onBlur={ ( event ) => onBlur( event ) }
		>
			{ children }

			{ isCover ? (
				<>
					<Pill>{ __( 'Cover', 'woocommerce' ) }</Pill>
					<img alt={ alt } src={ src } id={ id } />
				</>
			) : (
				<SortableHandle>
					<img alt={ alt } src={ src } id={ id } />
				</SortableHandle>
			) }
		</div>
	</ConditionalWrapper>
);
