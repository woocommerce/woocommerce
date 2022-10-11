/**
 * External dependencies
 */
import { Popover } from '@wordpress/components';
import classnames from 'classnames';
import { createElement, useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getMenuPropsType } from './types';

type MenuProps = {
	children?: JSX.Element | JSX.Element[];
	getMenuProps: getMenuPropsType;
	isOpen: boolean;
	className?: string;
};

export const Menu = ( {
	children,
	getMenuProps,
	isOpen,
	className,
}: MenuProps ) => {
	const selectControlMenuRef = useRef< HTMLDivElement >( null );

	useEffect( () => {
		if (
			selectControlMenuRef.current &&
			selectControlMenuRef.current?.parentElement
		) {
			setBoundingRect(
				selectControlMenuRef.current?.parentElement.getBoundingClientRect()
			);
		}
	}, [ selectControlMenuRef.current ] );

	/* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
	return (
		<div
			ref={ selectControlMenuRef }
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
			{ isOpen && (
				<Popover
					focusOnMount={ false }
					className="woocommerce-experimental-select-control__popover-menu"
					position="bottom center"
					animate={ false }
				>
					<ul
						{ ...getMenuProps() }
						className="woocommerce-experimental-select-control__popover-menu-container"
						style={ {
							width: boundingRect?.width,
						} }
						onMouseUp={ ( e ) =>
							// Fix to prevent select control dropdown from closing when selecting within the Popover.
							e.stopPropagation()
						}
					>
						{ children }
					</ul>
				</Popover>
			) }
		</div>
	);
	/* eslint-enable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
};
