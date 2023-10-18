/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './product-list-header.scss';

interface ProductListHeaderProps {
	title: string;
	groupURL: string | null;
}

export default function ProductListHeader(
	props: ProductListHeaderProps
): JSX.Element {
	const { title, groupURL } = props;
	const isLoading = title === '';

	const classNames = classnames(
		'woocommerce-marketplace__product-list-header',
		{
			'is-loading': isLoading,
		}
	);

	return (
		<div className={ classNames } aria-hidden={ isLoading }>
			<h2 className="woocommerce-marketplace__product-list-title">
				{ title }
			</h2>
			{ groupURL !== null && (
				<span className="woocommerce-marketplace__product-list-link">
					<Link href={ groupURL } target="_blank">
						{ __( 'See more', 'woocommerce' ) }
					</Link>
				</span>
			) }
		</div>
	);
}
