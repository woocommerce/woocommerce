/// <reference types="react" />
import { Field } from './types';
declare type DynamicFormProps = {
    fields: Field[] | {
        [key: string]: Field;
    };
    validate: (values: Record<string, string>) => Record<string, string>;
    isBusy?: boolean;
    onSubmit?: (values: Record<string, string>) => void;
    onChange?: (value: Record<string, string>, values: Record<string, string>[], result: boolean) => void;
    submitLabel?: string;
};
export declare const DynamicForm: React.FC<DynamicFormProps>;
export {};
//# sourceMappingURL=dynamic-form.d.ts.map