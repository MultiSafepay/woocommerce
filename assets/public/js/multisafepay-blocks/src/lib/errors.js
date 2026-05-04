let multisafepayBlocksErrorLogged = false;

/**
 * Truncates a string to keep logs and UI messages compact.
 *
 * @param {string} value
 * @param {number} [maxLen=180]
 * @returns {string}
 */
export function multisafepayBlocksTruncateString( value, maxLen = 180 ) {
    if ( typeof value !== 'string' ) {
        return value;
    }
    if ( value.length <= maxLen ) {
        return value;
    }
    return value.slice( 0, maxLen ) + '…(' + ( value.length - maxLen ) + ' more)';
}

/**
 * Produces a compact, human-readable description from unknown error shapes.
 *
 * @param {unknown} error
 * @returns {string}
 */
export function multisafepayBlocksSummarizeError( error ) {
    if ( ! error ) {
        return '';
    }
    try {
        if ( typeof error === 'string' ) {
            return multisafepayBlocksTruncateString( error );
        }
        if ( error && typeof error.message === 'string' ) {
            return multisafepayBlocksTruncateString( error.message );
        }
        if ( error && typeof error.statusCode !== 'undefined' ) {
            return 'statusCode=' + String( error.statusCode );
        }
    } catch ( e ) {
        // no-op
    }
    return 'Unknown error';
}

/**
 * Logs an error only once per page lifetime (when debug is enabled) to avoid console spam.
 *
 * @param {boolean} debugEnabled
 * @param {string} message
 * @param {unknown} error
 * @returns {void}
 */
export function multisafepayBlocksLogErrorOnce( debugEnabled, message, error ) {
    if ( ! debugEnabled ) {
        return;
    }
    if ( multisafepayBlocksErrorLogged ) {
        return;
    }
    multisafepayBlocksErrorLogged = true;

    try {
        // eslint-disable-next-line no-console
        console.error( message, error || '' );
    } catch ( e ) {
        // no-op
    }
}
