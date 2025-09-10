/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { Button, Popover } from '@wordpress/components';
import { createElement, Fragment, useState } from '@wordpress/element';
import { KeyboardEvent } from 'react';
import { Icon, help } from '@wordpress/icons';
import { useInstanceId } from '@wordpress/compose';

type Position =
	| 'top left'
	| 'top right'
	| 'top center'
	| 'middle left'
	| 'middle right'
	| 'middle center'
	| 'bottom left'
	| 'bottom right'
	| 'bottom center';

type TooltipProps = {
	children?: JSX.Element | string;
	helperText?: string;
	position?: Position;
	text: JSX.Element | string;
	className?: string;
};

export const Tooltip = ( {
	children = <Icon icon={ help } />,
	className = '',
	helperText = __( 'Help', 'woocommerce' ),
	position = 'top center',
	text,
}: TooltipProps ) => {
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );

	const uniqueIdentifier = useInstanceId(
		Tooltip,
		'product_tooltip'
	) as string;

	return (
		<>
			<div className={ clsx( 'woocommerce-tooltip', uniqueIdentifier ) }>
				<Button
					className={ clsx(
						'woocommerce-tooltip__button',
						className
					) }
					onKeyDown={ (
						event: KeyboardEvent< HTMLButtonElement >
					) => {
						if ( event.key !== 'Enter' ) {
							return;
						}
						setIsPopoverVisible( true );
					} }
					onClick={ () => setIsPopoverVisible( ! isPopoverVisible ) }
					label={ helperText }
				>
					{ children }
				</Button>

				{ isPopoverVisible && (
					<Popover
						focusOnMount={ true }
						position={ position }
						inline
						className="woocommerce-tooltip__text"
						onFocusOutside={ ( event ) => {
							if (
								event.currentTarget?.classList.contains(
									uniqueIdentifier
								)
							) {
								return;
							}
							setIsPopoverVisible( false );
						} }
						onKeyDown={ (
							event: KeyboardEvent< HTMLDivElement >
						) => {
							if ( event.key !== 'Escape' ) {
								return;
							}
							setIsPopoverVisible( false );
						} }
					>
						{ text }
					</Popover>
				) }
			</div>
		</>
	);
};
