/**
 * Internal dependencies
 */
import config from './docusaurus.deploy.config';

const faster = {
	...config,

	future: {
		experimental_faster: true,
	},
};

export default faster;
