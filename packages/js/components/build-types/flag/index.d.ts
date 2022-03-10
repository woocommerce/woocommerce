export default Flag;
/**
 * Use the `Flag` component to display a country's flag using the operating system's emojis.
 *
 * @param {Object} props
 * @param {string} props.code
 * @param {Object} props.order
 * @param {string} props.className
 * @param {string} props.size
 * @param {boolean} props.hideFromScreenReader
 * @return {Object} - React component.
 */
declare function Flag({ code, order, className, size, hideFromScreenReader }: {
    code: string;
    order: Object;
    className: string;
    size: string;
    hideFromScreenReader: boolean;
}): Object;
declare namespace Flag {
    namespace propTypes {
        const code: PropTypes.Requireable<string>;
        const order: PropTypes.Requireable<object>;
        const className: PropTypes.Requireable<string>;
        const size: PropTypes.Requireable<number>;
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map