/**
 * External dependencies
 */
import clsx from 'clsx';
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './product-list-header.scss';

interface ProductListHeaderProps {
	title: string;
	description: string;
	groupURL: string | null;
	groupURLText: string | null;
	groupURLType: 'wc-admin' | 'wp-admin' | 'external' | undefined; // defined in Link component
}

export default function ProductListHeader(
	props: ProductListHeaderProps
): JSX.Element {
	const { title, description, groupURL, groupURLText, groupURLType } = props;
	const isLoading = title === '';

	const classNames = clsx( 'woocommerce-marketplace__product-list-header', {
		'is-loading': isLoading,
	} );

	return (
		<div className={ classNames } aria-hidden={ isLoading }>
			<h2 className="woocommerce-marketplace__product-list-title">
				{ title }
			</h2>
			{ description && (
				<p className="woocommerce-marketplace__product-list-description">
					{ description }
				</p>
			) }
			{ groupURL !== null && (
				<span className="woocommerce-marketplace__product-list-link">
					<Link
						href={ groupURL }
						type={ groupURLType }
						target={
							groupURLType === 'external' ? '_blank' : undefined
						}
						onClick={ () => {
							recordEvent( 'marketplace_see_more_clicked', {
								group_title: title,
								group_url: groupURL,
							} );
						} }
					>
						{ groupURLText ?? __( 'See more', 'woocommerce' ) }
					</Link>
				</span>
			) }
		</div>
	);
}
