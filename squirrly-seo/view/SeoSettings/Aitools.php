<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
if ( ! isset( $view ) ) {
	return;
}

/**
 * AI Tools view
 *
 * Turns on the OAuth sign-in layer, so Squirrly can be added to claude.ai as a standard
 * connector instead of only through a local MCP client.
 *
 * Called from Settings Controller
 */

$sq_mcp_blocker = SQ_Classes_McpOauthController::getBlocker();
$sq_mcp_active  = SQ_Classes_McpOauthController::isActive();
?>
<div id="sq_wrap">
	<?php $view->show_view( 'Blocks/Toolbar' ); ?>
	<?php do_action( 'sq_notices' ); ?>

    <div id="sq_content" class="d-flex flex-row bg-white my-0 p-0 m-0">
		<?php
		if ( ! SQ_Classes_Helpers_Tools::userCan( 'sq_manage_settings' ) ) {
			echo '<div class="col-12 alert alert-success text-center m-0 p-3">' . esc_html__( "You do not have permission to access this page. You need Squirrly SEO Admin role", 'squirrly-seo' ) . '</div>';

			return;
		}
		?>
		<?php $view->show_view( 'Blocks/Menu' ); ?>
        <div class="d-flex flex-row flex-nowrap flex-grow-1 bg-light m-0 p-0">
            <div class="flex-grow-1 sq_flex m-0 py-0 px-4">
				<?php do_action( 'sq_form_notices' ); ?>

                <div class="col-12 p-0 m-0">

                    <div class="sq_breadcrumbs my-4"><?php SQ_Classes_ObjController::getClass( 'SQ_Models_Menu' )->showBreadcrumbs( SQ_Classes_Helpers_Tools::getValue( 'page' ) . '/' . SQ_Classes_Helpers_Tools::getValue( 'tab' ) ) ?></div>

                    <h3 class="mt-4 card-title">
                        <?php echo esc_html__( "AI Tools", 'squirrly-seo' ); ?>
                        <div class="sq_help_question d-inline">
                            <a href="https://howto12.squirrly.co/kb/connect-squirrly-to-ai-assistants/" target="_blank"><i class="fa-solid fa-question-circle m-0 p-0"></i></a>
                        </div></h3>

                    <div class="col-7 small m-0 p-0">
						<?php echo esc_html__( "Let an AI assistant read and update your SEO from inside your own site, instead of pasting page content into a chat window and pasting suggestions back.", 'squirrly-seo' ); ?>
                    </div>
                    <div class="col-7 small m-0 p-0 py-2">
						<?php echo esc_html__( "Turning this on adds a sign-in step to your site, so Claude can be added as a connector the same way as your other tools. Your own WordPress login screen approves the connection, and whatever the connected user is allowed to do is exactly what the assistant is allowed to do.", 'squirrly-seo' ); ?>
                    </div>

                    <div class="d-flex flex-row p-0 m-0 mt-4 bg-white">
                        <div class="d-flex flex-column flex-grow-1 m-0 p-0">

                            <div class="col-12 py-0 px-4 m-0">

								<?php if ( $sq_mcp_blocker <> '' ) { ?>
                                    <div class="col-12 alert alert-warning m-0 p-3 my-4">
										<?php echo esc_html( $sq_mcp_blocker ); ?>
                                    </div>
								<?php } ?>

                                <form method="post" class="p-0 m-0">
									<?php SQ_Classes_Helpers_Tools::setNonce( 'sq_seosettings_save', 'sq_nonce' ); ?>
                                    <input type="hidden" name="action" value="sq_seosettings_save"/>

                                    <div class="col-12 row m-0 p-0 my-5">
                                        <div class="checker col-12 row m-0 p-0">
                                            <div class="col-12 m-0 p-0 sq-switch sq-switch-sm">
                                                <input type="hidden" name="sq_mcp_oauth" value="0"/>
                                                <input type="checkbox" id="sq_mcp_oauth" name="sq_mcp_oauth" class="sq-switch" <?php echo( SQ_Classes_Helpers_Tools::getOption( 'sq_mcp_oauth' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                                <label for="sq_mcp_oauth" class="ml-1"><?php echo esc_html__( "Allow AI assistants to connect with a sign-in", 'squirrly-seo' ); ?></label>
                                                <div class="small text-black-50 ml-5"><?php echo esc_html__( "Off by default. Nothing is exposed until you turn this on and approve a connection.", 'squirrly-seo' ); ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 m-0 p-0 mt-4">
                                        <button type="submit" class="btn btn-primary btn-lg m-0 p-0 py-2 px-4 rounded-0"><?php echo esc_html__( "Save Settings", 'squirrly-seo' ); ?></button>
                                    </div>
                                </form>

								<?php if ( $sq_mcp_active ) { ?>

                                    <div class="col-12 m-0 p-0 mt-5">
                                        <h5 class="card-title"><?php echo esc_html__( "Your connector address", 'squirrly-seo' ); ?></h5>
                                        <div class="col-12 small text-black-50 m-0 p-0 py-2">
											<?php echo esc_html__( "Paste this into Add custom connector in your AI tool.", 'squirrly-seo' ); ?>
                                        </div>
                                        <input type="text" class="col-8 m-0 p-2" readonly="readonly" onclick="this.select();" value="<?php echo esc_attr( SQ_Classes_McpOauthController::getServerUrl() ); ?>"/>
                                    </div>

                                    <div class="col-12 m-0 p-0 mt-5 mb-4">
                                        <h5 class="card-title"><?php echo esc_html__( "Can your server be reached?", 'squirrly-seo' ); ?></h5>
                                        <div class="col-12 small text-black-50 m-0 p-0 py-2">
											<?php echo esc_html__( "An AI tool does not ask you for a password. It fetches these two addresses to find out how to sign in. Some web server settings answer them before WordPress does, which leaves everything else working and only shows up as a failed connection.", 'squirrly-seo' ); ?>
                                        </div>

										<?php foreach ( SQ_Classes_McpOauthController::checkDiscovery() as $sq_mcp_label => $sq_mcp_check ) { ?>
                                            <div class="col-12 m-0 p-0 py-2">
                                                <div class="col-12 m-0 p-0">
													<?php if ( $sq_mcp_check['ok'] ) { ?>
                                                        <i class="fa-solid fa-circle-check text-success"></i>
													<?php } else { ?>
                                                        <i class="fa-solid fa-circle-xmark text-danger"></i>
													<?php } ?>
                                                    <span class="ml-2"><?php echo esc_html( home_url( SQ_Classes_McpOauthController::$discovery[ $sq_mcp_label ] ) ); ?></span>
                                                    <span class="small text-black-50 ml-2"><?php echo esc_html( $sq_mcp_check['code'] ? $sq_mcp_check['code'] : '-' ); ?></span>
                                                </div>
												<?php if ( $sq_mcp_check['message'] <> '' ) { ?>
                                                    <div class="col-12 small text-black-50 m-0 p-0 ml-4"><?php echo esc_html( $sq_mcp_check['message'] ); ?></div>
												<?php } ?>
                                            </div>
										<?php } ?>
                                    </div>

								<?php } ?>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
