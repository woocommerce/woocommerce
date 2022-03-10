export default SegmentedSelection;
/**
 * Create a panel of styled selectable options rendering stylized checkboxes and labels
 */
declare class SegmentedSelection extends Component<any, any, any> {
    constructor(props: any);
    constructor(props: any, context: any);
    render(): JSX.Element;
}
declare namespace SegmentedSelection {
    namespace propTypes {
        const className: PropTypes.Requireable<string>;
        const options: PropTypes.Validator<(PropTypes.InferProps<{
            value: PropTypes.Validator<string>;
            label: PropTypes.Validator<string>;
        }> | null | undefined)[]>;
        const selected: PropTypes.Requireable<string>;
        const onSelect: PropTypes.Validator<(...args: any[]) => any>;
        const name: PropTypes.Validator<string>;
        const legend: PropTypes.Validator<string>;
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map