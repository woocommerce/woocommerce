export default Pagination;
/**
 * Use `Pagination` to allow navigation between pages that represent a collection of items.
 * The component allows for selecting a new page and items per page options.
 */
declare class Pagination extends Component<any, any, any> {
    constructor(props: any);
    state: {
        inputValue: any;
    };
    previousPage(event: any): void;
    nextPage(event: any): void;
    onInputChange(event: any): void;
    onInputBlur(event: any): void;
    perPageChange(perPage: any): void;
    selectInputValue(event: any): void;
    renderPageArrows(): JSX.Element | null;
    renderPagePicker(): JSX.Element;
    renderPerPagePicker(): JSX.Element;
    render(): JSX.Element | null;
    pageCount: number | undefined;
}
declare namespace Pagination {
    namespace propTypes {
        const page: PropTypes.Validator<number>;
        const onPageChange: PropTypes.Requireable<(...args: any[]) => any>;
        const perPage: PropTypes.Validator<number>;
        const onPerPageChange: PropTypes.Requireable<(...args: any[]) => any>;
        const total: PropTypes.Validator<number>;
        const className: PropTypes.Requireable<string>;
        const showPagePicker: PropTypes.Requireable<boolean>;
        const showPerPagePicker: PropTypes.Requireable<boolean>;
        const showPageArrowsLabel: PropTypes.Requireable<boolean>;
    }
    namespace defaultProps {
        export { noop as onPageChange };
        export { noop as onPerPageChange };
        const showPagePicker_1: boolean;
        export { showPagePicker_1 as showPagePicker };
        const showPerPagePicker_1: boolean;
        export { showPerPagePicker_1 as showPerPagePicker };
        const showPageArrowsLabel_1: boolean;
        export { showPageArrowsLabel_1 as showPageArrowsLabel };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
import { noop } from "lodash/common/util";
//# sourceMappingURL=index.d.ts.map