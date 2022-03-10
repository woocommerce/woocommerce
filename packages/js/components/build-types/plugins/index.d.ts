export class Plugins extends Component<any, any, any> {
    constructor(...args: any[]);
    state: {
        hasErrors: boolean;
    };
    installAndActivate(event: any): Promise<false | undefined>;
    skipInstaller(): void;
    handleErrors(errors: any, response: any): void;
    handleSuccess(activePlugins: any, response: any): void;
    componentDidMount(): void;
    render(): JSX.Element | null;
}
export namespace Plugins {
    namespace propTypes {
        const onComplete: PropTypes.Validator<(...args: any[]) => any>;
        const onError: PropTypes.Requireable<(...args: any[]) => any>;
        const onSkip: PropTypes.Requireable<(...args: any[]) => any>;
        const skipText: PropTypes.Requireable<string>;
        const autoInstall: PropTypes.Requireable<boolean>;
        const pluginSlugs: PropTypes.Requireable<(string | null | undefined)[]>;
        const onAbort: PropTypes.Requireable<(...args: any[]) => any>;
        const abortText: PropTypes.Requireable<string>;
    }
    namespace defaultProps {
        const autoInstall_1: boolean;
        export { autoInstall_1 as autoInstall };
        export function onError_1(): void;
        export { onError_1 as onError };
        export function onSkip_1(): void;
        export { onSkip_1 as onSkip };
        const pluginSlugs_1: string[];
        export { pluginSlugs_1 as pluginSlugs };
    }
}
declare var _default: import("react").ComponentType<{}>;
export default _default;
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map