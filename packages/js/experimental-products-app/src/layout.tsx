/**
 * External dependencies
 */
import {
	useViewportMatch,
	useResizeObserver,
	useReducedMotion,
} from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { privateApis as editorPrivateApis } from '@wordpress/editor';
import {
	__unstableMotion as motion,
	__unstableAnimatePresence as AnimatePresence,
} from '@wordpress/components';
// @ts-expect-error - This component isn't available in WordPress 6.9. Given that it's an experimental project, it's okay to use it here. Remove the check below when WordPress 7.0 is the minimum supported version.
import { SnackbarNotices } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import SidebarContent from './sidebar';
import { Route } from './router';
import { unlock } from './lock-unlock';

const { NavigableRegion } = unlock( editorPrivateApis );

const ANIMATION_DURATION = 0.3;

type LayoutProps = {
	route: Route;
	showNewNavigation: boolean;
};

export function Layout( { route, showNewNavigation = false }: LayoutProps ) {
	const [ fullResizer ] = useResizeObserver();
	const isMobileViewport = useViewportMatch( 'medium', '<' );
	const disableMotion = useReducedMotion();

	const { key: routeKey, areas, widths } = route;
	const mobileArea = areas.mobile === true ? areas.content : areas.mobile;

	return (
		<>
			{ fullResizer }
			<div className="edit-site-layout">
				<div className="edit-site-layout__content">
					{ /*
						The NavigableRegion must always be rendered and not use
						`inert` otherwise `useNavigateRegions` will fail.
					*/ }
					{ ( ! isMobileViewport || ! areas.mobile ) &&
						showNewNavigation && (
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
												disableMotion ||
												isMobileViewport
													? 0
													: ANIMATION_DURATION,
											ease: 'easeOut',
										} }
										className="edit-site-layout__sidebar"
									>
										<SidebarContent routeKey={ routeKey }>
											{ areas.sidebar }
										</SidebarContent>
									</motion.div>
								</AnimatePresence>
							</NavigableRegion>
						) }

					{ SnackbarNotices && (
						<SnackbarNotices className="product_page_woocommerce-products-dashboard-snackbar" />
					) }

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

					{ isMobileViewport && mobileArea && (
						<div className="edit-site-layout__area">
							{ mobileArea }
						</div>
					) }

					{ ! isMobileViewport && areas.edit }
				</div>
			</div>
		</>
	);
}
