/**
 * External dependencies
 */
import { Popover } from '@wordpress/components';
import classnames from 'classnames';
import {
	createElement,
	useEffect,
	useRef,
	useState,
	createPortal,
	Children,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getMenuPropsType } from './types';

type MenuProps = {
	children?: JSX.Element | JSX.Element[];
	getMenuProps: getMenuPropsType;
	isOpen: boolean;
	className?: string;
	position?: Popover.Position;
	scrollIntoViewOnOpen?: boolean;
};

export const Menu = ( {
	children,
	getMenuProps,
	isOpen,
	className,
	position = 'bottom center',
	scrollIntoViewOnOpen = false,
}: MenuProps ) => {
	const [ boundingRect, setBoundingRect ] = useState< DOMRect >();
	const selectControlMenuRef = useRef< HTMLDivElement >( null );

	useEffect( () => {
		if ( selectControlMenuRef.current?.parentElement ) {
			setBoundingRect(
				selectControlMenuRef.current.parentElement.getBoundingClientRect()
			);
		}
	}, [ selectControlMenuRef.current ] );

	// Scroll the selected item into view when the menu opens.
	useEffect( () => {
		if ( isOpen && scrollIntoViewOnOpen ) {
			selectControlMenuRef.current?.scrollIntoView();
		}
	}, [ isOpen, scrollIntoViewOnOpen ] );

	/* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
	/* Disabled because of the onmouseup on the ul element below. */
	return (
		<div
			ref={ selectControlMenuRef }
			className="woocommerce-experimental-select-control__menu"
		>
			<div>
				<Popover
					// @ts-expect-error this prop does exist, see: https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/popover/index.tsx#L180.
					__unstableSlotName="woocommerce-select-control-menu"
					focusOnMount={ false }
					className={ classnames(
						'woocommerce-experimental-select-control__popover-menu',
						{
							'is-open': isOpen,
							'has-results': Children.count( children ) > 0,
						}
					) }
					position={ position }
					animate={ false }
				>
					<ul
						{ ...getMenuProps() }
						className={ classnames(
							'woocommerce-experimental-select-control__popover-menu-container',
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

export const MenuSlot: React.FC = () =>
	createPortal(
		<div aria-live="off">
			{ /* @ts-expect-error name does exist on PopoverSlot see: https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/popover/index.tsx#L555 */ }
			<Popover.Slot name="woocommerce-select-control-menu" />
		</div>,
		document.body
	);
