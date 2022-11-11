<?php
/**
* Plugin Name: Chotu Captain Onboarding
* Plugin URI: https://chotu.com/
* Description: Rest api for captain onboarding process on whatsapp.
* Version: 1.1.4
* Author: Mohd Nadeem, Y Ravi
* Author URI: zonvoir.com
**/
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
include('chotu_generate_CSF_onboard.php');
add_action( 'rest_api_init', 'chotu_rest_api_init' );
/**
 * chotu_rest_api_init
 *
 * @return void
 */
function chotu_rest_api_init() {
  register_rest_route( 'api/v2', '/createCaptain/', array(
      'methods' => 'post',
      'callback' => 'chotu_api_create_captain',
      'permission_callback' => '__return_true'
    ) );
   register_rest_route( 'api/v2', '/isCaptain/', array(
      'methods' => 'get',
      'callback' => 'chotu_validate_mobile_context',
      'permission_callback' => '__return_true'
    ) );
}
/**
 * chotu_validate_mobile_context
 *
 * @param  mixed $request
 * @return void
 */
function chotu_validate_mobile_context($request){
  //check if number is indian mobile or not
  
  if(!preg_match('/^[6789]\d{9}$/', $request['captain_mobile_number'])){
    return new WP_REST_Response( array('result'=> 50,'message'=>'invalid number','message_detail'=>'&#128308; The number is invalid, chotu supports only 10-digit Indian mobile numbers right now.','status' => 200,'data'=> array()));
  }
  $captain_username = $request['captain_mobile_number'];

  //split and validate the context
  if(isset($request['context'])){
    $request_context = explode(" ",$request['context'] );
    $context = $request_context[0];
    
    $category = '';
    if(isset($request_context[1])){
      $category = get_term_by( 'slug', trim($request_context[1]), 'product_cat' );
    }
  if(strtolower($request['context']) == "login"){
        return chotu_login_captain_shop($captain_username);
  }elseif(strtolower($request['context']) == "start"){
        //user has typed only START in the input without any category
        return new WP_REST_Response( array('result'=>60,'message'=>' invalid context','message_detail'=>'','status' => 200,'data'=> array()));  
      }elseif(strtolower($context) == "start"){
        if(empty($category) || isset($request_context[2])){
          return new WP_REST_Response( array('result'=>100,'message'=>'invalid category','message_detail'=>'','status' => 200,'data'=> array()) );
        }
        return new WP_REST_Response( array('result'=>40,'message'=>'not captain, start | start','message_detail'=>'','status' => 200,'data'=> $category));
      }
      elseif(strtolower($request['context']) == "menu"){
        return new WP_REST_Response( array('result'=>90,'message'=>'menu | startover','message_detail'=>'','status' => 200,'data'=> array()) );
      }else{
        return new WP_REST_Response( array('result'=>60,'message'=>' invalid context','message_detail'=>'','status' => 200,'data'=> array()));
      }
    }
}
/**
 * chotu_api_create_captain
 *
 * API function used to create captain object and update meta fields in userMeta table
 * @param  mixed $request
 * @return void
 */
function chotu_api_create_captain($request){
  if(!isset($request['captain_mobile_number']) || !isset($request['captain_name']) || !isset($request['captain_lat_long']) || !isset($request['captain_shop_category']) || !isset($request['captain_pincode']) || $request['captain_shop_category'] == '' || $request['captain_language'] == ''|| $request['captain_display_pic'] == ''){
    return new WP_REST_Response( array('result'=>70,'message'=>'all parameters not available','message_detail'=>'','status' => 200,'data'=> array() ) );
  }
  if(!preg_match('/^(\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?)$/', $request['captain_lat_long'])){
    return new WP_REST_Response( array('result'=>50,'message'=>'invalid captain_lat_long','message_detail'=>'','status' => 200,'data'=> array() ));
  }
  if(!preg_match('/^[6789]\d{9}$/', $request['captain_mobile_number'])){
    return new WP_REST_Response( array('result'=>30,'message'=>'invalid mobile number','message_detail'=>'','status' => 200,'data'=> array() ) );
  }

  if(!preg_match('/^[0-9A-Za-z\s\-]{0,30}+$/', trim($request['captain_name']))){
    return new WP_REST_Response( array('result'=>40,'message'=>'invalid name','message_detail'=>'','status' => 200,'data'=> array() ));
  }
  if(!preg_match('/^[0-9]{6}+$/', $request['captain_pincode'])){
    return new WP_REST_Response( array('result'=>60,'message'=>'invalid pin code','message_detail'=>'','status' => 200,'data'=> array() ) );
  }
  if( $request['captain_language'] ==''){
    return new WP_REST_Response( array('result'=>90,'message'=>'invalid language','message_detail'=>'','status' => 200,'data'=> array() ) );
  }
  if(!preg_match('/\.(jpg|png|webp|jpeg)$/',$request['captain_display_pic'])){
    return new WP_REST_Response( array('result'=>100,'message'=>'invalid image url','message_detail'=>'','status' => 200,'data'=> array() ) );
  }
  if($request['captain_shop_category'] ==''){
    return new WP_REST_Response( array('result'=>110,'message'=>'invalid category slug code','message_detail'=>'','status' => 200,'data'=> array() ) );
  }else{
    $category = get_term_by( 'slug', trim($request['captain_shop_category']), 'product_cat' );
    if(empty($category)){
      return new WP_REST_Response( array('result'=>110,'message'=>'invalid category slug code','message_detail'=>'','status' => 200,'data'=> array() ) );
    }
  }
  $captain_username = str_replace("+91", "", $request['captain_mobile_number']);
  $email = $request['captain_mobile_number'].'@chotu.com';
  if ((!email_exists($email)) && (!username_exists($request['captain_mobile_number']))) {
    $data['captain_username']  = $request['captain_mobile_number'];
    $data['captain_name']           = $request['captain_name'];
    $data['captain_lat_long']       = $request['captain_lat_long'];
    $data['captain_language']       = $request['captain_language'];
    if(isset($request['captain_display_pic']) && $request['captain_display_pic'] !=''){
      $data['captain_display_pic']  = $request['captain_display_pic'];
    }
    if(isset($request['captain_shop_category'])){
      $data['captain_shop_category'] = $request['captain_shop_category'];
    }
    if(isset($request['captain_pincode'])){
      $data['captain_pincode'] = $request['captain_pincode'];
    }
    if(chotu_api_save_captain_data($data)){
      $shop_link = site_url().'/'.$request['captain_mobile_number'].'/';
      $response = new WP_REST_Response( array( 'result' => 10,'message' =>'captain created successfully','message_detail'=>$shop_link,'status' => 200,'data'=> array('shop_link'=>$shop_link,'share_message'=>'Hey friends, I have just started my organic store with 20,000+ products. Order organic foods, natural beauty products, healthy snacks, superfoods, Ayurveda and many more. You can place your orders with me here : ')));
    }else{
      return new WP_REST_Response( array( 'result' => 80,'message'=>'unable to create captain right now, please try again later','message_detail'=>'','status' => 200,'data'=> array() ));
    }
  }else{
    $shop_link = site_url().'/'.$request['captain_mobile_number'].'/';
    $response = new WP_REST_Response(array('result'=> 20,'message'=>'captain already exists','message_detail'=>$shop_link,'status' => 200,'data'=> array('shop_link'=>$shop_link)));
  }
  return $response;
}

if(!function_exists('chotu_save_captain_data')){
  /**
   * chotu_save_captain_data
   *
   * @param  mixed $data
   * @return void
   */
  function chotu_save_captain_data($data){
    $password = wp_generate_password();
    //captain_username not to be confused with captain_name
    //captain_username  : wordpress user name (unique)
    //captain_name      : wordpress first name
    $captain_username = str_replace("+91", "", $data['captain_username']);
    $captain_url = site_url().'/'.$captain_username;
    $email = $captain_username.'@chotu.com';
    $captain_shop = $data['captain_name'];
    $captain_id = wp_create_user($captain_username,$password,$email);
    if (!is_wp_error($captain_id)) {
      update_user_meta( $captain_id, 'captain_area_served', '');
      update_user_meta( $captain_id, 'wpseo_title', $data['captain_name']);
      update_user_meta( $captain_id, 'captain_shop_feed_self', '');
      update_user_meta( $captain_id, 'captain_shop_feed_history', '');
      update_user_meta( $captain_id, 'url', $captain_url);
      chotu_save_default_captain_fields($captain_id);
      if(isset($data['captain_display_pic'])){
        $attach_id = chotu_upload_image($data['captain_display_pic']);
        update_user_meta( $captain_id, 'captain_display_pic', $attach_id);
      }
      if(isset($data['captain_shop_category'])){
        $captain_shop_category = 0;
        $category = get_term_by( 'slug', trim($data['captain_shop_category']), 'product_cat' );
        if(isset($category->term_id)){
          $captain_shop_category = $category->term_id;
          $shortcode = chotu_generate_shop_feed_onboard($data['captain_shop_category'],$category->term_id,$captain_pincode = 0,$captain_lat_long = 0);
          update_user_meta( $captain_id,'captain_shop_feed_oncreate',$shortcode);
          update_user_meta( $captain_id,'captain_shop_category',sanitize_text_field($data['captain_shop_category']));
        }
      }
      if(isset($data['captain_pincode'])){
        update_user_meta( $captain_id, 'captain_pincode', $data['captain_pincode'] );
      }
      if(isset($data['captain_language'])){
        update_user_meta( $captain_id, 'captain_language', $data['captain_language'] );
      }
      if(isset($data['captain_lat_long']) && $data['captain_lat_long'] !=''){
        update_user_meta( $captain_id, 'captain_lat_long', $data['captain_lat_long'] );
        chotu_set_captain_address( $captain_id,$data['captain_lat_long'] );
      }
      $user = new WP_User( $captain_id );
      $user->set_role( 'captain' );
      $userdata = array(
        'ID' => $captain_id,
        'user_nicename' => $captain_username,
        'first_name' => $captain_shop,
        'last_name' => $captain_shop,
        'display_name' => $captain_shop,
      );
      wp_update_user($userdata);
    }
    if($captain_id){
      return true;
    }
  }
}
if(!function_exists('chotu_save_default_captain_fields')){
  /* save default captains fields from json files*/
  /**
   * chotu_save_default_captain_fields
   *
   * @param  mixed $captain_id
   * @return void
   */
  function chotu_save_default_captain_fields($captain_id=''){
    // put the content of the site config in a variable
      update_user_meta($captain_id, 'description', get_option('captain_default_description'));
      update_user_meta($captain_id, 'captain_announcement', get_option('captain_default_announcement'));
      update_user_meta($captain_id, 'captain_cover_pic', get_option('captain_default_cover_pic'));
      update_user_meta($captain_id, 'wpseo_metadesc', get_option('captain_default_description'));
  }
}
if(!function_exists('chotu_upload_image')){
  /* upload image for captain onboarding process. pass image url and function return attachment id.*/
  /**
   * chotu_upload_image
   *
   * @param  mixed $image_url
   * @return int
   */
  function chotu_upload_image($image_url){
    $pathinfo         = pathinfo($image_url);
    $image_name       = $pathinfo['basename'];
    $upload_dir       = wp_upload_dir(); // Set upload folder
    $image_data       = file_get_contents($image_url); // Get image data
    $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
    $filename         = basename( $unique_file_name ); // Create image file name

    // Check folder permission and define file location
    if( wp_mkdir_p( $upload_dir['path'] ) ) {
      $file = $upload_dir['path'] . '/' . $filename;
    } else {
      $file = $upload_dir['basedir'] . '/' . $filename;
    }

    // Create the image  file on the server
    file_put_contents( $file, $image_data );

    // Check image file type
    $wp_filetype = wp_check_filetype( $filename, null );

    // Set attachment data
    $attachment = array(
      'post_mime_type' => $wp_filetype['type'],
      'post_title'     => sanitize_file_name( $filename ),
      'post_content'   => '',
      'post_status'    => 'inherit'
    );

    // Create the attachment
    $attach_id = wp_insert_attachment( $attachment, $file, $post_id = null );

    // Include image.php
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Define attachment metadata
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

    // Assign metadata to attachment
    wp_update_attachment_metadata( $attach_id, $attach_data );
    return $attach_id;
  }
}

if(!function_exists('chotu_set_captain_address')){
  function chotu_set_captain_address($captain_id,$captain_lat_long){
    if(!empty($captain_lat_long)){
      $lat_long = explode(",",$captain_lat_long);
      $key = get_option('locationiq_api_key');
      $data = json_decode(file_get_contents('https://us1.locationiq.com/v1/reverse?key='.$key.'&lat='.$lat_long[0].'&lon='.$lat_long[1].'&format=json'),true);
      update_user_meta($captain_id, 'captain_display_address', $data['display_name']);
    }
  }
}
if(!function_exists('chotu_create_nonce')){
  /**
   * chotu_create_nonce
   *
   * @param  mixed $length
   * @return string
   */
  function chotu_create_nonce($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }
}
if(!function_exists('chotu_login_captain_shop')){  
  /**
   * chotu_login_captain_shop
   * returns login URL for captain
   * @param  mixed $captain_username
   * @return void
   */
  function chotu_login_captain_shop($captain_username){
    if(username_exists($captain_username)) {
      $user = get_user_by('login', $captain_username);
      $nonce = chotu_create_nonce(15);
      update_user_meta($user->ID,'verify_nonce',$nonce);
      update_user_meta($user->ID,'verify_nonce_expiry',date('Y-m-d H:i:s', strtotime("+10 minutes")));
      $login_url = site_url().'/?auth='.$user->user_login.'&mynonce='.$nonce;
      return new WP_REST_Response( array('result'=>10,'message'=>'captain, login | login','message_detail'=>'','status' => 200,'data'=> array('login_url'=>$login_url)) );
    }else{
      return new WP_REST_Response( array('result'=>20,'message'=>'not captain, login | error','message_detail'=>'','status' => 200,'data'=> array()));
    }
  }
}
