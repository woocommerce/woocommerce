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
	title: string | React.ReactNode;
	name: string;
	value: string;
	onChange: ( value: string ) => void;
	subOptionsComponent?: React.ReactNode;
};

export const Choice = ( {
	className,
	selected,
	title,
	name,
	value,
	onChange,
	subOptionsComponent = null,
}: Props ) => {
	const changeHandler = () => {
		onChange( value );
	};
	const inputId = 'woocommerce-' + value.replace( /_/g, '-' );

	return (
		<div
			role="radio"
			className={ clsx(
				'woocommerce-profiler-choice-container',
				className
			) }
			onClick={ changeHandler }
			onKeyDown={ ( e ) => {
				if ( e.key === 'Enter' ) {
					changeHandler();
				}
			} }
			data-selected={ selected ? selected : null }
			tabIndex={ 0 }
		>
			<div className="woocommerce-profiler-choice">
				<input
					className="woocommerce-profiler-choice-input"
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
			</div>
			{ selected && subOptionsComponent && (
				<div className="woocommerce-profiler-choice-sub-options">
					{ subOptionsComponent }
				</div>
			) }
		</div>
	);
};
