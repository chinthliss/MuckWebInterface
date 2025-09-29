/**
 * Frequently used formatting functions
 */

import {AnsiUp} from "ansi_up";

const ansi_up = new AnsiUp();

/**
 * Takes a string representation of a Carbon object exported from PHP and turns it into something friendlier
 * Type is to support DataTables - if it's for sorting, the conversion to a string isn't done.
 */
export const carbonToString = (carbonString: string | null | undefined, type?: string): string => {
    if (!carbonString) return '--';
    if (type === 'sort') return carbonString;
    return new Date(carbonString).toLocaleString();
};

/**
 * Takes a UTC timestamp exported from the muck and turns it into something friendlier
 */
export const timestampToString = (timestamp: number | null | undefined): string => {
    if (!timestamp) return '--';
    return new Date(timestamp * 1000).toLocaleString();
};

/**
 * Version of timestampToString that won't interfere with DataTables sorting
 */
export const datatablesTimestampToString = (timestamp: number | null | undefined, type: string): string | number => {
    if (type == 'sort') return timestamp || 0;
    return timestampToString(timestamp);
}

/**
 * Takes a USD value and turns it into something friendlier
 */
export const usdToString = (usd: number | string | null): string => {
    if (!usd) return '--';
    if (typeof usd === 'number') usd = usd.toFixed(2);
    return "$" + usd;
};

/**
 * Converts the first letter of a string to be a capital
 */
export const capital = (text: string | null | undefined): string => {
    if (!text) return '';
    return text.substring(0, 1).toUpperCase() + text.slice(1);
};

export const capitalOnEveryWord = (text: string | null | undefined): string => {
    if (!text) return '';
    return text
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

/**
 * Joins an array of strings into one string
 */
export const arrayToList = (arrayToParse: string[], emptyWord: string = '', joinString: string = ', '): string => {
    if (!arrayToParse?.length) return emptyWord;
    return arrayToParse.join(joinString);
};

/**
 * Joins an array of strings into one string with newlines
 */
export const arrayToStringWithNewlines = (arrayToParse: string[], emptyWord: string = ''): string => {
    return arrayToList(arrayToParse, emptyWord, '\n');
};

/**
 * Joins an array of strings into one string with breaks
 */
export const arrayToStringWithBreaks = (arrayToParse: string[], emptyWord: string = ''): string => {
    return arrayToList(arrayToParse, emptyWord, '<br\>');
};

/**
 * Replaces key parts of HTML so that it can be used without being parsed
 */
export const escapeHTML = (text: string): string => {
    return text.replace(
        /[&<>'"]/g,
        found =>
            ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[found] || found)
    );
}

/**
 * Converts the parsed ANSI in a string into an HTML representation
 * Note that this will escape special characters so should not be used with escapeHTML
 */
export const ansiToHtml = (ansi: string): string => {
    return ansi_up.ansi_to_html(ansi);
}

export const rankedSalvageListToHtml = (list: { [grade: string]: number }): string => {
    let result = '';
    // Not great having this hard coded, but don't want to be constantly pulling it form the game
    const order = ['elite', 'rare', 'uncommon', 'common'];
    for (const rank in order) {
        if (rank in list) {
            result += `<span class="badge rounded-pill text-dark bg-salvage-${rank}-rank me-1">${rank} x ${list[rank]}</span>`;
        }
    }
    // Look for any extras because of weirdness
    for (const rank in list) {
        if (!(rank in order)) {
            result += `<span class="badge rounded-pill text-dark bg-salvage-${rank}-rank me-1">${rank} x ${list[rank]}</span>`;

        }
    }
    return result;
};
