<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
if ( ! isset( $view ) ) {
	return;
}

/**
 * Bulk "Add selected to Briefcase" bar for the Keyword Research results (Research and History)
 * The rows carry a .sq_research_bulk_input checkbox; global.js (sq_bulkBriefcase) sends the selection.
 */
?>
<div class="sq_research_bulk d-flex flex-row flex-wrap align-items-center m-0 p-0 my-2">
    <label class="m-0 mr-3 small font-weight-bold" style="cursor: pointer">
        <input type="checkbox" class="sq_research_bulk_all m-0 mr-1" style="vertical-align: middle"/>
		<?php echo esc_html__( "Select all", 'squirrly-seo' ); ?>
    </label>
	<?php if ( isset( $view->labels ) && ! empty( $view->labels ) ) { ?>
        <select class="sq_research_bulk_label m-0 mr-2 p-1" style="min-width: 160px">
            <option value=""><?php echo esc_html__( "No label", 'squirrly-seo' ); ?></option>
			<?php foreach ( $view->labels as $label ) {
				if ( ! isset( $label->id ) || (int) $label->id == 0 ) {
					continue;
				} ?>
                <option value="<?php echo (int) $label->id ?>"><?php echo esc_html( $label->name ) ?></option>
			<?php } ?>
        </select>
	<?php } ?>
    <button type="button" class="sq_research_bulk_add btn btn-sm btn-primary m-0" data-empty="<?php echo esc_attr__( "Select at least one keyword.", 'squirrly-seo' ) ?>">
		<?php echo esc_html__( "Add selected to Briefcase", 'squirrly-seo' ); ?>
    </button>
</div>
