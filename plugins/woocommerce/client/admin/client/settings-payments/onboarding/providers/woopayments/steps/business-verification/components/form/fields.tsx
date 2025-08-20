/**
 * External dependencies
 */
import React, { ComponentProps, forwardRef } from 'react';
import { TextControl, CustomSelectControl } from '@wordpress/components';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import GroupedSelectControl, {
	GroupedSelectControlProps,
	ListItem as GroupedSelectItem,
} from '../../../../components/grouped-select-control';

interface CommonProps {
	error?: string;
}

// Define SelectItem interface to match @wordpress/components CustomSelectControl
interface SelectItem {
	key: string;
	name?: string;
	className?: string;
	style?: React.CSSProperties;
}

export type TextFieldProps = ComponentProps< typeof TextControl > & CommonProps;
export type SelectFieldProps< ItemType > = ComponentProps< typeof CustomSelectControl > &
	CommonProps;
export type GroupedSelectFieldProps< ItemType > =
	GroupedSelectControlProps< ItemType > & CommonProps;

/**
 * Creates a field component decorating a control to display validation errors.
 *
 * @param Control Control component to render.
 * @param props   Control props plus common field props – {error?: string}.
 * @param ref     Optional React reference.
 * @return        Form field.
 */
const makeField = (
	Control: React.ElementType,
	props: CommonProps & Record< any, any >, // eslint-disable-line @typescript-eslint/no-explicit-any
	ref?: React.Ref< HTMLInputElement >
) => {
	const { error, ...rest } = props;
	if ( ! error ) return <Control { ...rest } ref={ ref } />;
	return (
		<>
			<Control
				{ ...rest }
				ref={ ref }
				className={ clsx( rest.className, 'has-error' ) }
			/>
			{ <div className="components-form-field__error">{ error }</div> }
		</>
	);
};

export const TextField = forwardRef< HTMLInputElement, TextFieldProps >(
	( props, ref ) => {
		return makeField( TextControl, props, ref );
	}
);

export const SelectField = < ItemType extends SelectItem >(
	props: SelectFieldProps< ItemType >
): JSX.Element => {
	const propsWithClassName = {
		...props,
		className: clsx( 'woopayments', props.className )
	};
	return makeField( CustomSelectControl, propsWithClassName );
};

export const GroupedSelectField = < ItemType extends GroupedSelectItem >(
	props: GroupedSelectControlProps< ItemType >
): JSX.Element => makeField( GroupedSelectControl, props );
