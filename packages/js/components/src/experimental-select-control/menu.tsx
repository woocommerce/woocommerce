/**
 * External dependencies
 */
import { Popover } from '@wordpress/components';
import classnames from 'classnames';
import { createElement, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getMenuPropsType } from './types';

type MenuProps = {
	children?: JSX.Element | JSX.Element[];
	getMenuProps: getMenuPropsType;
	isOpen: boolean;
	dropdownPlacement?: 'inline' | 'body';
	className?: string;
};

export const Menu = ( {
	children,
	getMenuProps,
	isOpen,
	dropdownPlacement = 'inline',
	className,
}: MenuProps ) => {
	const selectControlMenuRef = useRef< HTMLElement >( null );

	let childrenMarkup = children;
	if ( dropdownPlacement === 'body' ) {
		const selectControlParentElement =
			selectControlMenuRef.current?.parentElement;
		childrenMarkup = (
			<Popover
				focusOnMount={ false }
				className="woocommerce-experimental-select-control__popover-menu"
				position="bottom center"
			>
				<div
					className="woocommerce-experimental-select-control__popover-menu-container"
					style={ {
						width: selectControlParentElement?.getBoundingClientRect()
							.width,
					} }
					onMouseUp={ ( e ) =>
						// Fix to prevent select control dropdown from closing when selecting within the Popover.
						e.stopPropagation()
					}
				>
					{ children }
				</div>
			</Popover>
		);
	}

	return (
		<ul
			{ ...getMenuProps( { ref: selectControlMenuRef } ) }
			className={ classnames(
				'woocommerce-experimental-select-control__menu',
				className,
				{
					'is-open': isOpen,
					'has-results': Array.isArray( children )
						? children.length
						: Boolean( children ),
				}
			) }
		>
			{ isOpen && childrenMarkup }
		</ul>
	);
};
