<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
if ( ! isset( $view ) ) {
	return;
}

/**
 * Reconnect view
 *
 * Shown when Squirrly Cloud can no longer verify this site because it was cloned,
 * staged or moved to a new address. The user keeps their account token but must
 * re-run the signed handshake to bind this install again.
 */
?>
<?php
SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' )->loadMedia( 'bootstrap-reboot' );
SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' )->loadMedia( 'bootstrap' );
SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' )->loadMedia( 'fontawesome' );
SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' )->loadMedia( 'global' );
SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' )->loadMedia( 'navbar' );
?>
<div id="sq_wrap">
	<?php $view->show_view( 'Blocks/Toolbar' ); ?>
	<?php do_action( 'sq_notices' ); ?>
    <div id="sq_content" class="d-flex flex-row bg-white my-0 p-0 m-0">
        <div class="d-flex flex-row flex-nowrap flex-grow-1 bg-light m-0 p-0">
            <div class="flex-grow-1 sq_flex m-0 py-0 px-4">

                <div class="col-12 m-0 p-0">
                    <div class="col-12 px-2 py-3 text-center">
                        <div class="sq_logo sq_logo_50 m-0 p-0 mx-auto"></div>
                    </div>
                    <div id="sq_error" class="card col-12 p-0 tab-panel border-0">
                        <div class="col-8 mx-auto alert alert-warning text-center m-0 p-4">
                            <h4 class="mb-3"><?php echo esc_html__( "This site needs to reconnect to Squirrly Cloud", "squirrly-seo" ); ?></h4>
                            <p class="m-0">
                                <i class="fa-solid fa-exclamation-triangle" style="font-size: 18px !important;"></i>
								<?php echo esc_html__( "It looks like this website was cloned, staged or moved to a new address, so Squirrly Cloud can no longer verify it. Reconnect this site to keep your SEO features, Focus Pages and Live Assistant working.", "squirrly-seo" ); ?>
                            </p>
                            <form method="post" class="p-0 m-0 mt-4">
								<?php SQ_Classes_Helpers_Tools::setNonce( 'sq_account_reconnect', 'sq_nonce' ); ?>
                                <input type="hidden" name="action" value="sq_account_reconnect"/>
                                <button type="submit" class="btn btn-primary px-4">
									<?php echo esc_html__( "Reconnect this site", "squirrly-seo" ); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
