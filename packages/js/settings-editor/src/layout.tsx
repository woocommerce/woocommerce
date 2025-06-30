/**
 * External dependencies
 */
import {
	useViewportMatch,
	useResizeObserver,
	useReducedMotion,
} from '@wordpress/compose';
/* eslint-disable @woocommerce/dependency-group */
import {
	// @ts-expect-error missing type.
	EditorSnackbars,
} from '@wordpress/editor';
import {
	__unstableMotion as motion,
	__unstableAnimatePresence as AnimatePresence,
} from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { SidebarContent } from '@automattic/site-admin';

/**
 * Internal dependencies
 */
import { Route } from './types';
import { SectionTabs, Header } from './components';

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
	const isMobileViewport = useViewportMatch( 'medium', '<' );
	const disableMotion = useReducedMotion();

	const { key: routeKey, areas, widths } = route;

	return (
		<>
			{ fullResizer }
			<div className="woocommerce-site-layout">
				<div className="woocommerce-site-layout__content">
					{ /*
						The NavigableRegion must always be rendered and not use
						`inert` otherwise `useNavigateRegions` will fail.
						NOTE: NavigableRegion has been removed and will be replaced
						with the new component from @automattic/site-admin.
					*/ }
					{ ( ! isMobileViewport || ! areas.mobile ) && (
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
								className="woocommerce-site-layout__sidebar a8c-site-admin-sidebar"
							>
								<SidebarContent
									shouldAnimate={ false }
									routeKey={ routeKey }
								>
									{ areas.sidebar }
								</SidebarContent>
							</motion.div>
						</AnimatePresence>
					) }

					<EditorSnackbars />

					{ ! isMobileViewport && areas.content && (
						<div
							className="woocommerce-site-layout__area"
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
							className="woocommerce-site-layout__area"
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
