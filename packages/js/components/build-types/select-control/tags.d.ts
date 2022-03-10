export default Tags;
/**
 * A list of tags to display selected items.
 */
declare class Tags extends Component<any, any, any> {
    constructor(props: any);
    removeAll(): void;
    removeResult(key: any): () => void;
    render(): JSX.Element | null;
}
declare namespace Tags {
    namespace propTypes {
        const onChange: PropTypes.Requireable<(...args: any[]) => any>;
        const onSelect: PropTypes.Requireable<(...args: any[]) => any>;
        const selected: PropTypes.Requireable<(PropTypes.InferProps<{
            key: PropTypes.Validator<string | number>;
            label: PropTypes.Requireable<string>;
        }> | null | undefined)[]>;
        const showClearButton: PropTypes.Requireable<boolean>;
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=tags.d.ts.map