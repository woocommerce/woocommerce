/**
 * External dependencies
 */
import classnames from 'classnames';
import { withInstanceId } from '@woocommerce/base-hocs/with-instance-id';

/**
 * Internal dependencies
 */
import RadioControlOption from '../radio-control/option';

const RadioControlAccordion = ( {
	className,
	instanceId,
	id,
	selected,
	onChange,
	options = [],
} ) => {
	const radioControlId = id || instanceId;

	return (
		options.length && (
			<div
				className={ classnames(
					'wc-block-components-radio-control',
					className
				) }
			>
				{ options.map( ( option ) => {
					const hasOptionContent =
						typeof option === 'object' && 'content' in option;
					const checked = option.value === selected;
					return (
						<div
							className="wc-block-components-radio-control-accordion-option"
							key={ option.value }
						>
							<RadioControlOption
								name={ `radio-control-${ radioControlId }` }
								checked={ checked }
								option={ option }
								onChange={ ( value ) => {
									onChange( value );
									if (
										typeof option.onChange === 'function'
									) {
										option.onChange( value );
									}
								} }
							/>
							{ hasOptionContent && checked && (
								<div
									className={ classnames(
										'wc-block-components-radio-control-accordion-content',
										{
											'wc-block-components-radio-control-accordion-content-hide': ! checked,
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
		)
	);
};

export default withInstanceId( RadioControlAccordion );
export { RadioControlAccordion };
