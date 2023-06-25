/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import './marketplace.scss';

export default function Discover() {
	return (
		<>
			<nav className="woocommerce-marketplace__nav">
				<span>Discover</span>
				<a href="/wp-admin/admin.php?page=wc-admin&path=%2Fmarketplace%2Fextensions">Extensions</a>
				<a href="/wp-admin/admin.php?page=wc-admin&path=%2Fmarketplace%2Fthemes">Themes</a>
			</nav>
		</>
	);
}
