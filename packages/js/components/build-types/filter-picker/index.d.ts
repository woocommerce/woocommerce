export const DEFAULT_FILTER: "all";
export default FilterPicker;
/**
 * Modify a url query parameter via a dropdown selection of configurable options.
 * This component manipulates the `filter` query parameter.
 */
declare class FilterPicker extends Component<any, any, any> {
    constructor(props: any);
    state: {
        nav: any;
        animate: null;
        selectedTag: null;
    };
    selectSubFilter(value: any): void;
    getVisibleFilters(filters: any, nav: any): any;
    updateSelectedTag(tags: any): void;
    onTagChange(filter: any, onClose: any, config: any, tags: any): void;
    onContentMount(content: any): void;
    goBack(): void;
    componentDidUpdate({ query: prevQuery }: {
        query: any;
    }): void;
    getFilter(value: any): any;
    getButtonLabel(selectedFilter: any): any[];
    getAllFilterParams(): any[];
    update(value: any, additionalQueries?: {}): void;
    renderButton(filter: any, onClose: any, config: any): JSX.Element;
    render(): JSX.Element;
}
declare namespace FilterPicker {
    namespace propTypes {
        const config: PropTypes.Validator<PropTypes.InferProps<{
            /**
             * A label above the filter selector.
             */
            label: PropTypes.Requireable<string>;
            /**
             * Url parameters to persist when selecting a new filter.
             */
            staticParams: PropTypes.Validator<any[]>;
            /**
             * The url paramter this filter will modify.
             */
            param: PropTypes.Validator<string>;
            /**
             * The default paramter value to use instead of 'all'.
             */
            defaultValue: PropTypes.Requireable<string>;
            /**
             * Determine if the filter should be shown. Supply a function with the query object as an argument returning a boolean.
             */
            showFilters: PropTypes.Validator<(...args: any[]) => any>;
            /**
             * An array of filter a user can select.
             */
            filters: PropTypes.Requireable<(PropTypes.InferProps<{
                /**
                 * The chart display mode to use for charts displayed when this filter is active.
                 */
                chartMode: PropTypes.Requireable<string>;
                /**
                 * A custom component used instead of a button, might have special handling for filtering. TBD, not yet implemented.
                 */
                component: PropTypes.Requireable<string>;
                /**
                 * The label for this filter. Optional only for custom component filters.
                 */
                label: PropTypes.Requireable<string>;
                /**
                 * An array representing the "path" to this filter, if nested.
                 */
                path: PropTypes.Requireable<string>;
                /**
                 * An array of more filter objects that act as "children" to this item.
                 * This set of filters is shown if the parent filter is clicked.
                 */
                subFilters: PropTypes.Requireable<any[]>;
                /**
                 * The value for this filter, used to set the `filter` query param when clicked, if there are no `subFilters`.
                 */
                value: PropTypes.Validator<string>;
            }> | null | undefined)[]>;
        }>>;
        const path: PropTypes.Validator<string>;
        const query: PropTypes.Requireable<object>;
        const onFilterSelect: PropTypes.Requireable<(...args: any[]) => any>;
        const advancedFilters: PropTypes.Requireable<object>;
    }
    namespace defaultProps {
        const query_1: {};
        export { query_1 as query };
        export function onFilterSelect_1(): void;
        export { onFilterSelect_1 as onFilterSelect };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map