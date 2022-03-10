export default AdvancedFilters;
/**
 * Displays a configurable set of filters which can modify query parameters.
 */
declare class AdvancedFilters extends Component<any, any, any> {
    constructor({ query, config }: {
        query: any;
        config: any;
    }, ...args: any[]);
    instanceCounts: {};
    state: {
        match: any;
        activeFilters: any;
    };
    filterListRef: import("react").RefObject<any>;
    onMatchChange(match: any): void;
    onFilterChange(index: any, property: any, value: any, shouldResetValue?: boolean): void;
    getAvailableFilterKeys(): string[];
    addFilter(key: any, onClose: any): void;
    removeFilter(index: any): void;
    clearFilters(): void;
    getUpdateHref(activeFilters: any, matchValue: any): any;
    onFilter(): void;
    componentDidUpdate(prevProps: any): void;
    getInstanceNumber(key: any): number;
    getTitle(): JSX.Element;
    isEnglish(): boolean;
    orderFilters(a: any, b: any): number;
    render(): JSX.Element;
}
declare namespace AdvancedFilters {
    namespace propTypes {
        const config: PropTypes.Validator<PropTypes.InferProps<{
            title: PropTypes.Requireable<string>;
            filters: PropTypes.Requireable<{
                [x: string]: PropTypes.InferProps<{
                    labels: PropTypes.Requireable<PropTypes.InferProps<{
                        add: PropTypes.Requireable<string>;
                        remove: PropTypes.Requireable<string>;
                        rule: PropTypes.Requireable<string>;
                        title: PropTypes.Requireable<string>;
                        filter: PropTypes.Requireable<string>;
                    }>>;
                    rules: PropTypes.Requireable<(object | null | undefined)[]>;
                    input: PropTypes.Requireable<object>;
                }> | null | undefined;
            }>;
        }>>;
        const path: PropTypes.Validator<string>;
        const query: PropTypes.Requireable<object>;
        const onAdvancedFilterAction: PropTypes.Requireable<(...args: any[]) => any>;
        const siteLocale: PropTypes.Requireable<string>;
        const currency: PropTypes.Validator<object>;
    }
    namespace defaultProps {
        const query_1: {};
        export { query_1 as query };
        export function onAdvancedFilterAction_1(): void;
        export { onAdvancedFilterAction_1 as onAdvancedFilterAction };
        const siteLocale_1: string;
        export { siteLocale_1 as siteLocale };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map