/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';

/**
 * Internal dependencies
 */

export type CollapsedProps = {
	initialCollapsed?: boolean;
	toggleText: string;
	children: React.ReactNode;
} & React.HTMLAttributes< HTMLDivElement >;

export const CollapsibleContent: React.FC< CollapsedProps > = ( {
	initialCollapsed = true,
	toggleText,
	children,
	...props
}: CollapsedProps ) => {
	const [ collapsed, setCollapsed ] = useState( initialCollapsed );
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
			{ ! collapsed && (
				<div
					{ ...props }
					className="woocommerce-collapsible-content__content"
				>
					{ children }
				</div>
			) }
		</div>
	);
};
