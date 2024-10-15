/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './likert-scale.scss';

export interface LikertScaleProps {
	title: string;
	fieldName: string;
	onValueChange: ( value: number ) => void;
	validationFailed?: boolean;
}

export interface LikertChangeEvent {
	target: {
		value: number;
	};
}

export default function LikertScale( props: LikertScaleProps ): JSX.Element {
	const { title, fieldName, onValueChange, validationFailed } = props;
	const scaleOptions = [
		{
			value: 1,
			emoji: '😔',
			label: __( 'Strongly disagree', 'woocommerce' ),
		},
		{
			value: 2,
			emoji: '🙁',
			label: __( 'Disagree', 'woocommerce' ),
		},
		{
			value: 3,
			emoji: '😐',
			label: __( 'Neutral', 'woocommerce' ),
		},
		{
			value: 4,
			emoji: '🙂',
			label: __( 'Agree', 'woocommerce' ),
		},
		{
			value: 5,
			emoji: '😍',
			label: __( 'Strongly agree', 'woocommerce' ),
		},
	];

	const classes = clsx( 'woocommerce-marketplace__likert-scale', {
		'validation-failed': validationFailed,
	} );

	function valueChanged( e: React.ChangeEvent< HTMLInputElement > ) {
		onValueChange( parseInt( e.target.value, 10 ) );
	}

	return (
		<>
			<h2>{ title }</h2>
			<ol className={ classes }>
				{ scaleOptions.map( ( option ) => {
					const key = `${ fieldName }_${ option.value }`;
					return (
						<li
							key={ key }
							className="woocommerce-marketplace__likert-scale-item"
						>
							<input
								type="radio"
								name={ fieldName }
								value={ option.value }
								id={ key }
								onChange={ valueChanged }
								className="screen-reader-text"
							/>
							<label htmlFor={ key }>
								<div className="woocommerce-marketplace__likert-scale-icon">
									{ option.emoji }
								</div>
								<div className="woocommerce-marketplace__likert-scale-text">
									{ option.label }
								</div>
							</label>
						</li>
					);
				} ) }
			</ol>
		</>
	);
}
