/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import './marketplace.scss';

export default function Extensions() {
	return (
		<>
			<nav className="woocommerce-marketplace__nav">
				<a href="/wp-admin/admin.php?page=wc-admin&path=%2Fmarketplace">Discover</a>
				<span>Extensions</span>
				<a href="/wp-admin/admin.php?page=wc-admin&path=%2Fmarketplace%2Fthemes">Themes</a>
			</nav>
		</>
	);
}
