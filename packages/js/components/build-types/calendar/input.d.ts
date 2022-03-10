export default DateInput;
declare function DateInput({ disabled, value, onChange, dateFormat, label, describedBy, error, onFocus, onBlur, onKeyDown, errorPosition, }: {
    disabled: any;
    value: any;
    onChange: any;
    dateFormat: any;
    label: any;
    describedBy: any;
    error: any;
    onFocus: any;
    onBlur: any;
    onKeyDown: any;
    errorPosition: any;
}): JSX.Element;
declare namespace DateInput {
    namespace propTypes {
        const disabled: PropTypes.Requireable<boolean>;
        const value: PropTypes.Requireable<string>;
        const onChange: PropTypes.Validator<(...args: any[]) => any>;
        const dateFormat: PropTypes.Validator<string>;
        const label: PropTypes.Validator<string>;
        const describedBy: PropTypes.Validator<string>;
        const error: PropTypes.Requireable<string>;
        const errorPosition: PropTypes.Requireable<string>;
        const onFocus: PropTypes.Requireable<(...args: any[]) => any>;
        const onBlur: PropTypes.Requireable<(...args: any[]) => any>;
        const onKeyDown: PropTypes.Requireable<(...args: any[]) => any>;
    }
    namespace defaultProps {
        const disabled_1: boolean;
        export { disabled_1 as disabled };
        export function onFocus_1(): void;
        export { onFocus_1 as onFocus };
        export function onBlur_1(): void;
        export { onBlur_1 as onBlur };
        const errorPosition_1: string;
        export { errorPosition_1 as errorPosition };
        export { noop as onKeyDown };
    }
}
import PropTypes from "prop-types";
import { noop } from "lodash/common/util";
//# sourceMappingURL=input.d.ts.map