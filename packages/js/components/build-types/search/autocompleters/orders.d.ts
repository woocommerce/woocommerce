declare var _default: WPCompleter;
export default _default;
/**
 * A raw completer option.
 */
export type CompleterOption = any;
export type FnGetOptions = () => (CompleterOption[] | Promise<CompleterOption[]>);
export type FnGetOptionKeywords = (option: CompleterOption) => string[];
export type FnIsOptionDisabled = (option: CompleterOption) => string[];
export type FnGetOptionLabel = (option: CompleterOption) => (string | Array<(string | Node)>);
export type FnAllowContext = (before: string, after: string) => boolean;
export type OptionCompletion = {
    /**
     * the intended placement of the completion.
     */
    action: 'insert-at-caret' | 'replace';
    /**
     * the completion value.
     */
    value: OptionCompletionValue;
};
/**
 * A completion value.
 */
export type OptionCompletionValue = (string | WPElement | Object);
export type FnGetOptionCompletion = (value: CompleterOption, query: string) => (OptionCompletion | OptionCompletionValue);
export type WPCompleter = {
    /**
     * a way to identify a completer, useful for selective overriding.
     */
    name: string;
    /**
     * A class to apply to the popup menu.
     */
    className: string | null;
    /**
     * the prefix that will display the menu.
     */
    triggerPrefix: string;
    /**
     * the completer options or a function to get them.
     */
    options: (CompleterOption[] | FnGetOptions);
    /**
     * get the keywords for a given option.
     */
    getOptionKeywords: FnGetOptionKeywords | null;
    /**
     * get whether or not the given option is disabled.
     */
    isOptionDisabled: FnIsOptionDisabled | null;
    /**
     * get the label for a given option.
     */
    getOptionLabel: FnGetOptionLabel;
    /**
     * filter the context under which the autocomplete activates.
     */
    allowContext: FnAllowContext | null;
    /**
     * get the completion associated with a given option.
     */
    getOptionCompletion: FnGetOptionCompletion;
};
//# sourceMappingURL=orders.d.ts.map