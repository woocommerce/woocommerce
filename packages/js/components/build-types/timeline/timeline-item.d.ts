export default TimelineItem;
declare function TimelineItem(props: any): JSX.Element;
declare namespace TimelineItem {
    namespace propTypes {
        const className: PropTypes.Requireable<string>;
        const item: PropTypes.Validator<PropTypes.InferProps<{
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
            /**
             * The PHP clock format string used to format times, see php.net/date.
             */
            clockFormat: PropTypes.Requireable<string>;
        }>>;
    }
    namespace defaultProps {
        const className_1: string;
        export { className_1 as className };
        const item_1: {};
        export { item_1 as item };
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=timeline-item.d.ts.map