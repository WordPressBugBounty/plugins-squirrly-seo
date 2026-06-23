<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
if ( ! isset( $view ) ) {
	return;
}

/**
 * Connection Diagnostics block
 *
 * Read-only snapshot of the Squirrly Cloud connection / signed-auth state, to debug why a
 * cloned/staged/moved site can't reconnect. Click "Test Cloud connection" to also fire a live
 * checkin and see the raw Cloud response (signature_required / clone_detected / ...).
 */

if ( ! SQ_Classes_Helpers_Tools::userCan( 'sq_manage_settings' ) ) {
	return;
}

$run_live = ( SQ_Classes_Helpers_Tools::getValue( 'sq_run_diag' ) == '1' );
$debug    = SQ_Classes_RemoteController::getConnectionDebug( $run_live );

$reconnect_url = wp_nonce_url(
	admin_url( 'admin.php?page=sq_dashboard&action=sq_account_reconnect' ),
	'sq_account_reconnect',
	'sq_nonce'
);
$test_url = add_query_arg( array( 'sq_run_diag' => '1' ) ) . '#sq_diagnostics';
?>
<div id="sq_diagnostics" class="col-12 m-0 p-0 my-5">
    <h3 class="mt-4 card-title">
		<?php echo esc_html__( "Cloud Connection Diagnostics", 'squirrly-seo' ); ?>
    </h3>
    <div class="col-12 small m-0 p-0 mb-3 text-black-50">
		<?php echo esc_html__( "Use this to debug why a cloned, staged or moved site can't reconnect to Squirrly Cloud. Secrets are masked.", 'squirrly-seo' ); ?>
    </div>

    <table class="table table-sm table-bordered bg-white" style="max-width: 900px;">
        <tbody>
		<?php foreach ( $debug as $key => $value ) {
			if ( $key === 'live_checkin_response' ) {
				continue;
			}
			$is_warn = in_array( $key, array( 'user_can_manage_settings', 'request_will_be_signed', 'site_key_present' ), true )
			           && ( stripos( (string) $value, 'NO' ) === 0 || stripos( (string) $value, 'NO ' ) !== false );
			$is_flag = ( $key === 'url_changed_since_connect' && stripos( (string) $value, 'YES' ) !== false )
			           || ( $key === 'site_key_source' && stripos( (string) $value, 'constant' ) !== false )
			           || ( $key === 'live_checkin_error' && ! in_array( $value, array( '(none - connected)', '(no data)' ), true ) );
			?>
            <tr<?php echo ( $is_warn || $is_flag ) ? ' class="table-danger"' : ''; ?>>
                <td class="font-weight-bold" style="width: 320px;"><?php echo esc_html( $key ); ?></td>
                <td><code><?php echo esc_html( (string) $value ); ?></code></td>
            </tr>
		<?php } ?>
        </tbody>
    </table>

	<?php if ( $run_live && isset( $debug['live_checkin_response'] ) ) { ?>
        <div class="col-12 m-0 p-0 my-2">
            <div class="font-weight-bold small"><?php echo esc_html__( "Raw live checkin response:", 'squirrly-seo' ); ?></div>
            <pre class="bg-light border p-2" style="max-width: 900px; white-space: pre-wrap; word-break: break-all;"><?php echo esc_html( (string) $debug['live_checkin_response'] ); ?></pre>
        </div>
	<?php } ?>

    <div class="col-12 m-0 p-0 my-3">
        <a href="<?php echo esc_url( $test_url ); ?>" class="btn btn-light border px-3 m-0 mr-2 noloading"><?php echo esc_html__( "Test Cloud connection now", 'squirrly-seo' ); ?></a>
        <a href="<?php echo esc_url( $reconnect_url ); ?>" class="btn btn-primary px-3 m-0 noloading"><?php echo esc_html__( "Reconnect this site", 'squirrly-seo' ); ?></a>
    </div>

    <div class="col-12 small m-0 p-0 mt-3" style="max-width: 900px;">
        <div class="font-weight-bold"><?php echo esc_html__( "How to read this:", 'squirrly-seo' ); ?></div>
        <ul class="mx-3 mt-1">
            <li><?php echo esc_html__( "user_can_manage_settings = NO -> the current user can't run the reconnect; log in as a full admin.", 'squirrly-seo' ); ?></li>
            <li><?php echo esc_html__( "site_key_source = wp-config constant -> SQUIRRLY_SITE_KEY is hard-coded; reconnect can't mint a fresh key, so remove that constant on this copy.", 'squirrly-seo' ); ?></li>
            <li><?php echo esc_html__( "live_checkin_error = signature_required / clone_detected / invalid_signature -> the Cloud rejects this copy's identity; reconnect, or disconnect and connect it as its own site.", 'squirrly-seo' ); ?></li>
        </ul>
    </div>
</div>
