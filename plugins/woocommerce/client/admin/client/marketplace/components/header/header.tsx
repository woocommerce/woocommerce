/**
 * Internal dependencies
 */
import './header.scss';
import Banner from '../banner/banner';
import HeaderTitle from '../header-title/header-title';
import HeaderAccount from '../header-account/header-account';
import Tabs from '../tabs/tabs';
import Search from '../search/search';

export default function Header() {
	return (
		<div className="woocommerce-marketplace__header-container">
			<Banner />
			<header className="woocommerce-marketplace__header">
				<HeaderTitle />
				<Tabs
					additionalClassNames={ [
						'woocommerce-marketplace__header-tabs',
					] }
				/>
				<Search />
				<div className="woocommerce-marketplace__header-meta">
					<HeaderAccount page="wc-addons" />
				</div>
			</header>
		</div>
	);
}
