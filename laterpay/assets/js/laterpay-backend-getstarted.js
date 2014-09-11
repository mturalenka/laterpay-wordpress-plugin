jQuery.noConflict();
(function($) { $(function() {

    var throttledFlashMessage;

    function validateAPIKey() {
        var $apiKey     = $('.lp_api-key-input'),
            $merchantID = $('.lp_merchant-id-input'),
            keyValue    = $apiKey.val().trim(),
            idValue     = $merchantID.val().trim();

        // clear flash message timeout
        window.clearTimeout(throttledFlashMessage);

        if (keyValue.length !== $apiKey.val().length) {
            $apiKey.val(keyValue);
        }
        if (idValue.length !== $merchantID.val().length) {
            $merchantID.val(idValue);
        }

        if (keyValue.length === 32 && idValue.length === 22) {
            $('.lp_steps-background .lp_step-1').removeClass('lp_step-todo').addClass('lp_step-done');
            clearMessage();

            return true;
        } else {
            $('.lp_steps-background .lp_step-1').removeClass('lp_step-done').addClass('lp_step-todo');
        }

        if (idValue.length > 0 && idValue.length !== 22) {
            // set timeout to throttle flash message
            throttledFlashMessage = window.setTimeout(function() {
                setMessage(lpVars.i18nInvalidMerchantId, false);
            }, 500);
        }
        if (keyValue.length > 0 && keyValue.length !== 32) {
            // set timeout to throttle flash message
            throttledFlashMessage = window.setTimeout(function() {
                setMessage(lpVars.i18nInvalidApiKey, false);
            }, 500);
        }

        return false;
    }

    function validatePrice(price) {
        var corrected;

        // strip non-number characters
        price = price.replace(/[^0-9\,\.]/g, '');
        // convert price to proper float value
        if (price.indexOf(',') > -1) {
            price = parseFloat(price.replace(',', '.')).toFixed(2);
        } else {
            price = parseFloat(price).toFixed(2);
        }
        // prevent non-number prices
        if (isNaN(price)) {
            price = 0;
            corrected = true;
        }
        // prevent negative prices
        price = Math.abs(price);
        // correct prices outside the allowed range of 0.05 - 5.00
        if (price > 5) {
            price = 5;
            corrected = true;
        } else if (price > 0 && price < 0.05) {
            price = 0.05;
            corrected = true;
        }

        // show flash message when correcting an invalid price
        if (corrected) {
            setMessage(lpVars.i18nOutsideAllowedPriceRange, false);
        }

        return price.toFixed(2);
    }

    $('#lp_global-default-price').blur(function() {
        // validate price
        var $defaultPrice   = $('#lp_global-default-price'),
            defaultPrice    = $defaultPrice.val(),
            validatedPrice  = validatePrice(defaultPrice);
        if (lpVars.locale == 'de_DE') {
            validatedPrice = validatedPrice.replace('.', ',');
        }
        $defaultPrice.val(validatedPrice);
    });

    $('.lp_activate-plugin-button').click(function() {
        if (!validateAPIKey()) {
            setMessage($(this).data().error, false);
            return;
        }

        // validate price
        var $defaultPrice   = $('#lp_global-default-price'),
            defaultPrice    = $defaultPrice.val();
        // convert price to proper float value
        if (defaultPrice.indexOf(',') > -1) {
            defaultPrice = parseFloat(defaultPrice.replace(',', '.')).toFixed(2);
        } else {
            defaultPrice = parseFloat(defaultPrice).toFixed(2);
        }
        // prevent negative prices
        defaultPrice = Math.abs(defaultPrice);
        // correct prices outside the allowed range of 0.05 - 5.00
        if (defaultPrice > 5) {
            $defaultPrice.val(5);
        } else if (defaultPrice > 0 && defaultPrice < 0.05) {
            $defaultPrice.val(0.05);
        }

        $('.lp_steps-background .lp_step-todo').removeClass('lp_step-todo').addClass('lp_step-done');

        $.post(
            ajaxurl,
            $('#lp_get-started-form').serializeArray(),
            function(data) {
                window.location = 'post-new.php';
            }
        );

        return false;
    });

    $('.lp_api-key-input, .lp_merchant-id-input').bind('input', function() {
        validateAPIKey();
    });

    // hide pointer while viewing the getStarted tab
    $(document).ready(function() {
        if (typeof($().pointer) !== 'undefined' && $('#toplevel_page_laterpay-laterpay-admin').data('wpPointer')) {
            $('#toplevel_page_laterpay-laterpay-admin').data('wpPointer').pointer.hide();
        }
    });

    // disable tabs
    $('.lp_get-started-page .lp_nav-tabs li a')
    .mousedown(function() {
        alert(lpVars.i18nTabsDisabled);

        return false;
    });

});})(jQuery);