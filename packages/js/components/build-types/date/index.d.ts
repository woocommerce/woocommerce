export default Date;
/**
 * Use the `Date` component to display accessible dates or times.
 *
 * @param {Object} props
 * @param {Object} props.date
 * @param {string} props.machineFormat
 * @param {string} props.screenReaderFormat
 * @param {string} props.visibleFormat
 * @return {Object} -
 */
declare function Date({ date, machineFormat, screenReaderFormat, visibleFormat }: {
    date: Object;
    machineFormat: string;
    screenReaderFormat: string;
    visibleFormat: string;
}): Object;
declare namespace Date {
    namespace propTypes {
        const date: PropTypes.Validator<string | object>;
        const machineFormat: PropTypes.Requireable<string>;
        const screenReaderFormat: PropTypes.Requireable<string>;
        const visibleFormat: PropTypes.Requireable<string>;
    }
    namespace defaultProps {
        const machineFormat_1: string;
        export { machineFormat_1 as machineFormat };
        const screenReaderFormat_1: string;
        export { screenReaderFormat_1 as screenReaderFormat };
        const visibleFormat_1: string;
        export { visibleFormat_1 as visibleFormat };
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map