export function SearchListControl(props: Object): JSX.Element;
export namespace SearchListControl {
    namespace propTypes {
        const className: PropTypes.Requireable<string>;
        const isCompact: PropTypes.Requireable<boolean>;
        const isHierarchical: PropTypes.Requireable<boolean>;
        const isLoading: PropTypes.Requireable<boolean>;
        const isSingle: PropTypes.Requireable<boolean>;
        const list: PropTypes.Requireable<(PropTypes.InferProps<{
            id: PropTypes.Requireable<number>;
            name: PropTypes.Requireable<string>;
        }> | null | undefined)[]>;
        const messages: PropTypes.Requireable<PropTypes.InferProps<{
            /**
             * A more detailed label for the "Clear all" button, read to screen reader users.
             */
            clear: PropTypes.Requireable<string>;
            /**
             * Message to display when the list is empty (implies nothing loaded from the server
             * or parent component).
             */
            noItems: PropTypes.Requireable<string>;
            /**
             * Message to display when no matching results are found. %s is the search term.
             */
            noResults: PropTypes.Requireable<string>;
            /**
             * Label for the search input
             */
            search: PropTypes.Requireable<string>;
            /**
             * Label for the selected items. This is actually a function, so that we can pass
             * through the count of currently selected items.
             */
            selected: PropTypes.Requireable<(...args: any[]) => any>;
            /**
             * Label indicating that search results have changed, read to screen reader users.
             */
            updated: PropTypes.Requireable<string>;
        }>>;
        const onChange: PropTypes.Validator<(...args: any[]) => any>;
        const onSearch: PropTypes.Requireable<(...args: any[]) => any>;
        const renderItem: PropTypes.Requireable<(...args: any[]) => any>;
        const selected: PropTypes.Validator<any[]>;
        const debouncedSpeak: PropTypes.Requireable<(...args: any[]) => any>;
        const instanceId: PropTypes.Requireable<number>;
    }
}
declare var _default: any;
export default _default;
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map