/**
 * Common functions that might get reused across the site
 */

/**
 * Returns the last set csrf token, which is always set on a response.
 * @returns string
 */
export const csrf = () => {
    return document.querySelector('meta[name="csrf-token"]').content
};

/**
 * Returns dbref of the active character as an integer or 0 if not set
 * @returns number
 */
export const characterDbref = () => {
    const characterDbref = document.querySelector('meta[name="character-dbref"]')?.content;
    return characterDbref ? parseInt(characterDbref) : 0;
};

/**
 * Returns the name of the active character or a blank string if not set
 * @returns string
 */
export const characterName = () => {
    const characterName = document.querySelector('meta[name="character-name"]')?.content;
    return characterName ? characterName : '';
};
