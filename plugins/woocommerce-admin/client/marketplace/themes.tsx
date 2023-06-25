/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import './marketplace.scss';

export default function Themes() {
	return (
		<>
			<nav className="woocommerce-marketplace__nav">
				<a href="/wp-admin/admin.php?page=wc-admin&path=%2Fmarketplace">
					Discover
				</a>
				<a href="/wp-admin/admin.php?page=wc-admin&path=%2Fmarketplace%2Fextensions">
					Extensions
				</a>
				<span>Themes</span>
			</nav>
		</>
	);
}
