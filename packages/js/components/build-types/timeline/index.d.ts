export default Timeline;
declare function Timeline(props: any): JSX.Element;
declare namespace Timeline {
    namespace propTypes {
        const className: PropTypes.Requireable<string>;
        const items: PropTypes.Validator<(PropTypes.InferProps<{
            /**
             * Date for the timeline item.
             */
            date: PropTypes.Validator<Date>;
            /**
             * Icon for the Timeline item.
             */
            icon: PropTypes.Validator<PropTypes.ReactElementLike>;
            /**
             * Headline displayed for the list item.
             */
            headline: PropTypes.Validator<string | PropTypes.ReactElementLike>;
            /**
             * Body displayed for the list item.
             */
            body: PropTypes.Requireable<(string | PropTypes.ReactElementLike | null | undefined)[]>;
            /**
             * Allows users to toggle the timestamp on or off.
             */
            hideTimestamp: PropTypes.Requireable<boolean>;
        }> | null | undefined)[]>;
        const groupBy: PropTypes.Requireable<string>;
        const orderBy: PropTypes.Requireable<string>;
        const dateFormat: PropTypes.Requireable<string>;
        const clockFormat: PropTypes.Requireable<string>;
    }
    namespace defaultProps {
        const className_1: string;
        export { className_1 as className };
        const items_1: never[];
        export { items_1 as items };
        const groupBy_1: string;
        export { groupBy_1 as groupBy };
        const orderBy_1: string;
        export { orderBy_1 as orderBy };
        const dateFormat_1: string;
        export { dateFormat_1 as dateFormat };
        const clockFormat_1: string;
        export { clockFormat_1 as clockFormat };
    }
}
import PropTypes from "prop-types";
export { orderByOptions, groupByOptions } from "./util";
//# sourceMappingURL=index.d.ts.map