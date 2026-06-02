declare module '@wordpress/editor/build-types/components/keyboard-shortcut-help-modal/config' {
	export const textFormattingShortcuts: ({
	    keyCombination: {
	        modifier: string;
	        character: string;
	    };
	    description: import("@wordpress/i18n").TranslatableText<"Make the selected text bold.">;
	    aliases?: undefined;
	} | {
	    keyCombination: {
	        modifier: string;
	        character: string;
	    };
	    description: import("@wordpress/i18n").TranslatableText<"Make the selected text italic.">;
	    aliases?: undefined;
	} | {
	    keyCombination: {
	        modifier: string;
	        character: string;
	    };
	    description: import("@wordpress/i18n").TranslatableText<"Convert the selected text into a link.">;
	    aliases?: undefined;
	} | {
	    keyCombination: {
	        modifier: string;
	        character: string;
	    };
	    description: import("@wordpress/i18n").TranslatableText<"Remove a link.">;
	    aliases?: undefined;
	} | {
	    keyCombination: {
	        character: string;
	        modifier?: undefined;
	    };
	    description: import("@wordpress/i18n").TranslatableText<"Insert a link to a post or page.">;
	    aliases?: undefined;
	} | {
	    keyCombination: {
	        modifier: string;
	        character: string;
	    };
	    description: import("@wordpress/i18n").TranslatableText<"Underline the selected text.">;
	    aliases?: undefined;
	} | {
	    keyCombination: {
	        modifier: string;
	        character: string;
	    };
	    description: import("@wordpress/i18n").TranslatableText<"Strikethrough the selected text.">;
	    aliases?: undefined;
	} | {
	    keyCombination: {
	        modifier: string;
	        character: string;
	    };
	    description: import("@wordpress/i18n").TranslatableText<"Make the selected text inline code.">;
	    aliases?: undefined;
	} | {
	    keyCombination: {
	        modifier: string;
	        character: string;
	    };
	    aliases: {
	        modifier: string;
	        character: string;
	    }[];
	    description: import("@wordpress/i18n").TranslatableText<"Convert the current heading to a paragraph.">;
	} | {
	    keyCombination: {
	        modifier: string;
	        character: string;
	    };
	    description: import("@wordpress/i18n").TranslatableText<"Convert the current paragraph or heading to a heading of level 1 to 6.">;
	    aliases?: undefined;
	} | {
	    keyCombination: {
	        modifier: string;
	        character: string;
	    };
	    description: import("@wordpress/i18n").TranslatableText<"Add non breaking space.">;
	    aliases?: undefined;
	})[];
}
