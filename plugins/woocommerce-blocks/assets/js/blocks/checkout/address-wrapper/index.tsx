/**
 * External dependencies
 */
import { CSSTransition, TransitionGroup } from 'react-transition-group';
/**
 * Internal dependencies
 */
import './style.scss';

export const AddressWrapper = ( {
	isEditing = false,
	addressCard,
	addressForm,
}: {
	isEditing: boolean;
	addressCard: () => JSX.Element;
	addressForm: () => JSX.Element;
} ): JSX.Element | null => {
	return (
		<TransitionGroup className="address-fade-transition-wrapper">
			{ ! isEditing && (
				<CSSTransition
					timeout={ 300 }
					classNames="address-fade-transition"
				>
					{ addressCard() }
				</CSSTransition>
			) }
			{ isEditing && (
				<CSSTransition
					timeout={ 300 }
					classNames="address-fade-transition"
				>
					{ addressForm() }
				</CSSTransition>
			) }
		</TransitionGroup>
	);
};

export default AddressWrapper;
