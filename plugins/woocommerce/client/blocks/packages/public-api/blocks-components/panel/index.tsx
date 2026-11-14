/**
 * External dependencies
 */
import { useRef, useState } from '@wordpress/element';
import clsx from 'clsx';
import { Icon, chevronUp, chevronDown } from '@wordpress/icons';
import type { ReactNode, ReactElement } from 'react';
import { Button } from '@ariakit/react';
import deprecated from '@wordpress/deprecated';
import { usePresence } from '@woocommerce/base-hooks';
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

	// The open state that the last key press on the button set. A key press applies immediately.
	const keyStateRef = useRef< boolean | null >( null );
	const animate = keyStateRef.current !== isOpen;
	// The content grows and collapses, so the content below moves with it.
	const content = usePresence( isOpen || null, { animate, collapse: true } );

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
				// Stops the gap under the button from animating after a key press. Set in the same render as
				// `aria-expanded`, so the browser sees both together.
				{ ...( ! animate && { 'data-wc-instant': '' } ) }
				className="wc-block-components-panel__button"
				onClick={ ( event ) => {
					// A click that a key press starts has no click count.
					keyStateRef.current = event.detail === 0 ? ! isOpen : null;
					setIsOpen( ! isOpen );
				} }
			>
				<Icon
					aria-hidden="true"
					className="wc-block-components-panel__button-icon"
					icon={ isOpen ? chevronUp : chevronDown }
				/>
				{ title }
			</Button>
			{ content.value && (
				<div
					ref={ content.ref }
					className="wc-block-components-panel__content"
					// Closing content is already gone for the shopper. React 18 has no `inert` prop.
					{ ...( content.isExiting && { inert: '' } ) }
				>
					{ children }
				</div>
			) }
		</div>
	);
};

export default Panel;
