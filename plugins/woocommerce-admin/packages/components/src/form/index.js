/**
 * External dependencies
 */
import { cloneElement, Component } from '@wordpress/element';
import { noop } from 'lodash';
import PropTypes from 'prop-types';

/**
 * A form component to handle form state and provide input helper props.
 */
class Form extends Component {
	constructor( props ) {
		super();

		this.state = {
			values: props.initialValues,
			errors: props.errors,
			touched: props.touched,
		};

		this.getInputProps = this.getInputProps.bind( this );
		this.handleSubmit = this.handleSubmit.bind( this );
		this.setTouched = this.setTouched.bind( this );
		this.setValue = this.setValue.bind( this );
	}

	componentDidMount() {
		this.validate();
	}

	async isValidForm() {
		await this.validate();
		return ! Object.keys( this.state.errors ).length;
	}

	validate() {
		const { values } = this.state;
		const errors = this.props.validate( values );
		this.setState( { errors } );
	}

	setValue( name, value ) {
		this.setState( prevState => ( {
			values: { ...prevState.values, [ name ]: value },
		} ), this.validate );
	}

	setTouched( name, touched = true ) {
		this.setState( prevState => ( {
			touched: { ...prevState.touched, [ name ]: touched },
		} ) );
	}

	handleChange( name, value ) {
		const { values } = this.state;

		// Handle native events.
		if ( value.target ) {
			if ( 'checkbox' === value.target.type ) {
				this.setValue( name, ! values[ name ] );
			} else {
				this.setValue( name, value.target.value );
			}
		} else {
			this.setValue( name, value );
		}
	}

	handleBlur( name ) {
		this.setTouched( name );
	}

	async handleSubmit() {
		const { values } = this.state;
		const touched = {};
		Object.keys( values ).map( name => touched[ name ] = true );
		this.setState( { touched } );

		if ( await this.isValidForm() ) {
			this.props.onSubmitCallback( values );
		}
	}

	getInputProps( name ) {
		const { errors, touched, values } = this.state;

		return {
			value: values[ name ],
			checked: Boolean( values[ name ] ),
			selected: values[ name ],
			onChange: ( value ) => this.handleChange( name, value ),
			onBlur: () => this.handleBlur( name ),
			className: touched[ name ] && errors[ name ] ? 'has-error' : null,
			help: touched[ name ] ? errors[ name ] : null,
		};
	}

	getStateAndHelpers() {
		const { values, errors, touched } = this.state;

		return {
			values,
			errors,
			touched,
			setTouched: this.setTouched,
			setValue: this.setValue,
			handleSubmit: this.handleSubmit,
			getInputProps: this.getInputProps,
		};
	}

	render() {
		const element = this.props.children( this.getStateAndHelpers() );
		return cloneElement( element );
	}
}

Form.propTypes = {
	/**
	 * A renderable component in which to pass this component's state and helpers.
	 * Generally a number of input or other form elements.
	 */
	children: PropTypes.any,
	/**
	 * Object of all initial errors to store in state.
	 */
	errors: PropTypes.object,
	/**
	 * Object key:value pair list of all initial field values.
	 */
	initialValues: PropTypes.object.isRequired,
	/**
	 * Function to call when a form is submitted with valid fields.
	 */
	onSubmitCallback: PropTypes.func,
	/**
	 * A function that is passed a list of all values and
	 * should return an `errors` object with error response.
	 */
	validate: PropTypes.func,
};

Form.defaultProps = {
	errors: {},
	initialValues: {},
	onSubmitCallback: noop,
	touched: {},
	validate: noop,
};

export default Form;
