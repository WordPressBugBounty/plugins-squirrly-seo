<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
if ( ! isset( $view ) ) {
	return;
}

/**
 * Focus Pages view
 *
 * Called from Focus Page List view
 */
?>
<?php
if ( ! empty( $view->focuspages ) ) { ?>
	<?php if ( SQ_Classes_Helpers_Tools::getValue( 'sid' ) ) { ?>
        <div class="sq_back_button">
            <button type="button" class="btn btn-sm btn-primary py-1 px-5" onclick="location.href = '<?php echo esc_url( SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_focuspages', 'pagelist' ) ) ?>';" style="cursor: pointer"><?php echo esc_html__( "Show All Focus Pages", 'squirrly-seo' ) ?></button>
        </div>
	<?php } ?>
	<?php if ( isset( $view->labels ) && ! empty( $view->labels ) ) {
		$keyword_labels = SQ_Classes_Helpers_Tools::getValue( 'slabel', array() );

		?>

        <div class="col-12 row m-0 p-0 my-2">
            <form id="sq_focuspages_form" method="get" class="form-inline m-0 p-0 ignore">
	            <?php SQ_Classes_Helpers_Tools::setNonce( 'sq_focuspages_search_label', 'sq_nonce', false ); ?>
                <input type="hidden" name="action" value="sq_focuspages_search_label"/>

                <input type="hidden" name="page" value="<?php echo esc_attr( SQ_Classes_Helpers_Tools::getValue( 'page' ) ) ?>">
                <input type="hidden" name="tab" value="<?php echo esc_attr( SQ_Classes_Helpers_Tools::getValue( 'tab' ) ) ?>">

                <div class="col-6 row p-0 m-0">
                    <select name="slabel[]" class="d-inline-block m-0 p-1" onchange="jQuery('form#sq_focuspages_form').submit();">
                        <option value=""><?php echo esc_html__( "Filter by Red Element", 'squirrly-seo' ) ?></option>
						<?php
						$keyword_labels = SQ_Classes_Helpers_Tools::getValue( 'slabel', array() );
						foreach ( $view->labels as $category => $label ) {
							if ( $label->show ) { ?>
                                <option value="<?php echo esc_attr( $category ) ?>" <?php echo( in_array( (string) $category, (array) $keyword_labels ) ? 'selected="selected"' : '' ) ?> ><?php echo esc_html( $label->name ) ?></option>
							<?php }
						}
						?>
                    </select>
                </div>

            </form>

			<?php if ( SQ_Classes_Helpers_Tools::getValue( 'slabel' ) || SQ_Classes_Helpers_Tools::getValue( 'sid' ) ) { ?>
                <div class="m-0 p-0">
                    <button type="button" class="btn btn-link text-primary px-5" onclick="location.href = '<?php echo esc_url( SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_focuspages', 'pagelist' ) ) ?>';" style="cursor: pointer"><?php echo esc_html__( "Refresh Filter", 'squirrly-seo' ) ?></button>
                </div>
			<?php } ?>
        </div>
		<?php
	} ?>

    <div class="col-12 m-0 p-0 position-relative">
        <div class="<?php echo( ! SQ_Classes_Helpers_Tools::getValue( 'sid' ) ? 'sq_overflow' : '' ) ?> col-12 m-0 p-0 flexcroll" <?php echo( ! SQ_Classes_Helpers_Tools::getValue( 'sid' ) ? 'style="max-height: 590px;"' : '' ) ?>>
            <div class="col-12 m-0 p-0 border-0 " style="display: inline-block;">
                <table class="table table-striped table-hover <?php echo( SQ_Classes_Helpers_Tools::getValue( 'sid' ) ? 'detailed' : 'sq_focuspages_sticky' ) ?>">
                    <thead>
                    <tr>
                        <th><?php echo esc_html__( "Permalink", 'squirrly-seo' ) ?></th>
                        <th><?php echo esc_html__( "Chance to Rank", 'squirrly-seo' ) ?></th>
						<?php
						$categories     = SQ_Classes_ObjController::getClass( 'SQ_Models_FocusPages' )->getCategories();
						$keyword_labels = SQ_Classes_Helpers_Tools::getValue( 'slabel', array() );

						if ( ! empty( $categories ) ) {
							foreach ( $categories as $name => $title ) {
								$class = '';
								if ( ! empty( $keyword_labels ) && ! in_array( $name, (array) $keyword_labels ) ) {
									$class = 'hidden';
								}
								?>
                                <th class="text-center <?php echo esc_attr( $class ) ?>"><?php echo esc_html( $title ) ?></th>
							<?php }
						}
						?>
                        <th style="width: 10px"></th>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					if ( ! empty( $view->focuspages ) ) {
						foreach ( $view->focuspages as $index => $focuspage ) {
							$view->focuspage = $focuspage;

							if ( isset( $view->focuspage->id ) && $view->focuspage->id <> '' ) {

								$view->post = $view->focuspage->getWppost();
								if ( ! SQ_Classes_Helpers_Tools::userCan( 'sq_manage_focuspages' ) ) {
									continue;
								}
								if ( ! empty( $keyword_labels ) && $view->focuspage->audit_error ) {
									continue;
								}

								$class = ( $index % 2 ? 'even' : 'odd' );

								?>
                                <tr id="sq_row_<?php echo esc_attr( $focuspage->id ) ?>" class="<?php echo esc_attr( $class ) ?>">
									<?php $view->show_view( 'FocusPages/FocusPageRow' ); ?>
                                </tr>
								<?php
							}
						}
					} ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <style>
        #sq_wrap .sq_focuspages_sticky thead th:first-child,
        #sq_wrap .sq_focuspages_sticky tbody td:first-child {
            position: -webkit-sticky;
            position: sticky;
            left: 0;
            box-shadow: inset -1px 0 0 #e9e9e9;
        }
        #sq_wrap .sq_focuspages_sticky thead th:first-child {
            z-index: 3;
            background-color: #1c3c50;
        }
        #sq_wrap .sq_focuspages_sticky tbody td:first-child {
            z-index: 2;
            background-color: #fff;
        }
        #sq_wrap .sq_focuspages_sticky tbody tr:nth-of-type(even) td:first-child {
            background-color: #fbfbfb;
        }
        #sq_wrap .sq_focuspages_sticky tbody tr:hover td:first-child {
            background-color: rgb(252 251 255);
        }
    </style>

    <script>
        (function ($) {
            if (window.sq_focuspages_reaudit_bound) {
                return;
            }
            window.sq_focuspages_reaudit_bound = true;

            var sq_inprogress   = <?php echo wp_json_encode( esc_html__( "In progress", "squirrly-seo" ) ); ?>;
            var sq_reaudit_fail = <?php echo wp_json_encode( esc_html__( "Could not send the page for re-audit. Please try again.", "squirrly-seo" ) ); ?>;

            $(document).on('click', '.sq_focuspages_reaudit', function () {
                var $btn = $(this);
                $btn.prop('disabled', true).addClass('sq_minloading');

                $.post(ajaxurl, {
                    action: 'sq_ajax_focuspages_reaudit',
                    id: $btn.data('id'),
                    post_id: $btn.data('post_id'),
                    type: $btn.data('type'),
                    term_id: $btn.data('term_id'),
                    taxonomy: $btn.data('taxonomy'),
                    sq_nonce: sqAdmin.nonce
                }).done(function (response) {
                    if (response && response.success) {
                        $btn.replaceWith('<span class="small ml-2 text-black-50"><i class="fa-solid fa-clock-o"></i> ' + sq_inprogress + '</span>');
                    } else {
                        $btn.prop('disabled', false).removeClass('sq_minloading');
                        if ($.isFunction($.sq_showMessage)) {
                            $.sq_showMessage((response && response.data) ? response.data : sq_reaudit_fail).addClass('sq_error');
                        }
                    }
                }).fail(function () {
                    $btn.prop('disabled', false).removeClass('sq_minloading');
                    if ($.isFunction($.sq_showMessage)) {
                        $.sq_showMessage(sq_reaudit_fail).addClass('sq_error');
                    }
                });
            });
        })(jQuery);
    </script>
<?php } elseif ( SQ_Classes_Helpers_Tools::getValue( 'slabel' ) || SQ_Classes_Helpers_Tools::getValue( 'sid' ) ) { ?>
    <div class="col-12 m-0 p-0">
        <h4 class="text-center"><?php echo sprintf( esc_html__( "No data for this filter. %sShow All%s Focus Pages.", 'squirrly-seo' ), '<a href="' . esc_url( SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_focuspages', 'pagelist' ) ) . '" >', '</a>' ) ?></h4>
    </div>
<?php } elseif ( ! SQ_Classes_Error::isError() ) { ?>
    <div class="col-12 m-0 p-0 ">
        <h4 class="text-center"><?php echo esc_html__( "Welcome to Focus Pages", 'squirrly-seo' ); ?></h4>
        <div class="col-12 m-2 text-center">
            <a href="<?php echo esc_url( SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_focuspages', 'addpage' ) ) ?>" class="btn btn-lg btn-primary"><i class="fa-solid fa-plus-square-o"></i> <?php echo esc_html__( "Add a new page as Focus Page to get started", 'squirrly-seo' ); ?>
            </a>
        </div>
    </div>
<?php } else { ?>
    <div class="col-12 m-0 p-0">
        <div class="col-12 px-2 py-3 text-center">
            <img src="<?php echo esc_url( _SQ_ASSETS_URL_ . 'img/noconnection.png' ) ?>" alt="" style="width: 300px">
        </div>
        <div class="col-12 m-2 text-center">
            <div class="col-12 alert alert-success text-center m-0 p-3">
                <i class="fa-solid fa-exclamation-triangle" style="font-size: 18px !important;"></i> <?php echo sprintf( esc_html__( "There is a connection error with Squirrly Cloud. Please check the connection and %srefresh the page%s.", 'squirrly-seo' ), '<a href="javascript:void(0);" onclick="location.reload();" >', '</a>' ) ?>
            </div>
        </div>
    </div>
<?php } ?>

<?php
if ( ! empty( $view->focuspages ) ) {
	foreach ( $view->focuspages as $focuspage ) {
		if ( isset( $focuspage->id ) ) { ?>
            <div id="sq_assistant_<?php echo esc_attr( $focuspage->id ) ?>" class="sq_assistant">
				<?php
				$categories = apply_filters( 'sq_assistant_categories_page', $focuspage->id );

				if ( ! empty( $categories ) ) {
					foreach ( $categories as $index => $category ) {
						if ( isset( $category->assistant ) ) {
							//show /view/Assistant/Asistant.php for the current Focus Page
							echo $category->assistant;
						}
					}
				}
				?>
            </div>
			<?php
			//get the keywords modal based on the focus page
			echo SQ_Classes_ObjController::getClass( 'SQ_Models_Assistant' )->getKeywordsModal( $focuspage );
		} ?>
	<?php }
} ?>
