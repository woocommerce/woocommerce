/**
 * External dependencies
 */
import { createElement, useRef } from '@wordpress/element';

function SidebarContentWrapper( { children }: { children: React.ReactNode } ) {
	const wrapperRef = useRef< HTMLDivElement | null >( null );
	const wrapperCls = 'edit-site-sidebar__screen-wrapper';

	return (
		<div ref={ wrapperRef } className={ wrapperCls }>
			{ children }
		</div>
	);
}

export default function SidebarContent( {
	routeKey,
	children,
}: {
	routeKey: string;
	children: React.ReactNode;
} ) {
	return (
		<div className="edit-site-sidebar__content">
			<SidebarContentWrapper key={ routeKey }>
				{ children }
			</SidebarContentWrapper>
		</div>
	);
}
