<div class="lp-page wp-core-ui">

    <div id="message" style="display:none;">
        <p></p>
    </div>

    <div class="tabs-area">
        <ul class="tabs getstarted">
            <li class="current"><a href="#"><?php _e('Get Started', 'laterpay'); ?></a></li>
            <li><a href="#"><?php _e('Pricing', 'laterpay'); ?></a></li>
            <li><a href="#"><?php _e('Appearance', 'laterpay'); ?></a></li>
            <li><a href="#"><?php _e('Account', 'laterpay'); ?></a></li>
        </ul>
    </div>

    <div class="steps-progress">
        <span class="progress-line">
            <span class="st-1 done"></span>
            <span class="st-2 done"></span>
            <span class="st-3 todo"></span>
        </span>
    </div>

    <div class="lp-wrap">
        <form id="get_started_form" method="post">
            <input type="hidden" name="form"    value="get_started_form">
            <input type="hidden" name="action"  value="getstarted">
            <?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('laterpay_form'); ?>
            <ul class="step-row clearfix">
                <li>
                    <div class="progress-step first">
                        <span class="input-icon merchant-id-icon" data-icon="i"></span>
                        <input type="text"
                                maxlength="22"
                                name="get_started[laterpay_sandbox_merchant_id]"
                                class="lp-input merchant-id-input"
                                value="<?php echo LATERPAY_DEFAULT_SANDBOX_MERCHANT_ID ?>"
                                placeholder="<?php _e('Paste Sandbox Merchant ID here', 'laterpay'); ?>">
                        <br>
                        <span class="input-icon api-key-icon" data-icon="j"></span>
                        <input type="text"
                                maxlength="32"
                                name="get_started[laterpay_sandbox_api_key]"
                                value="<?php echo LATERPAY_DEFAULT_SANDBOX_API_KEY ?>"
                                class="lp-input api-key-input"
                                placeholder="<?php _e('Paste Sandbox API Key here', 'laterpay'); ?>">
                    </div>
                    <p>
                        <?php _e('You can try the plugin immediately<br> with the provided Sandbox API credentials.', 'laterpay'); ?>
                    </p>
                    <p>
                        <?php _e('To actually sell content, you first have to register with LaterPay as a merchant and request your Live API credentials at <a href="https://merchant.laterpay.net" target="blank">merchant.laterpay.net</a>.', 'laterpay'); ?>
                    </p>
                </li>
                <li>
                    <div class="progress-step">
                        <p class="centered">
                            <?php _e('The default price for posts is', 'laterpay'); ?>
                            <input type="text"
                                    name="get_started[laterpay_global_price]"
                                    id="global-default-price"
                                    class="lp-input number"
                                    value="<?php echo $global_default_price; ?>"
                                    placeholder="<?php _e('0.00' ,'laterpay'); ?>">
                            <select name="get_started[laterpay_currency]" class="lp-input">
                                <?php foreach ($Currencies->getCurrencies() as $item): ?>
                                    <option value="<?php echo $item->short_name; ?>"<?php if ( $item->short_name == LATERPAY_CURRENCY_DEFAULT ): ?> selected<?php endif; ?>>
                                        <?php echo $item->short_name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                    </div>
                    <p>
                        <?php _e('Set a <strong>default price</strong> for all posts (0 makes everything free).<br>You can set more advanced prices later.', 'laterpay'); ?>
                    </p>
                </li>
                <li>
                    <div class="progress-step last">
                        <a href="#" class="button button-primary activate-lp" data-error="<?php _e('Please enter your LaterPay API key to activate LaterPay on this site.', 'laterpay'); ?>">
                            <?php _e('Activate LaterPay Test Mode', 'laterpay'); ?>
                        </a>
                    </div>
                    <p>
                        <?php _e('In Test Mode, LaterPay is not visible for regular visitors, but only for admins. Payments are only simulated and not actually booked.', 'laterpay'); ?>
                    </p>
                    <p>
                        <?php _e('Activate the plugin and go to the “Add Post” page,<br>where you can check out your new options.', 'laterpay'); ?>
                    </p>
                </li>
            </ul>
        </form>
    </div>

</div>
