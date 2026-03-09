/**
 * Payhub Inline Checkout JS
 */
const PayhubPop = {
    setup: function(options) {
        return {
            openIframe: function() {
                console.log("Opening Payhub Checkout for", options.email);
                // In a real scenario, this would load the checkout.php in an iframe
                const checkoutUrl = 'checkout.php?amount=' + (options.amount / 100) + '&email=' + options.email + '&ref=' + options.ref;
                window.location.href = checkoutUrl;
            }
        };
    }
};
