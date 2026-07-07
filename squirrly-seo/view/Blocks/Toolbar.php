<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
if ( ! isset( $view ) ) {
	return;
}

/**
 * ToolBar Block view
 *
 * Called on all Squirrly Settings pages
 */
?>
<?php $page = apply_filters( 'sq_page', SQ_Classes_Helpers_Tools::getValue( 'page', '' ) ); ?>
<div id="sq_toolbarblog" class="col-12 m-0 p-0">
    <nav class="navbar navbar-expand-sm bg-primary p-0 m-0 py-1">
        <div class="container-fluid p-0 m-0">
	        <?php if ( $page == 'sq_dashboard' ) { ?>
            <div class="justify-content-start col p-0 m-0 px-3" style="line-height: 36px;" id="navigation">
                <a href="<?php echo esc_url( SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_dashboard' ) ) ?>"><span class="sq_logo sq_logo_30 float-left" style="margin: 3px 0;"></span></a>
                <a href="<?php echo esc_url( SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_dashboard' ) ) ?>"><span class="sq_logo_toolbar_text ml-2 text-white float-left" style="font-size: 16px;"><?php echo  'GEO Plugin by Squirrly SEO (v'. SQ_VERSION .')' ?></span></a>
				<?php do_action( 'sq_toolbar_start' ); ?>
            </div>
	        <?php }else{?>
            <div class="justify-content-start col p-0 m-0 px-3" id="navigation">
                <a href="<?php echo esc_url( SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_dashboard' ) ) ?>"><span class="sq_logo sq_logo_30 float-left"></span></a>
                <a href="<?php echo esc_url( SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_dashboard' ) ) ?>"><span class="sq_logo_toolbar_text ml-2 text-white float-left"><?php echo esc_html( _SQ_MENU_NAME_ ) ?></span></a>
		        <?php do_action( 'sq_toolbar_start' ); ?>
            </div>
            <?php }?>

            <?php if ( SQ_Classes_Helpers_Tools::getMenuVisible( 'show_seo' ) ) { ?>
                <div class="justify-content-end col p-0 m-0">
                    <div class="row text-right p-0 m-0">
                        <div class="col p-0 m-0 sq_save_ajax">
                            <button id="sq_seoexpert" name="sq_seoexpert" class="btn btn-link text-white" data-action="sq_ajax_seosettings_save" data-name="sq_seoexpert" data-value="0" <?php echo( ! SQ_Classes_Helpers_Tools::getOption( 'sq_seoexpert' ) ? 'style="text-decoration: underline; font-weight: 700;"' : '' ) ?> ><?php echo esc_html__( "SEO Beginner", "squirrly-seo" ); ?></button>
                            <button id="sq_seoexpert" name="sq_seoexpert" class="btn btn-link text-white" data-action="sq_ajax_seosettings_save" data-name="sq_seoexpert" data-value="1" <?php echo( SQ_Classes_Helpers_Tools::getOption( 'sq_seoexpert' ) ? 'style="text-decoration: underline; font-weight: 700;"' : '' ) ?> ><?php echo esc_html__( "SEO Expert", "squirrly-seo" ); ?></button>
                        </div>
                        <div class="p-2 m-0 mt-1 pb-0 sq_account_info" style="display: inline-block"></div>

						<?php do_action( 'sq_toolbar_end' ); ?>
                    </div>
                </div>
			<?php } ?>
        </div>
    </nav>
</div>

<?php
//Assistant sidebar collapse state (persisted via the sq_assistant cookie, read server-side like the left menu)
$sq_assistant_collapsed = ( isset( $_COOKIE['sq_assistant'] ) && $_COOKIE['sq_assistant'] === 'collapse' );
?>
<?php if ( $sq_assistant_collapsed ) { ?>
    <style id="sq_assistant_precollapse">
        #sq_wrap .sq_col_side { flex: 0 0 45px !important; min-width: 45px !important; overflow: hidden; }
    </style>
<?php } ?>
<style>
    /* collapse / expand toggle button */
    #sq_wrap .sq_col_side .sq_assistant_toggle { text-align: right; padding: 8px 12px 0; }
    #sq_wrap .sq_col_side .sq_assistant_toggle span { cursor: pointer; }
    #sq_wrap .sq_col_side .sq_assistant_toggle .sq_assistant_open { display: none; }

    /* collapsed = narrow strip showing the task icons; expands on hover (same behaviour as the small-screen sidebar) */
    #sq_wrap .sq_col_side.sq_col_side_collapsed { flex: 0 0 45px !important; min-width: 45px !important; position: relative; z-index: 6; }
    #sq_wrap .sq_col_side.sq_col_side_collapsed .sq_assistant_toggle { text-align: center; padding: 10px 0; }
    #sq_wrap .sq_col_side.sq_col_side_collapsed .sq_assistant_collapse { display: none; }
    #sq_wrap .sq_col_side.sq_col_side_collapsed .sq_assistant_open { display: inline-block; }
    #sq_wrap .sq_col_side.sq_col_side_collapsed .sq_assistant_open i { font-size: 20px; }

    #sq_wrap .sq_col_side.sq_col_side_collapsed .card-title,
    #sq_wrap .sq_col_side.sq_col_side_collapsed .sq_assistant li.sq_save_ajax,
    #sq_wrap .sq_col_side.sq_col_side_collapsed .sq_assistant li.sq_task div,
    #sq_wrap .sq_col_side.sq_col_side_collapsed .sq_assistant li h4,
    #sq_wrap .sq_col_side.sq_col_side_collapsed .sq_assistant li table,
    #sq_wrap .sq_col_side.sq_col_side_collapsed .sq_assistant li button,
    #sq_wrap .sq_col_side.sq_col_side_collapsed .sq_assistant div,
    #sq_wrap .sq_col_side.sq_col_side_collapsed .sq_assistant a {
        display: none;
    }

    /* on hover, expand to a fixed overlay - exactly like the small-screen sidebar (position:fixed escapes the flex/overflow that pins it to the strip) */
    /* z-index must sit above the Squirrly Snippet close button (.sq-close-absolute is 10000) so the expanded sidebar isn't overlapped by it on admin pages */
    #sq_wrap .sq_col_side.sq_col_side_collapsed:hover {
        width: 270px !important;
        position: fixed;
        top: var(--sq-overlay-top, 78px); /* synced below the toolbar; drops to the admin bar once the toolbar scrolls away */
        right: 0;
        bottom: 0;
        height: auto;
        overflow-y: auto;
        z-index: 10001;
        box-shadow: -3px 0 14px rgba(0, 0, 0, 0.12);
    }
    /* inside the fixed overlay, drop the sticky offset so short/empty content (and the toggle) stays at the very top instead of drifting down */
    #sq_wrap .sq_col_side.sq_col_side_collapsed:hover .sq_sticky { position: static !important; top: auto !important; align-self: flex-start !important; }
    #sq_wrap .sq_col_side.sq_col_side_collapsed:hover .sq_assistant_toggle { text-align: right; padding: 8px 12px 0; }
    #sq_wrap .sq_col_side.sq_col_side_collapsed:hover .card-title,
    #sq_wrap .sq_col_side.sq_col_side_collapsed:hover .sq_assistant li.sq_save_ajax,
    #sq_wrap .sq_col_side.sq_col_side_collapsed:hover .sq_assistant li.sq_task div,
    #sq_wrap .sq_col_side.sq_col_side_collapsed:hover .sq_assistant li h4,
    #sq_wrap .sq_col_side.sq_col_side_collapsed:hover .sq_assistant li table,
    #sq_wrap .sq_col_side.sq_col_side_collapsed:hover .sq_assistant li button,
    #sq_wrap .sq_col_side.sq_col_side_collapsed:hover .sq_assistant div,
    #sq_wrap .sq_col_side.sq_col_side_collapsed:hover .sq_assistant a {
        display: block;
    }

    /* full-width buttons in the assistant sidebar */
    #sq_wrap .sq_col_side .sq_assistant .btn { width: 100%; display: block; margin-left: 0; margin-right: 0; }
</style>
<script>
    (function ($) {
        var COOKIE     = 'sq_assistant';
        var collapsed  = <?php echo $sq_assistant_collapsed ? 'true' : 'false'; ?>;
        var toggleHtml = '<div class="sq_assistant_toggle">' +
            '<span class="sq_assistant_collapse" title="<?php echo esc_attr__( 'Collapse', 'squirrly-seo' ) ?>"><i class="dashicons-before dashicons-arrow-right-alt text-black-50"></i></span>' +
            '<span class="sq_assistant_open" title="<?php echo esc_attr__( 'Expand', 'squirrly-seo' ) ?>"><i class="dashicons-before dashicons-arrow-left-alt text-black-50"></i></span>' +
            '</div>';

        $(function () {
            $('.sq_col_side').each(function () {
                var $side   = $(this);
                var $sticky = $side.find('.sq_sticky').first();
                if (!$sticky.length) {
                    return; //only sidebars that actually hold an assistant
                }
                if (!$sticky.children('.sq_assistant_toggle').length) {
                    $sticky.prepend(toggleHtml);
                }
                $side.toggleClass('sq_col_side_collapsed', collapsed);
            });
            //the pre-collapse style was only to avoid a flash; the class now governs the state
            $('#sq_assistant_precollapse').remove();

            //keep the hover-overlay top aligned to the bottom of the toolbar, which scrolls away
            var $toolbar  = $('#sq_toolbarblog');
            var $adminbar = $('#wpadminbar');
            function sqUpdateOverlayTop() {
                var floor = $adminbar.length ? Math.max(0, $adminbar[0].getBoundingClientRect().bottom) : 0;
                var top   = floor;
                if ($toolbar.length) {
                    top = Math.max(floor, $toolbar[0].getBoundingClientRect().bottom);
                }
                document.documentElement.style.setProperty('--sq-overlay-top', top + 'px');
            }
            sqUpdateOverlayTop();
            $(window).on('scroll.sqassistant resize.sqassistant', sqUpdateOverlayTop);

            //if the page loaded already collapsed, let width-based widgets (Focus Pages table) recompute
            if (collapsed) {
                setTimeout(function () { $(window).trigger('resize'); }, 250);
            }
        });

        if (!window.sq_assistant_toggle_bound) {
            window.sq_assistant_toggle_bound = true;
            $(document).on('click', '.sq_assistant_collapse', function () {
                $(this).closest('.sq_col_side').addClass('sq_col_side_collapsed');
                $.sq_setCookie(COOKIE, 'collapse');
                $(window).trigger('resize'); //let widgets like the Focus Pages table recompute against the new sidebar width
            });
            $(document).on('click', '.sq_assistant_open', function () {
                $(this).closest('.sq_col_side').removeClass('sq_col_side_collapsed');
                $.sq_setCookie(COOKIE, 'open');
                $(window).trigger('resize');
            });
            //while the mouse is over a collapsed sidebar it becomes a 270px fixed overlay, so the table can only
            //reclaim the space once the mouse leaves and the strip is back to 45px - recompute then
            $(document).on('mouseleave', '.sq_col_side_collapsed', function () {
                $(window).trigger('resize');
            });
        }
    })(jQuery);
</script>
