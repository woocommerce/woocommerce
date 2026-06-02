/// <reference path="./index.d.ts" />

declare module '@wordpress/block-editor/components/url-input/button' {
	import { ComponentType } from "react";

	import URLInput from "@wordpress/block-editor/components/url-input";

	declare namespace URLInputButton {
	    interface Props extends Pick<URLInput.Props, "onChange"> {
	        children?: never | undefined;
	        /**
	         * This should be set to the attribute (or component state) property used to store the URL.
	         */
	        url: string;
	    }
	}
	declare const URLInputButton: ComponentType<URLInputButton.Props>;

	export default URLInputButton;
}
