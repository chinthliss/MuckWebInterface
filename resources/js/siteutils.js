/**
 * Common functions that might get reused across the site
 */

export const csrf = () => {
    return document.querySelector('meta[name="csrf-token"]').content
};
