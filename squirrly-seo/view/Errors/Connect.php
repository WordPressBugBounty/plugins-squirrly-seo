<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
if ( ! isset( $view ) ) {
	return;
}

/**
 * Login/Register view
 *
 */
?>
<?php
SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' )->loadMedia( 'bootstrap-reboot' );
SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' )->loadMedia( 'bootstrap' );
SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' )->loadMedia( 'fontawesome' );
SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' )->loadMedia( 'global' );
SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' )->loadMedia( 'navbar' );

$page = apply_filters( 'sq_page', SQ_Classes_Helpers_Tools::getValue( 'page', '' ) );

//name the screen that was opened and say what it does: before connecting, every one of them showed the same card
$sq_gated = array(
	'sq_checkseo'     => esc_html__( "It checks your site and gives you the next SEO goals to work on.", "squirrly-seo" ),
	'sq_audits'       => esc_html__( "It audits your pages, scores the site and lists the tasks to fix.", "squirrly-seo" ),
	'sq_aivisibility' => esc_html__( "It shows the visits that AI answer engines like ChatGPT, Perplexity and Gemini send to your site.", "squirrly-seo" ),
	'sq_research'     => esc_html__( "It finds the keywords people search for in your niche, with search volume and competition.", "squirrly-seo" ),
	'sq_focuspages'   => esc_html__( "It shows what each of your most important pages still needs to rank, task by task.", "squirrly-seo" ),
	'sq_rankings'     => esc_html__( "It shows the Google position, clicks and impressions of your keywords.", "squirrly-seo" ),
);
$sq_gate = $page;
if ( $page == 'sq_audits' && SQ_Classes_Helpers_Tools::getValue( 'tab' ) == 'aivisibility' ) {
	$sq_gate = 'sq_aivisibility';
}
$sq_gate_title = '';
if ( isset( $sq_gated[ $sq_gate ] ) ) {
	$sq_mainmenu = SQ_Classes_ObjController::getClass( 'SQ_Models_Menu' )->getMainMenu();
	if ( isset( $sq_mainmenu[ $sq_gate ]['title'] ) ) {
		$sq_gate_title = $sq_mainmenu[ $sq_gate ]['title'];
	}
}
?>
<div id="sq_wrap">
	<?php $view->show_view( 'Blocks/Toolbar' ); ?>
	<?php do_action( 'sq_notices' ); ?>
    <div class="d-flex flex-row my-0 p-0 m-0">
        <div class="sq_flex flex-grow-1 mx-0 px-2">
            <div class="mx-auto">
				<?php if ( $sq_gate_title <> '' ) { ?>
                    <div class="sq_breadcrumbs col-8 mx-auto mt-4 mb-3" style="min-width: 600px; padding: 0 26px;">
                        <a href="<?php echo esc_url( SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_dashboard' ) ) ?>"><i class="fa-solid fa-house text-black-50"></i></a><i class="text-black-50 mx-1">/</i><?php echo esc_html( $sq_gate_title ) ?>
                    </div>
				<?php } ?>
                <div class="col-8 mx-auto my-2 p-2 offset-2 bg-white rounded-top" style="min-width: 600px;">
                    <div class="col-12 text-center m-2 p-0 e-connect">
                        <div class="mt-3 mb-4 mx-auto e-connect-link">
                            <div class="p-0 mx-2 float-left" style="width:48px;">
                                <div class="sq_wordpress_icon m-0 p-0" style="width: 48px; height: 48px;">
                                </div>
                            </div>
                            <div class="p-0 mx-2 float-right" style="width:48px;">
                                <div class="sq_logo sq_logo_50 m-0 p-0"></div>
                            </div>
                        </div>
                        <h4><?php echo esc_html__( "Connect Your Site to Squirrly Cloud", "squirrly-seo" ); ?></h4>
						<?php if ( $sq_gate_title <> '' ) { ?>
                            <div class="small my-2"><?php echo sprintf( esc_html__( "%s opens after you connect this site to a free Squirrly Cloud account.", "squirrly-seo" ), '<strong>' . esc_html( $sq_gate_title ) . '</strong>' ) ?></div>
                            <div class="small text-black-50"><?php echo $sq_gated[ $sq_gate ]; //phpcs:ignore ?></div>
						<?php } else { ?>
                            <div class="small"><?php echo sprintf( esc_html__( "Get Access to the Non-Human SEO Consultant, Focus Pages, GEO Audits and all our features %s by creating a free account", "squirrly-seo" ), '<br/>' ) ?></div>
						<?php } ?>
                    </div>

					<?php SQ_Classes_ObjController::getClass( 'SQ_Core_Blocklogin' )->init(); ?>
                </div>
            </div>

        </div>

    </div>
</div>
