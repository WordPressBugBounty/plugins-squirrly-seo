<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class SQ_Models_Audits {

	/**
	 * @var array todos
	 */
	protected $_todo = array();

	/**
	 * @var SQ_Models_Domain_AuditPage
	 */
	protected $_auditpage;

	public function getTasks() {
		return array(
			'blogging'  => array(
				'RecentPosting' => array(
					'complete'     => false,
					'title'        => esc_html__( "Recent blog posting?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '<div class="sq_list_success">' . esc_html__( "Your latest post is", 'squirrly-seo' ) . ' %s ' . esc_html__( "day(s) old.", 'squirrly-seo' ) . '</div>',
					'fail_list'    => '<div class="sq_list_error">' . esc_html__( "Your latest post is", 'squirrly-seo' ) . ' %s ' . esc_html__( "day(s) old.", 'squirrly-seo' ) . '</div>',
					'description'  => sprintf( esc_html__( "Answer and search engines favor sites that publish regularly. This check looks only at your blog-post sitemap to see how recently you posted - it's lightweight and doesn't crawl your whole site. %s Keep a steady publishing rhythm; even one quality post per month signals an active, maintained site. %s Use SEO Goals and the Keyword Research tool to plan what to publish next.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Consistency beats volume. A predictable cadence of useful posts keeps your site fresh in the eyes of both Google and AI answer engines.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Publish new blog posts regularly to keep your site active", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Optimization' => array(
					'complete'     => false,
					'title'        => esc_html__( "Average Content Optimization", 'squirrly-seo' ),
					'success'      => '%s%%. ' . esc_html__( "Great!", 'squirrly-seo' ),
					'fail'         => '%s%%. ' . esc_html__( "hmm...", 'squirrly-seo' ),
					'success_list' => '',
					'fail_list'    => '',
					'description'  => sprintf( esc_html__( "Optimizing a page means writing it so both people and engines clearly understand what it answers, not stuffing in keywords. %s Start by finding the words and questions your audience actually searches for; Squirrly's Keyword Research shows you exactly that. %s Then write naturally with the Live Assistant guiding you, so your page becomes the clearest answer available.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Optimization is NOT about stuffing in keywords. It's about writing the page in such a way that Search Engine bots and Humans alike will easily understand that the page is exactly about the topic they were searching for. Use the Live Assistant from Squirrly SEO to get the job done with ease.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Use tools like Squirrly Keyword Research and Squirrly Live Assistant to optimize your content", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistants' ),
				),
				'DcPublisher'  => array(
					'complete'     => false,
					'title'        => esc_html__( "DcPublisher Meta", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages without DcPublisher meta", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => esc_html__( "Dublin Core is an older set of tags that note who published your content. It's a minor signal today and won't make or break your visibility in search or AI answers, but it doesn't hurt to include it. Squirrly SEO can add it for you.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add the meta DcPublisher tag in the page's header", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
			),
			'traffic'   => array(
				'TopTen'    => array(
					'complete'     => false,
					'title'        => esc_html__( "Top Ten Pages This Week", 'squirrly-seo' ),
					'success'      => '',
					'fail'         => '',
					'success_list' => '<div class="sq_list_success">%s</div>',
					'fail_list'    => '<div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "This shows the pages that brought you the most visitors in the last week, when there's enough Google Analytics data. %s Pages that consistently attract visitors are seen as valuable by search and AI engines. %s Aim to keep your key pages drawing steady traffic.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
				),
				'PageViews' => array(
					'complete'     => false,
					'title'        => esc_html__( "Page Traffic", 'squirrly-seo' ),
					'success'      => '{total} ' . esc_html__( " total visits / mo.", 'squirrly-seo' ),
					'fail'         => '',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages with low traffic", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "This looks at the overall traffic reaching your pages. %s Be present where your audience already is, such as marketplaces, YouTube, Pinterest or an email list, so traffic keeps coming back on its own. %s Rank for topics with low competition; Squirrly's Keyword Research helps you spot the easy wins. %s Use SEO Goals to help new pages rank faster.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Get each person who arrives on your site once to leave something that you can use later on to bring them to your site again. You can use Facebook Pixel and then retarget them, you can make them subscribe to Desktop Notifications to receive push notifications, you can have them download an app, subscribe to a newsletter, etc. Sometimes it's best if you can create clever funnels that will ensure that any person may start following you on multiple such channels.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Try to gain organic traffic to your site's pages", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
			),
			'seo'       => array(
				'NoIndex'                   => array(
					'complete'     => false,
					'title'        => esc_html__( "Visible for search engines?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages with noindex", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "If a page is set to no-index, you're telling search and AI engines to ignore it, so it can't appear in results or be used in answers. %s Make sure the pages you want found are not set to no-index; Squirrly SEO lets you control this per page with a click. %s If a page is meant to stay private that's fine, just remove it from this audit.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Some pages (like thank-you or login pages) are fine to keep hidden. For everything you want found or quoted by AI, make sure no-index is off.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add the correct meta robots tag in the pages", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'NoFollow'                  => array(
					'complete'     => false,
					'title'        => esc_html__( "Followed by search engines?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages with nofollow", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "A no-follow tag tells search and AI crawlers not to follow the links on a page, which can stop them from discovering and connecting your content. %s Make sure the pages you want fully explored are not set to no-follow; Squirrly SEO lets you control this per page with no code.%s", 'squirrly-seo' ), '<ul><li>', '</li></ul>' ),
					'protip'       => esc_html__( "A few pages (like Contact or Terms) are fine as no-follow. For your main content, keep links followable so engines can map your site.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add the correct meta robots tag in the pages", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'SafeBrowsing'              => array(
					'complete'     => false,
					'title'        => esc_html__( "Is your site Safe Browsing?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '',
					'description'  => sprintf( esc_html__( "If Google flags your site as unsafe, browsers warn visitors away and search and AI engines stop trusting and surfacing your content. %s Scan your site, remove any malware, and keep WordPress and your plugins updated. %s You can check your current status anytime with Google's Safe Browsing tool.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "This is top priority if you're flagged: browsers will block visitors and your visibility in both search and AI results drops sharply.", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Speed'                     => array(
					'complete'     => false,
					'title'        => esc_html__( "Page load time", 'squirrly-seo' ),
					'success'      => '{total}' . 's ' . esc_html__( "average is a good time" ),
					'fail'         => '{total}' . 's ' . esc_html__( "average is slow" ),
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The slow pages are", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "How fast your pages load affects whether people stay, and slow pages get pushed down in search and overlooked by AI answer engines. %s Compress your images (a tool like ShortPixel shrinks them a lot without losing quality) and turn on caching or a speed plugin. %s Then check your score with Google PageSpeed Insights to see what to improve next.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Faster pages mean more engagement, lower bounce rates, and better visibility in both search and AI answers.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Optimize your site's speed", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'DuplicateTitles'           => array(
					'complete'     => false,
					'title'        => esc_html__( "Duplicate Titles", 'squirrly-seo' ),
					'success'      => esc_html__( "No duplicate titles.", 'squirrly-seo' ),
					'fail'         => esc_html__( "We found duplicates.", 'squirrly-seo' ),
					'success_list' => '<div class="sq_list_success"><span class="text-primary">' . esc_html__( "Great!", 'squirrly-seo' ) . '</span> ' . esc_html__( "The pages on your site have unique title tags.", 'squirrly-seo' ) . '</div>',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The Pages with Duplicate Titles are", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "When two pages share the same title, search engines and AI answer engines can't tell which one to show, so neither gets surfaced or cited well. %s Give every page its own clear title that says exactly what it covers and what question it answers. %s Squirrly SEO's Patterns feature keeps every title unique automatically, with no code needed. It's free.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "A clear, unique title is the first thing Google and AI tools like ChatGPT read to decide what your page answers. Let Squirrly SEO keep them all unique for you.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add different titles to each page. You can do it manually or use SEO tools (like Squirrly) for that.", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'DuplicateDescription'      => array(
					'complete'     => false,
					'title'        => esc_html__( "Duplicate Descriptions", 'squirrly-seo' ),
					'success'      => esc_html__( "No duplicate descriptions.", 'squirrly-seo' ),
					'fail'         => esc_html__( "We found duplicates.", 'squirrly-seo' ),
					'success_list' => '<div class="sq_list_success"><span class="text-primary">' . esc_html__( "Great!", 'squirrly-seo' ) . '</span> ' . esc_html__( "The pages on your site have unique meta descriptions.", 'squirrly-seo' ) . '</div>',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The Pages on which we found duplicates are", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "If several pages use the same description, search engines and AI answers can't tell them apart and your pages end up competing with each other. %s Write a short, unique summary for each page that previews the answer it gives. %s Squirrly SEO can generate a unique description for every page automatically.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Your description is the short summary AI answers and Google snippets often quote. Use Squirrly SEO to keep each one unique and clear.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add different description to each page. You can do it manually or use SEO tools (like Squirrly) for that.", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'EmptyTitles'               => array(
					'complete'     => false,
					'title'        => esc_html__( "Empty Titles", 'squirrly-seo' ),
					'success'      => esc_html__( "All pages have titles.", 'squirrly-seo' ),
					'fail'         => esc_html__( "There are some pages without title.", 'squirrly-seo' ),
					'success_list' => '<div class="sq_list_success"><span class="text-primary">' . esc_html__( "Great!", 'squirrly-seo' ) . '</span> ' . esc_html__( "all the pages on your site have the title tag defined :-)", 'squirrly-seo' ) . '</div>',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages with empty Title tags are", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "A page with no title is almost invisible. Search engines and AI answer engines have nothing to read to understand what it's about, so it rarely gets shown. %s Make sure every page has a clear title stating its main topic. %s Squirrly SEO fills in any missing titles automatically with its Patterns feature.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Never leave a page without a title; it's the single most important label both Google and AI engines read. Squirrly SEO fills in any that are missing.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add a Title tag to each page in your site.", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'EmptyDescription'          => array(
					'complete'     => false,
					'title'        => esc_html__( "Empty Descriptions", 'squirrly-seo' ),
					'success'      => esc_html__( "All articles have description.", 'squirrly-seo' ),
					'fail'         => esc_html__( "There are some pages without description.", 'squirrly-seo' ),
					'success_list' => '<div class="sq_list_success"><span class="text-primary">' . esc_html__( "Great!", 'squirrly-seo' ) . '</span> ' . esc_html__( "all the pages on your site have meta description", 'squirrly-seo' ) . '</div>',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages with empty description are", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "When a page has no description, search engines and AI tools have to guess your summary, and the guess is usually a poor one that gets ignored. %s Add a short, clear description to every page that sums up what it answers. %s Squirrly SEO can write descriptions automatically for any page that's missing one.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Don't let engines guess your summary. Squirrly SEO writes a clear description for any page that's missing one. For this to work, the page needs to have at least one full paragraph of text inside the page, so that the patterns system (found in snippets and Bulk SEO) can write it.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add meta description to each page in your site.", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Title'                     => array(
					'complete'     => false,
					'title'        => esc_html__( "Do you have a title tag?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ),
					'fail'         => esc_html__( "No" ),
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages without title tag are", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Your title is the headline both people and AI answer engines use to decide if your page is worth reading. Without one, your page can't really be understood or surfaced. %s On WordPress, Squirrly SEO makes sure every page has a clear title and lets you fine-tune it, with no code.%s", 'squirrly-seo' ), '<ul><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Platforms like Shopify handle this aspect with their default engine.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add a Title tag to this page of your site", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Description'               => array(
					'complete'     => false,
					'title'        => esc_html__( "Do you have a meta description?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ),
					'fail'         => esc_html__( "No" ),
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages without description meta are", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "The description is the short summary shown under your title in Google and often reused in AI answers. A weak or missing one means people scroll right past you. %s Write it like an honest, one-line preview of what the page answers. %s Squirrly SEO can create and customize these for every page automatically.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Platforms like Shopify handle this with their default engines.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add meta description to this page of your site", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Keywords'                  => array(
					'complete'     => false,
					'title'        => esc_html__( "Meta Keyword", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ),
					'fail'         => esc_html__( "No keywords.", 'squirrly-seo' ),
					'success_list' => '<div class="sq_list_success_title">' . esc_html__( "Your keywords are", 'squirrly-seo' ) . ':</div><div class="sq_list_success">%s</div>',
					'fail_list'    => '',
					'description'  => esc_html__( "Knowing the main topic of each page helps search engines and AI answer engines match your content to what people actually ask. Focus each page on one clear topic and the questions around it; that matters far more than a long list of keywords.", 'squirrly-seo' ),
					'protip'       => esc_html__( "Pick topics people are actively searching and asking about. Squirrly's Keyword Research shows you exactly what they want to know.", 'squirrly-seo' ),
					'solution'     => '',
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Canonical'                 => array(
					'complete'     => false,
					'title'        => esc_html__( "Canonical Link", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages without canonical meta", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "A canonical link tells search engines and AI engines which version of a page is the original, so your content gets full credit instead of looking like a duplicate. %s It matters most when the same content lives at more than one address, or when you republish a post somewhere else like Medium. %s Squirrly SEO's Bulk SEO lets you set this for your pages without touching any code.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Platforms like Shopify handle this with their default engine. On WordPress you can use Squirrly SEO to control canonical links and make sure you avoid having duplicate content.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add canonical meta link in the page header", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Jsonld'                    => array(
					'complete'     => false,
					'title'        => esc_html__( "Meta Json-LD?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages without Json-LD meta", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Structured data (JSON-LD) hands engines your information already organized, so they can understand and reuse it. That makes you far more likely to be quoted in AI answers and shown as a rich result. %s Squirrly SEO adds this for you automatically, including full details for WooCommerce products.%s", 'squirrly-seo' ), '<ul><li>', '</li></ul>' ),
					'protip'       => esc_html__( "On WordPress you can use Squirrly SEO to add the Json-LD Structured data. Squirrly will automatically structure the information from all your products if you use Woocommerce plugin for eCommerce.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Make sure you activated JSON-LD Structured Data in All Features", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Encoding'                  => array(
					'complete'     => false,
					'title'        => esc_html__( "Page Encoding", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages without encoding meta", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Page encoding tells browsers and crawlers how to read your text, so letters, symbols and other languages don't turn into garbled characters that confuse readers and AI engines. %s It's a small technical detail, and Squirrly SEO sets it for all your pages automatically.%s", 'squirrly-seo' ), '<ul><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Platforms like Shopify handle this with their default engine. On WordPress you can use Squirrly SEO  to get encoding specified for all your pages. Without specifying the encoding, search engines such as Google will be more likely to suggest other pages and rank other pages that DO have the specification made.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add the meta encoding tag in the page's header", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Sitemap'                   => array(
					'complete'     => false,
					'title'        => esc_html__( "Does your site have a feed or sitemap?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '',
					'description'  => sprintf( esc_html__( "A sitemap is a simple map of your site that helps search and AI engines find and understand all your pages quickly, including your newest content. %s Make sure visitors and bots can reach it, usually at /sitemap.xml. %s Squirrly SEO generates a complete sitemap and feed for your whole site automatically, for free.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Your feeds and sitemaps should contain the date when your content was published and last updated. This is super important for Google to know, as it's always looking to surface fresh content to people who search on search engines. PLUS, having this gives you the opportunity to show up when users of Google say they want to see only results from the last week. If you had anything published during the last week, these people will see it and you will gain traffic.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add a RSS feed and Sitemap to your site", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Robots'                    => array(
					'complete'     => false,
					'title'        => esc_html__( "Does your site have a robots.txt file?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '',
					'description'  => sprintf( esc_html__( "A robots.txt file tells crawlers, including AI crawlers, which parts of your site they're allowed to read. A correct one makes sure your important pages can be found and cited. %s Squirrly SEO can create and edit this file for you, so you decide exactly what search engines and AI engines can access.%s", 'squirrly-seo' ), '<ul><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Platforms like Shopify handle this with their default engine. On WordPress you can use Squirrly SEO  to create and customize your robots.txt", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add robots.txt file in your site", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Viewport'                  => array(
					'complete'     => false,
					'title'        => esc_html__( "Meta Viewport", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages without viewport meta", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "The viewport setting makes your pages display correctly on phones and tablets. Search and AI engines favor content that works well on mobile, since that's where most people read. %s A good responsive theme, or Squirrly SEO, takes care of this for you.%s", 'squirrly-seo' ), '<ul><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Platforms like Shopify handle this with their default engine. On WordPress, you need to make sure the WordPress theme you buy is responsive and has this definition.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add the meta viewport tag in the page's header", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Gzip'                      => array(
					'complete'     => false,
					'title'        => esc_html__( "Site optimized for speed?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages without gzip", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Compression (GZIP) makes your pages download faster by sending them in a smaller, zipped form, and faster pages are favored by search and AI engines. %s It's switched on at your web server level. %s Ask your host or developer to enable it, or use a performance plugin that turns it on for you.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Setting this up saves 50% to 80% bandwidth, which will make all your pages load a lot faster.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Use gzip to increase your site's speed", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'DuplicateOGMetas'          => array(
					'complete'     => false,
					'title'        => esc_html__( "Duplicate Open Graph Tags?", 'squirrly-seo' ),
					'success'      => esc_html__( "No duplicates", 'squirrly-seo' ),
					'fail'         => esc_html__( "We found some ...", 'squirrly-seo' ),
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages with duplicate Open Graph metas", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Duplicate tags in your page's code confuse search and AI engines about which information is correct, which can hurt how your page is shown and shared. %s These usually appear when two plugins add the same tags. %s Squirrly SEO removes duplicate tags from all your pages automatically, with no work from you.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "On WordPress you can use Squirrly SEO to Remove Duplicate Meta codes from all your pages. It removes them automatically. No work on your behalf.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Make sure you don't have duplicate meta tags in your site's header", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'DuplicateTCMetas'          => array(
					'complete'     => false,
					'title'        => esc_html__( "Duplicate Twitter Card Tags?", 'squirrly-seo' ),
					'success'      => esc_html__( "No duplicates", 'squirrly-seo' ),
					'fail'         => esc_html__( "We found some ...", 'squirrly-seo' ),
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages with duplicate Twitter Card metas", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Duplicate tags in your page's code confuse search and AI engines about which information is correct, which can hurt how your page is shown and shared. %s These usually appear when two plugins add the same tags. %s Squirrly SEO removes duplicate tags from all your pages automatically, with no work from you.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "On WordPress you can use Squirrly SEO to Remove Duplicate Meta codes from all your pages. It removes them automatically. No work on your behalf.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Make sure you don't have duplicate meta tags in your site's header", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'DuplicateTitleMetas'       => array(
					'complete'     => false,
					'title'        => esc_html__( "Duplicate Title Tags?", 'squirrly-seo' ),
					'success'      => esc_html__( "No duplicates", 'squirrly-seo' ),
					'fail'         => esc_html__( "We found some ...", 'squirrly-seo' ),
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages with duplicate Title metas", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Duplicate tags in your page's code confuse search and AI engines about which information is correct, which can hurt how your page is shown and shared. %s These usually appear when two plugins add the same tags. %s Squirrly SEO removes duplicate tags from all your pages automatically, with no work from you.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "On WordPress you can use Squirrly SEO to Remove Duplicate Meta codes from all your pages. It removes them automatically. No work on your behalf.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Make sure you don't have duplicate meta tags in your site's header", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'DuplicateDescriptionMetas' => array(
					'complete'     => false,
					'title'        => esc_html__( "Duplicate Description Tags?", 'squirrly-seo' ),
					'success'      => esc_html__( "No duplicates", 'squirrly-seo' ),
					'fail'         => esc_html__( "We found some ...", 'squirrly-seo' ),
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages with duplicate Description metas", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Duplicate tags in your page's code confuse search and AI engines about which information is correct, which can hurt how your page is shown and shared. %s These usually appear when two plugins add the same tags. %s Squirrly SEO removes duplicate tags from all your pages automatically, with no work from you.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "On WordPress you can use Squirrly SEO to Remove Duplicate Meta codes from all your pages. It removes them automatically. No work on your behalf.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Make sure you don't have duplicate meta tags in your site's header", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),

			),
			'social'    => array(
				'TopTenSocials' => array(
					'complete'     => false,
					'title'        => esc_html__( "Top Shared Pages", 'squirrly-seo' ),
					'success'      => '',
					'fail'         => '',
					'success_list' => '<div class="sq_list_success">%s</div>',
					'fail_list'    => '<div class="sq_list_success">%s</div>',
					'description'  => sprintf( esc_html__( "This shows the pages people shared most on social media. %s Shares act as signals of trust and popularity; the more your content is shared, the more search and AI engines treat it as worth surfacing. %s So make your best content easy and rewarding to share.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => sprintf( esc_html__( "Find proven methods for sharing on social media inside our free 10,000 Visits from Social Media training. More courses on social media are available within %s Education Cloud PLUS %s: the Premiere education platform of Squirrly.", 'squirrly-seo' ), '<a href="https://www.squirrly.co/learning/education-cloud/" target="_blank" >', '</a>' ),
				),
				'Shares'        => array(
					'complete'     => false,
					'title'        => esc_html__( "Shares", 'squirrly-seo' ),
					'success'      => '',
					'fail'         => '',
					'success_list' => '<div class="sq_list_success">%s</div>',
					'fail_list'    => '<div class="sq_list_success">%s</div>',
					'description'  => sprintf( esc_html__( "Social shares are signals that real people find your content useful. %s Make it easy for your audience to follow and share you, and connect with the customers and subscribers you already have on the platforms they use. %s Run the occasional giveaway or campaign to get your best pages in front of more people.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "All the shares and likes that your fans will give your pages will contribute to the total number of shares from social media (social signals). When Google’s algorithm starts “seeing” that people share your pages on social media, it will consider that your site is becoming popular and will increase its rankings.", 'squirrly-seo' ),
					'solution'     => esc_html__( "You have to share your articles with your fans", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'ShareButtons'  => array(
					'complete'     => false,
					'title'        => esc_html__( "Share Buttons in your articles?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages without share buttons", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Share buttons let visitors spread your content in one click, building the social signals that boost trust. %s Place them somewhere easy to spot. %s Choose a lightweight option, since slow-loading buttons can hurt your page speed more than they help.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "All there is to it is: make the buttons obvious, so people can easily find them. Make sure they don't slow your site down. Make sure they look great on mobile.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add Social Share buttons in your articles", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'FollowButtons' => array(
					'complete'     => false,
					'title'        => esc_html__( "Social 'Follow me' Buttons?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages without social buttons", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Follow buttons let visitors connect with you on social media, which builds trust and brings people back to your site. %s Add links to your profiles, ideally in the footer where people expect to find them.%s", 'squirrly-seo' ), '<ul><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Place the buttons in your site's footer, to make sure they're always accessible. Web users are used to finding them there when they wish to connect to brands on social media.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add links to your Social Media profiles to strengthen social signals and keep readers engaged.", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'OpenGraph'     => array(
					'complete'     => false,
					'title'        => esc_html__( "Open Graph protocol?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages without Open Graph metas", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Open Graph tags control how your page looks when it's shared or shown as a preview: the title, description and image people see. Good previews get far more clicks. %s On WordPress, Squirrly SEO sets all of these for you automatically, including the preview image. %s Just give each page a clear title, description and image.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Fixing this will improve Click Through Rates on Facebook, LinkedIN. Guaranteed. Make sure you use this to control how your pages look on social media when people share them.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add the meta Open Graph tag in your page's header.", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'TwitterCard'   => array(
					'complete'     => false,
					'title'        => esc_html__( "Twitter Card?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "The pages without Twitter Card metas", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Twitter Cards control how your page looks when shared on X/Twitter: the title, description and image in the preview. %s On WordPress, Squirrly SEO fills these in for you automatically. %s Just give each page a clear title, description and image.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Fixing this will improve Click Through Rates on Twitter. Guaranteed. Make sure you use this to control how your pages look on social media when people share them.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add Twitter Card to make your articles look better on Twitter.", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),

			),
			'inbound'   => array(

				'MozLinks'      => array(
					'complete'     => false,
					'title'        => esc_html__( "Moz Backlinks", 'squirrly-seo' ),
					'success'      => '{total} ' . esc_html__( "link(s)", 'squirrly-seo' ),
					'fail'         => '{total} ' . esc_html__( "link(s)", 'squirrly-seo' ),
					'success_list' => '<div class="sq_list_success_title">' . esc_html__( "Moz Backlinks Count", 'squirrly-seo' ) . ':</div><div class="sq_list_success">%s</div>',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "Moz Backlinks Count", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Backlinks are links from other sites to yours. They act as votes of confidence that tell search and AI engines your content is trustworthy. %s Earn them honestly: be listed as an alternative to other tools, share coupons or giveaways on relevant sites, or contribute to blogs and communities in your niche. %s Avoid cheap, shady link services; they do more harm than good.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => '',
					'solution'     => esc_html__( "Find more blogs, forums, directories to add links there. Contribute to the respective community and they will appreciate it.", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'NoFollowLinks' => array(
					'complete'     => false,
					'title'        => esc_html__( "Links with noFollow?", 'squirrly-seo' ),
					'success'      => '',
					'fail'         => esc_html__( "No" ),
					'success_list' => '<div class="sq_list_success">%s</div>',
					'fail_list'    => '',
					'description'  => sprintf( esc_html__( "No-follow tells engines not to pass your site's credit through a particular link, which lets you keep your authority focused where it matters. %s Add no-follow to links that aren't important, like login or terms pages, and to outbound links to sites you don't want to vouch for. %s Keep normal followed links only for high-quality sites you're happy to be associated with.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "You could add no-follow to most of the links from your site that go towards external, third-party websites. The only external sites you should leave without No-Follow are sites that you'd like to be associated with by Google. This is to say that in some cases you may want to send do-follow links to other people's sites if they are super high authority and would help Google better understand what your site's content is all about.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add nofollow links to pages like Terms and Conditions.", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),

			),
			'authority' => array(

				'Authority'   => array(
					'complete'     => false,
					'title'        => esc_html__( "Page Authority", 'squirrly-seo' ),
					'success'      => '{total} ' . esc_html__( "average authority", 'squirrly-seo' ),
					'fail'         => '{total} ' . esc_html__( "average authority", 'squirrly-seo' ),
					'success_list' => '<div class="sq_list_success">%s</div>',
					'fail_list'    => '<div class="sq_list_success">%s</div>',
					'description'  => sprintf( esc_html__( "Authority is Squirrly's estimate of how likely your pages are to rank and be trusted, based on traffic, social signals and backlinks. %s Raise it by getting more visitors, more shares, and more sites linking to you. %s The Traffic and Social sections of this audit have ideas for each, and Squirrly's Focus Pages guide you step by step.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "You can build up a solid Content Strategy using the SEO Goals and our brand new Private SEO Consultant. In a Plugin. Powered by Machine Learning and Cloud Services: [link]https://plugin.squirrly.co/best-seo-goals/[/link] or you can start getting more BackLinks using the BackLinks Assistant [link]https://www.producthunt.com/upcoming/backlinks-assistant-by-squirrly[/link].", 'squirrly-seo' ),
					'solution'     => esc_html__( "Get links to your page from domains with authority.", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'SemrushRank' => array(
					'complete'     => false,
					'title'        => esc_html__( "Semrush Rank", 'squirrly-seo' ),
					'success'      => '%s ',
					'fail'         => '%s ',
					'success_list' => '',
					'fail_list'    => '',
					'description'  => sprintf( esc_html__( "Semrush Rank is a global position based on how much organic search traffic your site gets, so a LOWER number means more authority - rank #1 is the most visible site in the world. %s There's no fixed pass mark, but the lower the better: getting into the low hundreds of thousands (or below) signals strong, well-recognized authority, while a rank up in the tens of millions means you're still building it. %s The only way to lower it is to grow real traffic - publish and promote useful content consistently so more people find and link to you.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "A certain and tested way of increasing Semrush rank is creating and promoting many pieces of fresh content. An agency like Squirrly's Content Agency can help you with this. [link]http://www.squirrly.co/agency[/link]", 'squirrly-seo' ),
					'solution'     => esc_html__( "Try to gain organic traffic to your site.", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'DomainAge'   => array(
					'complete'     => false,
					'title'        => esc_html__( "Domain Age", 'squirrly-seo' ),
					'success'      => '%s ',
					'fail'         => '%s ',
					'success_list' => '',
					'fail_list'    => '',
					'description'  => sprintf( esc_html__( "Older domains are often trusted a little more, and you can't speed up time, but you can make the most of what you have. %s Make sure your site is fully crawlable and submitted in Google Search Console, with an up-to-date sitemap. %s A newer domain just needs consistency and quality to build trust over time.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "If Squirrly could crawl your website and find your pages + show you the Audit, it means your domain and pages can be crawled. Just make sure you're not stopping the Google crawlers in your code via \"no-index\" or via robots.txt", 'squirrly-seo' ),
					'solution'     => esc_html__( "Your domain is new. I know it will get older, but still, it's good to know what to expect if it's new :)", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Favicon'     => array(
					'complete'     => false,
					'title'        => esc_html__( "Site Icon", 'squirrly-seo' ),
					'success'      => '%s ',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '',
					'description'  => sprintf( esc_html__( "A favicon is the small icon shown in the browser tab next to your page title. It's a small branding touch that makes your site look polished and recognizable. %s On WordPress you can upload one in your site settings or through Squirrly SEO.%s", 'squirrly-seo' ), '<ul><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Platforms like Shopify handle this with their default engine. On WordPress you can use Squirrly SEO to upload and control the favicon displayed on your pages.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add an icon for your site", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'AppleIcon'   => array(
					'complete'     => false,
					'title'        => esc_html__( "IPad and IPhone Icons", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '',
					'description'  => sprintf( esc_html__( "This is the icon shown when someone saves your site to the home screen on an iPhone or iPad. It's a small branding detail that makes your site look professional. %s On WordPress, Squirrly SEO can set it for you.%s", 'squirrly-seo' ), '<ul><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Platforms like Shopify handle this with their default engine. On WordPress you can use Squirrly SEO to upload and control the Apple Icon displayed on user's home screens when they bookmark your pages.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add an icon for your site", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
			),
			'geo'       => array(
				'AiCrawlers'   => array(
					'complete'     => false,
					'title'        => esc_html__( "Allowed for AI crawlers?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "AI crawlers blocked in robots.txt", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Answer engines like ChatGPT, Perplexity, Google AI Overviews and Claude use their own crawlers (GPTBot, ClaudeBot, PerplexityBot, Google-Extended and others). If your robots.txt blocks them, your content can't be cited as an answer. %s Open your Robots File settings and make sure the AI crawlers you want to allow are NOT disallowed. %s Only keep an AI crawler blocked if you intentionally don't want that engine to use your content.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Being readable by AI crawlers is the first requirement of GEO (Generative Engine Optimization). If the bots can't fetch the page, none of the other answer-readiness work matters.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Allow AI crawlers in your robots.txt so answer engines can cite your content", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_seosettings', 'robots' ),
				),
				'LlmsTxt'      => array(
					'complete'     => false,
					'title'        => esc_html__( "llms.txt file present?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '',
					'description'  => sprintf( esc_html__( "llms.txt is an emerging standard that tells generative engines which content on your site matters and how to use it. %s Squirrly SEO can generate this file for you from the LLMs File settings. %s Enable it so AI engines get a clean, curated map of your best content.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Think of llms.txt as a sitemap written for answer engines instead of search crawlers. It's cheap to add and helps generative engines understand your site faster.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Generate an llms.txt file so generative engines can map your content", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_seosettings', 'llms' ),
				),
				'AnswerSchema' => array(
					'complete'     => false,
					'title'        => esc_html__( "Answer-ready structured data?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "Pages without answer-ready schema", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Answer engines extract answers from structured data. Pages that expose schema like FAQPage, QAPage, HowTo or Article are far more likely to be quoted in AI answers and rich results. On our AISQ blog you can read about the truth behind Schema usage by LLMs. It is a very important read, because schema for AEO / GEO does NOT work the same way it does for SEO. Read all about it here: [link]https://aisq.com/schema-used-for-aeo-geo-the-truth/[/link] %s Use the Squirrly Live Assistant to add the right JSON-LD schema for each page's content type. %s FAQ and How-To pages benefit the most from this.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Structured data is how you hand an answer engine the answer pre-packaged. A page with clear FAQ/Q&A schema is much easier for an AI to cite than a wall of text.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add answer-ready JSON-LD schema (FAQ, How-To, Article) to your pages", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'AnswerBlock'       => array(
					'complete'     => false,
					'title'        => esc_html__( "Opens with a direct answer?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "Pages that don't lead with a direct answer", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Answer engines lift the first clear, concise answer they find. Pages that bury the point under long intros rarely get quoted. %s Put a 2-4 sentence direct answer right below the main heading, then expand on it. %s Lead with the answer, then give the context - not the other way around.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Write the opening paragraph as if it's the snippet an AI will read aloud: a complete, self-contained answer to the page's main question.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Open each page with a concise, direct answer to its main question", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'QuestionHeadings'  => array(
					'complete'     => false,
					'title'        => esc_html__( "Uses question-style headings?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "Pages without question-style headings", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Generative engines match real user questions to headings phrased as questions (the People Also Ask shape). %s Turn key subheadings into the actual questions your audience asks, and answer each one right below. %s Mirror the wording people search with.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Use the Keyword Research tool to find the exact questions people ask, then make those your H2/H3 headings.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add question-style H2/H3 headings and answer each one directly", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'StructuredContent' => array(
					'complete'     => false,
					'title'        => esc_html__( "Has extractable lists or tables?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "Pages without lists or tables", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Answer engines pull steps, comparisons and key facts from lists and tables far more easily than from paragraphs. %s Where it fits, present steps as numbered lists, options as bullet lists, and specs or comparisons as tables. %s Make your content easy to chunk and reuse.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "A how-to with a clean numbered list, or a comparison with a table, is exactly the format AI answers reuse. Structure invites citation.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Add lists and tables so engines can extract steps and facts", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'ContentDepth'      => array(
					'complete'     => false,
					'title'        => esc_html__( "Enough depth to be useful?", 'squirrly-seo' ),
					'success'      => '{total} ' . esc_html__( "words total", 'squirrly-seo' ),
					'fail'         => '',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "Pages that are too thin to answer well", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Thin pages rarely get cited because they don't fully answer the question. Aim for enough depth to cover the topic and the follow-up questions around it. %s Expand thin pages with the sub-questions, examples and details your audience needs. %s Depth beats padding - cover the topic completely, don't just add words.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "Use the Live Assistant to see whether you've covered the topic thoroughly enough to be the best answer available.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Expand thin pages so they fully answer the topic", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
				'Freshness'         => array(
					'complete'     => false,
					'title'        => esc_html__( "Is the content fresh?", 'squirrly-seo' ),
					'success'      => esc_html__( "Yes" ) . '!',
					'fail'         => esc_html__( "No" ) . '!',
					'success_list' => '',
					'fail_list'    => '<div class="sq_list_error_title">' . esc_html__( "Pages that look outdated", 'squirrly-seo' ) . ':</div><div class="sq_list_error">%s</div>',
					'description'  => sprintf( esc_html__( "Answer engines favor recently updated content, especially for anything that changes over time. A current dateModified signals the page is still accurate. %s Review and update older pages, then make sure the published/updated date is reflected in the page schema. %s Keep your best pages current.%s", 'squirrly-seo' ), '<ul><li>', '</li><li>', '</li></ul>' ),
					'protip'       => esc_html__( "A quick refresh with a new date often does more for AI visibility than writing a brand-new page from scratch.", 'squirrly-seo' ),
					'solution'     => esc_html__( "Refresh outdated pages and keep their schema dates current", 'squirrly-seo' ),
					'link'         => SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_assistant', 'bulkseo' ),
				),
			)
		);
	}

	public function prepareAudit( $audit ) {
		$groups = $todo = array();

		$tasks = $this->getTasks();

		if ( ! empty( $audit->audit ) ) {
			foreach ( $audit->audit as $group => $rows ) {
				$audittasks = array();

				//initialize group
				$groups[ $group ]['complete'] = 0;
				$groups[ $group ]['total']    = 0;

				foreach ( $rows as $row ) {

					if ( ! isset( $tasks[ $group ][ $row->audit_task ] ) ) {
						continue;
					}

					$audittask = SQ_Classes_ObjController::getDomain( 'SQ_Models_Domain_AuditTask', array_merge( $tasks[ $group ][ $row->audit_task ], (array) $row ) );

					if ( $audittask->audit_task == 'AlexaRank' && $audittask->value == 0 ) {
						continue;
					}

					if ( $audittask->audit_task == 'SemrushRank' && $audittask->value == 0 ) {
						continue;
					}

					//RecentPosting: the cloud audit doesn't always send the day count.
					//Fall back to this site's latest published post so the number always shows.
					if ( $audittask->audit_task == 'RecentPosting' && ( $audittask->value === null || $audittask->value === '' || $audittask->value === false ) ) {
						$sq_last_posts = get_posts( array(
							'numberposts' => 1,
							'post_status' => 'publish',
							'post_type'   => 'post',
							'orderby'     => 'date',
							'order'       => 'DESC',
						) );

						if ( ! empty( $sq_last_posts ) ) {
							$sq_last_time        = (int) get_post_time( 'U', true, $sq_last_posts[0] );
							$audittask->value    = max( 0, (int) floor( ( time() - $sq_last_time ) / DAY_IN_SECONDS ) );
							$audittask->complete = ( $audittask->value <= 30 );
						}
					}

					$replace = '';
					switch ( $audittask->audit_task ) {
						case 'TopTen':
							if ( ( is_object( $audittask->value ) || is_array( $audittask->value ) ) && ! empty( $audittask->value ) ) {
								$replace .= '
                                      <table class="table_vals table table-striped my-3">
                                       <thead>
                                        <tr>
                                          <th>' . esc_html__( "URL", 'squirrly-seo' ) . '</th>
                                          <th>' . esc_html__( "Visitors", 'squirrly-seo' ) . '</th>
                                          <th>' . esc_html__( "Bounce", 'squirrly-seo' ) . '</th>
                                        </tr>
                                       </thead>
                                       <tbody>';

								foreach ( $audittask->value as $value ) {
									$value   = (array) $value;
									$replace .= '<tr>';
									if ( $value['permalink'] <> '' ) {
										$replace .= '';
										$replace .= '<td><a href="' . $value['permalink'] . '" target="_blank">' . $value['permalink'] . '</a></td>';
										$replace .= '<td>' . number_format( (float) $value['visitors'], 0, '.', ',' ) . '</td>';
										$replace .= '<td>' . $value['bounces'] . '%</td>';
									}
									$replace .= '</tr>';
								}
								$replace .= '</tbody></table>';
							} else {
								$replace = '<div class="my-2 small">' . esc_html__( "No traffic data found", 'squirrly-seo' ) . '</div>';
							}
							break;

						case 'TopTenSocials':
						case 'MajesticInboundLinks':
						case 'MajesticUniqueDomains':
						case 'MozLinks':
						case 'NoFollowLinks':
						case 'TopTenAuthority':
						case 'Authority':
							if ( ( is_object( $audittask->value ) || is_array( $audittask->value ) ) && ! empty( $audittask->value ) ) {
								$replace .= '
                                      <table class="table_vals table table-striped my-3">
                                        <thead>
                                            <tr>
                                              <th>' . esc_html__( "URL", 'squirrly-seo' ) . '</th>
                                              <th>' . esc_html__( "Total", 'squirrly-seo' ) . '</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

								foreach ( $audittask->value as $post_id => $value ) {
									$replace .= '<tr>';
									$replace .= '<td><a href="' . $audit->urls->$post_id . '" target="_blank">' . $audit->urls->$post_id . '</a></td>';
									$replace .= '<td>' . number_format( (float) $value, 0, '.', ',' ) . '</td>';
									$replace .= '</tr>';
								}
								$replace .= '</tbody></table>';

							}
							break;
						case 'Speed':
							if ( ( is_object( $audittask->urls ) || is_array( $audittask->urls ) ) && ! empty( $audittask->urls ) ) {
								$replace .= '
                                      <table class="table_vals table table-striped my-3">
                                        <thead>
                                            <tr>
                                              <th>' . esc_html__( "URL", 'squirrly-seo' ) . '</th>
                                              <th>' . esc_html__( "Total", 'squirrly-seo' ) . '</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

								foreach ( $audittask->urls as $post_id ) {
									if ( ! isset( $audittask->value->$post_id ) ) {
										continue;
									}

									$replace .= '<tr>';
									$replace .= '<td><a href="' . $audit->urls->$post_id . '" target="_blank">' . $audit->urls->$post_id . '</a></td>';
									$replace .= '<td>' . number_format( (float) $audittask->value->$post_id, 1, '.', ',' ) . ' s</td>';
									$replace .= '</tr>';

								}
								$replace .= '</tbody></table>';

							}
							break;
						case 'Shares':
							if ( ( is_object( $audittask->value ) || is_array( $audittask->value ) ) && ! empty( $audittask->value ) ) {
								foreach ( $audittask->value as $post_id => $value ) {
									if ( ! $audit->urls->$post_id ) {
										continue;
									}

									$replace .= '<div class="sq_list_success_title my-2"><a href="' . $audit->urls->$post_id . '" target="_blank">' . $audit->urls->$post_id . '</a></div>';

									$replace .= '<table class="table_vals table table-striped my-3"><tbody>';

									$tableOfContents = array(
										'facebookShareCount'  => array(
											'icon'  => 'fasq-brands fa-facebook',
											'title' => esc_html__( "Facebook reactions", 'squirrly-seo' )
										),
										'facebookLikeCount'   => array(
											'icon'  => 'fasq-brands fa-facebook',
											'title' => esc_html__( "Facebook shares", 'squirrly-seo' )
										),
										'reditShareCount'     => array(
											'icon'  => 'fasq-brands fa-reddit',
											'title' => esc_html__( "Reddit shares", 'squirrly-seo' )
										),
										'pinterestShareCount' => array(
											'icon'  => 'fasq-brands fa-pinterest',
											'title' => esc_html__( "Pinterest shares", 'squirrly-seo' )
										),
									);
									foreach ( $value as $name => $shares ) {
										$replace .= '<tr>';
										$replace .= '<td><i class="sq-brands ' . $tableOfContents[ $name ]['icon'] . '"></i>' . $tableOfContents[ $name ]['title'] . '</td>';
										$replace .= '<td>' . number_format( (float) $shares, 0, '.', ',' ) . '</td>';
										$replace .= '</tr>';
									}
									$replace .= '</tbody></table>';


								}
							}
							break;

						case 'DcPublisher':
						case 'SafeBrowsing':
						case 'DuplicateTitles':
						case 'DuplicateDescription':
						case 'EmptyTitles':
						case 'EmptyDescription':
						case 'Title':
						case 'Description':
						case 'Canonical':
						case 'Encoding':
						case 'Viewport':
						case 'Gzip':
						case 'DuplicateOGMetas':
						case 'DuplicateTCMetas':
						case 'DuplicateTitleMetas':
						case 'DuplicateDescriptionMetas':
						case 'Jsonld':
						case 'AnswerSchema':
						case 'AnswerBlock':
						case 'QuestionHeadings':
						case 'StructuredContent':
						case 'Freshness':
						case 'FollowButtons':
						case 'ShareButtons':
						case 'OpenGraph':
						case 'TwitterCard':
							if ( ! empty( $audittask->urls ) ) {
								$replace .= '<ul>';
								foreach ( $audittask->urls as $post_id ) {
									if ( ! $audit->urls->$post_id ) {
										continue;
									}

									$replace .= '<li class="my-1 mx-4" style="list-style: initial"><a href="' . $audit->urls->$post_id . '" target="_blank">' . $audit->urls->$post_id . '</a></li>';

								}
								$replace .= '</ul>';


							}
							break;

						case 'NoIndex':
						case 'NoFollow':
						case 'ExternalLinks':
						case 'PageViews':
						case 'ContentDepth':
							if ( ! empty( $audittask->urls ) ) {

								$replace .= '
                                      <table class="table_vals table table-striped my-3">
                                        <thead>
                                            <tr>
                                              <th>' . esc_html__( "URL", 'squirrly-seo' ) . '</th>
                                              <th>' . esc_html__( "Value", 'squirrly-seo' ) . '</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

								foreach ( $audittask->urls as $post_id ) {
									if ( ! isset( $audittask->value->$post_id ) ) {
										continue;
									}

									$value = $audittask->value->$post_id;

									$replace .= '<tr>';
									$replace .= '<td><a href="' . $audit->urls->$post_id . '" target="_blank">' . $audit->urls->$post_id . '</a></td>';
									$replace .= '<td>' . $value . '</td>';
									$replace .= '</tr>';
								}
								$replace .= '</tbody></table>';
							}
							break;

						case 'Keywords':
							if ( ( is_object( $audittask->value ) || is_array( $audittask->value ) ) && ! empty( $audittask->value ) ) {
								$replace .= '<ul>';
								foreach ( $audittask->value as $value ) {
									$replace .= '<li class="my-1 mx-4" style="list-style: initial">' . $value . '</li>';
								}
								$replace .= '</ul>';
							}
							break;
						default:
							if ( ! is_array( $audittask->value ) && ! is_object( $audittask->value ) ) {
								if ( is_numeric( $audittask->value ) ) {
									$replace = '<strong>' . number_format( (float) $audittask->value, 0, '.', ',' ) . '</strong>';
								} else {
									$replace = '<strong>' . $audittask->value . '</strong>';
								}
							}
					}

					//update the value message
					$audittask->value = urldecode( $replace );

					if ( in_array( $audittask->audit_task, array( 'Speed', 'Authority' ) ) ) {
						$audittask->total = number_format( (float) $audittask->total, 1, '.', ',' );
					} else {
						$audittask->total = (int) $audittask->total;

					}

					//correct the success message
					$audittask->success      = str_replace( array( '{site}', '{total}' ), array(
						home_url(),
						$audittask->total
					), $audittask->success );
					$audittask->success      = sprintf( $audittask->success, $audittask->value );
					$audittask->success_list = sprintf( $audittask->success_list, $audittask->value );

					//correct the fail message
					$audittask->fail      = str_replace( array( '{site}', '{total}' ), array(
						home_url(),
						$audittask->total
					), $audittask->fail );
					$audittask->fail      = sprintf( $audittask->fail, $audittask->value );
					$audittask->fail_list = sprintf( $audittask->fail_list, $audittask->value );

					if ( $audittask->description <> '' ) {
						$audittask->description = str_replace( array( '{site}', '{total}' ), array(
							home_url(),
							$audittask->total
						), $audittask->description );
						$audittask->description = preg_replace_callback( '/\[link\]([^\[]*)\[\/link\]/i', function ( $m ) {
							return '<a href="' . esc_url( $m[1] ) . '" target="_blank">' . esc_html( $m[1] ) . '</a>';
						}, $audittask->description );
					}
					if ( $audittask->protip <> '' ) {
						$audittask->protip = preg_replace_callback( '/\[link\]([^\[]*)\[\/link\]/i', function ( $m ) {
							return '<a href="' . esc_url( $m[1] ) . '" target="_blank">' . esc_html( $m[1] ) . '</a>';
						}, $audittask->protip );
					}

					if ( ! $audittask->complete && $audittask->solution <> '' ) {
						$this->_todo[ $audittask->audit_task ] = array(
							'title'       => $audittask->title,
							'description' => $audittask->description,
							'todo'        => $audittask->solution,
						);
						if ( $audittask->protip <> '' ) {
							$this->_todo[ $audittask->audit_task ]['description'] .= '<div class="my-3 p-0"><strong class="text-info">' . esc_html__( "PRO TIP", 'squirrly-seo' ) . ':</strong> ' . $audittask->protip . '</div>';
						}

					} elseif ( $audittask->complete ) {
						$groups[ $group ]['complete'] ++;
					}

					$groups[ $group ]['total'] ++;
					$audittasks[] = $audittask;
				}

				//update the audit group with the valid tasks
				$audit->audit->$group = $audittasks;


				if ( $groups[ $group ]['total'] > 0 ) {
					$color     = 'sq_audit_task_completed_green';
					$colorname = '';
					if ( $groups[ $group ]['complete'] < ( $groups[ $group ]['total'] / 2 ) ) {
						$color     = 'sq_audit_task_completed_red';
						$colorname = esc_html__( "Requires Attention!", 'squirrly-seo' );
					}
					if ( $groups[ $group ]['complete'] >= ( $groups[ $group ]['total'] / 2 ) ) {
						$color     = 'sq_audit_task_completed_yellow';
						$colorname = esc_html__( "Can be improved.", 'squirrly-seo' );
					}
					if ( $groups[ $group ]['complete'] == $groups[ $group ]['total'] ) {
						$color     = 'sq_audit_task_completed_green';
						$colorname = esc_html__( "Great!", 'squirrly-seo' );
					}

					$groups[ $group ]['color']     = $color;
					$groups[ $group ]['colorname'] = $colorname;
				} else {
					unset( $groups[ $group ] );
				}
			}

			if ( ! empty( $this->_todo ) ) {
				krsort( $this->_todo );
				add_filter( 'sq_assistant_tasks', array( $this, 'setAssistantTasks' ) );
			}

			//show the AEO/GEO group first in the audit
			$sq_priority_group = 'geo';
			if ( isset( $groups[ $sq_priority_group ] ) ) {
				$groups = array( $sq_priority_group => $groups[ $sq_priority_group ] ) + $groups;
			}
			if ( isset( $audit->audit->$sq_priority_group ) ) {
				$sq_reordered                     = new stdClass();
				$sq_reordered->$sq_priority_group = $audit->audit->$sq_priority_group;
				foreach ( $audit->audit as $sq_group => $sq_rows ) {
					if ( $sq_group !== $sq_priority_group ) {
						$sq_reordered->$sq_group = $sq_rows;
					}
				}
				$audit->audit = $sq_reordered;
			}

			$audit->groups              = json_decode( wp_json_encode( $groups ) );
			$audit->next_audit_datetime = date_i18n( 'd M Y', strtotime( $audit->audit_datetime ) + ( 3600 * 24 * 8 ) );
		}

		return $audit;
	}

	/**
	 * Se the assistant tasks for the Squirrly Assistant
	 *
	 * @param  $tasks
	 *
	 * @return mixed
	 */
	public function setAssistantTasks( $tasks ) {

		foreach ( $this->_todo as $audit_task => $todo ) {
			$this->_todo[ $audit_task ] = array(
				'title'       => $todo['title'],
				'description' => $todo['description'],
				'function'    => false,
			);
		}

		return $this->_todo;

	}

	/**
	 * Parse all categories for a single page
	 *
	 * @param SQ_Models_Domain_AuditPage $auditpage
	 *
	 * @return $this
	 */
	public function parseAuditPage( SQ_Models_Domain_AuditPage $auditpage ) {
		//set focus pages from API
		$this->_auditpage = $auditpage;

		//Set the focus page audit as success
		if ( isset( $this->_auditpage->audit_datetime ) ) {
			$this->_auditpage->audit_datetime = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $this->_auditpage->audit_datetime ) );
		} else {
			$this->_auditpage->audit_datetime = esc_html__( "not yet", 'squirrly-seo' );
		}

		if ( $post = $this->_auditpage->getWppost() ) {
			if ( $post->post_status <> '' && $post->post_status <> 'publish' ) { //just if the  Page is public
				$this->_auditpage->audit_error = 404;
			}
		}


		return $this;
	}

	/**
	 * Return the audit page
	 *
	 * @return SQ_Models_Domain_AuditPage
	 */
	public function getAuditPage() {
		return $this->_auditpage;
	}


}
