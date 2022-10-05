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
import { ConditionalWrapper } from '../util/conditional-wrapper';

export type ImageGalleryItemProps = {
	alt: string;
	isCover?: boolean;
	src: string;
	displayToolbar?: boolean;
	className?: string;
	onClick?: () => void;
	children?: JSX.Element;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGalleryItem: React.FC< ImageGalleryItemProps > = ( {
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
					<img alt={ alt } src={ src } />
				</>
			) : (
				<SortableHandle>
					<img alt={ alt } src={ src } />
				</SortableHandle>
			) }
		</div>
	</ConditionalWrapper>
);
