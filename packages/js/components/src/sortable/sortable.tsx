/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import {
	createElement,
	useCallback,
	useEffect,
	useRef,
	useState,
	createContext,
} from '@wordpress/element';
import { Context, DragEvent, DragEventHandler, KeyboardEvent } from 'react';
import { speak } from '@wordpress/a11y';
import { throttle } from 'lodash';
import { v4 } from 'uuid';

/**
 * Internal dependencies
 */
import {
	getItemName,
	getNextIndex,
	getPreviousIndex,
	isBefore,
	isDraggingOverAfter,
	isDraggingOverBefore,
	isLastDroppable,
	moveIndex,
} from './utils';
import { SortableItem } from './sortable-item';
import { SortableChild } from './types';

export type SortableProps = {
	children: SortableChild | SortableChild[] | null | undefined;
	isHorizontal?: boolean;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onOrderChange?: ( items: SortableChild[] ) => void;
};

const THROTTLE_TIME = 16;

export const SortableContext: Context< {} > = createContext( {} );

export const Sortable = ( {
	children,
	isHorizontal = false,
	onDragEnd = () => null,
	onDragOver = () => null,
	onDragStart = () => null,
	onOrderChange = () => null,
}: SortableProps ) => {
	const ref = useRef< HTMLOListElement >( null );
	const [ items, setItems ] = useState< SortableChild[] >( [] );
	const [ selectedIndex, setSelectedIndex ] = useState< number >( -1 );
	const [ dragIndex, setDragIndex ] = useState< number | null >( null );
	const [ dropIndex, setDropIndex ] = useState< number | null >( null );

	useEffect( () => {
		if ( ! children ) {
			return;
		}
		setItems( Array.isArray( children ) ? children : [ children ] );
	}, [ children ] );

	const resetIndexes = () => {
		setTimeout( () => {
			setDragIndex( null );
			setDropIndex( null );
		}, THROTTLE_TIME );
	};

	const persistItemOrder = () => {
		if (
			dropIndex !== null &&
			dragIndex !== null &&
			dropIndex !== dragIndex
		) {
			const nextItems = moveIndex( dragIndex, dropIndex, items );
			setItems( nextItems as JSX.Element[] );
			onOrderChange( nextItems );
		}
		resetIndexes();
	};

	const handleDragStart = (
		event: DragEvent< HTMLDivElement >,
		index: number
	) => {
		setDropIndex( index );
		setDragIndex( index );
		onDragStart( event );
	};

	const handleDragEnd = ( event: DragEvent< HTMLDivElement > ) => {
		persistItemOrder();
		onDragEnd( event );
	};

	const handleDragOver = (
		event: DragEvent< HTMLLIElement >,
		index: number
	) => {
		if ( dragIndex === null ) {
			return;
		}

		// Items before the current item cause a one off error when
		// removed from the old array and spliced into the new array.
		// TODO: Issue with dragging into same position having to do with isBefore returning true intially.
		let targetIndex = dragIndex < index ? index : index + 1;
		if ( isBefore( event, isHorizontal ) ) {
			targetIndex--;
		}

		setDropIndex( targetIndex );
		onDragOver( event );
	};

	const throttledHandleDragOver = useCallback(
		throttle( handleDragOver, THROTTLE_TIME ),
		[ dragIndex ]
	);

	const handleKeyDown = ( event: KeyboardEvent< HTMLLIElement > ) => {
		const { key } = event;
		const isSelecting = dragIndex === null || dropIndex === null;
		const selectedLabel = getItemName( ref.current, selectedIndex );

		// Select or drop on spacebar press.
		if ( key === ' ' ) {
			if ( isSelecting ) {
				speak(
					sprintf(
						/** Translators: Selected item label */
						__(
							'%s selected, use up and down arrow keys to reorder',
							'woocommerce'
						),
						selectedLabel
					),
					'assertive'
				);
				setDragIndex( selectedIndex );
				setDropIndex( selectedIndex );
				return;
			}

			setSelectedIndex( dropIndex );
			speak(
				sprintf(
					/* translators: %1$s: Selected item label, %2$d: Current position in list, %3$d: List total length */
					__(
						'%1$s dropped, position in list: %2$d of %3$d',
						'woocommerce'
					),
					selectedLabel,
					dropIndex + 1,
					items.length
				),
				'assertive'
			);
			persistItemOrder();
			return;
		}

		if ( key === 'ArrowUp' ) {
			if ( isSelecting ) {
				setSelectedIndex(
					getPreviousIndex( selectedIndex, items.length )
				);
				return;
			}
			const previousDropIndex = getPreviousIndex(
				dropIndex,
				items.length
			);
			setDropIndex( previousDropIndex );
			speak(
				sprintf(
					/* translators: %1$s: Selected item label, %2$d: Current position in list, %3$d: List total length */
					__( '%1$s, position in list: %2$d of %3$d', 'woocommerce' ),
					selectedLabel,
					previousDropIndex + 1,
					items.length
				),
				'assertive'
			);
			return;
		}

		if ( key === 'ArrowDown' ) {
			if ( isSelecting ) {
				setSelectedIndex( getNextIndex( selectedIndex, items.length ) );
				return;
			}
			const nextDropIndex = getNextIndex( dropIndex, items.length );
			setDropIndex( nextDropIndex );
			speak(
				sprintf(
					/* translators: %1$s: Selected item label, %2$d: Current position in list, %3$d: List total length */
					__( '%1$s, position in list: %2$d of %3$d', 'woocommerce' ),
					selectedLabel,
					nextDropIndex + 1,
					items.length
				),
				'assertive'
			);
			return;
		}

		if ( key === 'Escape' ) {
			resetIndexes();
			speak(
				__(
					'Reordering cancelled. Restoring the original list order',
					'woocommerce'
				),
				'assertive'
			);
		}
	};

	return (
		<SortableContext.Provider value={ {} }>
			<ol
				className={ classnames( 'woocommerce-sortable', {
					'is-dragging': dragIndex !== null,
					'is-horizontal': isHorizontal,
				} ) }
				ref={ ref }
				role="listbox"
			>
				{ items.map( ( child, index ) => {
					const isDragging = index === dragIndex;
					const itemClasses = classnames( {
						'is-dragging-over-after': isDraggingOverAfter(
							index,
							dragIndex,
							dropIndex
						),
						'is-dragging-over-before': isDraggingOverBefore(
							index,
							dragIndex,
							dropIndex
						),
						'is-last-droppable': isLastDroppable(
							index,
							dragIndex,
							items.length
						),
					} );

					if (
						child.props.className &&
						child.props.className.indexOf( 'non-sortable-item' ) !==
							-1
					) {
						return <li>{ child }</li>;
					}

					return (
						<SortableItem
							key={ child.key || index }
							className={ itemClasses }
							id={ `${ index }-${ v4() }` }
							index={ index }
							isDragging={ isDragging }
							isSelected={ selectedIndex === index }
							onDragEnd={ ( event ) => handleDragEnd( event ) }
							onDragStart={ ( event ) =>
								handleDragStart( event, index )
							}
							onDragOver={ ( event ) => {
								event.preventDefault();
								throttledHandleDragOver( event, index );
							} }
							onKeyDown={ ( event ) => handleKeyDown( event ) }
						>
							{ child }
						</SortableItem>
					);
				} ) }
			</ol>
		</SortableContext.Provider>
	);
};
