/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import clsx from 'clsx';
import { Icon, chevronUp, chevronDown } from '@wordpress/icons';
import type { ReactNode, ReactElement } from 'react';
import { Button } from '@ariakit/react';
import { usePresence } from '@woocommerce/base-hooks';
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

	// The content grows in under the button as the content below makes room, and collapses back when closed.
	const presence = usePresence( isOpen, {
		collapse: ':scope > .wc-block-components-panel__content',
	} );

	if ( titleTag ) {
		deprecated( "Panel component's titleTag prop", {
			since: '9.4.0',
		} );
	}

	return (
		<div
			ref={ presence.ref }
			role={ headingLevel ? 'heading' : undefined }
			aria-level={ headingLevel ? headingLevel : undefined }
			className={ clsx(
				className,
				'wc-block-components-panel',
				presence.className,
				{ 'has-border': hasBorder }
			) }
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
			{ presence.isPresent && (
				<div
					className="wc-block-components-panel__content"
					// React 18 has no `inert` prop; an empty string sets the attribute.
					{ ...( presence.transition === 'exiting'
						? { inert: '' }
						: {} ) }
				>
					{ children }
				</div>
			) }
		</div>
	);
};

export default Panel;
