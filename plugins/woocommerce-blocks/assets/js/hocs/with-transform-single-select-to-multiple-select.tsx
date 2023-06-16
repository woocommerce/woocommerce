/**
 * External dependencies
 */
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - Ignoring because @wordpress/element library does not have type definition for FunctionComponent
// eslint-disable-next-line
import { FunctionComponent } from '@wordpress/element';

type SelectedOption = number | string | null | number[] | string[];

interface OriginalComponentProps {
	selected?: SelectedOption;
}

/**
 * HOC that transforms a single select to a multiple select.
 *
 * @param {FunctionComponent< Record< string, unknown > >} OriginalComponent Component being wrapped.
 */
const withTransformSingleSelectToMultipleSelect = (
	OriginalComponent: FunctionComponent< Record< string, unknown > >
) => {
	return ( props: OriginalComponentProps ): JSX.Element => {
		let { selected } = props;
		selected = selected === undefined ? null : selected;
		const isNil = selected === null;

		return Array.isArray( selected ) ? (
			<OriginalComponent { ...props } />
		) : (
			<OriginalComponent
				{ ...props }
				selected={ isNil ? [] : [ selected ] }
			/>
		);
	};
};

export default withTransformSingleSelectToMultipleSelect;
