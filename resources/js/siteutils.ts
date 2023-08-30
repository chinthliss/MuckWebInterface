/**
 * Common functions that might get reused across the site
 */

/**
 * Returns the last set csrf token, which is always set on a response.
 */
export const csrf = (): string | null => {
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content
};

/**
 * Returns the present account ID, if logged in, otherwise null.
 */
export const accountId = (): number | null => {
    let id: string = (document.querySelector('meta[name="account-id"]') as HTMLMetaElement)?.content;
    return id ? parseInt(id) : null;
};

/**
 * Returns dbref of the active character as an integer or null if not set
 */
export const characterDbref = (): number | null => {
    const characterDbref = (document.querySelector('meta[name="character-dbref"]') as HTMLMetaElement)?.content;
    return characterDbref ? parseInt(characterDbref) : null;
};

/**
 * Returns the name of the active character or null if not set
 */
export const characterName = (): string | null => {
    const characterName = (document.querySelector('meta[name="character-name"]') as HTMLMetaElement)?.content;
    return characterName ? characterName : null;
};

/**
 * Returns the game-specific lookup for certain words.
 */
export const lex = (word: string): string => {
    return mwiSiteLexicon[word.toLowerCase()] || word;
};
