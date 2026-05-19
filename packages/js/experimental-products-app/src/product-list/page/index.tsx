/**
 * External dependencies
 */
import { Page } from '@wordpress/admin-ui';
import { Stack } from '@wordpress/ui';

type ProductListPageProps = {
	ariaLabel: string;
	children: React.ReactNode;
	className?: string;
};

type ProductListPageHeaderProps = {
	actions?: React.ReactNode;
	subTitle?: React.ReactNode;
	title: React.ReactNode;
	toolbar?: React.ReactNode;
};

export function ProductListPage( {
	ariaLabel,
	children,
	className,
}: ProductListPageProps ) {
	return (
		<Page className={ className } ariaLabel={ ariaLabel }>
			{ children }
		</Page>
	);
}

export function ProductListPageHeader( {
	actions,
	subTitle,
	title,
	toolbar,
}: ProductListPageHeaderProps ) {
	return (
		<header className="woocommerce-product-list-page__header">
			<Stack direction="row" justify="space-between" gap="sm">
				<Stack direction="row" gap="sm" align="center" justify="start">
					<h2 className="woocommerce-product-list-page__header-title">
						{ title }
					</h2>
				</Stack>
				<Stack
					direction="row"
					gap="sm"
					className="woocommerce-product-list-page__header-actions"
					align="center"
				>
					{ actions }
				</Stack>
			</Stack>
			{ subTitle && (
				<p className="woocommerce-product-list-page__header-subtitle">
					{ subTitle }
				</p>
			) }
			{ toolbar }
		</header>
	);
}
