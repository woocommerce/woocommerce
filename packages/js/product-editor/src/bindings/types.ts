/**
 * External dependencies
 */
import type { BlockEditProps, BlockAttributes } from '@wordpress/blocks';
import {
	// @ts-expect-error no exported member.
	type ComponentType,
} from '@wordpress/element';
/**
 * Internal dependencies
 */

export type AttrbuteBindingProps = {
	source: string;
	args: { prop: string };
};

export type BoundBlockAttributes = BlockAttributes & {
	metadata?: {
		bindings: Record< string, AttrbuteBindingProps >;
	};
	// attribute?: string;
};

export type BoundBlockEditInstance = CoreBlockEditProps< BoundBlockAttributes >;
export type BoundBlockEditComponent = ComponentType< BoundBlockEditInstance >;

/*
 * Block Binding API
 */
export type BindingUseSourceProps = {
	/*
	 * The placeholder value for the source.
	 */
	placeholder: string | null;
	/*
	 * The value of the source.
	 */
	useValue: [ string, ( newValue: string ) => void ];
};

export interface BindingSourceHandlerProps< T > {
	/*
	 * The name of the binding source handler.
	 */
	name: string;

	/*
	 * The human-readable label for the binding source handler.
	 */
	label: string;

	/*
	 * React custom hook to bind a source to a block.
	 */
	useSource: (
		blockProps: CoreBlockEditProps< BlockAttributes >,
		sourceArgs: T
	) => BindingUseSourceProps;

	lockAttributesEditing: boolean;
}

/*
 * Core Types
 */

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export interface CoreBlockEditProps< T extends Record< string, any > >
	extends BlockEditProps< T > {
	readonly name: string;
	readonly context: Record< string, string >;
}

export type BlockProps = CoreBlockEditProps< BlockAttributes >;
