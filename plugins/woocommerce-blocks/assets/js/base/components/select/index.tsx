/**
 * External dependencies
 */
import { Icon, chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';

export const Select = ( { label } ) => {
	return (
		<div className="wc-blocks-components-select">
			<div className="wc-blocks-components-select__container">
				<label className="wc-blocks-components-select__label">
					{ label }
				</label>
				<select
					className="wc-blocks-components-select__select"
					size={ 1 }
				>
					<option>Åland Islands</option>
					<option>Option 2</option>
					<option>Option 3</option>
				</select>
				<Icon
					className="wc-blocks-components-select__expand"
					icon={ chevronDown }
				/>
			</div>
		</div>
	);
};
