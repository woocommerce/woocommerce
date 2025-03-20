/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { TabPanel } from '@wordpress/components';
import { privateApis as routerPrivateApis } from '@wordpress/router';
/* eslint-disable @woocommerce/dependency-group */
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
import { getQueryArgs } from '@wordpress/url';
/* eslint-enable @woocommerce/dependency-group */

const { useHistory, useLocation } = unlock( routerPrivateApis );

export const SectionTabs = ( {
	children,
	tabs = [],
	activeSection,
}: {
	children: React.ReactNode;
	tabs?: Array< {
		name: string;
		title: string;
	} >;
	activeSection?: string;
} ) => {
	const history = useHistory();
	const {
		params: { postType, page },
	} = useLocation();

	if ( tabs.length <= 1 ) {
		return <div>{ children }</div>;
	}

	const onSelect = ( tabName: string ) => {
		const currentArgs = getQueryArgs( window.location.href );

		if ( currentArgs.section === tabName ) {
			return;
		}

		const params =
			tabName === 'default'
				? {
						page,
						postType,
						tab: currentArgs.tab,
				  }
				: {
						page,
						postType,
						tab: currentArgs.tab,
						section: tabName,
				  };
		history.push( params );
	};

	return (
		<TabPanel
			className="woocommerce-settings-section-tabs"
			tabs={ tabs }
			onSelect={ onSelect }
			initialTabName={ activeSection || tabs[ 0 ].name }
		>
			{ () => <>{ children }</> }
		</TabPanel>
	);
};
