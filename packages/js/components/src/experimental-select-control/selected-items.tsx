/**
 * External dependencies
 */
import classnames from 'classnames';
import {
	createElement,
	forwardRef,
	useImperativeHandle,
	useRef,
} from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import Tag from '../tag';
import {
	getItemLabelType,
	getItemValueType,
	SelectedItemFocusHandle,
} from './types';

type SelectedItemsProps< ItemType > = {
	isReadOnly: boolean;
	items: ItemType[];
	getItemLabel: getItemLabelType< ItemType >;
	getItemValue: getItemValueType< ItemType >;
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore These are the types provided by Downshift.
	getSelectedItemProps: ( { selectedItem: any, index: any } ) => {
		[ key: string ]: string;
	};
	onRemove: ( item: ItemType ) => void;
	onBlur?: ( event: React.FocusEvent ) => void;
	onSelectedItemsEnd?: () => void;
};

const PrivateSelectedItems = < ItemType, >(
	{
		isReadOnly,
		items,
		getItemLabel,
		getItemValue,
		getSelectedItemProps,
		onRemove,
		onBlur,
		onSelectedItemsEnd,
	}: SelectedItemsProps< ItemType >,
	ref: React.ForwardedRef< SelectedItemFocusHandle >
) => {
	const classes = classnames(
		'woocommerce-experimental-select-control__selected-items',
		{
			'is-read-only': isReadOnly,
		}
	);

	const lastRemoveButtonRef = useRef< HTMLButtonElement >( null );

	useImperativeHandle(
		ref,
		() => {
			return () => lastRemoveButtonRef.current?.focus();
		},
		[]
	);

	if ( isReadOnly ) {
		return (
			<div className={ classes }>
				{ items
					.map( ( item ) => {
						return decodeEntities( getItemLabel( item ) );
					} )
					.join( ', ' ) }
			</div>
		);
	}

	const focusSibling = ( event: React.KeyboardEvent< HTMLDivElement > ) => {
		const selectedItem = ( event.target as HTMLElement ).closest(
			'.woocommerce-experimental-select-control__selected-item'
		);
		const sibling =
			event.key === 'ArrowLeft' || event.key === 'Backspace'
				? selectedItem?.previousSibling
				: selectedItem?.nextSibling;
		if ( sibling ) {
			(
				( sibling as HTMLElement ).querySelector(
					'.woocommerce-tag__remove'
				) as HTMLElement
			 )?.focus();
			return true;
		}
		return false;
	};

	return (
		<div className={ classes }>
			{ items.map( ( item, index ) => {
				return (
					// Disable reason: We prevent the default action to keep the input focused on click.
					// Keyboard users are unaffected by this change.
					/* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
					<div
						key={ `selected-item-${ index }` }
						className="woocommerce-experimental-select-control__selected-item"
						{ ...getSelectedItemProps( {
							selectedItem: item,
							index,
						} ) }
						onMouseDown={ ( event ) => {
							event.preventDefault();
						} }
						onClick={ ( event ) => {
							event.preventDefault();
						} }
						onKeyDown={ ( event ) => {
							if (
								event.key === 'ArrowLeft' ||
								event.key === 'ArrowRight'
							) {
								const focused = focusSibling( event );
								if (
									! focused &&
									event.key === 'ArrowRight' &&
									onSelectedItemsEnd
								) {
									onSelectedItemsEnd();
								}
							} else if (
								event.key === 'ArrowUp' ||
								event.key === 'ArrowDown'
							) {
								event.preventDefault(); // prevent unwanted scroll
							} else if ( event.key === 'Backspace' ) {
								onRemove( item );
								focusSibling( event );
							}
						} }
						onBlur={ onBlur }
					>
						{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
						{ /* @ts-ignore Additional props are not required. */ }
						<Tag
							id={ getItemValue( item ) }
							remove={ () => () => onRemove( item ) }
							label={ getItemLabel( item ) }
							ref={
								index === items.length - 1
									? lastRemoveButtonRef
									: undefined
							}
						/>
					</div>
				);
			} ) }
		</div>
	);
};

export const SelectedItems = forwardRef( PrivateSelectedItems ) as < ItemType >(
	props: SelectedItemsProps< ItemType > & {
		ref?: React.ForwardedRef< SelectedItemFocusHandle >;
	}
) => ReturnType< typeof PrivateSelectedItems >;
