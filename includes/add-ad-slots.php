<?php

if ( ! defined('ABSPATH') ) {
	die;
}

// get admin url for current page using slug
$admin_url = menu_page_url( 'man-add-ad-slots',false); 

?>

<h3>Add Ad Segments</h3>
<?php
global $wpdb;

$table_name = $wpdb->prefix . 'adsegment';
$table_name_segment_details = $wpdb->prefix . 'ad_segment_details';
if(isset($_GET['mir-network-slot-edit-nonce']) && current_user_can('administrator') && wp_verify_nonce($_GET['mir-network-slot-edit-nonce'], 'mir-network-slot-edit-action')){
	if(isset($_GET['id'])){
		if($_GET['action'] == 'delete'){

			$wpdb->delete($table_name,array('id' => intval($_GET['id'])));
			//$wpdb->delete($table_name_segment_details,array('seg_id' => $_GET['id']));
			echo 'Deleted Successfully!!';
			$btntxt = 'Add ';
			$labell =  '';
			$addresss =  '';
			$get_data = '';
			$size_id_db = '';
			$id = '';
		}else{

			$id = intval($_GET['id']);
			$get_data = $wpdb->get_results("SELECT * FROM $table_name WHERE id = $id");
			$labell =  $get_data[0]->ad_segment_name;
			$addresss =  $get_data[0]->address_id;
			$size_id_db =  $get_data[0]->size_id;
			$btntxt = 'Update ';
		}
	}else{
		$id = '';
		$btntxt = 'Add ';
		$labell =  '';
		$addresss =  '';
		$get_data = '';
		$size_id_db = '';
	}
}
if(isset($_GET['msg'])){

	if(intval($_GET['msg']) == 1){
		echo 'Added Successfully!!';
	}else if(intval($_GET['msg']) == 2){
		echo "Updated Successfully!!";
	}else{
		echo "Label or Address already exists";
	
	}
}

$address_table_name = $wpdb->prefix . 'wallet_address';
$all_address = $wpdb->get_results("SELECT * FROM $address_table_name");

$slot_size_table_name = $wpdb->prefix . 'ads_size';
$all_size = $wpdb->get_results("SELECT * FROM $slot_size_table_name WHERE status=1");



?>
<form action='<?php echo get_admin_url(); ?>admin-post.php' method="post">
	<?php wp_nonce_field( 'mir-network-slot-form-action', 'mir-network-slot-form-field'); ?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label for="label">Ad Segment Name</label></th>
				<td>
					<input name="label" type="text" id="label" value="<?php echo $labell;?>" class="regular-text" required>

				</td>
			</tr>
			<tr>
				<th scope="row"><label for="address">Assign Wallet Address</label></th>
				<td>
					<select name="address_id" required>
						<option value="">Select Wallet Address</option>
						<?php
							foreach($all_address as $address){
								if($addresss == $address->id){
									$selected = 'selected';
								}else{
									$selected = '';
								}
								echo "<option ".$selected." value='".$address->id."'>".$address->address." ( ". $address->label ." )</option>";
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="size">Ad Size</label></th>
				<td>
					<select name="size_id" required>
						<option value="">Select Ad Segment Size</option>
						<?php
							foreach($all_size as $size){

								if($size_id_db == $size->id){
									$selected = 'selected';
								}else{
									$selected = '';
								}


								$naem = $size->width." X ". $size->height ." - " .$size->name;
								echo "<option ".$selected." value='".$size->id."'>".$naem." </option>";
							}
						?>
					</select>
				</td>
			</tr>
			<input type='hidden' name='action' value='submit-form-add-ad-slots' />
			<input type='hidden' name='hide' value="<?php echo intval($id);?>" />
			<input type="hidden" name="redirect_url" value="<?php echo esc_url($admin_url);?>">
		</tbody>
	</table>
	<p class="submit">
		<input type="submit" name="mir_ad_slot_submit" id="submit" class="button button-primary" value="<?php echo $btntxt;?> Ad Segment">
	</p>
</form>

<table class="widefat fixed" cellspacing="0">
    <thead>
    <tr>

            <th id="cb" class="manage-column column-columnname" scope="col" style="width: 6%;">#</th> 
            <th id="columnname" class="manage-column column-columnname" scope="col" style="width: 20%;">Ad Segment Name</th>
            <th id="columnname" class="manage-column column-columnname" scope="col" style="width: 30%;">Assigned Address</th>
            <th id="columnname" class="manage-column column-columnname" scope="col" style="width: 25%;">Assigned Size</th> 
            <th id="columnname" class="manage-column column-columnname" scope="col" >Shortcode</th> 

    </tr>
    </thead>

    <tfoot>
    <tr>
    		<th id="cb" class="manage-column column-columnname" scope="col">#</th> 
            <th id="columnname" class="manage-column column-columnname" scope="col">Ad Segment Name</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Assigned Address</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Assigned Size</th>
            <th id="columnname" class="manage-column column-columnname" scope="col">Shortcode</th> 
    </tr>
    </tfoot>

    <tbody>       
	<?php 
		$table_name = $wpdb->prefix . 'adsegment';
		$data = $wpdb->get_results("SELECT * FROM $table_name");
		$x=1;
		if(empty($data)){echo 'No data yet!!';}	
		foreach ($data as $value) {

			$s_id = intval($value->size_id);
			$a_id = intval($value->address_id);

			$size_data = $wpdb->get_results("SELECT * FROM $slot_size_table_name WHERE id = $s_id");
			$data_size = $size_data[0]->width." X ". $size_data[0]->height ." - " .$size_data[0]->name;
			$address_table_name = $wpdb->prefix . 'wallet_address';
			$all_addresss = $wpdb->get_results("SELECT * FROM $address_table_name WHERE id = $a_id");
			$addd = $all_addresss[0]->address;

	?>
        <tr class="alternate" valign="top"> 
            <th class="check-column" scope="row" style="padding: 11px 0 0 10px;"><?php _e($x);?></th>
            <td class="column-columnname"><?php esc_html_e($value->ad_segment_name);?>
                <div class="row-actions">

                    <span><a href="<?php print wp_nonce_url($admin_url.'&action=edit&id='.intval($value->id), 'mir-network-slot-edit-action', 'mir-network-slot-edit-nonce'); ?>">Edit</a> |</span>

                    <span><a href="<?php print wp_nonce_url($admin_url.'&action=delete&id='.intval($value->id), 'mir-network-slot-edit-action', 'mir-network-slot-edit-nonce'); ?>">Delete</a></span>
                </div>
            </td>
            <td class="column-columnname"><?php esc_html_e($addd);?>
                <div class="row-actions">
                    <span><a href="<?php print wp_nonce_url($admin_url.'&action=edit&id='.intval($value->id), 'mir-network-slot-edit-action', 'mir-network-slot-edit-nonce'); ?>">Edit</a> |</span>
                    
                    <span><a href="<?php print wp_nonce_url($admin_url.'&action=delete&id='.intval($value->id), 'mir-network-slot-edit-action', 'mir-network-slot-edit-nonce'); ?>">Delete</a></span>
                </div>
            </td>
            <td class="column-columnname"><?php esc_html_e($data_size);?>
                <div class="row-actions">
                    <span><a href="<?php print wp_nonce_url($admin_url.'&action=edit&id='.intval($value->id), 'mir-network-slot-edit-action', 'mir-network-slot-edit-nonce'); ?>">Edit</a> |</span>
                    
                    <span><a href="<?php print wp_nonce_url($admin_url.'&action=delete&id='.intval($value->id), 'mir-network-slot-edit-action', 'mir-network-slot-edit-nonce'); ?>">Delete</a></span>
                </div>
            </td>
            <td class="column-columnname"><?php esc_html_e("[MAN-" . $value->ad_segment_name . "]");?></td>

        </tr>
    <?php $x++;} ?>
    </tbody>
</table>