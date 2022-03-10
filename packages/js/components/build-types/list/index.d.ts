export default List;
/**
 * List component to display a list of items.
 *
 * @param {Object} props props for list
 */
declare function List(props: Object): JSX.Element;
declare namespace List {
    namespace propTypes {
        const className: PropTypes.Requireable<string>;
        const items: PropTypes.Requireable<(PropTypes.InferProps<{
            /**
             * Content displayed after the list item text.
             */
            after: PropTypes.Requireable<PropTypes.ReactNodeLike>;
            /**
             * Content displayed before the list item text.
             */
            before: PropTypes.Requireable<PropTypes.ReactNodeLike>;
            /**
             * Additional class name to style the list item.
             */
            className: PropTypes.Requireable<string>;
            /**
             * Content displayed beneath the list item title.
             */
            content: PropTypes.Requireable<string | number | boolean | {} | PropTypes.ReactElementLike | PropTypes.ReactNodeArray>;
            /**
             * Href attribute used in a Link wrapped around the item.
             */
            href: PropTypes.Requireable<string>;
            /**
             * Called when the list item is clicked.
             */
            onClick: PropTypes.Requireable<(...args: any[]) => any>;
            /**
             * Target attribute used for Link wrapper.
             */
            target: PropTypes.Requireable<string>;
            /**
             * Title displayed for the list item.
             */
            title: PropTypes.Requireable<string | number | boolean | {} | PropTypes.ReactElementLike | PropTypes.ReactNodeArray>;
            /**
             * Unique key for list item.
             */
            key: PropTypes.Requireable<string>;
        }> | null | undefined)[]>;
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map