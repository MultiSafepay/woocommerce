/**
 * Collects a stable subset of browser capabilities for server-side fraud/risk checks.
 *
 * This mirrors the data shape expected by the MultiSafepay API.
 *
 * @package MultiSafepay
 * @returns {string} JSON string.
 */

export function getCustomerBrowserInfo() {
    const nav          = window.navigator;
    let javaEnabled    = false;
    let platform       = '';
    let cookiesEnabled = false;
    let language       = '';
    let userAgent      = '';

    try {
        javaEnabled = nav.javaEnabled() || false;
    } catch ( error ) {
        // no-op
    }
    try {
        platform = nav.platform || '';
    } catch ( error ) {
        // no-op
    }
    try {
        cookiesEnabled = ! ! nav.cookieEnabled || false;
    } catch ( error ) {
        // no-op
    }
    try {
        language = nav.language || '';
    } catch ( error ) {
        // no-op
    }
    try {
        userAgent = nav.userAgent || '';
    } catch ( error ) {
        // no-op
    }

    const info = {
        browser: {
            javascript_enabled: true,
            java_enabled: javaEnabled,
            cookies_enabled: cookiesEnabled,
            language: language,
            screen_color_depth: window.screen.colorDepth,
            screen_height: window.screen.height,
            screen_width: window.screen.width,
            time_zone: new Date().getTimezoneOffset(),
            user_agent: userAgent,
            platform: platform,
        },
    };

    return JSON.stringify( info );
}
