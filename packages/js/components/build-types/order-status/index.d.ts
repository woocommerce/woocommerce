export default OrderStatus;
/**
 * Use `OrderStatus` to display a badge with human-friendly text describing the current order status.
 *
 * @param {Object} props
 * @param {Object} props.order
 * @param {string} props.order.status
 * @param {string} props.className
 * @param {Object} props.orderStatusMap
 * @param {boolean} props.labelPositionToLeft
 * @return {Object} -
 */
declare function OrderStatus({ order: { status }, className, orderStatusMap, labelPositionToLeft, }: {
    order: {
        status: string;
    };
    className: string;
    orderStatusMap: Object;
    labelPositionToLeft: boolean;
}): Object;
declare namespace OrderStatus {
    namespace propTypes {
        const order: PropTypes.Validator<object>;
        const className: PropTypes.Requireable<string>;
        const orderStatusMap: PropTypes.Requireable<object>;
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map