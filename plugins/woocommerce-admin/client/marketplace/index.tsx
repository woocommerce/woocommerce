/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './marketplace.scss';
import Tabs from './components/tabs/tabs';
import Content from './components/content/content';

export default function Marketplace() {
	const [ selectedTab, setSelectedTab ] = useState( 'discover' );

	return (
		<>
			<Tabs selectedTab={ selectedTab } setSelectedTab={ setSelectedTab } />
			<Content selectedTab={ selectedTab } />
		</>
	);
}
