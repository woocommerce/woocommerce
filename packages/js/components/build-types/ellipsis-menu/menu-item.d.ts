export default MenuItem;
/**
 * `MenuItem` is used to give the item an accessible wrapper, with the `menuitem` role and added keyboard functionality (`onInvoke`).
 * `MenuItem`s can also be deemed "clickable", though this is disabled by default because generally the inner component handles
 * the click event.
 */
declare class MenuItem extends Component<any, any, any> {
    constructor(...args: any[]);
    onClick(event: any): void;
    onFocusFormToggle(): void;
    onKeyDown(event: any): void;
    container: import("react").RefObject<any>;
    render(): JSX.Element;
}
declare namespace MenuItem {
    namespace propTypes {
        const checked: PropTypes.Requireable<boolean>;
        const children: PropTypes.Requireable<PropTypes.ReactNodeLike>;
        const isCheckbox: PropTypes.Requireable<boolean>;
        const isClickable: PropTypes.Requireable<boolean>;
        const onInvoke: PropTypes.Validator<(...args: any[]) => any>;
    }
    namespace defaultProps {
        const isClickable_1: boolean;
        export { isClickable_1 as isClickable };
        const isCheckbox_1: boolean;
        export { isCheckbox_1 as isCheckbox };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=menu-item.d.ts.map