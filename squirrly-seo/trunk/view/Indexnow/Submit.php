<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
if ( ! isset( $view ) ) {
	return;
}

/**
 * Index Now Submit view
 *
 * Called from Index Now Controller
 */
?>
<div id="sq_wrap">
	<?php $view->show_view( 'Blocks/Toolbar' ); ?>
	<?php do_action( 'sq_notices' ); ?>

    <div id="sq_content" class="d-flex flex-row bg-white my-0 p-0 m-0">
		<?php
		if ( ! SQ_Classes_Helpers_Tools::userCan( 'sq_manage_snippet' ) ) {
			echo '<div class="col-12 alert alert-success text-center m-0 p-3">' . esc_html__( "You do not have permission to access this page. You need Squirrly SEO Admin role.", "squirrly-seo" ) . '</div>';

			return;
		}
		?>
		<?php $view->show_view( 'Blocks/Menu' ); ?>
        <div class="d-flex flex-row flex-nowrap flex-grow-1 bg-light m-0 p-0">
            <div class="flex-grow-1 sq_flex m-0 py-0 px-4 pb-4">
				<?php do_action( 'sq_form_notices' ); ?>

                <div class="sq_breadcrumbs my-4"><?php SQ_Classes_ObjController::getClass( 'SQ_Models_Menu' )->showBreadcrumbs( SQ_Classes_Helpers_Tools::getValue( 'page' ) . '/' . SQ_Classes_Helpers_Tools::getValue( 'tab', 'submit' ) ) ?></div>

                <div class="col-12 p-0 m-0">
					<?php
					//Verify the key file is publicly reachable - this is the usual cause of 401/403 errors.
					$sq_keycheck = $view->model->verifyKeyFile();
					$sq_key_link = '<a href="' . esc_url( $sq_keycheck['url'] ) . '" target="_blank">' . esc_html( $sq_keycheck['url'] ) . '</a>';
					if ( ! empty( $sq_keycheck['ok'] ) ) {
						?>
                        <div class="col-12 alert alert-success m-0 p-2 px-3 my-3">
                            <i class="fa-solid fa-circle-check"></i>
							<?php echo esc_html__( "Your IndexNow key file is publicly accessible, so search engines can verify your submissions.", "squirrly-seo" ); ?>
                            <a href="<?php echo esc_url( $sq_keycheck['url'] ) ?>" target="_blank" class="ml-1"><?php echo esc_html__( "View key file", "squirrly-seo" ); ?></a>
                        </div>
						<?php
					} elseif ( (int) $sq_keycheck['code'] >= 400 ) {
						?>
                        <div class="col-12 alert alert-danger m-0 p-2 px-3 my-3">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <strong><?php echo esc_html__( "Your IndexNow key file is being blocked.", "squirrly-seo" ); ?></strong>
							<?php echo ' ' . sprintf( esc_html__( "Search engines must read %s to verify your site, but it returns %s. This is the usual cause of 401/403 errors. Check for HTTP authentication, a coming-soon/maintenance mode, a security or firewall plugin, or a CDN bot rule that blocks this URL.", "squirrly-seo" ), $sq_key_link, '<strong>' . esc_html( 'HTTP ' . (int) $sq_keycheck['code'] ) . '</strong>' ); ?>
                        </div>
						<?php
					} elseif ( (int) $sq_keycheck['code'] === 200 ) {
						//Reachable, but not returning the plain key (cache/another handler/HTML page)
						?>
                        <div class="col-12 alert alert-warning m-0 p-2 px-3 my-3">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <strong><?php echo esc_html__( "Your IndexNow key file returns unexpected content.", "squirrly-seo" ); ?></strong>
							<?php echo ' ' . sprintf( esc_html__( "%s should return only your key, but it returns something else (often a page cache or another plugin handling the URL). Search engines may fail to verify it.", "squirrly-seo" ), $sq_key_link ); ?>
                        </div>
						<?php
					} else {
						//Could not connect (often a server loopback restriction) - don't alarm, just inform.
						?>
                        <div class="col-12 alert alert-warning m-0 p-2 px-3 my-3">
                            <i class="fa-solid fa-circle-info"></i>
							<?php echo ' ' . sprintf( esc_html__( "Squirrly could not verify your IndexNow key file from your server (this can happen on hosts that block self-requests). Please open %s in a new tab to confirm it shows your key.", "squirrly-seo" ), $sq_key_link ); ?>
                        </div>
						<?php
					}
					?>
					<?php $metas = json_decode( wp_json_encode( SQ_Classes_Helpers_Tools::getOption( 'sq_metas' ) ) ); ?>
                    <form method="POST">
						<?php SQ_Classes_Helpers_Tools::setNonce( 'sq_seosettings_indexnow_submit' ); ?>
                        <input type="hidden" name="action" value="sq_seosettings_indexnow_submit"/>

                        <h3 class="mt-4 card-title">
							<?php echo esc_html__( "Submit URLs for LLM-Indexing", "squirrly-seo" ); ?>
                            <div class="sq_help_question d-inline">
                                <a href="https://howto12.squirrly.co/kb/indexnow/" target="_blank"><i class="fa-solid fa-question-circle"></i></a>
                            </div>
                        </h3>
                        <div class="col-10 small m-0 p-0">
							<?php echo sprintf( esc_html__( "Boost AI discovery by making sure you reach the Retrieval layer of LLMs like ChatGPT, Claude, Perplexity, Gemini, Google AI Overviews, Google AI Mode. %sClick to read more on our AISQ website%s, to understand why this boosts your AI discoverability. Manually send URLs to the IndexNow API AND the Google Indexing API.", "squirrly-seo" ), '<a href="https://aisq.com/boost-ai-indexing-and-ai-search-engine-discovery/" target="_blank">', '</a>' ); ?>
                        </div>

                        <div class="col-12 p-0 m-0 my-5">

                            <div class="col-12 row m-0 p-0 my-5">
                                <div class="col-4 m-0 p-0 font-weight-bold">
                                    <label for="indexnow_urls"><?php echo esc_html__( "URLs", "squirrly-seo" ); ?>
                                        :</label>
                                    <div class="small text-black-50 my-1 pr-3"><?php echo esc_html__( "Insert the URLs you want to send to LLM Indexing (one per line, up to 10,000)", "squirrly-seo" ); ?></div>
                                </div>
                                <div class="col-8 p-0">
                                    <textarea id="indexnow_urls" class="form-control" name="urls" rows="5" placeholder="<?php echo esc_url( home_url() ) ?>"></textarea>
                                </div>
                            </div>

                            <div class="col-12 m-0 p-0 mt-5">
                                <button type="submit" class="btn btn-primary btn-lg m-0 p-0 py-2 px-4 rounded-0"><?php echo esc_html__( "Submit URLs", "squirrly-seo" ); ?></button>
                            </div>

                        </div>
                    </form>
                </div>

				<?php $log = get_option( 'sq_indexnow_log', [] ); ?>

                <div class="col-12 m-0 p-0">
                    <div class="col-12 m-0 p-0 my-5">
                        <h3 class="py-0 card-title">
							<?php echo esc_html__( "LLM Indexing History (sent to both Google Indexing API and IndexNow)", "squirrly-seo" ); ?>
                        </h3>
                        <div class="col-7 small m-0 p-0">
							<?php echo esc_html__( "Check the log to see how your URLs were submited. Make sure your Google Search Console is connected.", "squirrly-seo" ); ?>
                        </div>
						<?php if ( ! empty( $log ) ) { ?>
                            <div class="col-12 text-right m-0 p-0 my-1">
                                <form method="POST">
									<?php SQ_Classes_Helpers_Tools::setNonce( 'sq_seosettings_indexnow_clear' ); ?>
                                    <input type="hidden" name="action" value="sq_seosettings_indexnow_clear"/>
                                    <button type="submit" class="btn btn-light btn-sm"><?php echo esc_html__( "Clear log", "squirrly-seo" ); ?></button>
                                </form>
                            </div>
                            <table class="table table-striped table-hover">
                                <thead>
                                <tr>
                                    <th style="width: 50%;" scope="col"><?php echo esc_html__( "URL", "squirrly-seo" ) ?></th>
                                    <th style="width: 25%;" scope="col"><?php echo esc_html__( "Message", 'squirrly-seo' ) ?></th>
                                    <th style="width: 25%;" scope="col"><?php echo esc_html__( "Date", 'squirrly-seo' ) ?></th>
                                </tr>
                                </thead>
                                <tbody>
								<?php
								$log = array_slice( $log, - 20 );
								$log = array_reverse( $log );
								foreach ( $log as $row ) {
									//wp_date() already converts the UTC timestamp to the site timezone,
									//so we must NOT add gmt_offset again (that double-applies the offset).
									$timestamp = (int) $row['time'];

									?>
                                    <tr>
                                        <td>
                                            <?php
                                            //One entry can hold several URLs (batch submit), stored newline-separated in the url field.
                                            $row_urls  = array_values( array_filter( explode( "\n", (string) $row['url'] ) ) );
                                            $first_url = array_shift( $row_urls );
                                            ?>
                                            <?php echo esc_html( $first_url ) ?>
                                            <em><?php echo( ! esc_attr( $row['manual'] ) ? '<i title="' . esc_attr__( "Submited automatically", 'squirrly-seo' ) . '" class="dashicons dashicons-cloud-saved m-1"></i>' : '' ) ?></em>
											<?php if ( ! empty( $row_urls ) ) { ?>
                                                <details class="small text-black-50 mt-1">
                                                    <summary style="cursor:pointer"><?php echo esc_html( sprintf( _n( '+%d more URL', '+%d more URLs', count( $row_urls ), 'squirrly-seo' ), count( $row_urls ) ) ) ?></summary>
													<?php foreach ( $row_urls as $row_url ) { echo esc_html( $row_url ) . '<br>'; } ?>
                                                </details>
											<?php } ?>
                                        </td>
                                        <?php
                                        //Endpoints this row was submitted to, stored at submit time so it stays accurate even if the settings change later.
                                        $row_endpoints = isset( $row['endpoints'] ) ? array_values( array_filter( (array) $row['endpoints'] ) ) : array();
                                        ?>
                                        <td>
											<?php if ( ! empty( $row_endpoints ) ) { ?>
                                                <a href="#" class="sq_show_endpoints" style="color:inherit;text-decoration:none;cursor:pointer;line-height:20px" data-endpoints="<?php echo esc_attr( wp_json_encode( $row_endpoints ) ) ?>" title="<?php echo esc_attr__( "See where this was submitted", "squirrly-seo" ) ?>">
													<?php echo esc_html( $row['status'] ) ?>
                                                    <em>(<?php echo esc_html( $row['message'] ) ?>)</em>
                                                    <i class="dashicons dashicons-info-outline" style="font-size:14px;height:14px;width:14px;line-height:20px;vertical-align:middle;position:relative;top:-2px;opacity:.5"></i>
                                                </a>
											<?php } else { ?>
												<?php echo esc_html( $row['status'] ) ?>
                                                <em>(<?php echo esc_html( $row['message'] ) ?>)</em>
											<?php } ?>
                                        </td>
                                        <td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) ) ?></td>
                                    </tr>
									<?php
								}
								?>

                                </tbody>
                            </table>

                            <div id="sq_endpoints_modal" style="display:none;position:fixed;inset:0;z-index:100050;background:rgba(0,0,0,.5)">
                                <div style="background:#fff;max-width:520px;margin:8% auto;padding:20px 24px;border-radius:6px;position:relative;box-shadow:0 10px 40px rgba(0,0,0,.25)">
                                    <span id="sq_endpoints_close" style="position:absolute;top:8px;right:14px;cursor:pointer;font-size:22px;line-height:1;color:#555">&times;</span>
                                    <h5 style="margin:0 0 12px"><?php echo esc_html__( "Submitted to these IndexNow endpoints", "squirrly-seo" ) ?></h5>
                                    <ul id="sq_endpoints_list" style="margin:0;padding-left:18px;word-break:break-all"></ul>
                                </div>
                            </div>
                            <script>
                                (function ($) {
                                    var $modal = $('#sq_endpoints_modal'), $list = $('#sq_endpoints_list');

                                    $(document).on('click', '.sq_show_endpoints', function (e) {
                                        e.preventDefault();
                                        var endpoints = [];
                                        try { endpoints = JSON.parse($(this).attr('data-endpoints')) || []; } catch (err) {}
                                        $list.empty();
                                        $.each(endpoints, function (i, url) { $list.append($('<li></li>').text(url)); });
                                        $modal.show();
                                    });

                                    $modal.on('click', function (e) { if (e.target === this) $modal.hide(); });
                                    $('#sq_endpoints_close').on('click', function () { $modal.hide(); });
                                    $(document).on('keyup', function (e) { if (e.key === 'Escape') $modal.hide(); });
                                })(jQuery);
                            </script>
						<?php } else { ?>
                            <table class="table table-striped table-hover mt-3">
                                <thead>
                                <tr>
                                    <th style="width: 50%;"><?php echo esc_html__( "URL", 'squirrly-seo' ) ?></th>
                                    <th scope="col"><?php echo esc_html__( "Message", 'squirrly-seo' ) ?></th>
                                    <th scope="col"><?php echo esc_html__( "Date", 'squirrly-seo' ) ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td colspan="3"><?php echo esc_html__( "No requests so far", 'squirrly-seo' ) ?></td>
                                </tbody>
                            </table>
						<?php } ?>
                    </div>
                </div>

				<?php do_action( 'sq_indexnow_after' ); ?>

				<?php SQ_Classes_ObjController::getClass( 'SQ_Core_BlockKnowledgeBase' )->init(); ?>

            </div>

            <div class="sq_col_side bg-white">
                <div class="col-12 m-0 p-0 sq_sticky">
					<?php do_action( 'sq_indexnow_side_after' ); ?>
                </div>
            </div>
        </div>
    </div>
</div>
