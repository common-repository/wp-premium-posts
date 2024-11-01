<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

add_filter( 'query_vars', 'wpp_add_query_vars' );
function wpp_add_query_vars( $public_query_vars ) {
	$public_query_vars[] = 'wpp_featured';

	return $public_query_vars;
}

add_action( 'pre_get_posts', 'wpp_pre_get_posts' );
function wpp_pre_get_posts( $query ) {
	if ( $query->get( 'wpp_featured' ) == 'yes' ) {
		$query->set( 'meta_key', '_wpp_is_featured' );
		$query->set( 'meta_value', 'yes' );
	}

	return $query;
}

function wpp_posts_limit( $product_id, $product_sub_id ) {
	$prices     = get_post_meta( $product_id, 'tajer_product_prices', true );
	$is_enabled = $prices[ $product_sub_id ]['wpp_enable'];
	$limit      = $prices[ $product_sub_id ]['posts_limit'];


	if ( $is_enabled == 'yes' ) {
		$enabled = true;
	} else {
		$enabled = false;
	}

	$result             = new stdClass();
	$result->is_enabled = $enabled;
	$result->limit      = $limit;

	return $result;
}

function wpp_form_settings( $form_id ) {
//	if(array_key_exists('wpp_form_settings', $GLOBALS) ){
//		return false;
//	}
//
//	global $wpp_form_settings;

	$fms_form_setting = get_post_meta( $form_id, 'fms_form_settings', true );

	if ( isset( $fms_form_setting['tajer'] ) ) {
		$wpp_form_settings = (object) $fms_form_setting['tajer'];

		return $wpp_form_settings;
	} else {
		return false;
	}
}

function wpp_can_publish( $form_settings ) {
	$result                          = new stdClass();
	$result->can                     = false;
	$result->free                    = false;
	$result->premium                 = false;
	$result->posts_limit             = 0;
	$result->free_posts_published    = 0;
	$result->premium_posts_published = 0;
	$result->user_product_id         = 0;

	//http://wordpress.stackexchange.com/questions/80303/query-all-posts-where-a-meta-key-does-not-exist
//	$args = array(
//		'post_type'   => 'post',
//		'author'      => get_current_user_id(),
//		'numberposts' => - 1,
//
//		'meta_query' => array(
//			'relation' => 'OR',
//			array(
//				'key'     => '_wpp_is_free',
//				'compare' => 'NOT EXISTS',
//				'value'   => ''
//			),
//			array(
//				'key'   => '_wpp_is_free',
//				'value' => 'yes'
//			)
//		)
//	);
//
//	$published_posts_count = count( get_posts( $args ) );
//	wp_reset_postdata();

	$form_products = array();

	$result->free_posts_published    = (int) get_user_meta( get_current_user_id(), 'wpp_user_free_posts', true );
	$result->premium_posts_published = (int) get_user_meta( get_current_user_id(), 'wpp_user_premium_posts', true );

	$allowable_free_posts = (int) $form_settings->free_posts;
	if ( $allowable_free_posts <= $result->free_posts_published ) {
		foreach ( $form_settings->products as $product ) {
			$form_products[ $product['product_id'] ] = $product['product_sub_ids'];
		}

		//Get user products
		$user_products = Tajer_DB::get_user_products();

		//If the user didn't buy any product return false
		if ( empty( $user_products ) || is_null( $user_products ) ) {
			$result->can = false;
		}

//		$user_products_array = array();

		foreach ( $user_products as $user_product ) {

			if ( $result->can && ( $result->posts_limit > 0 ) ) {
				break;
			}

//			$user_products_array[ $user_product->product_id ] = $user_product->product_sub_id;


//			foreach ( $user_products_array as $pid => $puid ) {
			if ( array_key_exists( $user_product->product_id, $form_products ) ) {
				if ( in_array( 'all', $form_products[ $user_product->product_id ] ) || in_array( $user_product->product_sub_id, $form_products[ $user_product->product_id ] ) ) {
					$user_have_posts = tajer_get_user_product_meta( $user_product->id, 'wpp_posts_limit' );
					if ( $user_have_posts > 0 ) {
						$result->user_product_id = $user_product->id;
						$result->premium         = true;
						$result->can             = true;
						$result->posts_limit     = $user_have_posts;

						break;
					}
				}
			}
//			}


		}
	} elseif ( $allowable_free_posts > $result->free_posts_published ) {
		$result->can  = true;
		$result->free = true;
	}

	return $result;
}