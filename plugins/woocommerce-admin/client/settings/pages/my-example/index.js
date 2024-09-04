/**
 * Internal dependencies
 */
import { useSettingsLocation } from '../../routes';

const MyExample = () => {
	const { section } = useSettingsLocation();
	return <div>My Example: { section }</div>;
};

export default MyExample;
