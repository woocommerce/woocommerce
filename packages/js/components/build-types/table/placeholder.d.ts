export default TablePlaceholder;
/**
 * `TablePlaceholder` behaves like `Table` but displays placeholder boxes instead of data. This can be used while loading.
 */
declare class TablePlaceholder extends Component<any, any, any> {
    constructor(props: any);
    constructor(props: any, context: any);
    render(): JSX.Element;
}
declare namespace TablePlaceholder {
    namespace propTypes {
        const query: PropTypes.Requireable<object>;
        const caption: PropTypes.Validator<string>;
        const headers: PropTypes.Requireable<(PropTypes.InferProps<{
            hiddenByDefault: PropTypes.Requireable<boolean>;
            defaultSort: PropTypes.Requireable<boolean>;
            isSortable: PropTypes.Requireable<boolean>;
            key: PropTypes.Requireable<string>;
            label: PropTypes.Requireable<PropTypes.ReactNodeLike>;
            required: PropTypes.Requireable<boolean>;
        }> | null | undefined)[]>;
        const numberOfRows: PropTypes.Requireable<number>;
    }
    namespace defaultProps {
        const numberOfRows_1: number;
        export { numberOfRows_1 as numberOfRows };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=placeholder.d.ts.map