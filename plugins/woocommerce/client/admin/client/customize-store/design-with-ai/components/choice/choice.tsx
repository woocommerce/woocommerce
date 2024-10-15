/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './choice.scss';

type Props = {
	className?: string;
	selected: boolean;
	title: string;
	subtitle?: string;
	name: string;
	value: string;
	onChange: ( value: string ) => void;
};

export const Choice = ( {
	className,
	selected,
	title,
	subtitle,
	name,
	value,
	onChange,
}: Props ) => {
	const changeHandler = () => {
		onChange( value );
	};
	const inputId = 'woocommerce-' + value.replace( /_/g, '-' );

	return (
		<div
			role="radio"
			className={ clsx( 'woocommerce-cys-choice-container', className ) }
			onClick={ changeHandler }
			onKeyDown={ ( e ) => {
				if ( e.key === 'Enter' ) {
					changeHandler();
				}
			} }
			data-selected={ selected ? selected : null }
			tabIndex={ 0 }
		>
			<div className="woocommerce-cys-choice">
				<input
					className="woocommerce-cys-choice-input"
					id={ inputId }
					name={ name }
					type="radio"
					value={ value }
					checked={ !! selected }
					onChange={ changeHandler }
					data-selected={ selected ? selected : null }
					// Stop the input from being focused when the parent div is clicked
					tabIndex={ -1 }
				></input>
				<label htmlFor={ inputId } className="choice__title">
					{ title }
				</label>
				{ subtitle && <p className="choice__subtitle">{ subtitle }</p> }
			</div>
		</div>
	);
};
