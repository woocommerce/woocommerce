/**
 * External dependencies
 */
import { chevronRightSmall, Icon } from '@wordpress/icons';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './product-breadcrumbs.scss';

type BreadCrumb = {
	href: string;
	title: string;
	type?: 'wp-admin' | 'wc-admin';
};

export const ProductBreadcrumbs = ( {
	breadcrumbs,
}: {
	breadcrumbs: BreadCrumb[];
} ) => {
	return (
		<span className="woocommerce-product-breadcrumbs">
			{ breadcrumbs.map( ( breadcrumb ) => {
				const { href, title, type } = breadcrumb;
				return (
					<>
						<span className="woocommerce-product-breadcrumbs__item">
							<Link href={ href } type={ type || 'wp-admin' }>
								{ title }
							</Link>
						</span>
						<span className="woocommerce-product-breadcrumbs__separator">
							<Icon icon={ chevronRightSmall } />
						</span>
					</>
				);
			} ) }
		</span>
	);
};
