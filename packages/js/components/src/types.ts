/**
 * External dependencies
 */
import { IconType, Popover } from '@wordpress/components';
import type { ReactNode } from 'react';

// TabPanel types copied from @wordpress/components
export type Tab = {
	/**
	 * The key of the tab.
	 */
	name: string;
	/**
	 * The label of the tab.
	 */
	title: string;
	/**
	 * The class name to apply to the tab button.
	 */
	className?: string;
	/**
	 * The icon used for the tab button.
	 */
	icon?: IconType;
	/**
	 * Determines if the tab button should be disabled.
	 */
	disabled?: boolean;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
} & Record< any, any >;

export type TabPanelProps = {
	/**
	 * The class name to add to the active tab.
	 *
	 * @default 'is-active'
	 */
	activeClass?: string;
	/**
	 * A function which renders the tabviews given the selected tab.
	 * The function is passed the active tab object as an argument as defined by the tabs prop.
	 */
	children: ( tab: Tab ) => ReactNode;
	/**
	 * The class name to give to the outer container for the TabPanel.
	 */
	className?: string;
	/**
	 * The name of the tab to be selected upon mounting of component.
	 * If this prop is not set, the first tab will be selected by default.
	 */
	initialTabName?: string;
	/**
	 * The function called when a tab has been selected.
	 * It is passed the `tabName` as an argument.
	 */
	onSelect?: ( tabName: string ) => void;
	/**
	 * The orientation of the tablist.
	 *
	 * @default `horizontal`
	 */
	orientation?: 'horizontal' | 'vertical';
	/**
	 * Array of tab objects. Each tab object should contain at least a `name` and a `title`.
	 */
	tabs: Tab[];
	/**
	 * When `true`, the tab will be selected when receiving focus (automatic tab
	 * activation). When `false`, the tab will be selected only when clicked
	 * (manual tab activation). See the official W3C docs for more info.
	 * .
	 *
	 * @default true
	 *
	 * @see https://www.w3.org/WAI/ARIA/apg/patterns/tabpanel/
	 */
	selectOnMove?: boolean;
};

export type PopoverProps = typeof Popover extends React.FC< infer P >
	? P
	: never;

// Copy paste the Slot/Fill types not exported from @wordpress/components
export type SlotKey = string | symbol;

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type FillProps = Record< string, any >;

type SlotPropBase = {
	/**
	 * Slot name.
	 */
	name: SlotKey;

	/**
	 * props to pass from `Slot` to `Fill`.
	 *
	 * @default {}
	 */
	fillProps?: FillProps;
};

export type SlotComponentProps =
	| ( SlotPropBase & {
			/**
			 * By default, events will bubble to their parents on the DOM hierarchy (native event bubbling).
			 * If set to true, events will bubble to their virtual parent in the React elements hierarchy instead,
			 * also accept an optional `className`, `id`, etc.  to add to the slot container.
			 */
			bubblesVirtually: true;

			/**
			 * A function that returns nodes to be rendered.
			 * Supported only when `bubblesVirtually` is `false`.
			 */
			children?: never;

			/**
			 * Additional className for the `Slot` component.
			 * Supported only when `bubblesVirtually` is `true`.
			 */
			className?: string;

			/**
			 * Additional styles for the `Slot` component.
			 * Supported only when `bubblesVirtually` is `true`.
			 */
			style?: React.CSSProperties;
	  } )
	| ( SlotPropBase & {
			/**
			 * By default, events will bubble to their parents on the DOM hierarchy (native event bubbling).
			 * If set to true, events will bubble to their virtual parent in the React elements hierarchy instead,
			 * also accept an optional `className`, `id`, etc.  to add to the slot container.
			 */
			bubblesVirtually?: false;

			/**
			 * A function that returns nodes to be rendered.
			 * Supported only when `bubblesVirtually` is `false`.
			 */
			children?: ( fills: ReactNode ) => ReactNode;

			/**
			 * Additional className for the `Slot` component.
			 * Supported only when `bubblesVirtually` is `true`.
			 */
			className?: never;

			/**
			 * Additional styles for the `Slot` component.
			 * Supported only when `bubblesVirtually` is `true`.
			 */
			style?: never;
	  } );

export type FillComponentProps = {
	/**
	 * The name of the slot to fill into.
	 */
	name: SlotKey;

	/**
	 * Children elements or render function.
	 */
	children?: ReactNode | ( ( fillProps: FillProps ) => ReactNode );
};

export type SlotFillProviderProps = {
	/**
	 * The children elements.
	 */
	children: ReactNode;

	/**
	 * Whether to pass slots to the parent provider if existent.
	 */
	passthrough?: boolean;
};
