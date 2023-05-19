/**
 * External dependencies
 */
import { Popover } from '@wordpress/components';
import classnames from 'classnames';
import {
	createElement,
	useRef,
	useState,
	createPortal,
	Children,
	useLayoutEffect,
} from '@wordpress/element';

type ListboxProps = {
	children?: JSX.Element | JSX.Element[];
	isOpen: boolean;
	className?: string;
};

export const Listbox = ( { children, isOpen, className }: ListboxProps ) => {
	const [ boundingRect, setBoundingRect ] = useState< DOMRect >();
	const selectControlListboxRef = useRef< HTMLDivElement >( null );

	useLayoutEffect( () => {
		if (
			selectControlListboxRef.current?.parentElement &&
			selectControlListboxRef.current?.parentElement.clientWidth > 0
		) {
			setBoundingRect(
				selectControlListboxRef.current.parentElement.getBoundingClientRect()
			);
		}
	}, [
		selectControlListboxRef.current,
		selectControlListboxRef.current?.clientWidth,
	] );

	/* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
	/* Disabled because of the onmouseup on the ul element below. */
	return (
		<div
			ref={ selectControlListboxRef }
			className="woocommerce-experimental-select-control__listbox"
		>
			<div>
				<Popover
					// @ts-expect-error this prop does exist, see: https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/popover/index.tsx#L180.
					__unstableSlotName="woocommerce-select-control-listbox"
					focusOnMount={ false }
					className={ classnames(
						'woocommerce-experimental-select-control__popover-listbox',
						{
							'is-open': isOpen,
							'has-results': Children.count( children ) > 0,
						}
					) }
					animate={ false }
					position="bottom center"
				>
					<ul
						className={ classnames(
							'woocommerce-experimental-select-control__popover-listbox-container',
							className
						) }
						style={ {
							width: boundingRect?.width,
						} }
						onMouseUp={ ( e ) =>
							// Fix to prevent select control dropdown from closing when selecting within the Popover.
							e.stopPropagation()
						}
					>
						{ isOpen && children }
					</ul>
				</Popover>
			</div>
		</div>
	);
	/* eslint-enable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
};

export const ListboxSlot: React.FC = () =>
	createPortal(
		<div aria-live="off">
			{ /* @ts-expect-error name does exist on PopoverSlot see: https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/popover/index.tsx#L555 */ }
			<Popover.Slot name="woocommerce-select-control-Listbox" />
		</div>,
		document.body
	);
