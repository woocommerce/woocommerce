/**
 * External dependencies
 */
import classnames from 'classnames';
import { createElement } from '@wordpress/element';

export type SortablePlaceholderProps = {
	isOver: boolean;
};

export const SortablePlaceholder = ( {
	isOver = false,
}: SortablePlaceholderProps ) => {
	return (
		<li
			className={ classnames( 'woocommerce-sortable__placeholder', {
				'is-over': isOver,
			} ) }
			onDragOver={ ( event ) => {
				event.preventDefault();
			} }
		/>
	);
};
