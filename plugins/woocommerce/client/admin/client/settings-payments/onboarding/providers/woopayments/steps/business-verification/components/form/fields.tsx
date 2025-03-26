/**
 * External dependencies
 */
import React, { ComponentProps, forwardRef } from 'react';
import { TextControl } from '@wordpress/components';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import CustomSelectControl, {
	ControlProps as SelectControlProps,
	Item as SelectItem,
} from '../../../../components/custom-select-control';
import GroupedSelectControl, {
	GroupedSelectControlProps,
	ListItem as GroupedSelectItem,
} from '../../../../components/grouped-select-control';

interface CommonProps {
	error?: string;
}

export type TextFieldProps = ComponentProps< typeof TextControl > & CommonProps;
export type SelectFieldProps< ItemType > = SelectControlProps< ItemType > &
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
): JSX.Element => makeField( CustomSelectControl, props );

export const GroupedSelectField = < ItemType extends GroupedSelectItem >(
	props: GroupedSelectControlProps< ItemType >
): JSX.Element => makeField( GroupedSelectControl, props );
