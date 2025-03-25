/**
 * External dependencies
 */
import {
	useViewportMatch,
	useResizeObserver,
	useReducedMotion,
} from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
/* eslint-disable @woocommerce/dependency-group */
import {
	// @ts-expect-error missing type.
	EditorSnackbars,
	// @ts-expect-error missing type.
	privateApis as editorPrivateApis,
} from '@wordpress/editor';
import {
	__unstableMotion as motion,
	__unstableAnimatePresence as AnimatePresence,
} from '@wordpress/components';
import { createElement, Fragment, useRef } from '@wordpress/element';
/* eslint-disable @woocommerce/dependency-group */
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-ignore No types for this exist yet.
import SiteHub from '@wordpress/edit-site/build-module/components/site-hub';
// @ts-ignore No types for this exist yet.
import SidebarContent from '@wordpress/edit-site/build-module/components/sidebar';
/* eslint-enable @woocommerce/dependency-group */

/**
 * Internal dependencies
 */
import { Route } from './types';
import { SectionTabs, Header } from './components';

const { NavigableRegion } = unlock( editorPrivateApis );

const ANIMATION_DURATION = 0.3;

type LayoutProps = {
	route: Route;
	settingsPage?: SettingsPage;
	activeSection?: string;
	tabs?: Array< { name: string; title: string } >;
};

export function Layout( {
	route,
	settingsPage,
	tabs = [],
	activeSection,
}: LayoutProps ) {
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
							<Header
								hasTabs={ tabs.length > 1 }
								pageTitle={ settingsPage?.label }
							/>
							<SectionTabs
								tabs={ tabs }
								activeSection={ activeSection }
							>
								{ areas.content }
							</SectionTabs>
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
