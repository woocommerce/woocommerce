/**
 * Get a property given an object and an array of keys.
 * @param obj The object to search within.
 * @param pathKeys The array of keys used to find the nested property.
 * @returns unknown
 */
export function getPropertyByPath( obj: any, pathKeys: string[] ) {
    return pathKeys.reduce(
        ( acc, key ) => {
            if ( typeof acc === 'object' ) {
                return acc?.[ key ] ?? undefined
            }
            return undefined;
        }, 
        obj
    );
};