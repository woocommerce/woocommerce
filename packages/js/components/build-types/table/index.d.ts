export default TableCard;
/**
 * This is an accessible, sortable, and scrollable table for displaying tabular data (like revenue and other analytics data).
 * It accepts `headers` for column headers, and `rows` for the table content.
 * `rowHeader` can be used to define the index of the row header (or false if no header).
 *
 * `TableCard` serves as Card wrapper & contains a card header, `<Table />`, `<TableSummary />`, and `<Pagination />`.
 * This includes filtering and comparison functionality for report pages.
 */
declare class TableCard extends Component<any, any, any> {
    constructor(props: any);
    state: {
        showCols: any;
    };
    onColumnToggle(key: any): () => void;
    onPageChange(...params: any[]): void;
    componentDidUpdate({ headers: prevHeaders, query: prevQuery }: {
        headers: any;
        query: any;
    }): void;
    getShowCols(headers: any): any;
    getVisibleHeaders(): any;
    getVisibleRows(): any;
    render(): JSX.Element;
}
declare namespace TableCard {
    namespace propTypes {
        const hasSearch: PropTypes.Requireable<boolean>;
        const headers: PropTypes.Requireable<(PropTypes.InferProps<{
            hiddenByDefault: PropTypes.Requireable<boolean>;
            defaultSort: PropTypes.Requireable<boolean>;
            isSortable: PropTypes.Requireable<boolean>;
            key: PropTypes.Requireable<string>;
            label: PropTypes.Requireable<string | number | boolean | {} | PropTypes.ReactElementLike | PropTypes.ReactNodeArray>;
            required: PropTypes.Requireable<boolean>;
        }> | null | undefined)[]>;
        const ids: PropTypes.Requireable<(number | null | undefined)[]>;
        const isLoading: PropTypes.Requireable<boolean>;
        const onQueryChange: PropTypes.Requireable<(...args: any[]) => any>;
        const onColumnsChange: PropTypes.Requireable<(...args: any[]) => any>;
        const onSort: PropTypes.Requireable<(...args: any[]) => any>;
        const query: PropTypes.Requireable<object>;
        const rowHeader: PropTypes.Requireable<number | boolean>;
        const rows: PropTypes.Validator<((PropTypes.InferProps<{
            display: PropTypes.Requireable<PropTypes.ReactNodeLike>;
            value: PropTypes.Requireable<string | number | boolean>;
        }> | null | undefined)[] | null | undefined)[]>;
        const rowsPerPage: PropTypes.Validator<number>;
        const showMenu: PropTypes.Requireable<boolean>;
        const summary: PropTypes.Requireable<(PropTypes.InferProps<{
            label: PropTypes.Requireable<PropTypes.ReactNodeLike>;
            value: PropTypes.Requireable<string | number>;
        }> | null | undefined)[]>;
        const title: PropTypes.Validator<string>;
        const totalRows: PropTypes.Validator<number>;
        const rowKey: PropTypes.Requireable<(...args: any[]) => any>;
    }
    namespace defaultProps {
        const isLoading_1: boolean;
        export { isLoading_1 as isLoading };
        export function onQueryChange_1(): () => void;
        export { onQueryChange_1 as onQueryChange };
        export function onColumnsChange_1(): void;
        export { onColumnsChange_1 as onColumnsChange };
        const onSort_1: undefined;
        export { onSort_1 as onSort };
        const query_1: {};
        export { query_1 as query };
        const rowHeader_1: number;
        export { rowHeader_1 as rowHeader };
        const rows_1: never[];
        export { rows_1 as rows };
        const showMenu_1: boolean;
        export { showMenu_1 as showMenu };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map