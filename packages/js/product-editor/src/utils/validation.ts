/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Product as ProductType } from '@woocommerce/data';
import { z } from 'zod'

const Product = z.object( {
    name: z.string(),
    regular_price: z.coerce.number().nonnegative(),
    sale_price: z.coerce.number().nonnegative(),
} ).refine(
    ( data ) => data.regular_price >= data.sale_price,
    {
        message: __( 'Sale price should be less than sale price', 'woocommerce-admin' ),
        path: [ 'regular_price', 'sale_price' ],
    }
);

export function validateProduct( product : ProductType ) {
    return Product.safeParse( product );
}
