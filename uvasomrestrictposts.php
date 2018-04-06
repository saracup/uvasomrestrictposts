<?php
/*
Plugin Name: UVA Health/School of Medicine Post Restriction for NetBadge Access 
Plugin URI: http://technology.med.virginia.edu/digitalcommunications
Description: Adds custom permalink structure indicating level of privacy for posts. This allows Apache to prompt for Netbadge authentication based on custom text in the URL string. To enable this plugin to work, you need to change your permalinks to "Custom Structure" in the following pattern: <code>/[term you choose]/%uvasomrestriction%/%postname%/</code>. <a href="/wp-admin/options-permalink.php">Visit the Permalinks page to update your permalinks</a>.
Version: 0.1
Author: Cathy Finn-Derecki
Author URI: http://technology.med.virginia.edu/digitalcommunications
Copyright 2012  Cathy Finn-Derecki  (email : cad3r@virginia.edu)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
//register_activation_hook(__FILE__,'uvasomrestriction_activation_function');
/*function uvasom_posts_request_filter( $input ) {
	
	print_r( $input );
}
add_filter( 'posts_request', 'uvasom_posts_request_filter' );


if ( is_main_query() ) {
    // do stuff
}
//var_dump($query); 
add_filter('request', 'show_the_request');
function show_the_request($vars) {
	echo '<pre>';
	print_r($vars);
	echo '</pre>';
	return $vars;
}*/

add_action('init','uvasom_restriction_init');
function uvasom_restriction_init() {
    if (!taxonomy_exists('uvasomrestriction')) {
        register_taxonomy( 'uvasomrestriction', array('post','umw_document'),
		   array(   'hierarchical' => TRUE, 'label' => __('UVA SOM Restriction'),  
				'public' => TRUE, 'show_ui' => TRUE,
				'query_var' => 'uvasomrestriction',
				'rewrite' => true,
				'slug' => 'uvasomrestriction',
				'capabilities'=>array(
					'manage_terms' => 'manage_network',//or some other capability your clients don't have
					'edit_terms' => 'manage_network',
					'delete_terms' => 'manage_network',
					'assign_terms' =>'edit_posts')
				)
				);
	
	}
	
$parent_term = term_exists( 'uvasomrestriction', 'uvasomrestriction' ); // array is returned if taxonomy is given
$parent_term_id = $parent_term['term_id']; // get numeric term id
wp_insert_term(
  'UVA Only', // the term 
  'uvasomrestriction', // the taxonomy
  array(
    'description'=> 'Require UVA Netbadge credentials to read.',
    'slug' => 'privatenbauth-uva-only',
    'parent'=> $parent_term_id
  )
);
}
add_filter('post_link', 'uvasom_restriction_permalink', 10, 3);
 
function uvasom_restriction_permalink($permalink, $post_id, $leavename) {
     
        // Get post
        $post = get_post($post_id);
        if (!$post) return $permalink;
 
        // Get taxonomy terms
        $terms = wp_get_object_terms($post->ID, 'uvasomrestriction');   
        if (!is_wp_error($terms) && !empty($terms) && is_object($terms[0])) $taxonomy_slug = $terms[0]->slug;
        else $taxonomy_slug = 'public';
 
    return str_replace('%uvasomrestriction%', $taxonomy_slug, $permalink);
} 
//filter the title for protected posts so that the word "login" is automatically displayed 
function uvasomrestriction_icon($title, $id) {
	$uvasomrestriction = wp_get_object_terms($id, 'uvasomrestriction');
	if (in_array('privatenbauth-uva-only',$uvasomrestriction)){ 
        return $title.' (Login)';
    }
    return $title;
}
add_filter('the_title', 'uvasomrestriction_icon', 10, 2);
//suppress the content of protected posts when displayed in search, feeds, and archives

function uvasomrestriction_suppress_content($content) {
	globaL $id;
	//$uvasomrestriction =  wp_get_post_terms( $id, 'uvasomrestriction' );
	if(is_archive()||(is_tax())||is_category()||is_front_page()||is_home()||is_feed('rss2')) {
	//if (in_array('privatenbauth-uva-only',$uvasomrestriction)){ 
	$taxonomy_terms = get_the_term_list( $id, 'uvasomrestriction');
		if(!strpos($taxonomy_terms,'UVA Only')===false) {
		//$content =  print_r( $taxonomy_terms);
		$content = '<p><em>Login required to view this post</em></p>';
		}
	}
	return $content;
}
add_filter('the_content', 'uvasomrestriction_suppress_content');
//create custom RSS feed for mailchimp and UVA connect
add_action('init', 'customRSS');
function customRSS(){
        add_feed('uvasomrestmailchimp', 'uvasomrestcustomfeed');
}
function uvasomrestcustomfeed(){
	remove_filter('the_content', 'uvasomrestriction_suppress_content');

     header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
	 $numposts = 0;
	 //$posts = query_posts('showposts='.$numposts.'&cat=212'); 
     $more = 1;

    echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

    <rss version="2.0"
        xmlns:content="http://purl.org/rss/1.0/modules/content/"
        xmlns:wfw="http://wellformedweb.org/CommentAPI/"
        xmlns:dc="http://purl.org/dc/elements/1.1/"
        xmlns:atom="http://www.w3.org/2005/Atom"
        xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
        xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
        <?php do_action('rss2_ns'); ?>
    >

        <channel>
            <title><?php bloginfo_rss('name'); wp_title_rss(); ?></title>
            <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
            <link><?php bloginfo_rss('url') ?></link>
            <description><?php bloginfo_rss("description") ?></description>
            <lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
            <language><?php echo get_option('rss_language'); ?></language>
            <sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
            <sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
            <?php do_action('rss2_head'); ?>
            <?php while( have_posts()) : the_post(); ?>
            <item>
                <title><?php the_title_rss() ?></title>
                <link><?php the_permalink_rss() ?></link>
                <comments><?php comments_link_feed(); ?></comments>
                <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
                <dc:creator><?php the_author() ?></dc:creator>
                <?php the_category_rss('rss2') ?>

                <guid isPermaLink="false"><?php the_guid(); ?></guid>
                <description><![CDATA[<?php the_excerpt_rss() ?>]]></description>
                <?php $content = get_the_content_feed('rss2');?>
            <?php if ( strlen( $content  ) > 0 ) : ?>
                <content:encoded><![CDATA[<?php echo $content ?>]]></content:encoded>
            <?php else : ?>
                <content:encoded><![CDATA[<?php echo the_excerpt_rss() ?>]]></content:encoded>
            <?php endif; ?>

                <wfw:commentRss><?php echo esc_url( get_post_comments_feed_link(null, 'rss2') ); ?></wfw:commentRss>
                <slash:comments><?php echo get_comments_number(); ?></slash:comments>
        <?php rss_enclosure(); ?>
            <?php do_action('rss2_item'); ?>
            </item>
            <?php endwhile; ?>
        </channel>
    </rss>
<?php
}
?>