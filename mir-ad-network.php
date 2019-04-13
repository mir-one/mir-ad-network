<?php
/**
 *	@package MIR ads network 
 */
/*
Plugin Name: Mir Ad Network
Plugin URI: https://t.me/mirplatform
Description: Peer-to-Peer Ad Network using the Mir blockchain
Version: 1.0.0
Auther: https://t.me/inozemtsev_roman
Auther URI: https://t.me/mirplatform
Text Domain: mir-ad-network
License: GPLv3
*/

namespace MirAdsNetwork;

if ( ! defined('ABSPATH') ) {
	die;
}

global $wpdb;
$tadn_jal_db_version = '1.0';

class TADN_MirAdsNetwork
{

	/*
	**	initialze all tables and populate required predefined data 
	*/
	function __construct(){
		/*
		**	Initialize some stuff to get started
		*/
		$this->tadn_create_WalletAdress_table();
		$this->tadn_create_adSegment_table();
		$this->tadn_create_adSize_table();
		$this->tadn_create_ad_segment_details_table();
		$this->tadn_create_min_amount_txid_table();
		include( plugin_dir_path( __FILE__ ). 'includes/create-menus.php');

		/*
		**	handle wallet address form request
		**	submit-form-mir
		**  submit-form-add-ad-slots
		*/
		add_action('admin_post_submit-form-mir', array($this , 'tadn_handle_form_action')); 
		add_action('admin_post_nopriv_submit-form-mir', array($this, 'tadn_handle_form_action')); 

		add_action('admin_post_submit-form-add-ad-slots', array($this , 'tadn_handle_form_action_slot')); 
		add_action('admin_post_nopriv_submit-form-add-ad-slots', array($this, 'tadn_handle_form_action_slot')); 

		
		add_action('man_cronjob', array($this, 'tadn_do_this_hourly'));

		add_filter( 'cron_schedules', array($this,'tadn_add_cron_interval' ));

		
	}

	

	function tadn_add_cron_interval( $schedules ) {
		 $schedules['onemin'] = array(
		 'interval' => 60,
		 'display' => esc_html__( 'Every one min' ),
		 );

		return $schedules;
	}

	
	function tadn_activate(){
		
		if (! wp_next_scheduled ( 'man_cronjob' )) {
			wp_schedule_event(time(), 'onemin', 'man_cronjob');
			update_option('api_server','https://node.mir.dei.su');
		}
	}

	function tadn_deactivate(){

		// nothing here
		wp_clear_scheduled_hook('man_cronjob');

	}
	function tadn_do_this_hourly() {
		// do something every one minute

		// include decoding file
		include(plugin_dir_path( __FILE__ ).'base58php/test.php');

		// db connection
		global $wpdb;
		//tables names
		$table_name_segment_details = $wpdb->prefix . 'ad_segment_details';
		$table_name_address 		= $wpdb->prefix . 'wallet_address';
		$table_name 				= $wpdb->prefix . 'adsegment';
		$slot_size_table_name 		= $wpdb->prefix . 'ads_size';

		//fetch ad_seg data
		$datas = $wpdb->get_results("SELECT * FROM $table_name");

		// some predefine value from settings
		$min_amount = get_option('min_amount');
		$ad_time = get_option('ad_time');;

		foreach($datas as $data){

			$seg_id = $data->id;
			$size_id = $data->size_id;
			$all_size = $wpdb->get_results("SELECT * FROM $slot_size_table_name WHERE id = $size_id");
			$slug_name = $data->ad_segment_name;
			$address = $data->address_id;
			$width = $all_size[0]->width;
			$height = $all_size[0]->height;

			$get_address = $wpdb->get_results("SELECT * FROM $table_name_address WHERE id = $address");
			$address = $get_address[0]->address;

			$blacklist_array = explode(',', get_option('blacklist'));
			// check for backlkist/spam address
			if(!in_array($address, $blacklist_array)){

				$url = get_option('api_server'). '/transactions/address/'.$address.'/limit/10';

				$response = file_get_contents($url);
			
				$response = json_decode($response,true);
				
				foreach($response[0] as $value){

					if($value['type'] == 4){

						$attachement = $value['attachment'];
						$amount = $value['amount'] / 100000000;
						$min_txid = $value['id'];
						$sender = $value['sender'];
						$decoded_attachment = decode_attachement($attachement);

						foreach($blacklist_array as $values){

							if(substr_count($decoded_attachment, $values) > 0 || in_array($sender, $blacklist_array)){
								$backlkist_count = 1;
							}


						}

						$table_name_min_txid = $wpdb->prefix . 'min_amount_txid';

						$chck_min_amount = $wpdb->get_results("SELECT * FROM $table_name_min_txid WHERE txid = '$min_txid'");
						if( $amount < $min_amount && empty($chck_min_amount)){

							$wpdb->insert($table_name_min_txid,array('txid' => $min_txid));

						}

						if( ( $amount >= $min_amount ) && ( strpos($decoded_attachment,'Ad') === 0 ) && (strpos($decoded_attachment,'(') === 3) && (strlen($decoded_attachment) <= 140) && !in_array($decoded_attachment, $blacklist_array) && substr_count($decoded_attachment,"(") == 3 && substr_count($decoded_attachment,")") == 3 && $backlkist_count == 0 && empty($chck_min_amount) ) {

							$a = explode("(",$decoded_attachment);

							$b = explode(")", $a[1]);

							$c = explode(")", $a[2]);

							$d = explode(")", $a[3]);

							$headline = $b[0];

							$des = $c[0];

							$clickable = $d[0];

							$txid = $value['id'];

							$time_period = $amount * $ad_time;

							$check = $wpdb->get_results('SELECT * FROM '.$table_name_segment_details .' WHERE txid = "'.$txid.'" AND headline = "'.esc_html($headline).'" AND des = "'.esc_html($des).'"');

							if(empty($check) && !empty($headline) && !empty($des) && !empty($clickable) && !empty($txid) && !empty($time_period)){

								$start_time = strtotime(date("Y-m-d h:i:sa"));
							
								//$end_time = date('Y-m-d h:i:sa', strtotime($add_time, $start_time));

								$insert = $wpdb->insert($table_name_segment_details,array(
									'seg_id' 		=> intval($seg_id),
									'headline'		=> esc_html($headline),
									'des'			=> esc_html($des),
									'clickable'		=> esc_url($clickable),
									'txid'			=> esc_html($txid),
									'time_period'	=> floatval($time_period),
									'time'			=> $start_time,
									'status'		=> 0
								));
								if($insert){
									echo "Inserted<br>";
								}else{
									echo "Not inserted<br>";
								}
							}else{
								echo "No duplicates :) <br>";
							}

						}else{
							echo "Ad submission formate not matched, Invalid ad :(";
						}
					}
				}
			}
			
		}
	}

	/*
	**	Create Wallet Address table 
	*/
	function tadn_create_WalletAdress_table(){

		global $wpdb;
		global $tadn_jal_db_version;

		$table_name = $wpdb->prefix . 'wallet_address';
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			address varchar(200) NOT NULL,
			label varchar(50) NOT NULL,
			status int(11) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'tadn_jal_db_version', $tadn_jal_db_version );

	}

	/*
	**	Create Ad Segment table 
	*/
	function tadn_create_adSegment_table(){

		global $wpdb;
		global $tadn_jal_db_version;

		$table_name = $wpdb->prefix . 'adsegment';
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			address_id int(11) NOT NULL,
			status int(11) NOT NULL,
			size_id int(11) NOT NULL,
			ad_segment_name varchar(256) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'tadn_jal_db_version', $tadn_jal_db_version );

	}

	/*
	**	Create Ad size table 
	** 	populate Ad size table with predefined size
	*/
	function tadn_create_adSize_table(){

		

		global $wpdb;
		global $tadn_jal_db_version;

		$table_name = $wpdb->prefix . 'ads_size';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			width int(11) NOT NULL,
			height int(11) NOT NULL,
			name varchar(256) NOT NULL,
			status int(11) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'tadn_jal_db_version', $tadn_jal_db_version );

		
		/*
		** insert predefined size of ad segments 
		** insert only once
		*/
		global $wpdb;
		$check = $wpdb->get_results('SELECT * FROM ' . $table_name);
		if(empty($check)){
			$width_array = array(728,320);
			$height_array = array(90,100);
			$name_array = array('Leaderboard','Large Mobile Banner');
			for($i=0;$i<5;$i++){
				$wpdb->insert( 
					$table_name, 
					array( 
						'width' => $width_array[$i], 
						'height' => $height_array[$i],
						'name' => $name_array[$i],
						'status' => 1
					) 
				);
			}
		}
	}

	/*
	**	Create create_ad_segment_details_table table 
	** 
	*/
	function tadn_create_ad_segment_details_table(){

		global $wpdb;
		global $tadn_jal_db_version;

		$table_name = $wpdb->prefix . 'ad_segment_details';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			ID int(11) NOT NULL AUTO_INCREMENT,
			seg_id int(11) NOT NULL,
			headline varchar(100) NOT NULL,
			des varchar(100) NOT NULL,
			clickable varchar(100) NOT NULL,
			txid varchar(1000) NOT NULL,
			time_period int(11) NOT NULL,
			time varchar(255) NOT NULL,
			status int(11) NOT NULL,
			approve_status int(11) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";


		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'tadn_jal_db_version', $tadn_jal_db_version );
	}

	/*
	**	Create create_ad_segment_details_table table 
	** 
	*/
	function tadn_create_min_amount_txid_table(){

		global $wpdb;
		global $tadn_jal_db_version;

		$table_name = $wpdb->prefix . 'min_amount_txid';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			txid varchar(656) NOT NULL,
			status int(11) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'tadn_jal_db_version', $tadn_jal_db_version );
	}

	/*
	**	handle wallet address form submit
	*/

	function tadn_handle_form_action(){
		if(!current_user_can('administrator') || ! isset( $_POST['mir-network-walletAddress-form-field'] ) || ! wp_verify_nonce( $_POST['mir-network-walletAddress-form-field'], 'mir-network-walletAddress-form-action' ) ) {

		   print 'Sorry, you Don\'t have Permission to perfrom this action.';
		   exit;

		}else{
			global $wpdb;
			$table_name = $wpdb->prefix . 'wallet_address';
			$address = sanitize_text_field($_POST['address']);
			$label = sanitize_text_field($_POST['label']);
			$id = intval($_POST['hide']);
			$strpos = strpos($address, '3J');
			$redirect_url = esc_url_raw($_POST['redirect_url']);
			if($strpos === 0 && strlen($address) == 35){

				$check = $wpdb->get_results("SELECT * FROM $table_name WHERE address = '$address' OR label = '$label' ");
				if($id == ''){
					if(empty($check)){

						$wpdb->insert($table_name, array('label' => $label, 'address' => $address));
						wp_redirect($redirect_url."&msg=1"); // add a hidden input with get_permalink()
			     		die();

					}else{

						wp_redirect($redirect_url."&msg=0"); // add a hidden input with get_permalink()
			     		die();

					}
				}else{
					$wpdb->update($table_name, array('label' => $label, 'address' => $address), array('id' => $id));
					wp_redirect($redirect_url."&msg=2"); // add a hidden input with get_permalink()
			     	die();

				}
			}else{

				wp_redirect($redirect_url."&msg=3"); // add a hidden input with get_permalink()
			    die();

			}
		}
	}

	/*
	**	handle ad segment form submit
	*/

	function tadn_handle_form_action_slot(){

		if(!current_user_can('administrator') || ! isset( $_POST['mir-network-slot-form-field'] ) || ! wp_verify_nonce( $_POST['mir-network-slot-form-field'], 'mir-network-slot-form-action' ) ) {

		   print 'Sorry, you Don\'t have Permission to perfrom this action.';
		   exit;

		}else{

			global $wpdb;
			$table_name = $wpdb->prefix . 'adsegment';
			$address_id = intval($_POST['address_id']);
			$label = str_replace(" ", "_", sanitize_text_field($_POST['label']));
			$id = intval($_POST['hide']);
			$size_id = intval($_POST['size_id']);

			$redirect_url = esc_url_raw($_POST['redirect_url']);

			$check = $wpdb->get_results("SELECT * FROM $table_name WHERE ad_segment_name = '$label' ");
			if($id == ''){
				if(empty($check)){

					$wpdb->insert($table_name, array('ad_segment_name' => $label, 'address_id' => $address_id, 'size_id' => $size_id));
					wp_redirect($redirect_url."&msg=1"); // add a hidden input with get_permalink()
		     		die();

				}else{

					wp_redirect($redirect_url."&msg=0"); // add a hidden input with get_permalink()
		     		die();

				}
			}else{
				$wpdb->update($table_name, array('ad_segment_name' => $label, 'address_id' => $address_id, 'size_id' => $size_id), array('id' => $id));
				wp_redirect($redirect_url."&msg=2"); // add a hidden input with get_permalink()
		     	die();
			}
		}
	}
}

if( class_exists('\MirAdsNetwork\TADN_MirAdsNetwork') ){
	$mir = new \MirAdsNetwork\TADN_MirAdsNetwork();
}


// db connection
global $wpdb;
//tables names
$table_name_segment_details = $wpdb->prefix . 'ad_segment_details';

$table_name 				= $wpdb->prefix . 'adsegment';
$slot_size_table_name 		= $wpdb->prefix . 'ads_size';

$imgUrl = plugins_url( 'includes/imgs/1.png', __FILE__ );
//fetch ad_seg data
$datas = $wpdb->get_results("SELECT * FROM $table_name");

foreach($datas as $data){
	
	
	$slug_name = esc_html($data->ad_segment_name);
	
	$cb = function() use ($slug_name) {

		// db connection
		global $wpdb;
		//tables names
		$table_name_segment_details = $wpdb->prefix . 'ad_segment_details';

		$table_name 				= $wpdb->prefix . 'adsegment';
		$slot_size_table_name 		= $wpdb->prefix . 'ads_size';
		if(intval(get_option('ad_approval')) == 1){
			$approval_query 			= "AND $table_name_segment_details.approve_status = 1";
		}else{
			$approval_query 			= "AND $table_name_segment_details.approve_status = 0";
		}

		$current_time = date("Y-m-d h:i:sa");
		$current_timestap = strtotime($current_time);
		//fetch ad_seg data
		$datas = $wpdb->get_results("SELECT * FROM $table_name_segment_details INNER JOIN $table_name ON $table_name_segment_details.seg_id=$table_name.id INNER JOIN $slot_size_table_name ON $table_name.size_id=$slot_size_table_name.id WHERE $table_name.ad_segment_name = '$slug_name' AND $table_name_segment_details.status < $table_name_segment_details.time_period $approval_query");

		$x = 0;
		if(!empty($datas)){
			foreach($datas as $data){


				$headline 	 = esc_html($data->headline);
				$width 		 = intval($data->width);
				$height 	 = intval($data->height);
				$des 		 = esc_html($data->des);
				$clickable 	 = esc_url($data->clickable);
				$time_period = floatval($data->time_period);
				$ids 	 	 = intval($data->ID);
				$txid		 = esc_html($data->txid);

				$imgUrl = plugins_url( 'includes/imgs/1.png', __FILE__ );

				if($width == 728){
					$img_width = '2%';
				}else if($width = 320){
					$img_width = '3%';
					$style_a = 'font-size:13px;';
				}else{
					$img_width = '5%';
				}

				$a[] = '<div style="width:'.$width.'px;height:'.$height.'px;border:1px solid black;padding: 10px;text-align: center;'.$style_a.'"><b style="text-transform: uppercase;">'.$headline.'</b><br>'.$des.'<br><a href="'.$clickable.'" target="_blank">'.$clickable.'</a><a href="https://explorer.mir.one/tx/'.$txid.'" target="_blank" style="margin-left:10px;"><img src="'.$imgUrl.'" alt="transaction id" width="'.$img_width.'"></a></div>man_idssx:'.$ids;
				$x++;

			}


			shuffle($a);

			$explode = explode("man_idssx:",$a[0]);

			$ad = $explode[0];
			$id =  intval($explode[1]);

			$check = $wpdb->get_results("SELECT * FROM $table_name_segment_details WHERE ID = $id");

			$db_time_period = $check[0]->time_period;

			$brsw_time_period = $check[0]->status;

			// update brsw_time_period

			if(empty($brsw_time_period) || $brsw_time_period == 0){
			 	$brsw_time_period = 1;
			}else{
			 	$brsw_time_period = $brsw_time_period + 1;
			}
			$update = $wpdb->update($table_name_segment_details,array('status' => $brsw_time_period),array('ID' => $id));

			// show ad
			return $ad;
			
		}
	};  

    add_shortcode( "MAN-$slug_name", $cb );
}

// activation
register_activation_hook( __FILE__, array( $mir, 'tadn_activate' ) );

// deactivation
register_deactivation_hook( __FILE__, array( $mir, 'tadn_deactivate' ) );