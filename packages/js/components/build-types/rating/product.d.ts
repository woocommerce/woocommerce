export default ProductRating;
/**
 * Display a set of stars representing the product's average rating.
 *
 * @param {Object} props
 * @param {Object} props.product
 * @return {Object} -
 */
declare function ProductRating({ product, ...props }: {
    product: Object;
}): Object;
declare namespace ProductRating {
    namespace propTypes {
        const product: PropTypes.Validator<object>;
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=product.d.ts.map