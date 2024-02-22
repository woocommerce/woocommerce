/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

export function LoadingState() {
	return (
		<div
			className="woocommerce-product-header is-loading"
			aria-hidden="true"
		>
			<div className="woocommerce-product-header__inner">
				<div />

				<div className="woocommerce-product-header__title" />

				<div className="woocommerce-product-header__actions">
					<div className="woocommerce-product-header__action" />
					<div className="woocommerce-product-header__action" />
					<div className="woocommerce-product-header__action" />
					<div className="woocommerce-product-header__action" />
				</div>
			</div>

			<div className="woocommerce-product-tabs">
				{ Array( 7 )
					.fill( 0 )
					.map( ( _, index ) => (
						<div key={ index } className="components-button" />
					) ) }
			</div>
		</div>
	);
}
