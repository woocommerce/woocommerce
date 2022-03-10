export default TimelineGroup;
declare function TimelineGroup(props: any): JSX.Element;
declare namespace TimelineGroup {
    namespace propTypes {
        const className: PropTypes.Requireable<string>;
        const group: PropTypes.Validator<PropTypes.InferProps<{
            /**
             * The group title.
             */
            title: PropTypes.Requireable<string>;
            /**
             * An array of list items.
             */
            items: PropTypes.Validator<(PropTypes.InferProps<{
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
        }>>;
        const orderBy: PropTypes.Requireable<string>;
        const clockFormat: PropTypes.Requireable<string>;
    }
    namespace defaultProps {
        const className_1: string;
        export { className_1 as className };
        export namespace group_1 {
            const title: string;
            const items: never[];
        }
        export { group_1 as group };
        const orderBy_1: string;
        export { orderBy_1 as orderBy };
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=timeline-group.d.ts.map