/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useContext, useEffect, useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import classNames from 'classnames';
import { getNewPath, navigateTo, useQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './tabs.scss';
import { DEFAULT_TAB_KEY, MARKETPLACE_PATH } from '../constants';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import { MarketplaceContextType } from '../../contexts/types';

export interface TabsProps {
	additionalClassNames?: Array< string > | undefined;
}

interface Tab {
	name: string;
	title: string;
	href?: string;
}

interface Tabs {
	[ key: string ]: Tab;
}

const tabs: Tabs = {
	search: {
		name: 'search',
		title: __( 'Search results', 'woocommerce' ),
	},
	discover: {
		name: 'discover',
		title: __( 'Discover', 'woocommerce' ),
	},
	extensions: {
		name: 'extensions',
		title: __( 'Browse', 'woocommerce' ),
	},
	themes: {
		name: 'themes',
		title: __( 'Themes', 'woocommerce' ),
	},
	'my-subscriptions': {
		name: 'my-subscriptions',
		title: __( 'My Subscriptions', 'woocommerce' ),
		href: getNewPath(
			{
				page: 'wc-addons',
				section: 'helper',
			},
			''
		),
	},
};

const setUrlTabParam = ( tabKey: string ) => {
	if ( tabKey === DEFAULT_TAB_KEY ) {
		navigateTo( {
			url: getNewPath( {}, MARKETPLACE_PATH, {} ),
		} );
		return;
	}
	navigateTo( {
		url: getNewPath( { tab: tabKey } ),
	} );
};

const getVisibleTabs = ( selectedTab: string ) => {
	if ( selectedTab === '' ) {
		return tabs;
	}
	const currentVisibleTabs = { ...tabs };
	if ( selectedTab !== 'search' ) {
		delete currentVisibleTabs.search;
	}

	return currentVisibleTabs;
};

const renderTabs = (
	contextValue: MarketplaceContextType,
	visibleTabs: Tabs
) => {
	const { selectedTab, setSelectedTab } = contextValue;
	const tabContent = [];
	for ( const tabKey in visibleTabs ) {
		tabContent.push(
			tabs[ tabKey ]?.href ? (
				<a
					className={ classNames(
						'woocommerce-marketplace__tab-button',
						'components-button',
						`woocommerce-marketplace__tab-${ tabKey }`
					) }
					href={ tabs[ tabKey ]?.href }
					key={ tabKey }
				>
					{ tabs[ tabKey ]?.title }
				</a>
			) : (
				<Button
					className={ classNames(
						'woocommerce-marketplace__tab-button',
						`woocommerce-marketplace__tab-${ tabKey }`,
						{
							'is-active': tabKey === selectedTab,
						}
					) }
					onClick={ () => {
						setSelectedTab( tabKey );
						setUrlTabParam( tabKey );
					} }
					key={ tabKey }
				>
					{ tabs[ tabKey ]?.title }
				</Button>
			)
		);
	}
	return tabContent;
};

const Tabs = ( props: TabsProps ): JSX.Element => {
	const { additionalClassNames } = props;
	const marketplaceContextValue = useContext( MarketplaceContext );
	const { selectedTab, setSelectedTab } = marketplaceContextValue;
	const [ visibleTabs, setVisibleTabs ] = useState( getVisibleTabs( '' ) );

	const query: Record< string, string > = useQuery();
	const queryLoaded = Object.keys( query ).length > 0;

	useEffect( () => {
		if ( query?.tab && tabs[ query.tab ] ) {
			setSelectedTab( query.tab );
		} else if ( queryLoaded ) {
			setSelectedTab( DEFAULT_TAB_KEY );
		}
	}, [ query, queryLoaded, setSelectedTab ] );

	useEffect( () => {
		setVisibleTabs( getVisibleTabs( selectedTab ) );
	}, [ selectedTab ] );
	return (
		<nav
			className={ classNames(
				'woocommerce-marketplace__tabs',
				additionalClassNames || []
			) }
		>
			{ renderTabs( marketplaceContextValue, visibleTabs ) }
		</nav>
	);
};

export default Tabs;
