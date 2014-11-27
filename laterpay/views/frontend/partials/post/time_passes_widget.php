<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<div class="lp_timePassWidget <?php echo $laterpay['time_pass_widget_class']; ?>">
    <?php foreach ( $laterpay['passes_list'] as $pass ): ?>
        <?php echo $this->render_pass( (array) $pass ); ?>
    <?php endforeach; ?>
    <div class="lp_timePassWidget_voucherCodeWrapper lp_u_clearfix">
        <input type="text"
                name="time_pass_voucher_code"
                class="lp_timePassWidget_voucherCode"
                maxlength="6">
        <p class="lp_timePassWidget_voucherCodeInputHint"><?php _e( 'Code', 'laterpay' ); ?></p>
        <a href="#" class="lp_timePassWidget_redeemVoucherCode lp_button"><?php _e( 'Redeem', 'laterpay' ); ?></a>
        <p class="lp_timePassWidget_voucherCodeHint"><?php _e( 'Redeem Voucher >', 'laterpay' ); ?></p>
    </div>
</div>
