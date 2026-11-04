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
                            <a href="https://howto.squirrly.co/faq/what-gets-configured-automatically-in-non-seo-expert-mode/" target="_blank" class="text-white"><i class="fa-solid fa-question-circle m-0 px-2" style="display: inline;"></i></a>
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
//screens whose tasks open in the side drawer (bullets): hide the column from the first paint, not after the script
$sq_assistant_page   = SQ_Classes_Helpers_Tools::getValue( 'page', '' );
$sq_assistant_tab    = SQ_Classes_Helpers_Tools::getValue( 'tab', '' );
$sq_assistant_drawer = ( $sq_assistant_page === 'sq_focuspages' && in_array( $sq_assistant_tab, array( '', 'pagelist' ), true ) ) || ( $sq_assistant_page === 'sq_assistant' && $sq_assistant_tab === 'bulkseo' );
?>
<?php if ( $sq_assistant_drawer ) { ?>
    <style id="sq_assistant_predrawer">
        #sq_wrap .sq_col_side { display: none; }
    </style>
<?php } else { ?>
    <style id="sq_assistant_pretoggle">
        /* room for the collapse/expand arrow the script adds on top, so the tasks don't jump down when it appears */
        #sq_wrap .sq_col_side .sq_sticky { padding-top: <?php echo $sq_assistant_collapsed ? 19 : 38 ?>px !important; } /* the panel has .p-0 */
    </style>
<?php } ?>
<?php if ( ! $sq_assistant_drawer && $sq_assistant_collapsed ) { ?>
    <style id="sq_assistant_precollapse">
        /* the collapsed strip as it will look, before the script adds the class: no task texts squeezed into 45px */
        #sq_wrap .sq_col_side { flex: 0 0 45px !important; min-width: 45px !important; overflow: hidden; }
        #sq_wrap .sq_col_side .card-title,
        #sq_wrap .sq_col_side .sq_assistant li.sq_save_ajax,
        #sq_wrap .sq_col_side .sq_assistant li.sq_task div,
        #sq_wrap .sq_col_side .sq_assistant li h4,
        #sq_wrap .sq_col_side .sq_assistant li table,
        #sq_wrap .sq_col_side .sq_assistant li button,
        #sq_wrap .sq_col_side .sq_assistant div,
        #sq_wrap .sq_col_side .sq_assistant a { display: none; }
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

    /* on hover (.sq_side_hover, set by the script below with a short open delay and a close grace period, so the
       panel doesn't flicker), the panel inside the 45px strip slides out as a fixed overlay. The strip stays in the
       layout, so the page doesn't shift. z-index sits above the Squirrly Snippet close button (.sq-close-absolute is 10000) */
    #sq_wrap .sq_col_side.sq_col_side_collapsed.sq_side_hover .sq_sticky {
        position: fixed !important;
        top: var(--sq-overlay-top, 78px) !important; /* synced below the toolbar; drops to the admin bar once the toolbar scrolls away */
        right: 0;
        bottom: 0;
        width: 270px;
        height: auto;
        overflow-y: auto;
        align-self: stretch !important; /* flex-start would size the fixed box to its content instead of top-to-bottom */
        background: #fff;
        z-index: 10001;
        box-shadow: -3px 0 14px rgba(0, 0, 0, 0.12);
        animation: sqSideIn .18s ease-out both;
    }
    /* a short slide: the panel is under the mouse from the first frame, so it can't slip away from it */
    @keyframes sqSideIn { from { transform: translateX(24px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    #sq_wrap .sq_col_side.sq_col_side_collapsed.sq_side_hover .sq_assistant_toggle { text-align: right; padding: 8px 12px 0; }
    #sq_wrap .sq_col_side.sq_col_side_collapsed.sq_side_hover .card-title,
    #sq_wrap .sq_col_side.sq_col_side_collapsed.sq_side_hover .sq_assistant li.sq_save_ajax,
    #sq_wrap .sq_col_side.sq_col_side_collapsed.sq_side_hover .sq_assistant li.sq_task div,
    #sq_wrap .sq_col_side.sq_col_side_collapsed.sq_side_hover .sq_assistant li h4,
    #sq_wrap .sq_col_side.sq_col_side_collapsed.sq_side_hover .sq_assistant li table,
    #sq_wrap .sq_col_side.sq_col_side_collapsed.sq_side_hover .sq_assistant li button,
    #sq_wrap .sq_col_side.sq_col_side_collapsed.sq_side_hover .sq_assistant div,
    #sq_wrap .sq_col_side.sq_col_side_collapsed.sq_side_hover .sq_assistant a {
        display: block;
    }

    /* full-width buttons in the assistant sidebar and the task drawer (Focus Pages headers use col-10 offset-1) */
    #sq_wrap .sq_col_side .btn { width: 100%; max-width: 100%; flex: none; display: block; margin-left: 0 !important; margin-right: 0 !important; }

    /* Screens with task bullets (Focus Pages, Bulk SEO): the tasks open in a side drawer when a bullet is clicked,
       over the page, and the table keeps the full width the rest of the time */
    #sq_wrap .sq_col_side.sq_side_drawer { display: none !important; }
    #sq_wrap .sq_col_side.sq_side_drawer.sq_side_drawer_open {
        display: block !important;
        position: fixed;
        top: var(--sq-overlay-top, 78px);
        right: 0;
        bottom: 0;
        width: 360px !important;
        max-width: 92vw;
        min-width: 0 !important;
        flex: none !important;
        overflow-y: auto;
        z-index: 10001;
        background: #fff;
        box-shadow: -4px 0 18px rgba(0, 0, 0, 0.16);
        animation: sqDrawerIn .18s ease-out;
    }
    @keyframes sqDrawerIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
    #sq_wrap .sq_col_side.sq_side_drawer .sq_assistant_toggle { display: none; }
    #sq_wrap .sq_col_side.sq_side_drawer .sq_sticky { position: static !important; top: auto !important; }
    #sq_wrap .sq_col_side.sq_side_drawer .sq_side_drawer_close { position: sticky; top: 0; z-index: 2; text-align: right; padding: 8px 12px 0; background: #fff; }
    #sq_wrap .sq_col_side.sq_side_drawer .sq_side_drawer_close span { cursor: pointer; font-size: 22px; line-height: 1; color: #777; }
</style>
<script>
    (function ($) {
        var COOKIE     = 'sq_assistant';
        var collapsed  = <?php echo $sq_assistant_collapsed ? 'true' : 'false'; ?>;
        var toggleHtml = '<div class="sq_assistant_toggle">' +
            '<span class="sq_assistant_collapse" title="<?php echo esc_attr__( 'Collapse', 'squirrly-seo' ) ?>"><i class="dashicons-before dashicons-arrow-right-alt text-black-50"></i></span>' +
            '<span class="sq_assistant_open" title="<?php echo esc_attr__( 'Expand', 'squirrly-seo' ) ?>"><i class="dashicons-before dashicons-arrow-left-alt text-black-50"></i></span>' +
            '</div>';

        //Side drawer for the task bullets: open() from the bullet click handlers (focuspages.js, bulkseo.js)
        $.sq_assistantDrawer = {
            enabled: false,
            open: function () {
                if (this.enabled) {
                    $('#sq_wrap .sq_col_side.sq_side_drawer').addClass('sq_side_drawer_open').scrollTop(0);
                }
            },
            close: function () {
                $('#sq_wrap .sq_col_side.sq_side_drawer').removeClass('sq_side_drawer_open');
            }
        };

        $(function () {
            if ($('#sq_wrap .sq_show_assistant, #sq_wrap .sq_show_snippet').length) {
                $('#sq_wrap .sq_col_side').each(function () {
                    var $sticky = $(this).find('.sq_sticky').first();
                    if (!$sticky.length) {
                        return;
                    }
                    $(this).addClass('sq_side_drawer');
                    $sticky.prepend('<div class="sq_side_drawer_close"><span title="<?php echo esc_attr__( 'Close', 'squirrly-seo' ) ?>">&times;</span></div>');
                    $.sq_assistantDrawer.enabled = true;
                });

                if ($.sq_assistantDrawer.enabled) {
                    $(document).on('click', '.sq_side_drawer_close span', function () {
                        $.sq_assistantDrawer.close();
                    });
                    $(document).on('keydown', function (e) {
                        if (e.key === 'Escape') {
                            $.sq_assistantDrawer.close();
                        }
                    });
                    //a click outside the drawer closes it (a bullet click opens it again with its own tasks)
                    $(document).on('mousedown', function (e) {
                        if (!$(e.target).closest('.sq_col_side, .sq_show_assistant, .sq_show_snippet, .tooltip, .modal').length) {
                            $.sq_assistantDrawer.close();
                        }
                    });
                    //the column is out of the layout now: let the Focus Pages table take the full width
                    setTimeout(function () { $(window).trigger('resize'); }, 50);
                }
            }
            //a screen the server expected to use the drawer, but without bullets (no Focus Pages yet): show the column
            if (!$.sq_assistantDrawer.enabled) {
                $('#sq_assistant_predrawer').remove();
            }

            $('.sq_col_side').each(function () {
                var $side   = $(this);
                var $sticky = $side.find('.sq_sticky').first();
                if (!$sticky.length) {
                    return; //only sidebars that actually hold an assistant
                }
                if (!$sticky.children('.sq_assistant_toggle').length) {
                    $sticky.prepend(toggleHtml);
                }
                $side.toggleClass('sq_col_side_collapsed', collapsed && !$side.hasClass('sq_side_drawer'));
            });
            //the pre-collapse style was only to avoid a flash; the class now governs the state
            $('#sq_assistant_precollapse, #sq_assistant_pretoggle').remove();

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
                $(this).closest('.sq_col_side').removeClass('sq_side_hover').addClass('sq_col_side_collapsed');
                $.sq_setCookie(COOKIE, 'collapse');
                $(window).trigger('resize'); //let widgets like the Focus Pages table recompute against the new sidebar width
            });
            $(document).on('click', '.sq_assistant_open', function () {
                $(this).closest('.sq_col_side').removeClass('sq_col_side_collapsed sq_side_hover');
                $.sq_setCookie(COOKIE, 'open');
                $(window).trigger('resize');
            });
            //hover panel of the collapsed sidebar: open after a short pause (a passing mouse doesn't open it) and
            //close only after the mouse has been away for a moment, so a fast move or the panel edge doesn't flicker it
            var sqHoverTimer = null;
            $(document).on('mouseenter', '.sq_col_side_collapsed', function () {
                var $side = $(this);
                clearTimeout(sqHoverTimer);
                if (!$side.hasClass('sq_side_hover')) {
                    sqHoverTimer = setTimeout(function () { $side.addClass('sq_side_hover'); }, 120);
                }
            }).on('mouseleave', '.sq_col_side_collapsed', function () {
                var $side = $(this);
                clearTimeout(sqHoverTimer);
                sqHoverTimer = setTimeout(function () { $side.removeClass('sq_side_hover'); }, 300);
            });
        }
    })(jQuery);
</script>
