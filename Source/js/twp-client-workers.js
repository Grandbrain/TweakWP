/**
 * Handles service workers registration.
 */
;(function () {
    if (typeof jQuery === 'undefined') return;
    jQuery(window).load(function () {
        if ('serviceWorker' in navigator && typeof twp_translations !== 'undefined'
            && 'urls' in twp_translations && 'scopes' in twp_translations) {
            var urls = twp_translations.urls.split('|');
            var scopes = twp_translations.scopes.split('|');
            if (urls.length === scopes.length) {
                for (var i = 0; i < urls.length; ++i) {
                    var url = urls[i].trim(), scope = scopes[i].trim();
                    if (!url) continue;
                    navigator.serviceWorker.register(url, scope ? {scope: scope} : null)
                        .then(function () {
                        })
                        .catch(function () {
                        });
                }
            }
        }
    });
})();