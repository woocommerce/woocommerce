/**
 * Get the full path from the current path and relative pointer as an array of keys.
 *
 * @param currentPath The current path as a JSON pointer.
 * @param relativePointer The relative path as a JSON pointer with an integer prefix.
 * @returns array
 */
export function getFullPath( currentPath: string, relativePointer: string ) {
    // Remove the initial `/` before the path and split.
    const pathParts = currentPath.slice( 1 ).split('/');
    const refParts = relativePointer.split('/');
    // Pop off the first number from the pointer.
    const integerPrefix = refParts.shift();

    for ( let i = 0; i < Number( integerPrefix ); i++ ) {
        pathParts.pop();
    }

    return [ ...pathParts, ...refParts ];
}