<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * User Account
 */
class SQ_Controllers_Account extends SQ_Classes_FrontController {

	/**
	 *
	 *
	 * @var object Checkin process
	 */
	public $checkin;

	public function action() {
		switch ( SQ_Classes_Helpers_Tools::getValue( 'action' ) ) {
			case 'sq_account_disconnect':

				if ( ! SQ_Classes_Helpers_Tools::userCan( 'sq_manage_settings' ) ) {
					return;
				}

				SQ_Classes_Helpers_Tools::saveOptions( 'sq_api', false );
				SQ_Classes_Helpers_Tools::saveOptions( 'sq_cloud_connect', false );
				SQ_Classes_Helpers_Tools::saveOptions( 'sq_cloud_token', false );

				break;
			case 'sq_account_reconnect':

				if ( ! SQ_Classes_Helpers_Tools::userCan( 'sq_manage_settings' ) ) {
					return;
				}

				//Recover a cloned/staged/moved site: drop the stale signed-auth keys and back-off
				//transients, then re-run the handshake so the Cloud can bind this install (creating a
				//fresh site row for a new/dev URL, or confirming an existing one). Keep sq_api for now
				//so the user isn't forced into a full re-login unless the handshake proves impossible.
				//The whole network sequence runs inside an output buffer so SQ_DEBUG dumps or a checkin
				//notice can't emit output here (we are on admin_menu, before the redirect) - stray
				//output would send headers early and make wp_safe_redirect() silently fail, leaving the
				//user staring at the same notice.
				ob_start();

				SQ_Classes_Helpers_SiteAuth::clearSiteKey();
				delete_transient( 'sq_checkin' );
				delete_transient( 'sq_checkin_down' );
				delete_transient( 'sq_checkin_clone' );
				delete_transient( 'sq_checkin_fallback' );
				delete_transient( 'sq_handshake_attempted' );

				SQ_Classes_Helpers_SiteAuth::ensureSiteKey();
				SQ_Classes_RemoteController::connect();

				if ( $token = SQ_Classes_RemoteController::getCloudToken() ) {
					if ( isset( $token->token ) && $token->token <> '' ) {
						SQ_Classes_Helpers_Tools::saveOptions( 'sq_cloud_token', $token->token );
						SQ_Classes_Helpers_Tools::saveOptions( 'sq_cloud_connect', 1 );
					}
				}

				//Verify the handshake actually took. A clone of a still-active site can't rebind - the
				//Cloud keeps the original's identity - so if checkin still reports the clone state,
				//escalate to a full disconnect: that drops the user onto the Connect screen, where
				//logging in establishes a legitimate fresh connection for this URL.
				delete_transient( 'sq_checkin' );
				delete_transient( 'sq_checkin_clone' );
				$verify = SQ_Classes_RemoteController::checkin();

				ob_end_clean();

				if ( is_wp_error( $verify ) && $verify->get_error_message() == 'reconnect' ) {
					SQ_Classes_Helpers_Tools::saveOptions( 'sq_api', false );
					SQ_Classes_Helpers_Tools::saveOptions( 'sq_cloud_connect', false );
					SQ_Classes_Helpers_Tools::saveOptions( 'sq_cloud_token', false );
					delete_transient( 'sq_checkin_clone' );
					SQ_Classes_Error::saveMessage( esc_html__( "This copy of the website couldn't be re-verified automatically because it looks like a clone of an active site. Please connect it to Squirrly Cloud again below to use it as its own site.", 'squirrly-seo' ), 'error' );
				}

				//Redirect to a clean dashboard URL so the result shows on a fresh request (no stale
				//action params, no half-rendered pre-handshake state) and a refresh won't re-run it.
				wp_safe_redirect( admin_url( 'admin.php?page=sq_dashboard' ) );
				exit();
			case 'sq_ajax_account_getaccount':

				SQ_Classes_Helpers_Tools::setHeader( 'json' );

				if ( ! SQ_Classes_Helpers_Tools::userCan( 'sq_manage_settings' ) ) {
					$response['error'] = SQ_Classes_Error::showNotices( esc_html__( "You do not have permission to perform this action", 'squirrly-seo' ), 'error' );
					echo wp_json_encode( $response );
					exit();
				}

				$json = array();

				$this->checkin = SQ_Classes_RemoteController::checkin();

				if ( ! is_wp_error( $this->checkin ) ) {

					$json['html'] = $this->get_view( 'Blocks/Account' );

					if ( SQ_Classes_Error::isError() ) {
						$json['error'] = SQ_Classes_Error::getError();
					}

				} else {
					//Surface the real reason so the issue is diagnosable, and always render a fallback view
					//containing the disconnect link so the user can never get locked out of the account page.
					$json['error'] = $this->checkin->get_error_message();
					$json['html']  = $this->get_view( 'Blocks/Account' );
				}
				echo wp_json_encode( $json );
				exit();
		}
	}
}
