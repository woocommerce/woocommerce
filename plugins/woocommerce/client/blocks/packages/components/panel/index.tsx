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
	headingLevel?: 2 | 3 | 4 | 5 | 6;
	title: ReactNode;
	titleTag?: keyof JSX.IntrinsicElements;
	state?: [ boolean, React.Dispatch< React.SetStateAction< boolean > > ];
}

const Panel = ( {
	children,
	className,
	initialOpen = false,
	hasBorder = false,
	headingLevel,
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
			role={ headingLevel ? 'heading' : undefined }
			aria-level={ headingLevel ? headingLevel : undefined }
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
				{ /* @ts-expect-error - TS wants the Icon component to define svg specific props, but it's not always SVG */ }
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
