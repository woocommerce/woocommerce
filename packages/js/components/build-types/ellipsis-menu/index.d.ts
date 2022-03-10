export default EllipsisMenu;
/**
 * This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.
 */
declare class EllipsisMenu extends Component<any, any, any> {
    constructor(props: any);
    constructor(props: any, context: any);
    render(): JSX.Element | null;
}
declare namespace EllipsisMenu {
    namespace propTypes {
        const label: PropTypes.Validator<string>;
        const renderContent: PropTypes.Requireable<(...args: any[]) => any>;
        const className: PropTypes.Requireable<string>;
        const onToggle: PropTypes.Requireable<(...args: any[]) => any>;
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map