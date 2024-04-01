/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * Internal dependencies
 */
import type { SidebarComponentProps } from '../xstate';
export const LaunchYourStoreHubSidebar: React.FC< SidebarComponentProps > = (
	props
) => {
	return (
		<div
			className={ classnames(
				'launch-store-sidebar__container',
				props.className
			) }
		>
			<p>Sidebar</p>
			<button
				onClick={ () => {
					props.sendEventToSidebar( { type: 'LAUNCH_STORE' } );
					// when we send LAUNCH_STORE to the sidebar machine, the sidebar machine sends the appropriate event to the main content machine to show the launching state
				} }
			>
				Launch Store
			</button>
			<button
				onClick={ () => {
					props.sendEventToSidebar( {
						type: 'OPEN_EXTERNAL_URL',
						url: 'https://example.com',
					} );
				} }
			>
				Open external URL
			</button>
		</div>
	);
};
