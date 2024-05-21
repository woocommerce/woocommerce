/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import classNames from 'classnames';
import { Icon, chevronUp, chevronDown } from '@wordpress/icons';
import type { ReactNode, ReactElement } from 'react';

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
	titleTag: TitleTag = 'div',
	state,
}: PanelProps ): ReactElement => {
	let [ isOpen, setIsOpen ] = useState< boolean >( initialOpen );
	// If state is managed externally, we override the internal state.
	if ( Array.isArray( state ) && state.length === 2 ) {
		[ isOpen, setIsOpen ] = state;
	}

	return (
		<div
			className={ classNames( className, 'wc-block-components-panel', {
				'has-border': hasBorder,
			} ) }
		>
			<TitleTag>
				<button
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
				</button>
			</TitleTag>
			{ isOpen && (
				<div className="wc-block-components-panel__content">
					{ children }
				</div>
			) }
		</div>
	);
};

export default Panel;
