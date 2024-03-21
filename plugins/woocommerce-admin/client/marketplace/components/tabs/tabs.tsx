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
		title: __( 'My subscriptions', 'woocommerce' ),
	},
};

const setUrlTabParam = ( tabKey: string ) => {
	navigateTo( {
		url: getNewPath(
			{ tab: tabKey === DEFAULT_TAB_KEY ? undefined : tabKey },
			MARKETPLACE_PATH,
			{}
		),
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
	marketplaceContextValue: MarketplaceContextType,
	visibleTabs: Tabs
) => {
	const { selectedTab, setSelectedTab } = marketplaceContextValue;

	const onTabClick = ( tabKey: string ) => {
		if ( tabKey === selectedTab ) {
			return;
		}
		setSelectedTab( tabKey );
		setUrlTabParam( tabKey );
	};

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
					onClick={ () => onTabClick( tabKey ) }
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

	useEffect( () => {
		if ( query?.tab && tabs[ query.tab ] ) {
			setSelectedTab( query.tab );
		} else if ( Object.keys( query ).length > 0 ) {
			setSelectedTab( DEFAULT_TAB_KEY );
		}
	}, [ query, setSelectedTab ] );

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
