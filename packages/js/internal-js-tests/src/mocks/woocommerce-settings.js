module.exports = {
	getSetting: ( key, backup ) => {
		return global.wcSettings[ key ] || backup;
	},
	getAdminLink: ( path ) => {
		if ( global.wcSettings && global.wcSettings.adminUrl ) {
			return global.wcSettings.adminUrl + path;
		}
		return path;
	},
	isWpVersion: ( version, operator ) => {
		if ( global.wcSettings.wpVersion ) {
			const wpVersion = parseFloat( global.wcSettings.wpVersion );
			version = parseFloat( version );
			switch ( operator ) {
				case '<':
					return wpVersion < version;
				case '<=':
					return wpVersion <= version;
				case '>':
					return wpVersion > version;
				case '>=':
					return wpVersion >= version;
				case '==':
					return wpVersion === version;
				case '!=':
					return wpVersion !== version;
			}
		}
		return false;
	},
};
