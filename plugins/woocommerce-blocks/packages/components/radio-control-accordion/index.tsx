/**
 * External dependencies
 */
import classnames from 'classnames';
import { withInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { RadioControlOption } from '../radio-control';
import { useMemo } from '@wordpress/element';

export interface RadioControlAccordionProps {
	className?: string;
	instanceId: number;
	id: string;
	onChange: ( value: string ) => void;
	options: Array< {
		value: string;
		label: string | JSX.Element;
		onChange?: ( value: string ) => void;
		name: string;
		content: JSX.Element;
	} >;
	selected: string | null;
	// Should the selected option be highlighted with a border?
	highlightChecked?: boolean;
}

const RadioControlAccordion = ( {
	className,
	instanceId,
	id,
	selected,
	onChange,
	options = [],
	highlightChecked = false,
}: RadioControlAccordionProps ): JSX.Element | null => {
	const radioControlId = id || instanceId;

	const selectedOptionNumber = useMemo( () => {
		return options.findIndex( ( option ) => option.value === selected );
	}, [ options, selected ] );

	if ( ! options.length ) {
		return null;
	}
	return (
		<div
			className={ classnames(
				'wc-block-components-radio-control',
				{
					'wc-block-components-radio-control--highlight-checked':
						highlightChecked,
					'wc-block-components-radio-control--highlight-checked--first-selected':
						highlightChecked && selectedOptionNumber === 0,
				},
				className
			) }
		>
			{ options.map( ( option ) => {
				const hasOptionContent =
					typeof option === 'object' && 'content' in option;
				const checked = option.value === selected;
				return (
					<div
						className={ classnames(
							'wc-block-components-radio-control-accordion-option',
							{
								'wc-block-components-radio-control-accordion-option--checked-option-highlighted':
									checked && highlightChecked,
							}
						) }
						key={ option.value }
					>
						<RadioControlOption
							name={ `radio-control-${ radioControlId }` }
							checked={ checked }
							option={ option }
							onChange={ ( value ) => {
								onChange( value );
								if ( typeof option.onChange === 'function' ) {
									option.onChange( value );
								}
							} }
						/>
						{ hasOptionContent && checked && (
							<div
								className={ classnames(
									'wc-block-components-radio-control-accordion-content',
									{
										'wc-block-components-radio-control-accordion-content-hide':
											! checked,
									}
								) }
							>
								{ option.content }
							</div>
						) }
					</div>
				);
			} ) }
		</div>
	);
};

export default withInstanceId( RadioControlAccordion );
export { RadioControlAccordion };
