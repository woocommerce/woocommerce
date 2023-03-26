/**
 * External dependencies
 */
import { chevronRightSmall, Icon } from '@wordpress/icons';
import { Fragment } from '@wordpress/element';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './product-breadcrumbs.scss';

type Breadcrumb = {
	href?: string;
	title: string | JSX.Element;
	type?: 'wp-admin' | 'wc-admin';
};

export const ProductBreadcrumbs = ( {
	breadcrumbs,
}: {
	breadcrumbs: Breadcrumb[];
} ) => {
	const visibleBreadcrumbs =
		breadcrumbs.length > 3
			? [
					breadcrumbs[ 0 ],
					{
						title: <>&hellip;</>,
					},
					breadcrumbs[ breadcrumbs.length - 1 ],
			  ]
			: breadcrumbs;

	return (
		<span className="woocommerce-product-breadcrumbs">
			{ visibleBreadcrumbs.map( ( breadcrumb ) => {
				const { href, title, type } = breadcrumb;
				return (
					<Fragment key={ href }>
						<span className="woocommerce-product-breadcrumbs__item">
							{ href ? (
								<Link href={ href } type={ type || 'wp-admin' }>
									{ title }
								</Link>
							) : (
								title
							) }
						</span>
						<span className="woocommerce-product-breadcrumbs__separator">
							<Icon icon={ chevronRightSmall } />
						</span>
					</Fragment>
				);
			} ) }
		</span>
	);
};
