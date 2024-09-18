/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import clsx from 'clsx';
import { Icon, chevronUp, chevronDown } from '@wordpress/icons';
import type { ReactNode, ReactElement } from 'react';
import { Button } from '@ariakit/react';
import deprecated from '@wordpress/deprecated';
/**
 * Internal dependencies
 */
import './style.scss';

export interface PanelProps {
	children: ReactNode;
	className?: string | undefined;
	initialOpen?: boolean;
	hasBorder?: boolean;
	title: ReactNode;
	titleTag?: keyof JSX.IntrinsicElements;
	state?: [ boolean, React.Dispatch< React.SetStateAction< boolean > > ];
}

const Panel = ( {
	children,
	className,
	initialOpen = false,
	hasBorder = false,
	title,
	/**
	 * @deprecated The `titleTag` prop is deprecated and will be removed in a future version.
	 * Use the `title` prop to pass a custom React element instead.
	 */
	titleTag,
	state,
}: PanelProps ): ReactElement => {
	let [ isOpen, setIsOpen ] = useState< boolean >( initialOpen );
	// If state is managed externally, we override the internal state.
	if ( Array.isArray( state ) && state.length === 2 ) {
		[ isOpen, setIsOpen ] = state;
	}

	if ( titleTag ) {
		deprecated( "Panel component's titleTag prop", {
			since: '9.4.0',
		} );
	}

	return (
		<div
			className={ clsx( className, 'wc-block-components-panel', {
				'has-border': hasBorder,
			} ) }
		>
			<Button
				render={ <div /> }
				aria-expanded={ isOpen }
				className="wc-block-components-panel__button"
				onClick={ () => setIsOpen( ! isOpen ) }
			>
				<Icon
					aria-hidden="true"
					className="wc-block-components-panel__button-icon"
					icon={ isOpen ? chevronUp : chevronDown }
				/>
				{ title }
			</Button>
			{ isOpen && (
				<div className="wc-block-components-panel__content">
					{ children }
				</div>
			) }
		</div>
	);
};

export default Panel;
