/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import classNames from 'classnames';
import { getNewPath, navigateTo, useQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './tabs.scss';
import { DEFAULT_TAB_KEY, MARKETPLACE_PATH } from '../constants';

export interface TabsProps {
	selectedTab?: string | undefined;
	setSelectedTab: ( value: string ) => void;
}

interface Tab {
	name: string;
	title: string;
}

interface Tabs {
	[ key: string ]: Tab;
}

const tabs: Tabs = {
	discover: {
		name: 'discover',
		title: __( 'Discover', 'woocommerce' ),
	},
	extensions: {
		name: 'extensions',
		title: __( 'Extensions', 'woocommerce' ),
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

const renderTabs = ( props: TabsProps ) => {
	const { selectedTab, setSelectedTab } = props;
	const tabContent = [];
	for ( const tabKey in tabs ) {
		tabContent.push(
			<Button
				className={ classNames( 'woocommerce-marketplace__tab-button', {
					'is-active': tabKey === selectedTab,
				} ) }
				onClick={ () => {
					setSelectedTab( tabKey );
					setUrlTabParam( tabKey );
				} }
				key={ tabKey }
			>
				{ tabs[ tabKey ]?.title }
			</Button>
		);
	}
	return tabContent;
};

const Tabs = ( props: TabsProps ): JSX.Element => {
	const { setSelectedTab } = props;

	interface Query {
		path?: string;
		tab?: string;
	}

	const query: Query = useQuery();

	useEffect( () => {
		if ( query?.tab && tabs[ query.tab ] ) {
			setSelectedTab( query.tab );
		} else {
			setSelectedTab( DEFAULT_TAB_KEY );
		}
	}, [ query, setSelectedTab ] );

	return (
		<nav className="woocommerce-marketplace__tabs">
			{ renderTabs( props ) }
		</nav>
	);
};

export default Tabs;
