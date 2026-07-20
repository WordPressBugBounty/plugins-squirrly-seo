<?php
defined('ABSPATH') || die('Cheatin\' uh?');

class SQ_Controllers_Indexnow extends SQ_Classes_FrontController
{

    //Min seconds before the same URL may be submitted again (IndexNow 429s the same URL more than once per 24h).
    const INDEX_TIMELAPS = 86400;

    /** @var array of submitted posts */
    public $processed = array();

    /** @var array of URLs queued for deferred auto submission */
    public $deferred = array();

    public function __construct() {
        parent::__construct();

        if(!SQ_Classes_Helpers_Tools::getOption('sq_auto_indexnow')){
            return;
        }

        //Auto-Indexing
        $post_types = (array)SQ_Classes_Helpers_Tools::getOption('indexnow_post_type');
        if(!empty($post_types)) {
            //Only submit when the content actually changed in the editor
            add_action( 'post_updated', array( $this, 'postUpdated' ), 10, 3 );

            foreach ($post_types as $post_type) {
                add_filter("save_post_{$post_type}", array($this, 'savePost'), 10, 3);
                add_filter("bulk_actions-edit-{$post_type}", array($this, 'bulkOption'), 11, 1);
                add_filter("handle_bulk_actions-edit-{$post_type}", array($this, 'bulkActions'), 10, 3);
            }
        }

        add_filter( 'post_row_actions', array($this, 'postRowOption'), 10, 2 );
        add_filter( 'page_row_actions', array($this, 'postRowOption'), 10, 2 );
        add_filter( 'admin_init', array($this, 'postRowAction'));
    }

    function init()
    {
        $tab = preg_replace("/[^a-zA-Z0-9]/", "", SQ_Classes_Helpers_Tools::getValue('tab', 'submit'));

        if (method_exists($this, $tab)) {
	        if ( SQ_Classes_Helpers_Tools::userCan( 'sq_manage_snippet' ) ) {
				call_user_func(array($this, $tab));
	        }
        }

        $this->show_view('Indexnow/' . esc_attr(ucfirst($tab)));

    }

    /**
     * Called when action is triggered
     *
     * @return void
     */
    public function action()
    {
        parent::action();

        switch (SQ_Classes_Helpers_Tools::getValue('action')) {

            ///////////////////////////////////////////INDEX NOW
            case 'sq_seosettings_indexnow':

                if (!SQ_Classes_Helpers_Tools::userCan('sq_manage_settings')) {
                    return;
                }

                //reset the post types
                SQ_Classes_Helpers_Tools::saveOptions('indexnow_post_type', array());
                SQ_Classes_Helpers_Tools::saveOptions('indexnow_endpoints', array());

                //Save the settings
	            if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    SQ_Classes_ObjController::getClass('SQ_Models_Settings')->saveValues($_POST);
                }


                //show the saved message
                if (!SQ_Classes_Error::isError()) {
                    SQ_Classes_Error::setMessage(esc_html__("Saved", 'squirrly-seo'));
                }

                break;
            case 'sq_seosettings_indexnow_submit':

                if ( !SQ_Classes_Helpers_Tools::userCan('sq_manage_snippet') ) {
                    return;
                }

				//The calls are with POST and GET
	            if(isset($_POST['urls'])){
		            $urls = $_POST['urls'];
	            }elseif(isset($_GET['urls'])){
		            $urls = $_GET['urls'];
	            }

                if ( empty( $urls ) ) {
                    SQ_Classes_Error::setError(esc_html__("No URLs found. Please add URLs to index.", 'squirrly-seo'));
                }

				if(strpos($urls, "\n") !== false){
					$urls = array_map( 'trim', explode( "\n", $urls ));
				}elseif(strpos($urls, " ") !== false){
					$urls = array_map( 'trim', explode( " ", $urls ));
				}
	            $urls = array_map( 'esc_url_raw', (array)$urls);
	            $urls = array_map( 'urldecode', $urls);
	            $urls = array_unique( $urls );
                $urls = array_values( $urls );

                if ( ! $urls ) {
                    SQ_Classes_Error::setError(esc_html__("Invalid URLs provided. Please add valid URLs.", 'squirrly-seo'));
                }

                //Soft warning: pause while IndexNow keeps erroring, or skip URLs sent in the last 24h, so we don't trigger IndexNow 429.
                if ( $this->model->isSubmitPaused() ) {
                    SQ_Classes_Error::setError($this->model->getBackoffMessage(), 'warning');
                } elseif ( $this->model->wasSubmittedRecently( $urls, self::INDEX_TIMELAPS ) ) {
                    SQ_Classes_Error::setError(esc_html__("These URLs were already submitted in the last 24 hours and were not sent again to avoid rate limiting (429).", 'squirrly-seo'), 'warning');
                } else {
                    $result = SQ_Classes_ObjController::getClass('SQ_Models_Indexnow')->submitUrl( $urls , true);
                    if ( ! $result ) {
                        SQ_Classes_Error::setError(esc_html__("Failed to submit the URLs. Please try again.", 'squirrly-seo'));
                    }
                }

                $urls_number = count( $urls );
                if (!SQ_Classes_Error::isError()) {
                    SQ_Classes_Error::setMessage(sprintf(
                        _n(
                            'Successfully submitted %s URL.',
                            'Successfully submitted %s URLs.',
                            $urls_number,
                            'squirrly-seo'
                        ),
                        $urls_number)
                    );
                }


                break;
            case 'sq_seosettings_indexnow_clear':

                if ( !SQ_Classes_Helpers_Tools::userCan('sq_manage_settings') ) {
                    return;
                }

                $this->model->deleteLog();

                //show the saved message
                if (!SQ_Classes_Error::isError()) {
                    SQ_Classes_Error::setMessage(esc_html__("Log cleared", 'squirrly-seo'));
                }

                break;
            case 'sq_seosettings_indexnow_reset':

                if ( !SQ_Classes_Helpers_Tools::userCan('sq_manage_settings') ) {
                    return;
                }

                SQ_Classes_ObjController::getClass('SQ_Models_Indexnow')->resetIndexnowKey();

                //show the saved message
                if (!SQ_Classes_Error::isError()) {
                    SQ_Classes_Error::setMessage(esc_html__("Regenerated", 'squirrly-seo'));
                }

                break;
        }

    }


    public function bulkOption($actions){
        $actions['sq_indexnow'] = esc_html__( 'Submit to LLM Indexing', 'squirrly-seo' );
        return $actions;
    }

    public function bulkActions($redirect, $doaction, $object_ids){
        if ( $doaction !== 'sq_indexnow' || empty( $object_ids ) ) {
            return $redirect;
        }

        if ( !SQ_Classes_Helpers_Tools::userCan('sq_manage_snippet') ) {
            return $redirect;
        }

        $urls = [];
        foreach ( $object_ids as $object_id ) {
            $urls[] = get_permalink( $object_id );
        }

        $this->submitUrl( $urls, true );

        return $redirect;
    }

    /**
     * Action links for the post listing screens.
     *
     * @param array  $actions Action links.
     * @param object $post    Current post object.
     * @return array
     */
    public function postRowOption( $actions, $post ) {
        if ( !SQ_Classes_Helpers_Tools::userCan('sq_manage_snippet') ) {
            return $actions;
        }

        if ( $post->post_status !== 'publish' ) {
            return $actions;
        }

        $post_types = (array)SQ_Classes_Helpers_Tools::getOption('indexnow_post_type');
        if ( !in_array( $post->post_type, $post_types, true ) ) {
            return $actions;
        }

        $link = wp_nonce_url(
            add_query_arg(
                [
                    'action'  => 'sq_indexnow_post',
                    'indexnow_post_id' => $post->ID,
                    'method'  => 'indexnow_submit',
                ]
            ),
            'sq_indexnow_post'
        );

        $actions['indexnow_submit'] = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Submit to LLM', 'squirrly-seo' ) . '</a>';

        return $actions;
    }

    /**
     * Handle post row action link actions.
     *
     * @return void
     */
    public function postRowAction() {
        if ( !SQ_Classes_Helpers_Tools::getValue('action' ) == 'sq_indexnow_post' ) {
            return;
        }

        if ( !$post_id = absint(SQ_Classes_Helpers_Tools::getValue('indexnow_post_id')) ) {
            return;
        }

        if ( !SQ_Classes_Helpers_Tools::userCan('sq_manage_snippet') ) {
            return;
        }

        if ( ! wp_verify_nonce( SQ_Classes_Helpers_Tools::getValue( '_wpnonce' ), 'sq_indexnow_post' ) ) {
            return;
        }

        $this->submitUrl( get_permalink( $post_id ), true );

        wp_redirect( remove_query_arg( array('action', 'indexnow_post_id', 'method', '_wpnonce') ) );
        exit;
    }

    /**
     * When an existing post from a watched post type is updated, submit its URL only if
     * the content really changed. Trashing, untrashing, quick/bulk edit, status flips or
     * metadata-only saves must never reach IndexNow.
     *
     * @param  int     $post_id     Post ID.
     * @param  WP_Post $post_after  Post object after the update.
     * @param  WP_Post $post_before Post object before the update.
     *
     * @return void
     */
    public function postUpdated( $post_id, $post_after, $post_before ) {

        $post_types = (array) SQ_Classes_Helpers_Tools::getOption( 'indexnow_post_type' );
        if ( ! in_array( $post_after->post_type, $post_types, true ) ) {
            return;
        }

        //Trash/untrash and any other non-published state are not submissions.
        if ( $post_after->post_status !== 'publish' ) {
            return;
        }

        //Restoring a post from trash is not a content change.
        if ( $post_before->post_status === 'trash' ) {
            return;
        }

        //Quick Edit and Bulk Edit never touch the content itself.
        if ( $this->isInlineEdit() ) {
            return;
        }

        //A post published for the first time is always submitted.
        if ( $post_before->post_status === 'publish' && ! $this->isContentChanged( $post_after, $post_before ) ) {
            return;
        }

        $this->savePost( $post_id, $post_after, true );

    }

    /**
     * Submit a post that was inserted directly as published (no previous revision to compare with).
     *
     * @param  int     $post_id Post ID.
     * @param  WP_Post $post    Post object.
     * @param  bool    $update  Whether this was an update of an existing post.
     *
     * @return void
     */
    public function savePost( $post_id, $post, $update = false ) {

        //Updates are handled by postUpdated() which can compare the content.
        if ( $update && ! doing_action( 'post_updated' ) ) {
            return;
        }

        if ( in_array( $post_id, $this->processed, true ) ) {
            return;
        }

        if ( $post->post_status !== 'publish' ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( $this->isInlineEdit() ) {
            return;
        }

        if (!SQ_Classes_ObjController::getClass('SQ_Models_Post')->isIndexable($post_id)) {
            return;
        }

        if ( !$url = apply_filters( 'sq_publish_url', get_permalink($post), $post ) ) {
            return;
        }

        $this->queueUrl( $url );
        $this->processed[] = $post_id;

    }

    /**
     * Whether the current request comes from Quick Edit or Bulk Edit in the posts list.
     *
     * @return bool
     */
    private function isInlineEdit() {
        $action = SQ_Classes_Helpers_Tools::getValue( 'action' );

        return in_array( $action, array( 'inline-save', 'inline-save-tax', 'bulk-edit', 'edit' ), true );
    }

    /**
     * Compare the fields that make up the page content as seen by crawlers.
     *
     * @param  WP_Post $post_after
     * @param  WP_Post $post_before
     *
     * @return bool
     */
    private function isContentChanged( $post_after, $post_before ) {
        $fields = array( 'post_title', 'post_content', 'post_excerpt' );

        foreach ( $fields as $field ) {
            if ( $post_after->$field !== $post_before->$field ) {
                return true;
            }
        }

        return apply_filters( 'sq_indexnow_content_changed', false, $post_after, $post_before );
    }

    /**
     * Queue an automatic submission to run on 'shutdown' so the post save isn't delayed.
     */
    private function queueUrl( $url ) {
        if ( empty( $this->deferred ) ) {
            add_action( 'shutdown', array( $this, 'submitDeferred' ), 20 );
        }

        $this->deferred[] = $url;
    }

    /**
     * Submit the queued URLs after the page response is sent, capturing the real status.
     */
    public function submitDeferred() {
        if ( empty( $this->deferred ) ) {
            return;
        }

        $urls           = array_values( array_unique( $this->deferred ) );
        $this->deferred = array();

        //Close the connection first so the blocking submit adds no perceptible delay.
        if ( function_exists( 'fastcgi_finish_request' ) ) {
            fastcgi_finish_request();
        }

        foreach ( $urls as $url ) {
            $this->submitUrl( $url, false );
        }
    }

	/**
	 * Submit an URL to Auto-Indexing
	 * @param string $url
	 * @param bool $manual
	 *
	 * @return false
	 */
    private function submitUrl( $url, $manual ) {

        if ( !$url = apply_filters( 'sq_submit_url', $url, $manual ) ) {
            return false;
        }

        //Pause every submission while IndexNow keeps returning errors (429/403/422/...) so we stop hitting it.
        if ( $this->model->isSubmitPaused() ) {
            if ( $manual ) {
                SQ_Classes_Error::saveMessage( $this->model->getBackoffMessage(), 'warning' );
            }

            return false;
        }

        //Skip URLs already submitted in the last 24h to avoid IndexNow 429; warn on manual submits.
        if ( $this->model->wasSubmittedRecently( $url, self::INDEX_TIMELAPS ) ) {
            if ( $manual ) {
                SQ_Classes_Error::saveMessage( esc_html__( 'This page was already submitted to LLM Indexing in the last 24 hours and was not sent again to avoid rate limiting (429).', 'squirrly-seo' ), 'warning' );
            }

            return false;
        }

        $submitted = $this->model->submitUrl( $url, $manual );

        if ( ! $manual ) {
            return $submitted;
        }

        $count = is_array( $url ) ? count( $url ) : 1;

        if ( $submitted ) {
            SQ_Classes_Error::saveMessage(sprintf(_n( '%s page submitted to LLM Indexing.', '%s pages submitted to LLM Indexing.', $count, 'squirrly-seo' ), $count), 'notice notice-success');
        }else{
            SQ_Classes_Error::saveMessage(esc_html__( 'Error submitting page to LLM Indexing.','squirrly-seo' ), 'notice notice-error');
        }

        return $submitted;
    }
}
