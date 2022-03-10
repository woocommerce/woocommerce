export default SummaryNumber;
/**
 * A component to show a value, label, and optionally a change percentage and children node. Can also act as a link to a specific report focus.
 *
 * @param {Object} props
 * @param {Node} props.children
 * @param {number} props.delta Change percentage. Float precision is rendered as given.
 * @param {string} props.href
 * @param {string} props.hrefType
 * @param {boolean} props.isOpen
 * @param {string} props.label
 * @param {string} props.labelTooltipText
 * @param {Function} props.onToggle
 * @param {string} props.prevLabel
 * @param {number|string} props.prevValue
 * @param {boolean} props.reverseTrend
 * @param {boolean} props.selected
 * @param {number|string} props.value
 * @param {Function} props.onLinkClickCallback
 * @return {Object} -
 */
declare function SummaryNumber({ children, delta, href, hrefType, isOpen, label, labelTooltipText, onToggle, prevLabel, prevValue, reverseTrend, selected, value, onLinkClickCallback, }: {
    children: Node;
    delta: number;
    href: string;
    hrefType: string;
    isOpen: boolean;
    label: string;
    labelTooltipText: string;
    onToggle: Function;
    prevLabel: string;
    prevValue: number | string;
    reverseTrend: boolean;
    selected: boolean;
    value: number | string;
    onLinkClickCallback: Function;
}): Object;
declare namespace SummaryNumber {
    namespace propTypes {
        const delta: PropTypes.Requireable<number>;
        const href: PropTypes.Requireable<string>;
        const hrefType: PropTypes.Validator<string>;
        const isOpen: PropTypes.Requireable<boolean>;
        const label: PropTypes.Validator<string>;
        const labelTooltipText: PropTypes.Requireable<string>;
        const onToggle: PropTypes.Requireable<(...args: any[]) => any>;
        const prevLabel: PropTypes.Requireable<string>;
        const prevValue: PropTypes.Requireable<string | number>;
        const reverseTrend: PropTypes.Requireable<boolean>;
        const selected: PropTypes.Requireable<boolean>;
        const value: PropTypes.Requireable<string | number>;
        const onLinkClickCallback: PropTypes.Requireable<(...args: any[]) => any>;
    }
    namespace defaultProps {
        const href_1: string;
        export { href_1 as href };
        const hrefType_1: string;
        export { hrefType_1 as hrefType };
        const isOpen_1: boolean;
        export { isOpen_1 as isOpen };
        const prevLabel_1: string;
        export { prevLabel_1 as prevLabel };
        const reverseTrend_1: boolean;
        export { reverseTrend_1 as reverseTrend };
        const selected_1: boolean;
        export { selected_1 as selected };
        export { noop as onLinkClickCallback };
    }
}
import PropTypes from "prop-types";
import { noop } from "lodash/common/util";
//# sourceMappingURL=number.d.ts.map