<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WPP_FMS_Part {
	public function __construct() {
		add_action( 'fms_post_form_tab_list', array( $this, 'add_tajer_nav_tab' ) );
		add_action( 'fms_post_form_tab_content', array( $this, 'add_tajer_tab_content' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_wpp_get_product_sub_ids', array( $this, 'get_product_sub_ids' ) );
		add_action( 'fms_after_insert_post', array( $this, 'set_post_meta_data' ), 10, 5 );
		add_filter( 'fms_posting_form', array( $this, 'restrict_form_access' ), 10, 3 );
		add_filter( 'fms_posting_form_validation_messages', array( $this, 'form_validation' ), 10, 3 );
	}

	function form_validation( $fms_validation_status, $form_id, $post_id ) {

		if ( $post_id ) {
			return $fms_validation_status;
		}

		$form_settings = wpp_form_settings( $form_id );

		if ( ! $form_settings ) {
			return $fms_validation_status;
		}

		if ( $form_settings->enable != 'on' ) {
			return $fms_validation_status;
		}

		$can_publish = wpp_can_publish( $form_settings );

		//Get post content field characters count
		$characters_count = mb_strlen( $_POST['post_content'] );

		//Get post content field words count
		$words_count = str_word_count( $_POST['post_content'] );

		if ( ( (int) $form_settings->words_limit > 0 ) && ( $words_count > (int) $form_settings->words_limit ) ) {
			if ( is_array( $fms_validation_status ) ) {
				$fms_validation_status[] = $form_settings->words_limit_validation_message;
			} else {
				$fms_validation_status = array( $form_settings->words_limit_validation_message );
			}
		}

		if ( ( (int) $form_settings->characters_limit > 0 ) && ( $characters_count > (int) $form_settings->characters_limit ) ) {
			if ( is_array( $fms_validation_status ) ) {
				$fms_validation_status[] = $form_settings->characters_limit_validation_message;
			} else {
				$fms_validation_status = array( $form_settings->characters_limit_validation_message );
			}
		}

		if ( ! $can_publish->can ) {
			if ( is_array( $fms_validation_status ) ) {
				$fms_validation_status[] = $form_settings->posts_limit_validation_message;
			} else {
				$fms_validation_status = array( $form_settings->posts_limit_validation_message );
			}
		}

		return $fms_validation_status;
	}

	function set_post_meta_data( $form_id, $post_id, $is_update, $fms_fields_setting, $fms_form_setting ) {
		if ( $is_update ) {
			return;
		}

		$form_settings = wpp_form_settings( $form_id );

		if ( ! $form_settings ) {
			return;
		}

		if ( $form_settings->enable != 'on' ) {
			return;
		}

		$can_publish = wpp_can_publish( $form_settings );
		if ( ! $can_publish->can ) {
			update_post_meta( $post_id, '_wpp_illegal_post', 'yes' );
		} else {
			if ( $form_settings->featured == 'on' ) {
				update_post_meta( $post_id, '_wpp_is_featured', 'yes' );

				if ( ( (int) $form_settings->featured_expiration_date ) > 0 ) {
					update_post_meta( $post_id, '_wpp_featured_expiration', $form_settings->featured_expiration_date );
				}
			}

			if ( ( (int) $form_settings->post_expiration_date ) > 0 ) {
				update_post_meta( $post_id, '_wpp_post_expiration', $form_settings->post_expiration_date );
				update_post_meta( $post_id, '_wpp_force_delete', $form_settings->force_delete );
			}

			if ( $can_publish->free ) {
				update_post_meta( $post_id, '_wpp_is_free', 'yes' );
				update_user_meta( get_current_user_id(), 'wpp_user_free_posts', ( $can_publish->free_posts_published + 1 ) );
			} elseif ( $can_publish->premium ) {
				update_post_meta( $post_id, '_wpp_is_premium', $can_publish );
				update_user_meta( get_current_user_id(), 'wpp_user_premium_posts', ( $can_publish->premium_posts_published + 1 ) );
				$new_user_product_posts_limit = $can_publish->posts_limit - 1;
				tajer_update_user_product_meta( $can_publish->user_product_id, 'wpp_posts_limit', $new_user_product_posts_limit );
			}
		}

	}

	function restrict_form_access( $html, $form_id, $post_id ) {

		if ( $post_id ) {
			return $html;
		}

		$form_settings = wpp_form_settings( $form_id );

		if ( ! $form_settings ) {
			return $html;
		}

		if ( $form_settings->enable != 'on' ) {
			return $html;
		}

		$can_publish = wpp_can_publish( $form_settings );

		if ( ! $can_publish->can ) {
			return $form_settings->form_restriction_message;
		} else {
			return $html;
		}
	}

	function get_product_sub_ids() {

		if ( ! ( isset( $_REQUEST['wppNonce'] ) && wp_verify_nonce( $_REQUEST['wppNonce'], 'wpp' ) ) ) {
			wp_die( 'Security Check!' );
		}

		if ( ! current_user_can( apply_filters( 'wpp_admin_products_get_product_sub_ids_capability', 'manage_options' ) ) ) {
			wp_die( 'Security Check!' );
		}

		$product_id = $_REQUEST['productId'];

		$product_sub_ids        = tajer_get_product_sub_ids_with_names( (int) $product_id );
		$product_sub_ids['all'] = __( "All", 'wpp' );

		$html = '';
		foreach ( $product_sub_ids as $id => $name ) {
			$html .= '<option value="' . $id . '">' . $name . '</option>';
		}


		$response = array(
			'subIds' => $html
		);

		$response = apply_filters( 'get_product_sub_ids_in_products_post_type', $response );

		tajer_response( $response );
	}

	function add_tajer_nav_tab( $post ) {
		?>
		<li class=""><a href="#fms-metabox-tajer" class="nav-tab"
		                id="fms-tajer-tab"><?php esc_html_e( 'Premium Posts', 'wpp' ); ?></a>
		</li>
		<?php
	}

	function enqueue_scripts() {
		global $pagenow, $post;

		if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, array( 'fms_forms', 'fms_contact_forms', 'fms_regist_forms' ) ) ) {
			return;
		}

		// scripts
		wp_enqueue_script( 'wpp-admin-js', WPP_URL . 'js/admin-fms-wpp.js', array(
			'fms-formbuilder'
		) );

		// styles
		wp_enqueue_style( 'wpp-admin-css', WPP_URL . 'css/admin-fms-wpp.css', array(
			'fms-formbuilder'
		) );
	}

	function add_tajer_tab_content( $post ) {
		$form_settings                       = get_post_meta( $post->ID, 'fms_form_settings', true );
		$enable                              = isset( $form_settings['tajer']['enable'] ) ? $form_settings['tajer']['enable'] : 'off';
		$featured                            = isset( $form_settings['tajer']['featured'] ) ? $form_settings['tajer']['featured'] : 'off';
		$force_delete                        = isset( $form_settings['tajer']['force_delete'] ) ? $form_settings['tajer']['force_delete'] : 'off';
		$products                            = isset( $form_settings['tajer']['products'] ) ? $form_settings['tajer']['products'] : array();
		$featured_expiration_date            = isset( $form_settings['tajer']['featured_expiration_date'] ) ? $form_settings['tajer']['featured_expiration_date'] : '';
		$post_expiration_date                = isset( $form_settings['tajer']['post_expiration_date'] ) ? $form_settings['tajer']['post_expiration_date'] : '';
		$free_posts                          = isset( $form_settings['tajer']['free_posts'] ) ? $form_settings['tajer']['free_posts'] : '';
		$characters_limit                    = isset( $form_settings['tajer']['characters_limit'] ) ? $form_settings['tajer']['characters_limit'] : '';
		$words_limit                         = isset( $form_settings['tajer']['words_limit'] ) ? $form_settings['tajer']['words_limit'] : '';
		$form_restriction_message            = isset( $form_settings['tajer']['form_restriction_message'] ) ? $form_settings['tajer']['form_restriction_message'] : __( 'Unfortunately you cant publish posts anymore, if you want to continue publish posts you should buy one of our packages!', 'wpp' );
		$characters_limit_validation_message = isset( $form_settings['tajer']['characters_limit_validation_message'] ) ? $form_settings['tajer']['characters_limit_validation_message'] : __( 'Unfortunately you cant publish more than 1000 characters in the post content field!', 'wpp' );
		$words_limit_validation_message      = isset( $form_settings['tajer']['words_limit_validation_message'] ) ? $form_settings['tajer']['words_limit_validation_message'] : __( 'Unfortunately you cant publish more than 100 words in the post content field!', 'wpp' );
		$posts_limit_validation_message      = isset( $form_settings['tajer']['posts_limit_validation_message'] ) ? $form_settings['tajer']['posts_limit_validation_message'] : __( 'Unfortunately you exceeded the number of allowed posts!', 'wpp' );
		$args                                = array( 'post_type' => 'tajer_products', 'numberposts' => - 1 );
		$all_products                        = get_posts( $args );
		?>
		<div id="fms-metabox-tajer" class="group">

			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Premium Posts', 'wpp' ); ?></th>
					<td>
						<label>
							<input id="wpp_enable" type="checkbox" name="fms_settings[tajer][enable]"
							       value="on"<?php checked( $enable, 'on' ); ?>>
							<?php esc_html_e( 'Enable Premium Posts', 'wpp' ); ?>
						</label>
					</td>
				</tr>

				<tr class="wpp-options">
					<th><?php esc_html_e( 'Products Assignment', 'wpp' ); ?></th>
					<td>
						<table id="wpp-products">
							<?php if ( ! empty( $products ) ) { ?>
								<?php foreach ( $products as $id => $product ) { ?>
									<tr>
										<td>
											<select name="fms_settings[tajer][products][<?php echo $id; ?>][product_id]"
											        class="wpp-chosen">
												<?php
												foreach ( $all_products as $one_product ) {
													$selected = false;
													if ( strval( $one_product->ID ) == $product['product_id'] ) {
														$selected = true;
													}
													echo '<option ' . selected( $selected, true, false ) . ' value="' . $one_product->ID . '">' . $one_product->post_title . '</option>';
												}
												?>
											</select>
										</td>
										<td>
											<select
												name="fms_settings[tajer][products][<?php echo $id; ?>][product_sub_ids][]"
												multiple
												class="wpp-chosen">
												<?php
												$product_sub_ids        = tajer_get_product_sub_ids_with_names( (int) $product['product_id'] );
												$product_sub_ids['all'] = __( "All", 'wpp' );
												foreach ( $product_sub_ids as $product_sub_id => $name ) {
													if ( in_array( $product_sub_id, $product['product_sub_ids'] ) ) {
														echo '<option value="' . $product_sub_id . '" selected>' . $name . '</option>';
													} else {
														echo '<option value="' . $product_sub_id . '">' . $name . '</option>';
													}
												}
												?>
												<option value="all">All</option>
											</select>
										</td>
										<td>
											<a class="button wpp-plus" href="#">+</a>
											<a class="button wpp-minus" href="#">-</a>
											<label class="fms-cond-logic-loading hide"></label>
										</td>
									</tr>
								<?php } ?>
							<?php } else { ?>
								<tr>
									<td>
										<select name="fms_settings[tajer][products][1][product_id]" class="wpp-chosen">
											<?php
											foreach ( $all_products as $one_product ) {
												echo '<option value="' . $one_product->ID . '">' . $one_product->post_title . '</option>';
											}
											?>
										</select>
									</td>
									<td>
										<select name="fms_settings[tajer][products][1][product_sub_ids][]" multiple
										        class="wpp-chosen">
											<?php
											$first_product           = reset( $all_products );
											$product__sub_ids        = tajer_get_product_sub_ids_with_names( $first_product->ID );
											$product__sub_ids['all'] = __( "All", 'wpp' );

											foreach ( $product__sub_ids as $product_sub_id_id => $product_sub_id_name ) {
												echo '<option value="' . $product_sub_id_id . '">' . $product_sub_id_name . '</option>';
											}
											?>
										</select>
									</td>
									<td>
										<a class="button wpp-plus" href="#">+</a>
										<a class="button wpp-minus" href="#">-</a>
										<label class="fms-cond-logic-loading hide"></label>
									</td>
								</tr>
							<?php } ?>
						</table>
					</td>
				</tr>

				<tr class="wpp-options">
					<th><?php esc_html_e( 'Featured Posts', 'wpp' ); ?></th>
					<td>
						<label>
							<input id="wpp-featured" type="checkbox" name="fms_settings[tajer][featured]"
							       value="on"<?php checked( $featured, 'on' ); ?>>
							<?php esc_html_e( 'Enable Featured Posts', 'wpp' ); ?>
						</label>
					</td>
				</tr>

				<tr class="wpp-options featured-options">
					<th><?php esc_html_e( 'Featured Expiration Date(in days)', 'wpp' ); ?></th>
					<td><input type="text" name="fms_settings[tajer][featured_expiration_date]" class="regular-text"
					           value="<?php echo esc_attr( $featured_expiration_date ) ?>">
						<div class="description">
							<?php esc_html_e( 'Put 0 or leave it empty for unlimited number of days.', 'wpp' ) ?>
						</div>
					</td>
				</tr>

				<tr class="wpp-options">
					<th><?php esc_html_e( 'Post Expiration Date(in days)', 'wpp' ); ?></th>
					<td><input type="text" name="fms_settings[tajer][post_expiration_date]" class="regular-text"
					           value="<?php echo esc_attr( $post_expiration_date ) ?>">
						<div class="description">
							<?php esc_html_e( 'Put 0 or leave it empty for unlimited number of days.', 'wpp' ) ?>
						</div>
					</td>
				</tr>

				<tr class="wpp-options">
					<th><?php esc_html_e( 'Force Delete', 'wpp' ); ?></th>
					<td>
						<label>
							<input id="wpp-force-delete" type="checkbox" name="fms_settings[tajer][force_delete]"
							       value="on"<?php checked( $force_delete, 'on' ); ?>>
							<?php esc_html_e( 'Enable Force Delete', 'wpp' ); ?>
						</label>

						<div class="description">
							<?php esc_html_e( 'If checked it will bypass trash and force expired post deletion, if not it will move the expired post to the trash.', 'wpp' ) ?>
						</div>
					</td>
				</tr>

				<tr class="wpp-options">
					<th><?php esc_html_e( 'Allowable Free Posts', 'wpp' ); ?></th>
					<td><input type="text" name="fms_settings[tajer][free_posts]" class="regular-text"
					           value="<?php echo esc_attr( $free_posts ) ?>">
						<div class="description">
							<?php esc_html_e( 'For example allow the user to publish 5 posts for free then if he/she wants to publish more he/she should pay.', 'wpp' ) ?>
						</div>
					</td>
				</tr>

				<tr class="wpp-options">
					<th><?php esc_html_e( 'Post Content Characters Limit', 'wpp' ); ?></th>
					<td><input type="text" name="fms_settings[tajer][characters_limit]" class="regular-text"
					           value="<?php echo esc_attr( $characters_limit ) ?>">
						<div class="description">
							<?php esc_html_e( 'Put 0 or leave it empty for unlimited number of characters.', 'wpp' ) ?>
						</div>
					</td>
				</tr>

				<tr class="wpp-options">
					<th><?php esc_html_e( 'Post Content Words Limit', 'wpp' ); ?></th>
					<td><input type="text" name="fms_settings[tajer][words_limit]" class="regular-text"
					           value="<?php echo esc_attr( $words_limit ) ?>">
						<div class="description">
							<?php esc_html_e( 'Put 0 or leave it empty for unlimited number of words.', 'wpp' ) ?>
						</div>
					</td>
				</tr>

				<tr class="wpp-options">
					<th><?php esc_html_e( 'Form Restriction Message', 'wpp' ); ?></th>
					<td>
					<textarea rows="6" cols="60"
					          name="fms_settings[tajer][form_restriction_message]"><?php echo $form_restriction_message ?></textarea>
						<div class="description">
							<?php esc_html_e( 'This message appears to the user instead of the form in case the user can not publish posts. HTML is allowed.', 'wpp' ) ?>
						</div>
					</td>
				</tr>

				<tr class="wpp-options">
					<th><?php esc_html_e( 'Characters limit validation message', 'wpp' ); ?></th>
					<td>
					<textarea rows="6" cols="60"
					          name="fms_settings[tajer][characters_limit_validation_message]"><?php echo $characters_limit_validation_message ?></textarea>
						<div class="description">
							<?php esc_html_e( 'Text only.', 'wpp' ) ?>
						</div>
					</td>
				</tr>

				<tr class="wpp-options">
					<th><?php esc_html_e( 'Words limit validation message', 'wpp' ); ?></th>
					<td>
					<textarea rows="6" cols="60"
					          name="fms_settings[tajer][words_limit_validation_message]"><?php echo $words_limit_validation_message ?></textarea>
						<div class="description">
							<?php esc_html_e( 'Text only.', 'wpp' ) ?>
						</div>
					</td>
				</tr>

				<tr class="wpp-options">
					<th><?php esc_html_e( 'Posts limit validation message', 'wpp' ); ?></th>
					<td>
					<textarea rows="6" cols="60"
					          name="fms_settings[tajer][posts_limit_validation_message]"><?php echo $posts_limit_validation_message ?></textarea>
						<div class="description">
							<?php esc_html_e( 'Text only.', 'wpp' ) ?>
						</div>
					</td>
				</tr>
			</table>
			<?php wp_nonce_field( 'wpp', 'wpp' ); ?>
		</div>
		<?php
	}
}

$WPP_FMS_Part = new WPP_FMS_Part();