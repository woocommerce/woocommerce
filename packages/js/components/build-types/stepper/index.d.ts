import React from 'react';
interface StepperProps {
    /** Additional class name to style the component. */
    className?: string;
    /** The current step's key. */
    currentStep: string;
    /** An array of steps used. */
    steps: Array<{
        /** Content displayed when the step is active. */
        content: React.ReactNode;
        /** Description displayed beneath the label. */
        description: string | Array<string>;
        /** Optionally mark a step complete regardless of step index. */
        isComplete?: boolean;
        /** Key used to identify step. */
        key: string;
        /** Label displayed in stepper. */
        label: string;
        /** A function to be called when the step label is clicked. */
        onClick?: (key: string) => void;
    }>;
    /** If the stepper is vertical instead of horizontal. */
    isVertical: boolean;
    /**  Optionally mark the current step as pending to show a spinner. */
    isPending: boolean;
}
/**
 * A stepper component to indicate progress in a set number of steps.
 */
export declare const Stepper: React.FC<StepperProps>;
export default Stepper;
//# sourceMappingURL=index.d.ts.map