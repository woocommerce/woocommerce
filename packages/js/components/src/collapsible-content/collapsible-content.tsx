/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';

/**
 * Internal dependencies
 */

export type CollapsedProps = {
	isCollapsed?: boolean;
	toggleText: string;
	children: React.ReactNode;
} & React.HTMLAttributes< HTMLDivElement >;

export const CollapsibleContent: React.FC< CollapsedProps > = ( {
	isCollapsed = true,
	toggleText,
	children,
	...props
}: CollapsedProps ) => {
	const [ collapsed, setCollapsed ] = useState( isCollapsed );
	return (
		<div
			aria-expanded={ collapsed ? 'false' : 'true' }
			className={ `woocommerce-collapsible-content` }
		>
			<button onClick={ () => setCollapsed( ! collapsed ) }>
				<div>
					<span>{ toggleText }</span>
					<Icon icon={ collapsed ? chevronDown : chevronUp } />
				</div>
			</button>
			{ ! collapsed && <div { ...props }>{ children }</div> }
		</div>
	);
};
