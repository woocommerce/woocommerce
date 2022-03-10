export default ReportFilters;
/**
 * Add a collection of report filters to a page. This uses `DatePicker` & `FilterPicker` for the "basic" filters, and `AdvancedFilters`
 * or a comparison card if "advanced" or "compare" are picked from `FilterPicker`.
 *
 * @return {Object} -
 */
declare class ReportFilters extends Component<any, any, any> {
    constructor();
    renderCard(config: any): JSX.Element | null | undefined;
    onRangeSelect(data: any): void;
    getDateQuery(query: any): {
        period: any;
        compare: any;
        before: any;
        after: any;
        primaryDate: any;
        secondaryDate: any;
    };
    render(): JSX.Element;
}
declare namespace ReportFilters {
    namespace propTypes {
        const siteLocale: PropTypes.Requireable<string>;
        const advancedFilters: PropTypes.Requireable<object>;
        const filters: PropTypes.Requireable<any[]>;
        const path: PropTypes.Validator<string>;
        const query: PropTypes.Requireable<object>;
        const showDatePicker: PropTypes.Requireable<boolean>;
        const onDateSelect: PropTypes.Requireable<(...args: any[]) => any>;
        const onFilterSelect: PropTypes.Requireable<(...args: any[]) => any>;
        const onAdvancedFilterAction: PropTypes.Requireable<(...args: any[]) => any>;
        const currency: PropTypes.Requireable<object>;
        const dateQuery: PropTypes.Requireable<PropTypes.InferProps<{
            period: PropTypes.Validator<string>;
            compare: PropTypes.Validator<string>;
            before: PropTypes.Requireable<object>;
            after: PropTypes.Requireable<object>;
            primaryDate: PropTypes.Validator<PropTypes.InferProps<{
                label: PropTypes.Validator<string>;
                range: PropTypes.Validator<string>;
            }>>;
            secondaryDate: PropTypes.Requireable<PropTypes.InferProps<{
                label: PropTypes.Validator<string>;
                range: PropTypes.Validator<string>;
            }>>;
        }>>;
        const isoDateFormat: PropTypes.Requireable<string>;
    }
    namespace defaultProps {
        const siteLocale_1: string;
        export { siteLocale_1 as siteLocale };
        export namespace advancedFilters_1 {
            export const title: string;
            const filters_1: {};
            export { filters_1 as filters };
        }
        export { advancedFilters_1 as advancedFilters };
        const filters_2: never[];
        export { filters_2 as filters };
        const query_1: {};
        export { query_1 as query };
        const showDatePicker_1: boolean;
        export { showDatePicker_1 as showDatePicker };
        export function onDateSelect_1(): void;
        export { onDateSelect_1 as onDateSelect };
        const currency_1: any;
        export { currency_1 as currency };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map