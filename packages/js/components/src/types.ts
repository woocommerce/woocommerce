/**
 * External dependencies
 */
import { Fill, Popover, TabPanel } from '@wordpress/components';
import type { ReactNode } from 'react';
import React from 'react';

export type TabPanelProps = React.ComponentProps< typeof TabPanel >;
export type Tab = TabPanelProps[ 'tabs' ][ number ];
export type PopoverProps = React.ComponentProps< typeof Popover >;
export type FillComponentProps = React.ComponentProps< typeof Fill >;

// Copy paste the Slot/Fill types that currently don't work with ComponentProps
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
