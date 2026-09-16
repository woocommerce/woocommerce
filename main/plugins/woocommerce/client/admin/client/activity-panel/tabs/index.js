/**
 * External dependencies
 */
import { NavigableMenu } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Tab } from '../tab';

/**
 * Renders the activity-panel tab strip. Decisions about what a click means
 * (open / close / switch) live in the parent's `onTabClick` handler, not
 * here — Tabs just forwards the clicked tab and renders selection state
 * from props. Previously this component maintained its own mirror of the
 * panel's open state and computed click intent from it, which created a
 * one-frame gap where a same-tab close could pop back open mid-animation.
 */
export const Tabs = ( {
	tabs,
	onTabClick,
	selectedTab: selectedTabName,
	tabOpen = false,
} ) => {
	return (
		<NavigableMenu
			role="tablist"
			orientation="horizontal"
			className="woocommerce-layout__activity-panel-tabs"
		>
			{ tabs &&
				tabs.map( ( tab, i ) => {
					if ( tab.component ) {
						const { component: Comp, options } = tab;
						return <Comp key={ i } { ...options } />;
					}
					return (
						<Tab
							key={ i }
							index={ i }
							isPanelOpen={ tabOpen }
							selected={ selectedTabName === tab.name }
							{ ...tab }
							onTabClick={ () => onTabClick( tab ) }
						/>
					);
				} ) }
		</NavigableMenu>
	);
};
