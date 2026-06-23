<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
if ( ! isset( $view ) ) {
	return;
}

/**
 * Audit Status view
 *
 * Called from Audits View
 */
?>
<?php if ( ! empty( $view->auditpages ) ) { ?>
    <div class="col-12 m-0 p-0">
		<?php

		$call_timestamp = $audit_timestamp = 0;
		$audit_datetime = '';

		if ( get_transient( 'sq_auditpage_all' ) ) {
			$call_timestamp = (int) get_transient( 'sq_auditpage_all' );
		}

		if ( isset( $view->audit->audit_datetime ) && $view->audit->audit_datetime ) {
			//wp_date() already converts the UTC datetime into the site timezone,
			//so we must NOT add gmt_offset manually (that double-applies the offset).
			$audit_timestamp = strtotime( $view->audit->audit_datetime );
			$audit_datetime  = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $audit_timestamp );
		}

		$now_timestamp = time();

		//"In progress": a website audit was requested in the last few minutes
		//(sq_auditpage_all transient, set to time() on request in SQ_Controllers_Audits).
		//We use a LOCAL-only time window on purpose - comparing against the audit's
		//remote datetime is unreliable because the WordPress server clock and the Cloud
		//clock can differ, which would keep showing "In progress" after the audit is ready.
		$in_progress = ( $call_timestamp > 0 && $call_timestamp > ( $now_timestamp - 300 ) );

		if ( ! empty( $view->audit ) && (int) $view->audit->score > 0 ) {
			$color              = false;
			$view->audit->error = isset( $view->audit->error ) ? (bool) $view->audit->error : false;

			if ( (int) $view->audit->score > 0 ) {
				$color = '#D32F2F';
				if ( ( (int) $view->audit->score >= 50 ) ) {
					$color = 'orange';
				}
				if ( ( (int) $view->audit->score >= 90 ) ) {
					$color = '#4CAF50';
				}
			}

			if ( $view->audit->score < 50 ) {
				$message = esc_html__( "Your score is low. A medium score is over 50, and a good score is over 80.", "squirrly-seo" );
			} elseif ( $view->audit->score >= 50 && $view->audit->score < 80 ) {
				$message = esc_html__( "Your score is medium. A good score is over 80.", "squirrly-seo" );
			} elseif ( $view->audit->score >= 80 && $view->audit->score < 100 ) {
				$message = esc_html__( "Your score is good. Keep it as high as possible for good results.", "squirrly-seo" );
			}
			?>
            <div class="sq_audit_score row m-0 p-0">
				<?php if ( SQ_Classes_Helpers_Tools::getValue( 'sid' ) ) { ?>
                    <div class="col-12 row m-0 my-5 p-2 py-3 bg-white">
                        <div class="m-0 p-0 mx-2 pt-2 text-center" style="width: 100px;">
                            <input id="knob_<?php echo (int) $view->audit->id ?>" type="text" value="<?php echo (int) $view->audit->score ?>" class="dial" style="box-shadow: none; border: none; background: none; width: 1px; color: white" title="<?php echo esc_attr__( "Audit Score", "squirrly-seo" ) ?>">
                            <script>
                                (function ($) {
                                    $(document).ready(function () {
                                        $("#knob_<?php echo (int) $view->audit->id ?>").knob({
                                            'min': 0,
                                            'max': 100,
                                            'readOnly': true,
                                            'width': 75,
                                            'height': 75,
                                            'skin': "tron",
                                            'fgColor': '#6405e8'
                                        });
                                    });
                                })(jQuery);
                            </script>
                        </div>
                        <div class="col m-0 p-0 text-left">
                            <div class="font-weight-bold"><?php echo esc_html__( "Your audit score is", "squirrly-seo" ) . ': ' ?><?php echo (int) $view->audit->score ?></div>
                            <div class="sq_audit_header_message small text-dark"><?php echo wp_kses_post( $message ) ?></div>
                            <div class="sq_date m-0 p-0">
								<?php echo esc_html__( "Audit Date", "squirrly-seo" ) . ': ' ?>
                                <span class="text-dark font-weight-bold"><?php echo esc_html( $audit_datetime ) ?></span>
                            </div>
                        </div>
                    </div>
				<?php } else { ?>
                    <div class="col m-0 p-0 sq_audit_header">
                        <a href="<?php echo esc_url( SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_audits', 'addpage' ) ) ?>" class="btn btn-lg btn-primary text-white mx-1">
                            <i class="fa-solid fa-plus-square-o"></i> <?php echo esc_html__( "Add a new page for Audit", "squirrly-seo" ); ?>
                        </a>
						<?php if ( ! $in_progress ) { ?>
                            <form method="post" class="sq_auditpages_request d-inline p-0 m-0">
								<?php SQ_Classes_Helpers_Tools::setNonce( 'sq_audits_update', 'sq_nonce' ); ?>
                                <input type="hidden" name="action" value="sq_audits_update"/>
                                <button type="submit" class="btn btn-lg btn-light border mx-1">
                                    <i class="fa-solid fa-refresh"></i> <?php echo esc_html__( "Request New Audit", "squirrly-seo" ) ?>
                                </button>
                            </form>
						<?php } ?>
                    </div>

                    <div class="float-right text-right m-0 p-0 py-2">
                        <div class="row m-0 p-0 align-items-center">
                            <div class="sq_date m-0 p-0">
								<?php echo esc_html__( "Audit Date", "squirrly-seo" ) . ': ' ?>
                                <span class="text-dark font-weight-bold"><?php echo esc_html( $audit_datetime ) ?></span>
                            </div>
							<?php if ( $in_progress ) { ?>
                                <span class="small text-black-50 m-0 p-0 pl-2"><?php echo esc_html__( "In progress", "squirrly-seo" ) ?></span>
							<?php } ?>
                        </div>
                    </div>
				<?php } ?>

            </div>
		<?php } else { ?>
            <div class="sq_audit_score row p-2">
                <div class="col-8 sq_audit_header">
					<?php if ( $call_timestamp > 0 ) { ?>
                        <h3 class="card-title text-primary">
                            <i class="fa-solid fa-clock-o" aria-hidden="true"></i> <?php echo esc_html__( "Audit in progress", "squirrly-seo" ); ?>
                        </h3>
					<?php } ?>
                </div>
                <div class="col-4 float-right text-right">
                    <div class="my-1">
						<?php echo esc_html__( "Audit not ready yet", "squirrly-seo" ) ?>
                    </div>
                    <form method="post" class="sq_auditpages_request p-0 m-0">
						<?php SQ_Classes_Helpers_Tools::setNonce( 'sq_audits_update', 'sq_nonce' ); ?>
                        <input type="hidden" name="action" value="sq_audits_update"/>
                        <button type="submit" class="btn btn-sm bg-warning text-white inline p-0 px-2 m-0" <?php if ( $call_timestamp > time() - 3600 ) {
							echo 'disabled="disabled"' . ' title="' . esc_attr__( "You can refresh the audit once every hour", "squirrly-seo" ) . '"';
						} ?>>
							<?php echo esc_html__( "Request Website Audit", "squirrly-seo" ) ?>
                        </button>
                    </form>
                </div>
            </div>

			<?php
		}
		?>

    </div>
	<?php if ( ! SQ_Classes_Helpers_Tools::getValue( 'sid', false ) ) { ?>
		<?php if ( ! empty( $view->audit ) ) { ?>
			<?php
			$days_back = (int) SQ_Classes_Helpers_Tools::getValue( 'days_back', 30 );
			if ( ! empty( $view->audit->stats ) ) {
				$scores           = [];
				$positive_changes = 0;
				$audits           = [];
				$scores[]         = array(
					esc_html__( "Date", "squirrly-seo" ),
					esc_html__( "On-Page", "squirrly-seo" ),
					esc_html__( "Off-Page", "squirrly-seo" )
				);
				if ( ! empty( $view->audit->stats ) ) {

					foreach ( $view->audit->stats as $name => $values ) {
						switch ( $name ) {
							case 'score':
								if ( ! empty( $values ) ) {
									foreach ( $values as $date => $value ) {

										$audits[ $date ] = $value;

										if ( isset( $value->onpage ) && isset( $value->offpage ) ) {
											$scores[] = array(
												gmdate( 'm/d/Y', strtotime( $date ) ),
												(int) $value->onpage,
												(int) $value->offpage
											);
										}
									}
								} else {
									$scores[] = array( gmdate( 'm/d/Y' ), 0, 0 );
								}
								break;
							case 'tasks':
								if ( ! empty( $values ) ) {
									foreach ( $values as $group => $completed ) {
										if ( ! empty( $completed ) ) {
											$progress[] = sprintf( esc_html__( "You've completed %s tasks from %s", "squirrly-seo" ), '<strong>' . count( (array) $completed ) . '</strong>', '<strong>' . esc_html( ucfirst( $group ) ) . '</strong>' );
										}
									}
								}
								break;
						}
					}

				}

				//prevent chart error
				if ( count( $scores ) == 1 ) {
					$scores[] = array( gmdate( 'm/d/Y' ), 0, 0 );
				}

				?>
                <div class="sq_stats row m-0 p-0 my-4">
                    <div class="card col-7 p-0 m-0 bg-white shadow-sm">
                        <div class="card-content overflow-hidden m-0">
                            <div class="media align-items-stretch">
                                <div class="media-body p-3">
                                    <h5><?php echo esc_html__( "Scores", "squirrly-seo" ) ?></h5>
                                    <span class="small"><?php echo sprintf( esc_html__( "the latest %s days evolution for Audit", "squirrly-seo" ), (int) $days_back ) ?></span>
                                    <div class="media-right py-3 media-middle ">
                                        <div class="col-12 px-0">
                                            <div id="sq_chart_score" class="sq_chart no-p" style="width:95%; height: 90px;"></div>
                                            <script>
                                                if (typeof google !== 'undefined') {
                                                    google.setOnLoadCallback(function () {
                                                        drawScoreChart("sq_chart_score", <?php echo wp_json_encode( $scores ) ?> , false);
                                                    });
                                                }
                                            </script>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="card col-5 p-0 m-0 bg-white shadow-sm">
                        <div class="overflow-hidden m-0 p-0">
                            <div class="media align-items-stretch">
                                <div class="media-body p-2 py-3">
                                    <h5><?php echo esc_html__( "Progress & Achievements", "squirrly-seo" ) ?></h5>
                                    <span class="small"><?php echo sprintf( esc_html__( "the latest %s days progress for Audit Pages", "squirrly-seo" ), (int) $days_back ); ?></span>

                                    <div class="media-right py-3 media-middle ">
										<?php if ( ! empty( $progress ) ) {
											foreach ( $progress as $value ) {
												echo '<h6 class="col-12 px-0 text-success small""><i class="fa-solid fa-arrow-up"></i> ' . wp_kses_post( $value ) . '</h6>';
											}
										} else {
											echo '<h4 class="col-12 p-0 m-0 text-primary">' . esc_html__( "No progress found yet", "squirrly-seo" ) . '</h4>';
										} ?>
                                    </div>

                                </div>

                            </div>
                        </div>

                    </div>
					<?php if ( ! empty( $progress ) ) { ?>
                        <div class="col-12 p-0 m-0 pt-2 text-right">
                            <a class="btn btn-sm btn-link text-dark" href="https://twitter.com/intent/tweet?text=<?php echo esc_url( 'I love the results I get with Squirrly GEO Audit for my website. @SquirrlyHQ #GEO #SEO' ) ?>"><?php echo esc_html__( 'Share Your Success', "squirrly-seo" ) ?></a>
                        </div>
					<?php } ?>
                </div>


				<?php if ( ! empty( $view->aivisibility ) ) {
					$aiv        = $view->aivisibility;
					$ai_total   = isset( $aiv->total_visits ) ? (int) $aiv->total_visits : 0;
					$ai_percent = isset( $aiv->ai_percent ) ? (float) $aiv->ai_percent : null;
					$ai_conv    = isset( $aiv->conversions ) ? (int) $aiv->conversions : null;
					$ai_days    = isset( $aiv->days_back ) ? (int) $aiv->days_back : (int) SQ_Classes_Helpers_Tools::getValue( 'days_back', 30 );
					$ai_sources = ( isset( $aiv->sources ) && ! empty( $aiv->sources ) ) ? array_slice( (array) $aiv->sources, 0, 10 ) : array();
						//Each trackable AI visit reflects many more AI recommendations & brand exposures
						//that GA can't track (~14x to ~33x). Show the estimated range.
						$ai_reco_min = $ai_total * 14;
						$ai_reco_max = $ai_total * 33;
					?>
                    <div class="sq_ai_visibility row m-0 p-0 my-4">
                        <div class="card col-12 p-0 m-0 bg-white shadow-sm">
                            <div class="card-content m-0 p-3">

                                <div class="row m-0 p-0">
                                    <div class="col-12 m-0 p-0">
                                        <h5 class="m-0 p-0 font-weight-bold"><i class="fa-solid fa-robot text-primary"></i> <?php echo esc_html__( "AI Visibility", "squirrly-seo" ) ?></h5>
                                        <span class="small text-black-50"><?php echo sprintf( esc_html__( "How much traffic AI engines (ChatGPT, Perplexity, Gemini, Claude, and more) sent you in the last %d days, based on your connected Google Analytics.", "squirrly-seo" ), (int) $ai_days ) ?></span>
                                    </div>
                                </div>

                                <div class="row m-0 p-0 mt-3 align-items-start">
                                    <div class="col-6 m-0 p-0 pr-3">
                                        <div class="row m-0 p-0">
                                            <div class="col-6 m-0 p-0 pr-2">
                                                <div class="font-weight-bold text-dark" style="font-size: 2rem; line-height: 1.1;"><?php echo esc_html( number_format( $ai_total ) ) ?></div>
                                                <div class="text-black-50 small"><?php echo esc_html__( "trackable AI visits", "squirrly-seo" ) ?></div>
                                            </div>
											<?php if ( $ai_total > 0 ) { ?>
                                                <div class="col-6 m-0 p-0">
                                                    <div class="font-weight-bold text-dark" style="font-size: 2rem; line-height: 1.1;"><?php echo esc_html( number_format( $ai_reco_min ) . ' – ' . number_format( $ai_reco_max ) ) ?></div>
                                                    <div class="text-black-50 small"><?php echo esc_html__( "estimated AI recommendations & brand exposures", "squirrly-seo" ) ?></div>
                                                </div>
											<?php } ?>
                                        </div>
										<?php if ( $ai_percent !== null ) { ?>
                                            <div class="mt-3"><span class="font-weight-bold text-primary"><?php echo esc_html( number_format( $ai_percent, 1 ) ) ?>%</span> <span class="small text-black-50"><?php echo esc_html__( "of all your visits", "squirrly-seo" ) ?></span></div>
										<?php } ?>
										<?php if ( $ai_conv !== null ) { ?>
                                            <div class="mt-1"><span class="font-weight-bold text-success"><?php echo esc_html( number_format( $ai_conv ) ) ?></span> <span class="small text-black-50"><?php echo esc_html__( "conversions from AI", "squirrly-seo" ) ?></span></div>
										<?php } ?>
                                        <div class="small text-black-50 mt-2"><?php echo esc_html__( "Note: Google Analytics underreports AI traffic, so the real numbers are higher.", "squirrly-seo" ) ?></div>
                                    </div>

                                    <div class="col-6 m-0 p-0">
										<?php if ( ! empty( $ai_sources ) ) { ?>
                                            <table class="table table-sm table-striped small m-0">
                                                <thead>
                                                <tr>
                                                    <th style="width: 10px;">#</th>
                                                    <th><?php echo esc_html__( "AI source", "squirrly-seo" ) ?></th>
                                                    <th class="text-right"><?php echo esc_html__( "Visits", "squirrly-seo" ) ?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
												<?php $ai_i = 0;
												foreach ( $ai_sources as $s ) {
													$s = (object) $s;
													$ai_i ++;
													$s_label  = isset( $s->label ) && $s->label <> '' ? $s->label : ( isset( $s->source ) ? $s->source : '' );
													$s_visits = isset( $s->visits ) ? (int) $s->visits : 0;
													?>
                                                    <tr>
                                                        <td class="text-black-50"><?php echo (int) $ai_i ?></td>
                                                        <td class="font-weight-bold"><?php echo esc_html( $s_label ) ?></td>
                                                        <td class="text-right"><?php echo esc_html( number_format( $s_visits ) ) ?></td>
                                                    </tr>
												<?php } ?>
                                                </tbody>
                                            </table>
										<?php } else { ?>
                                            <div class="text-black-50 small p-2"><?php echo esc_html__( "No AI-engine traffic detected for this period yet. As AI search grows, sources like ChatGPT and Perplexity will start to appear here.", "squirrly-seo" ) ?></div>
										<?php } ?>
                                    </div>
                                </div>

								<?php if ( isset( $aiv->insight ) && $aiv->insight <> '' ) { ?>
                                    <div class="row m-0 p-0 mt-3">
                                        <div class="col-12 m-0 p-2 bg-light rounded small"><i class="fa-solid fa-chart-line text-success"></i> <?php echo wp_kses_post( $aiv->insight ) ?></div>
                                    </div>
								<?php } ?>

                            </div>
                        </div>
                    </div>
				<?php } ?>

                <div class="col-12 m-0 p-0">
                    <h4 class="card-title"><?php echo esc_html__( "Audit History", "squirrly-seo" ) ?></h4>
                    <div class="mx-0 my-2 p-0">
                        <form class="sq_form_bulk_submit" method="get">
	                        <?php SQ_Classes_Helpers_Tools::setNonce( 'sq_audits_compare' ); ?>
                            <input type="hidden" name="action" value="sq_audits_compare"/>
                            <div class="col-5 p-0 m-0 my-2">

                                <input type="hidden" name="page" value="sq_audits">
                                <input type="hidden" name="tab" value="compare">
                                <button type="button" class="sq_bulk_submit btn btn-sm btn-primary"><?php echo esc_html__( "Compare Audits", "squirrly-seo" ); ?></button>

                            </div>

                            <table class="sqd_blog_list table table-light table-striped table-hover">
                                <thead class="thead-light">
                                <tr>
                                    <th style="width: 10px;"></th>
                                    <th scope="col" class="text-center"><?php echo esc_html__( "Audit Score", "squirrly-seo" ) ?></th>
                                    <th scope="col" class="text-right"><?php echo esc_html__( "Page(s)", "squirrly-seo" ) ?></th>
                                    <th scope="col" class="text-center"><?php echo esc_html__( "Date", "squirrly-seo" ) ?></th>
                                    <th scope="col"></th>
                                </tr>
                                </thead>
                                <tbody>
								<?php
								$cnt = 0;
								foreach ( $audits as $date => $audit ) {
									$cnt ++;
									?>
                                    <tr id="sq_tr<?php echo (int) $audit->id ?>">
                                        <td style="width: 10px;">
                                            <label>
                                                <input type="checkbox" name="sid[]" class="sq_bulk_input" value="<?php echo (int) $audit->id ?>"/>
                                            </label>
                                        </td>
                                        <td class="text-center font-weight-bold td-blue"><?php echo (int) $audit->onpage ?></td>
                                        <td class="text-right font-weight-bold"><?php if ( isset( $audit->urls ) ) {
												echo count( (array) $audit->urls ) . ' ' . esc_html__( "pages", "squirrly-seo" );
											} ?></td>
                                        <td class="text-center"><?php echo esc_html( gmdate( 'd M Y', strtotime( $date ) ) ) ?></td>
                                        <td>
                                            <a href="<?php echo esc_url( SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_audits', 'audit', array( 'sid=' . (int) $audit->id ) ) ) ?>" class="btn <?php echo( (int) $cnt == 1 ? 'btn-primary' : 'btn-light border' ) ?> btn-sm" style="min-width: 150px"><?php echo( (int) $cnt == 1 ? esc_html__( "Show Latest Audit", "squirrly-seo" ) : esc_html__( "Show Audit", "squirrly-seo" ) ) ?></a>
                                        </td>
                                    </tr>
								<?php } ?>
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>

			<?php }
		} ?>
	<?php } ?>
<?php } ?>
