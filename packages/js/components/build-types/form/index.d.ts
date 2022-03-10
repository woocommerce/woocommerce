export default Form;
/**
 * A form component to handle form state and provide input helper props.
 */
declare class Form extends Component<any, any, any> {
    constructor(props: any);
    state: {
        values: any;
        errors: any;
        touched: any;
    };
    getInputProps(name: any): {
        value: any;
        checked: boolean;
        selected: any;
        onChange: (value: any) => void;
        onBlur: () => void;
        className: string | null;
        help: any;
    };
    handleSubmit(): Promise<void>;
    setTouched(name: any, touched?: boolean): void;
    setValue(name: any, value: any): void;
    componentDidMount(): void;
    isValidForm(): Promise<boolean>;
    validate(onValidate?: () => void): void;
    handleChange(name: any, value: any): void;
    handleBlur(name: any): void;
    getStateAndHelpers(): {
        values: any;
        errors: any;
        touched: any;
        setTouched: (name: any, touched?: boolean) => void;
        setValue: (name: any, value: any) => void;
        handleSubmit: () => Promise<void>;
        getInputProps: (name: any) => {
            value: any;
            checked: boolean;
            selected: any;
            onChange: (value: any) => void;
            onBlur: () => void;
            className: string | null;
            help: any;
        };
        isValidForm: boolean;
    };
    render(): import("react").DetailedReactHTMLElement<import("react").HTMLAttributes<HTMLElement>, HTMLElement>;
}
declare namespace Form {
    namespace propTypes {
        const children: PropTypes.Requireable<any>;
        const errors: PropTypes.Requireable<object>;
        const initialValues: PropTypes.Validator<object>;
        const touched: PropTypes.Requireable<object>;
        const onSubmitCallback: PropTypes.Requireable<(...args: any[]) => any>;
        const onSubmit: PropTypes.Requireable<(...args: any[]) => any>;
        const onChangeCallback: PropTypes.Requireable<(...args: any[]) => any>;
        const onChange: PropTypes.Requireable<(...args: any[]) => any>;
        const validate: PropTypes.Requireable<(...args: any[]) => any>;
    }
    namespace defaultProps {
        const errors_1: {};
        export { errors_1 as errors };
        const initialValues_1: {};
        export { initialValues_1 as initialValues };
        const onSubmitCallback_1: null;
        export { onSubmitCallback_1 as onSubmitCallback };
        export function onSubmit_1(): void;
        export { onSubmit_1 as onSubmit };
        const onChangeCallback_1: null;
        export { onChangeCallback_1 as onChangeCallback };
        export function onChange_1(): void;
        export { onChange_1 as onChange };
        const touched_1: {};
        export { touched_1 as touched };
        export function validate_1(): void;
        export { validate_1 as validate };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map