/**
 * Frequently used formatting functions
 */

export const carbonToString = (carbonString) => {
    if (!carbonString) return '--';
    return new Date(carbonString).toLocaleString();
};

export const usdToString = (usd) => {
    if (!usd) return '--';
    if (typeof usd === 'number') usd = Math.round(usd, 2);
    return "$" + usd;
};

export const capital = (text) => {
    if (typeof (text) !== 'string') text = text.toString();
    if (!text) return '';
    return text && text.substring(0, 1).toUpperCase() + text.slice(1);
};

export const arrayToList = (arrayToParse, emptyWord) => {
    if (!arrayToParse.length) return emptyWord || '';
    return arrayToParse.join(', ');
};
