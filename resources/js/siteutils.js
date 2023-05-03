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
 * Returns the present account ID, if logged in, otherwise null.
 * @returns {number|null}
 */
export const accountId = () => {
    let id = document.querySelector('meta[name="account-id"]').content;
    return id ? parseInt(id) : null;
};

/**
 * Returns dbref of the active character as an integer or null if not set
 * @returns {number|null}
 */
export const characterDbref = () => {
    const characterDbref = document.querySelector('meta[name="character-dbref"]')?.content;
    return characterDbref ? parseInt(characterDbref) : null;
};

/**
 * Returns the name of the active character or null if not set
 * @returns {string|null}
 */
export const characterName = () => {
    const characterName = document.querySelector('meta[name="character-name"]')?.content;
    return characterName ? characterName : null;
};

/**
 * Returns the game-specific lookup for certain words.
 * @param word
 * @returns {string}
 */
export const lex = (word) => {
    return mwiSiteLexicon[word.toLowerCase()] || word;
};
