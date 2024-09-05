/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

export function TableRowSkeleton() {
	return (
		<div
			className="woocommerce-table-row-skeleton woocommerce-product-variations__table-row"
			aria-hidden="true"
		>
			<div className="woocommerce-sortable__handle" />

			<div className="woocommerce-product-variations__selection">
				<div className="woocommerce-table-row-skeleton__checkbox" />
			</div>

			<div className="woocommerce-product-variations__attributes">
				{ Array( 2 )
					.fill( 0 )
					.map( ( _, index ) => (
						<div
							key={ index }
							className="woocommerce-tag woocommerce-product-variations__attribute"
						>
							<div className="woocommerce-table-row-skeleton__attribute-option" />
						</div>
					) ) }
			</div>

			<div className="woocommerce-product-variations__price">
				<div className="woocommerce-table-row-skeleton__regular-price" />
			</div>

			<div className="woocommerce-product-variations__quantity">
				<div className="woocommerce-table-row-skeleton__quantity" />
			</div>

			<div className="woocommerce-product-variations__actions">
				<div className="woocommerce-table-row-skeleton__visibility-icon" />

				<div className="woocommerce-table-row-skeleton__edit-link" />

				<div className="woocommerce-table-row-skeleton__menu-toggle" />
			</div>
		</div>
	);
}
