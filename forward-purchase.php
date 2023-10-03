<?php
/*
Plugin Name: Forward Purchase
Description: Adds a forward purchase program toggle to the thank you page.
Version: 1.8.2
Author: ASETENA | By octeton Inc. | octetoninc.com 
Author URI: octetoninc.com 
*/

// Enqueue necessary scripts and styles
function forward_purchase_enqueue_scripts() {
    // Enqueue script only on the thank you page
    if (!is_wc_endpoint_url('order-received')) {
        return;
    }

    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js', array('jquery'), '5.3.0', true);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css');
    wp_enqueue_style('forward-purchase-style', plugin_dir_url(__FILE__) . 'css/forward-purchase.css');
    wp_enqueue_script('forward-purchase-script', plugin_dir_url(__FILE__) . 'js/forward-purchase.js', array('jquery'), '', true);
}
add_action('wp_enqueue_scripts', 'forward_purchase_enqueue_scripts');


// Add the toggle switch
function forward_purchase_add_toggle_switch($order_id) {
    $order = wc_get_order($order_id);
    if ($order) {
        $order_id = $order->get_id();
        $payment_method = $order->get_payment_method();
        if ($payment_method !== 'cod') {
             // Localize order ID and ajax URL for JavaScript
                wp_localize_script('forward-purchase-script', 'forward_purchase_ajax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('forward_purchase_nonce'),
                    'order_id' => $order_id,
                ));

            ?>

            <div class="forward-purchase-container">
                <label style="font-weight: bold" for="forward-purchase-toggle">Activate Forward Purchase Program (Optional)</label>
                <!-- Toggle -->
                <div id="forward-purchase-toggle-container">
                    <label class="switch">
                        <input type="checkbox" id="forward-purchase-toggle">
                        <span class="slider round"></span>
                    </label>
                </div>
                <!-- Pop-Up Container -->
                <div class="pop-up-container container-fluid col-12">
                    <div class="pop-up col-4" id="pop-up-id">
                            <div class="col-12 form-item-container border border-light fp-text-header-container">
                                <h1 class="h5 text-dark text-center">FORWARD PURCHASE PROGRAM</h1>
                            </div>
                            <form action="#" method="POST" class="border border-light p-2 col-12 " >
                                <div class="container-fluid form-item-container mb-2">
                                    <div class="row">
                                        <div class="col-5  px-1  text-end align-items-center pt-2">
                                            <label for="orderIDPopUp" class="visually-visible"> Order ID:</label>
                                        </div>
                                        <div class="col-7  px-1">
                                            <input type="text" readonly class="form-control-plaintext outline-none" placeholder="<?php echo $order_id; ?>">
                                            <input type="hidden" class="form-control-plaintext outline-none" id="orderIDPopUp" value="<?php echo $order_id; ?>" name="current_order_id">
                                        </div>
                                    </div>
                                </div>
                                <div class="container-fluid col-12 form-item-container  mb-1">
                                    <div class="row">
                                        <div class="col-5 px-1">
                                            <div class="col-12 bg-light text-end">
                                                <label for="orderDate" class="visually-visible">Order Date:</label>
                                            </div>
                                            <div class="col-12">
                                                <input type="text" readonly class="form-control-plaintext text-center fs-6" value="<?php echo date('d-m-Y'); ?>">
                                                <input type="hidden" class="form-control-plaintext text-center fs-6" id="orderDate" name="orderDate" value="<?php echo date('Y-m-d');?>">
                                            </div>
                                        </div>
                                        <div class="col-7 text-start px-1">
                                            <div class="col-12 ">
                                                <label for="expDate" class="visually-visible">Expected Pickup Date:</label>
                                            </div>
                                            <div class="col-12 ">
                                                <input type="date" class="form-control" id="expDate" name="expected_pickup_date">
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                                <div class="container-fluid col-12 form-item-container text-start px-1 py-3">
                                    <input type="checkbox" id="t&c" required><span style="color: red;">* </span>
                                    <label for="t&c">I agree to the <a href="#">terms and conditions</a></label>
                                </div>
                                <div class="col-12 form-item-container confirm-btn-container d-grid gap-2 ">
                                    <button type="button"  name="forward_purchase_submit" class="btn btn-sm shadow-sm fs-6 confirm-btn btn-danger" id="confirm-date-btn">Confirm</button>
                                </div>
                            </form>
                        </div>
                </div>    
            </div>
            <?php
        }
    }
}
add_action('woocommerce_thankyou', 'forward_purchase_add_toggle_switch', 10, 1);

// Handle the AJAX request to get the current order ID
function forward_purchase_get_current_order_id() {
    check_ajax_referer('forward_purchase_nonce', 'security');
    $order_id = $_POST['order_id'];
    $order = wc_get_order($order_id);

    if ($order) {
        $order_id = $order->get_id();
        wp_send_json_success(array('order_id' => $order_id));
    } else {
        wp_send_json_error('Order not found.');
    }
}
add_action('wp_ajax_forward_purchase_get_current_order_id', 'forward_purchase_get_current_order_id');
add_action('wp_ajax_nopriv_forward_purchase_get_current_order_id', 'forward_purchase_get_current_order_id');


// Handle the AJAX request to get the current order status
function forward_purchase_get_current_order_status() {
    check_ajax_referer('forward_purchase_nonce', 'security');
    $order_id = $_POST['order_id'];
    error_log('Order ID Log '.$order_id);
    $order = wc_get_order($order_id);
    
    if ($order) {
        $order_id = $order->get_id();
        $order_status = $order->get_status();
        wp_send_json_success(array('order_status' => $order_status));
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_forward_purchase_get_current_order_status', 'forward_purchase_get_current_order_status');
add_action('wp_ajax_nopriv_forward_purchase_get_current_order_status', 'forward_purchase_get_current_order_status');


// Handle the AJAX request to update the order status
function forward_purchase_update_order_status() {
    check_ajax_referer('forward_purchase_nonce', 'security');
    $order_id = $_POST['order_id'];
    error_log('Forward Purchase Update Order Status function called.');
    $order = wc_get_order($order_id);

    // Check if the order with the said Post ID exist
    if ($order) 
    {
        $order_id = $order->get_id();
        if ($order_id) { 
            $forward_purchase_activated = isset($_POST['forward_purchase_activated']) ? sanitize_text_field($_POST['forward_purchase_activated']) : 'Sorry, No request found!';
            if ($forward_purchase_activated === '1') {
                $order->update_status('forward-purchase', __('Forward Purchase Program Activated.', 'forward-purchase-plugin'));
                wp_send_json_success(__('Forward Purchase Program activated.', 'forward-purchase-plugin'));
            } elseif ($forward_purchase_activated === '0') {
                $order->update_status('completed', __('Forward Purchase Program deactivated.', 'forward-purchase-plugin'));
                wp_send_json_success(__('Forward Purchase Program deactivated.', 'forward-purchase-plugin'));
            }
        } 
        else {
            echo "Order ID not found";
        }
    }
    // If the Post ID is not found, display an error or handle it accordingly
    else {
        echo 'Order not found.';
        return;
    } 
    
    wp_send_json_error(__('Invalid request.', 'forward-purchase-plugin'));
}
add_action('wp_ajax_forward_purchase_update_order_status', 'forward_purchase_update_order_status');
add_action('wp_ajax_nopriv_forward_purchase_update_order_status', 'forward_purchase_update_order_status');

// 
global $current_user;

// Handle the form submission
function forward_purchase_handle_form_submission() {

    // Verify the nonce before processing the form submission
    check_ajax_referer('forward_purchase_nonce', 'security');

    // Check if the form is submitted
    if (isset($_POST['forward_purchase_submit'])) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'mjay_forward_purchase';

        // Retrieve the submitted values
        $orderID = $_POST['order_id'];
        $orderDate = $_POST['order_date']; 
        $expectedDate = $_POST['expected_pickup_date'];
        
        // Sanitize the data before inserting into the database
        $orderID = sanitize_text_field($orderID);
        $orderDate = sanitize_text_field($orderDate);
        $expectedDate = sanitize_text_field($expectedDate);


        // Insert data into the database
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => get_current_user_id(),
                'order_id' => $orderID,
                'order_date' => $orderDate,
                'expected_date' => $expectedDate,
            ),
            array(
                '%d', // username
                '%d', // order_id
                '%s', // order_date
                '%s', // order_id_pick_up_date
            )
        );

        // Check for errors during the insertion process
        if ($wpdb->last_error !== '') {
            error_log('Database Insertion Error: ' . $wpdb->last_error);
            wp_send_json_error('Database Insertion Error');
        } else {
            wp_send_json_success(array('message' => 'Form submitted successfully!'));
        }
    }
}add_action('wp_ajax_forward_purchase_handle_form_submission', 'forward_purchase_handle_form_submission');
add_action('wp_ajax_nopriv_forward_purchase_handle_form_submission', 'forward_purchase_handle_form_submission');