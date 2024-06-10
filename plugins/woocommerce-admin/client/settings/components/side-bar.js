/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

export const SidebarContext = createContext( null );

export const SideBar = () => {
	return (
		<div className="woocommerce-settings-side-bar">
			<h2>Side Bar Content</h2>
		</div>
	);
};
