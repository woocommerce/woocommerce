interface BlockErrorBase {
	/**
	 * URL of the image to display.
	 * If it's `null` or an empty string, no image will be displayed.
	 * If it's not defined, the default image will be used.
	 */
	imageUrl?: string;
	/**
	 * Text to display as the heading of the error block.
	 * If it's `null` or an empty string, no header will be displayed.
	 * If it's not defined, the default header will be used.
	 */
	header?: string;
	/**
	 * Text to display in the error block below the header.
	 * If it's `null` or an empty string, nothing will be displayed.
	 * If it's not defined, the default text will be used.
	 */
	text: React.ReactNode;
	/**
	 * Text preceeding the error message.
	 */
	errorMessagePrefix?: string;
	/**
	 * Button cta.
	 */
	button?: React.ReactNode;
	/**
	 * Controls wether to show the error block or fail silently
	 */
	showErrorBlock?: boolean;
}

export interface BlockErrorProps extends BlockErrorBase {
	/**
	 * Error message to display below the content.
	 */
	errorMessage: React.ReactNode;
}

export type RenderErrorProps = {
	errorMessage: React.ReactNode;
};

export interface BlockErrorBoundaryProps extends BlockErrorBase {
	/**
	 * Override the default error with a function that takes the error message and returns a React component
	 */
	renderError?: ( props: RenderErrorProps ) => React.ReactNode;
	showErrorMessage?: boolean;
}

export interface DerivedStateReturn {
	errorMessage: JSX.Element | string;
	hasError: boolean;
}

export interface ReactError {
	status: string;
	statusText: string;
	message: string;
}
