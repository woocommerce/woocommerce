/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { DisplayState } from '../display-state';

export type CollapsedProps = {
	initialCollapsed?: boolean;
	toggleText: string;
	persistRender?: boolean;
	children: React.ReactNode;
} & React.HTMLAttributes< HTMLDivElement >;

export const CollapsibleContent: React.FC< CollapsedProps > = ( {
	initialCollapsed = true,
	toggleText,
	children,
	persistRender = false,
	...props
}: CollapsedProps ) => {
	const [ collapsed, setCollapsed ] = useState( initialCollapsed );

	const getState = () => {
		if ( ! collapsed ) {
			return 'visible';
		}

		return persistRender ? 'visually-hidden' : 'hidden';
	};

	return (
		<div
			aria-expanded={ collapsed ? 'false' : 'true' }
			className={ `woocommerce-collapsible-content` }
		>
			<button
				className="woocommerce-collapsible-content__toggle"
				onClick={ () => setCollapsed( ! collapsed ) }
			>
				<div>
					<span>{ toggleText }</span>
					<Icon
						icon={ collapsed ? chevronDown : chevronUp }
						size={ 16 }
					/>
				</div>
			</button>
			<DisplayState state={ getState() }>
				<div
					{ ...props }
					className="woocommerce-collapsible-content__content"
				>
					{ children }
				</div>
			</DisplayState>
		</div>
	);
};
