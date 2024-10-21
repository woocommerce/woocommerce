/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useContext, useEffect, useState, useMemo } from '@wordpress/element';
import { Button } from '@wordpress/components';
import clsx from 'clsx';
import { getNewPath, navigateTo, useQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './tabs.scss';
import { DEFAULT_TAB_KEY, MARKETPLACE_PATH } from '../constants';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import { MarketplaceContextType } from '../../contexts/types';
import { getAdminSetting } from '../../../utils/admin-settings';

export interface TabsProps {
	additionalClassNames?: Array< string > | undefined;
}

interface Tab {
	name: string;
	title: string;
	href?: string;
	showUpdateCount: boolean;
	updateCount: number;
}

interface Tabs {
	[ key: string ]: Tab;
}

const wccomSettings = getAdminSetting( 'wccomHelper', {} );
const wooUpdateCount = wccomSettings?.wooUpdateCount ?? 0;

const setUrlTabParam = ( tabKey: string, query: Record< string, string > ) => {
	const term = query.term ? { term: query.term.trim() } : {};
	navigateTo( {
		url: getNewPath(
			{ tab: tabKey === DEFAULT_TAB_KEY ? undefined : tabKey },
			MARKETPLACE_PATH,
			term
		),
	} );
};

const getVisibleTabs = (
	selectedTab: string,
	hasBusinessServices = false,
	tabs: Tabs
) => {
	if ( selectedTab === '' ) {
		return tabs;
	}
	const currentVisibleTabs = { ...tabs };
	if ( ! hasBusinessServices ) {
		delete currentVisibleTabs[ 'business-services' ];
	}

	return currentVisibleTabs;
};

const renderTabs = (
	marketplaceContextValue: MarketplaceContextType,
	visibleTabs: Tabs,
	tabs: Tabs,
	query: Record< string, string >
) => {
	const { selectedTab, setSelectedTab } = marketplaceContextValue;

	const onTabClick = ( tabKey: string ) => {
		if ( tabKey === selectedTab ) {
			return;
		}
		setSelectedTab( tabKey );
		setUrlTabParam( tabKey, query );
	};

	const tabContent = [];
	for ( const tabKey in visibleTabs ) {
		tabContent.push(
			tabs[ tabKey ]?.href ? (
				<a
					className={ clsx(
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
					className={ clsx(
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
					{ tabs[ tabKey ]?.showUpdateCount &&
						tabs[ tabKey ]?.updateCount > 0 && (
							<span
								className={ clsx(
									'woocommerce-marketplace__update-count',
									`woocommerce-marketplace__update-count-${ tabKey }`,
									{
										'is-active': tabKey === selectedTab,
									}
								) }
							>
								<span> { tabs[ tabKey ]?.updateCount } </span>
							</span>
						) }
				</Button>
			)
		);
	}
	return tabContent;
};

const Tabs = ( props: TabsProps ): JSX.Element => {
	const { additionalClassNames } = props;
	const marketplaceContextValue = useContext( MarketplaceContext );
	const { selectedTab, isLoading, setSelectedTab, hasBusinessServices } =
		marketplaceContextValue;
	const { searchResultsCount } = marketplaceContextValue;

	const query: Record< string, string > = useQuery();

	const tabs: Tabs = useMemo(
		() => ( {
			discover: {
				name: 'discover',
				title: __( 'Discover', 'woocommerce' ),
				showUpdateCount: false,
				updateCount: 0,
			},
			extensions: {
				name: 'extensions',
				title: __( 'Extensions', 'woocommerce' ),
				showUpdateCount: !! query.term && ! isLoading,
				updateCount: searchResultsCount.extensions,
			},
			themes: {
				name: 'themes',
				title: __( 'Themes', 'woocommerce' ),
				showUpdateCount: !! query.term && ! isLoading,
				updateCount: searchResultsCount.themes,
			},
			'business-services': {
				name: 'business-services',
				title: __( 'Business services', 'woocommerce' ),
				showUpdateCount: !! query.term && ! isLoading,
				updateCount: searchResultsCount[ 'business-services' ],
			},
			'my-subscriptions': {
				name: 'my-subscriptions',
				title: __( 'My subscriptions', 'woocommerce' ),
				showUpdateCount: true,
				updateCount: wooUpdateCount,
			},
		} ),
		[ query, isLoading, searchResultsCount ]
	);

	const [ visibleTabs, setVisibleTabs ] = useState(
		getVisibleTabs( '', false, tabs )
	);

	useEffect( () => {
		if ( query?.tab && tabs[ query.tab ] ) {
			setSelectedTab( query.tab );
		} else if ( Object.keys( query ).length > 0 ) {
			setSelectedTab( DEFAULT_TAB_KEY );
		}
	}, [ query, setSelectedTab, tabs ] );

	useEffect( () => {
		setVisibleTabs(
			getVisibleTabs( selectedTab, hasBusinessServices, tabs )
		);

		if ( selectedTab === 'business-services' && ! hasBusinessServices ) {
			setUrlTabParam( 'extensions', query );
		}
	}, [ selectedTab, hasBusinessServices, query, tabs ] );

	return (
		<nav
			className={ clsx(
				'woocommerce-marketplace__tabs',
				additionalClassNames || []
			) }
		>
			{ renderTabs( marketplaceContextValue, visibleTabs, tabs, query ) }
		</nav>
	);
};

export default Tabs;
