/**
 * External dependencies
 */
import { createElement, Fragment, useRef } from '@wordpress/element';
import {
	useViewportMatch,
	useResizeObserver,
	useReducedMotion,
} from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import {
	// @ts-expect-error missing type.
	EditorSnackbars,
	// @ts-expect-error missing type.
	privateApis as editorPrivateApis,
} from '@wordpress/editor';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	// @ts-expect-error missing type.
	__unstableMotion as motion,
	// @ts-expect-error missing type.
	__unstableAnimatePresence as AnimatePresence,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import SidebarContent from './sidebar';
import SiteHub from './site-hub';
import { Route } from './router';
import { unlock } from '../lock-unlock';

const { NavigableRegion } = unlock( editorPrivateApis );

const ANIMATION_DURATION = 0.3;

type LayoutProps = {
	route: Route;
};

export function Layout( { route }: LayoutProps ) {
	const [ fullResizer ] = useResizeObserver();
	const toggleRef = useRef< HTMLAnchorElement >( null );
	const isMobileViewport = useViewportMatch( 'medium', '<' );
	const disableMotion = useReducedMotion();

	const { key: routeKey, areas, widths } = route;

	return (
		<>
			{ fullResizer }
			<div className="edit-site-layout">
				<div className="edit-site-layout__content">
					{ /*
						The NavigableRegion must always be rendered and not use
						`inert` otherwise `useNavigateRegions` will fail.
					*/ }
					{ ( ! isMobileViewport || ! areas.mobile ) && (
						<NavigableRegion
							ariaLabel={ __( 'Navigation', 'woocommerce' ) }
							className="edit-site-layout__sidebar-region"
						>
							<AnimatePresence>
								<motion.div
									initial={ { opacity: 0 } }
									animate={ { opacity: 1 } }
									exit={ { opacity: 0 } }
									transition={ {
										type: 'tween',
										duration:
											// Disable transition in mobile to emulate a full page transition.
											disableMotion || isMobileViewport
												? 0
												: ANIMATION_DURATION,
										ease: 'easeOut',
									} }
									className="edit-site-layout__sidebar"
								>
									<SiteHub
										ref={ toggleRef }
										isTransparent={ false }
									/>
									<SidebarContent routeKey={ routeKey }>
										{ areas.sidebar }
									</SidebarContent>
								</motion.div>
							</AnimatePresence>
						</NavigableRegion>
					) }

					<EditorSnackbars />

					{ ! isMobileViewport && areas.content && (
						<div
							className="edit-site-layout__area"
							style={ {
								maxWidth: widths?.content,
							} }
						>
							{ areas.content }
						</div>
					) }

					{ ! isMobileViewport && areas.edit && (
						<div
							className="edit-site-layout__area"
							style={ {
								maxWidth: widths?.edit,
							} }
						>
							{ areas.edit }
						</div>
					) }
				</div>
			</div>
		</>
	);
}
