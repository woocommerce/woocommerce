export default ProductImage;
/**
 * Use `ProductImage` to display a product's or variation's featured image.
 * If no image can be found, a placeholder matching the front-end image
 * placeholder will be displayed.
 *
 * @param {Object} props
 * @param {Object} props.product
 * @param {string} props.alt
 * @param {number} props.width
 * @param {number} props.height
 * @param {string} props.className
 * @return {Object} -
 */
declare function ProductImage({ product, alt, width, height, className, ...props }: {
    product: Object;
    alt: string;
    width: number;
    height: number;
    className: string;
}): Object;
declare namespace ProductImage {
    namespace propTypes {
        const width: PropTypes.Requireable<number>;
        const height: PropTypes.Requireable<number>;
        const className: PropTypes.Requireable<string>;
        const product: PropTypes.Requireable<object>;
        const alt: PropTypes.Requireable<string>;
    }
    namespace defaultProps {
        const width_1: number;
        export { width_1 as width };
        const height_1: number;
        export { height_1 as height };
        const className_1: string;
        export { className_1 as className };
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map