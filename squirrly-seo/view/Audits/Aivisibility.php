<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
if ( ! isset( $view ) ) {
	return;
}

/**
 * AI Visibility page view
 *
 * Called from Audits Controller on the aivisibility tab
 */
?>
<div id="sq_wrap">
	<?php $view->show_view( 'Blocks/Toolbar' ); ?>
	<?php do_action( 'sq_notices' ); ?>
    <div id="sq_content" class="d-flex flex-row bg-white my-0 p-0 m-0">
		<?php
		if ( ! SQ_Classes_Helpers_Tools::userCan( 'sq_manage_focuspages' ) ) {
			echo '<div class="col-12 alert alert-success text-center m-0 p-3">' . esc_html__( "You do not have permission to access this page. You need Squirrly SEO Admin role.", "squirrly-seo" ) . '</div>';

			return;
		}
		?>
		<?php $view->show_view( 'Blocks/Menu' ); ?>
        <div class="d-flex flex-row flex-nowrap flex-grow-1 bg-light m-0 p-0">
            <div class="flex-grow-1 sq_flex m-0 py-0 px-4 pb-5">
				<?php do_action( 'sq_form_notices' ); ?>

                <div class="sq_breadcrumbs my-4"><?php SQ_Classes_ObjController::getClass( 'SQ_Models_Menu' )->showBreadcrumbs( SQ_Classes_Helpers_Tools::getValue( 'page' ) ) ?>
                    <i class="text-black-50 mx-1">/</i> <?php echo esc_html__( "AI Visibility", "squirrly-seo" ); ?>
                </div>

                <h3 class="mt-4 card-title">
                    <i class="fa-solid fa-robot text-primary"></i> <?php echo esc_html__( "AI Visibility", "squirrly-seo" ); ?>
                    <div class="sq_help_question d-inline">
                        <a href="https://howto12.squirrly.co/kb/seo-audit/" target="_blank"><i class="fa-solid fa-question-circle m-0 p-0"></i></a>
                    </div>
                </h3>
                <div class="col-9 small m-0 p-0 mb-4">
					<?php echo esc_html__( "See how much traffic AI answer engines - ChatGPT, Perplexity, Gemini, Claude and more - send to your site, which engines they are, and how it converts. Based on your connected Google Analytics.", "squirrly-seo" ); ?>
                </div>

				<?php
				$ai_days = (int) SQ_Classes_Helpers_Tools::getValue( 'days_back', 30 );

				if ( ! $view->ga_connected ) {
					?>
                    <div class="col-12 card bg-white shadow-sm p-4 m-0 my-3 text-center border-0">
                        <div class="my-2"><i class="fa-solid fa-robot text-primary" style="font-size: 2.5rem;"></i></div>
                        <h5 class="font-weight-bold"><?php echo esc_html__( "Connect Google Analytics to unlock AI Visibility", "squirrly-seo" ); ?></h5>
                        <div class="text-black-50 small my-2 mx-auto" style="max-width: 560px;"><?php echo esc_html__( "AI Visibility reads the traffic that AI engines send you from your Google Analytics. Connect GA4 to see your trackable AI visits, which engines send them, and your estimated AI brand exposures.", "squirrly-seo" ); ?></div>
                        <div class="my-3">
                            <a href="<?php echo esc_url( SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_seosettings', 'webmaster' ) ) ?>" class="btn btn-primary btn-lg"><i class="fa-solid fa-plug"></i> <?php echo esc_html__( "Connect Google Analytics", "squirrly-seo" ); ?></a>
                        </div>
                    </div>
					<?php
				} elseif ( empty( $view->aivisibility ) ) {
					?>
                    <div class="col-12 card bg-white shadow-sm p-4 m-0 my-3 text-center border-0">
                        <div class="my-2"><i class="fa-solid fa-robot text-primary" style="font-size: 2.5rem;"></i></div>
                        <h5 class="font-weight-bold"><?php echo esc_html__( "No AI-engine traffic detected yet", "squirrly-seo" ); ?></h5>
                        <div class="text-black-50 small my-2 mx-auto" style="max-width: 560px;"><?php echo sprintf( esc_html__( "We did not find traffic from AI engines in the last %d days. As AI search grows, sources like ChatGPT and Perplexity will start to appear here. Keep your GEO/AEO Audit green to improve your chances of being cited.", "squirrly-seo" ), (int) $ai_days ); ?></div>
                        <div class="my-3">
                            <a href="<?php echo esc_url( SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_audits' ) ) ?>" class="btn btn-primary"><?php echo esc_html__( "Open GEO Audit", "squirrly-seo" ); ?></a>
                        </div>
                    </div>
					<?php
				} else {
					$aiv        = $view->aivisibility;
					$ai_total   = isset( $aiv->total_visits ) ? (int) $aiv->total_visits : 0;
					$ai_percent = isset( $aiv->ai_percent ) ? (float) $aiv->ai_percent : null;
					$ai_conv    = isset( $aiv->conversions ) ? (int) $aiv->conversions : null;
					$ai_days    = isset( $aiv->days_back ) ? (int) $aiv->days_back : $ai_days;
					$ai_sources = ( isset( $aiv->sources ) && ! empty( $aiv->sources ) ) ? array_slice( (array) $aiv->sources, 0, 15 ) : array();

					//Each trackable AI visit reflects many more AI recommendations & brand exposures
					//that GA can't track (~14x to ~33x). Show the estimated range.
					$ai_reco_min = $ai_total * 14;
					$ai_reco_max = $ai_total * 33;
					?>
                    <div class="row m-0 p-0">

                        <div class="col-7 p-0 m-0 pr-3">
                            <div class="card bg-white shadow-sm border-0 h-100">
                                <div class="card-content m-0 p-4">
                                    <div class="small text-black-50 mb-2"><?php echo sprintf( esc_html__( "Last %d days", "squirrly-seo" ), (int) $ai_days ) ?></div>

                                    <div class="row m-0 p-0">
                                        <div class="col-6 m-0 p-0">
                                            <div class="font-weight-bold text-dark" style="font-size: 2.4rem; line-height: 1.1;"><?php echo esc_html( number_format( $ai_total ) ) ?></div>
                                            <div class="text-black-50 small"><?php echo esc_html__( "trackable AI visits", "squirrly-seo" ) ?></div>
                                        </div>
										<?php if ( $ai_total > 0 ) { ?>
                                            <div class="col-6 m-0 p-0">
                                                <div class="font-weight-bold text-primary" style="font-size: 2.4rem; line-height: 1.1;"><?php echo esc_html( number_format( $ai_reco_min ) . ' – ' . number_format( $ai_reco_max ) ) ?></div>
                                                <div class="text-black-50 small"><?php echo esc_html__( "estimated AI recommendations & brand exposures", "squirrly-seo" ) ?></div>
                                            </div>
										<?php } ?>
                                    </div>

                                    <div class="row m-0 p-0 mt-4">
										<?php if ( $ai_percent !== null ) { ?>
                                            <div class="col-6 m-0 p-0">
                                                <span class="font-weight-bold text-primary" style="font-size: 1.4rem;"><?php echo esc_html( number_format( $ai_percent, 1 ) ) ?>%</span>
                                                <div class="small text-black-50"><?php echo esc_html__( "of all your visits", "squirrly-seo" ) ?></div>
                                            </div>
										<?php } ?>
										<?php if ( $ai_conv !== null ) { ?>
                                            <div class="col-6 m-0 p-0">
                                                <span class="font-weight-bold text-success" style="font-size: 1.4rem;"><?php echo esc_html( number_format( $ai_conv ) ) ?></span>
                                                <div class="small text-black-50"><?php echo esc_html__( "conversions from AI", "squirrly-seo" ) ?></div>
                                            </div>
										<?php } ?>
                                    </div>

                                    <div class="small text-black-50 mt-4"><i class="fa-solid fa-circle-info"></i> <?php echo esc_html__( "Google Analytics underreports AI traffic, so your real numbers are higher than what is shown here.", "squirrly-seo" ) ?></div>

									<?php if ( isset( $aiv->insight ) && $aiv->insight <> '' ) { ?>
                                        <div class="mt-3 p-2 bg-light rounded small"><i class="fa-solid fa-chart-line text-success"></i> <?php echo wp_kses_post( $aiv->insight ) ?></div>
									<?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-5 p-0 m-0">
                            <div class="card bg-white shadow-sm border-0 h-100">
                                <div class="card-content m-0 p-4">
                                    <h5 class="m-0 p-0 mb-3 font-weight-bold"><?php echo esc_html__( "Top AI sources", "squirrly-seo" ) ?></h5>
									<?php if ( ! empty( $ai_sources ) ) { ?>
                                        <table class="table table-sm table-striped m-0">
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
                                        <div class="text-black-50 small"><?php echo esc_html__( "No individual AI sources for this period yet.", "squirrly-seo" ) ?></div>
									<?php } ?>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="sq_tips col-12 m-0 p-0 my-5">
                        <h5 class="text-left my-3 font-weight-bold">
                            <i class="fa-solid fa-exclamation-circle"></i> <?php echo esc_html__( "Want more AI visibility?", "squirrly-seo" ); ?>
                        </h5>
                        <ul class="mx-4 my-1">
                            <li class="text-left small"><?php echo esc_html__( "Keep your GEO/AEO Audit green - answer-ready content gets cited more often.", "squirrly-seo" ); ?></li>
                            <li class="text-left small"><?php echo esc_html__( "Allow AI crawlers and publish an llms.txt so engines can read and trust your site.", "squirrly-seo" ); ?></li>
                            <li class="text-left small"><?php echo esc_html__( "Use LLM Indexing to push new and updated pages to search and AI engines fast.", "squirrly-seo" ); ?></li>
                        </ul>
                    </div>
					<?php
				}
				?>

            </div>
        </div>
    </div>
</div>
