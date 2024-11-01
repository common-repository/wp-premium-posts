<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WPP_Tajer_Part {

	public function __construct() {
		add_action( 'tajer_pricing_options_dialog', array( $this, 'add_wpp_options' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'tajer_price_options_html', array( $this, 'add_wpp_pricing_options_hidden_fields' ), 10, 3 );
		add_action( 'tajer_user_product_created', array( $this, 'record_post_limit' ), 10, 2 );
	}

	function add_wpp_pricing_options_hidden_fields( $html, $field_name, $meta ) {
		$html .= '<input type="hidden" name="' . $field_name . '[wpp_enable]" value="' . ( is_array( $meta ) ? $meta['wpp_enable'] : '' ) . '"/>';
		$html .= '<input type="hidden" name="' . $field_name . '[posts_limit]" value="' . ( is_array( $meta ) ? $meta['posts_limit'] : '' ) . '"/>';

		return $html;
	}

	function record_post_limit( $result, $opts ) {
		if ( ! $result['is_insert'] ) {
			return;
		}

		$product_limit = wpp_posts_limit( $opts['product_id'], $opts['product_sub_id'] );

		if ( ! $product_limit->is_enabled ) {
			return;
		}

		tajer_update_user_product_meta( $result['id'], 'wpp_posts_limit', $product_limit->limit );
	}

	function add_wpp_options() {
		?>
		<tr>
			<td><label for="enable_wpp">
					<?php esc_html_e( 'Premium Posts', 'wpp' ); ?> &nbsp;</label></td>
			<td><input type="checkbox" name="enable_wpp" id="enable_wpp" value="yes"></td>
		</tr>

		<!--		<tr class="wpp-options">-->
		<!--			<td><label for="wpp_content_characters_limit">-->
		<!--					--><?php //esc_html_e( 'Post Content Characters Limit', 'wpp' ); ?><!-- &nbsp;</label></td>-->
		<!--			<td><input type="text" name="wpp_content_characters_limit" id="wpp_content_characters_limit"><br/><span-->
		<!--					class="description">--><?php //esc_html_e( 'Put 0 or leave it empty for unlimited number of characters.', 'wpp' ); ?><!--</span></td>-->
		<!--		</tr>-->
		<!---->
		<!--		<tr class="wpp-options">-->
		<!--			<td><label for="wpp_content_words_limit">-->
		<!--					--><?php //esc_html_e( 'Post Content Words Limit', 'wpp' ); ?><!-- &nbsp;</label></td>-->
		<!--			<td><input type="text" name="wpp_content_words_limit" id="wpp_content_words_limit"><br/><span-->
		<!--					class="description">--><?php //esc_html_e( 'Put 0 or leave it empty for unlimited number of words.', 'wpp' ); ?><!--</span></td>-->
		<!--		</tr>-->

		<tr class="wpp-options">
			<td><label for="wpp_posts_limit">
					<?php esc_html_e( 'Posts Limit', 'wpp' ); ?> &nbsp;</label></td>
			<td><input type="text" name="wpp_posts_limit" id="wpp_posts_limit"><br/><span
					class="description"><?php esc_html_e( 'Put 0 or leave it empty for unlimited number of posts.', 'wpp' ); ?></span>
			</td>
		</tr>

		<!--		<tr class="wpp-options">-->
		<!--			<td><label for="wpp_featured">-->
		<!--					--><?php //esc_html_e( 'Featured Posts', 'wpp' ); ?><!-- &nbsp;</label></td>-->
		<!--			<td><input type="checkbox" name="wpp_featured" id="wpp_featured" value="yes"></td>-->
		<!--		</tr>-->
		<!---->
		<!--		<tr class="wpp-options">-->
		<!--			<td><label for="wpp_featured_expiration">-->
		<!--					--><?php //esc_html_e( 'Featured Expiration Date(in days)', 'wpp' ); ?><!-- &nbsp;</label></td>-->
		<!--			<td><input type="text" name="wpp_featured_expiration" id="wpp_featured_expiration"><br/><span-->
		<!--					class="description">--><?php //esc_html_e( 'Put 0 or leave it empty for unlimited number of days.', 'wpp' ); ?><!--</span></td>-->
		<!--		</tr>-->
		<!---->
		<!--		<tr class="wpp-options">-->
		<!--			<td><label for="wpp_post_expiration">-->
		<!--					--><?php //esc_html_e( 'Post Expiration Date(in days)', 'wpp' ); ?><!-- &nbsp;</label></td>-->
		<!--			<td><input type="text" name="wpp_post_expiration" id="wpp_post_expiration"><br/><span-->
		<!--					class="description">--><?php //esc_html_e( 'Put 0 or leave it empty to disable it.', 'wpp' ); ?><!--</span></td>-->
		<!--		</tr>-->
		<?php
	}

	function enqueue_scripts() {
		global $pagenow, $post;

		if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, array( 'tajer_products' ) ) ) {
			return;
		}

		// scripts
		wp_enqueue_script( 'wpp-admin-js', WPP_URL . 'js/admin-tajer-wpp.js', array(
			'tajer-post-type-js'
		) );

		// styles
		wp_enqueue_style( 'wpp-admin-css', WPP_URL . 'css/admin-tajer-wpp.css', array(
			'tajer-post-type-css'
		) );
	}
}

$WPP_Tajer_Part = new WPP_Tajer_Part();