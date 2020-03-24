<?php
    add_action('plugins_loaded', 'init_your_gateway_class');
    function init_your_gateway_class()
    {
        class WC_Gateway_Your_Gateway extends WC_Payment_Gateway
        {
            private $allowedCurrencies = array(
                'SEK', 'EUR', 'NOK', 'USD', 'INR'
            );
            private $SUCCESS_CALLBACK_URL = "cirklepay_payment_success";
            private $FAILURE_CALLBACK_URL = "cirklepay_payment_failure";
            private $SUCCESS_REDIRECT_URL = "/checkout/order-received/";
            private $FAILURE_REDIRECT_URL = "/checkout/order-received/";
            private $API_HOST = " ";
            private $API_SESSION_CREATE_ENDPOINT = "/checkout/v1/session/create";

            public function __construct()
            {
                $this->id = 'cirklepay';     // payment gateway plugin ID
                $this->icon = plugins_url() . '/cirkle-pay/public/images/logo.jpg';    // URL of icon that will be displayed on the checkout page      	
                $this->has_fields = true;
                $this->method_title = 'CirklePay Payment Gateway Plugin';
                $this->method_description = 'CirklePay Payment Gateway Plugin.'; //displayed on option page            
                $this->supports = array(
                    'products'
                );

                // Method with all the options fields
                $this->init_form_fields();
                $this->init_settings();
                $this->title = $this->get_option('title');
                $this->description = $this->get_option('description');

                // Checking if valid to use
                if ($this->is_valid_for_use()) {
                    $this->enabled = $this->get_option('enabled');
                } else {
                    $this->enabled = 'no';
                }

                $this->testmode = 'yes' === $this->get_option('testmode');
                $this->merchant_id = $this->testmode ? $this->get_option('test_merchant_id') : $this->get_option('merchant_id');
                $this->auth_token = $this->testmode ? $this->get_option('test_auth_token') : $this->get_option('auth_token');
                $this->country_code = $this->get_option('country_code');

                // Site URL
                $this->siteUrl = get_site_url();

                // This action hook saves the settings
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

                // Callback Actions 
                add_action('woocommerce_api_' . $this->SUCCESS_CALLBACK_URL, array($this, 'payment_success'));
                add_action('woocommerce_api_' . $this->FAILURE_CALLBACK_URL, array($this, 'payment_failure'));
                add_action('woocommerce_receipt_' . $this->id, array(
                    $this,
                    'pay_for_order'
                ));
            }
            public function init_form_fields()
            {
                $this->form_fields = array(
                    'enabled' => array(
                        'title'       => 'Enable/Disable',
                        'label'       => 'Enable CirklePay Gateway',
                        'type'        => 'checkbox',
                        'description' => '',
                        'default'     => 'no'
                    ),
                    'title' => array(
                        'title'       => 'Title',
                        'type'        => 'text',
                        'description' => 'This controls the title which the user sees during checkout.',
                        'default'     => 'CirklePay',
                        'desc_tip'    => true,
                    ),
                    'description' => array(
                        'title'       => 'Description',
                        'type'        => 'text',
                        'description' => 'This controls the description which the user sees during checkout.',
                        'desc_tip'    => true,
                    ),
                    'testmode' => array(
                        'title'       => 'Test mode',
                        'label'       => 'Enable Test Mode',
                        'type'        => 'checkbox',
                        'description' => 'Place the payment gateway in test mode using test API keys.',
                        'default'     => 'yes',
                        'desc_tip'    => true,
                    ),
                    'test_merchant_id' => array(
                        'title'       => 'Test Mkey',
                        'type'        => 'text',
                        'placeholder' => 'Enter Test Mkey'
                    ),
                    'test_auth_token' => array(
                        'title'       => 'Test Salt',
                        'type'        => 'text',
                        'placeholder' => 'Enter Test Salt'
                    ),
                    'merchant_id' => array(
                        'title'       => 'Live Mkey',
                        'type'        => 'text',
                        'placeholder' => 'Enter Live Mkey'
                    ),
                    'auth_token' => array(
                        'title'       => 'Live Salt',
                        'type'        => 'text',
                        'placeholder' => 'Enter Live Salt'
                    ),
                    'country_code' => array(
                        'type' => 'select',
                        'title' => 'Country',
                        'label' => 'Country',
                        'options' => array(
                            ''   => 'Select Country',
                            'SE' => 'Sweden',
                            'FI' => 'Finland',
                            'NO' => 'Norway',
                            'DE' => 'Germany',
                            'CL' => 'Chile',
                            'IN' => 'India'
                        ),
                    )
                );
            }
            function is_valid_for_use()
            {
                return in_array(get_woocommerce_currency(), $this->allowedCurrencies);
            }

            function admin_options()
            {
                if ($this->is_valid_for_use()) {
                    parent::admin_options();
                } else {
    ?>
                 <div class="notice error is-dismissible">
                     <p><?php _e('CirklePay Does not support the selected currency ' . get_woocommerce_currency() . '!', 'my-text-domain'); ?></p>
                 </div>
             <?php
                }
            }
            public function validate_country_code_field($key, $value)
            {
                if ($this->validate_currency_with_country($value)) {
                    return $value;
                } else {
                ?>
                 <div class="notice error is-dismissible">
                     <p><?php _e('CirklePay does not support ' . get_woocommerce_currency() . ' for the selected country! ' . $this->validate_currency_with_country($value), 'my-text-domain'); ?></p>
                 </div>
             <?php
                }
            }
            private function validate_currency_with_country($value)
            {
                $status = false;
                switch ($value) {
                    case "CL":
                        $status = get_woocommerce_currency() == 'CLP';
                        break;
                    case "DE":
                        $status = get_woocommerce_currency() == 'EUR';
                        break;
                    case "SE":
                        $status = get_woocommerce_currency() == 'SEK';
                        break;
                    case "NO":
                        $status = get_woocommerce_currency() == 'NOK';
                        break;
                    case "FI":
                        $status = get_woocommerce_currency() == 'EUR';
                        break;
                    case "IN":
                        $status = get_woocommerce_currency() == 'INR';
                        break;
                }
                return $status;
            }


            public function payment_fields()
            {
                if ($description = $this->get_description()) {
                    echo wpautop(wptexturize($description));
                }
        
            }
            public function payment_scripts()
            {
            }
            public function validate_fields()
            {
            }
            public function process_payment($order_id)
            {
                global $woocommerce;

                //To receive order id 
                $order = wc_get_order($order_id);

                // //To receive order amount
                // $amount = $order->get_total();

                // //To receive woocommerce Currency
                // $currency = get_woocommerce_currency();

                // //To receive user id and order details
                // $merchantCustomerId = $order->get_user_id();
                // $merchantOrderId = $order->get_order_number();
                // $orderIdString = '?orderId=' . $order_id;
                // $mKey = $this->merchant_id;
                // $hash = '';
                // $salt = $this->auth_token;

                // $action = '';

                // $posted = array();
                // $posted['mKey'] = $mKey;
                // $posted['txnId'] = $order_id;
                // $posted['amount'] = $order->order_total;
                // $posted['firstName'] = isset($_POST['firstName']) ? $_POST['firstName'] : null;
                // $posted['email'] = isset($_POST['email']) ? $_POST['email'] : null;
                // $posted['phone'] = isset($_POST['phone']) ? $_POST['phone'] : null;
                // $posted['orderInfo'] = $order_id;
                // $string = $posted['mKey'] . '|' . $posted['txnId'] . '|' . $posted['amount'] . '|' . $posted['firstName'] . '|' . $posted['email'] . '|' . $posted['phone'] . '|' . $posted['orderInfo'] . '|' . $salt;
                // $hash = strtolower(hash('sha512', $string));


                // $transaction = array(
                //     "amount" => $amount,
                //     "currency" => $currency,
                // );
                // $transactions = array(
                //     $transaction
                // );

                // $requestBody = array(
                //     'txnId' => $order_id,
                //     'mKey' => $this->merchant_id,
                //     'hash'=> $hash,
                //     'amount'=> $amount,
                //     'firstName'=>$posted['firstName'],
                //     'email'=>$posted['email'],
                //     'phone'=>$posted['phone'],
                //     'orderInfo'=>$posted['orderInfo']
                // );

                // $header = array(
                //     'Content-Type' => 'application/json'
                // );

                // $args = array(
                //     'method' => 'POST',
                //     'headers' => $header,
                //     'body' => json_encode($requestBody),
                // );

                // //$apiUrl = $this->api_host . $this->API_SESSION_CREATE_ENDPOINT;
                // $apiUrl = 'https://stagepg.cirklepay.com/v1/process/transaction/';
                // $response = wp_remote_post($apiUrl, $args);

                // if (!is_wp_error($response)) {
                //     $body = json_decode($response['body'], true);
                //     echo $body;
                //     if ($body['status'] == 'OK') {
                //         $sessionId = $body['payload']['sessionId'];
                //         $url = $body['payload']['url'];
                //         $order->update_meta_data('cirklepay_session_id', $sessionId);
                //         $session_note = "CirklePay SessionID: " . $sessionId;
                //         $order->add_order_note($session_note);
                //         update_post_meta($order_id, '_session_id', $sessionId);
                //         $order->update_status('processing');
                //         return array(
                //             'result' => 'success',
                //             'redirect' => $url
                //         );
                //     } else {
                //         wc_add_notice('Please try again', 'error');
                //         return;
                //     }
                // } else {
                //     wc_add_notice('Connection error.', 'error');
                //     return;
                // }
                

                return array(
                    'result' => 'success',
                    'redirect' => $order->get_checkout_payment_url(true)
                );
            }
            public function pay_for_order($order_id)
            {
                $order = new WC_Order($order_id);
                $nonce = substr(str_shuffle(MD5(microtime())), 0, 12);
                wc_add_order_item_meta($order_id, 'ipn_nonce', $nonce);
                echo '<p>' . __('Redirecting to payment provider.', 'txtdomain') . '</p>';
                // add a note to show order has been placed and the user redirected
                $order->add_order_note(__('Order placed and user redirected.', 'txtdomain'));
                // update the status of the order should need be
                $order->update_status('on-hold', __('Awaiting payment.', 'txtdomain'));
                // remember to empty the cart of the user
                WC()->cart->empty_cart();
                $mKey = $this->merchant_id;
                $hash = '';
                $salt = $this->auth_token;
                if ($this->testmode) {
                    $action = 'https://stagepg.cirklepay.com/v1/process/transaction/';
                } else {
                    $action = 'https://livepg.cirklepay.com/v1/process/transaction/';
                }


                $posted = array();
                $posted['mKey'] = $mKey;
                //$posted['txnId'] = $order_id;
                $posted['txnId'] = "TSTTXNCKLPG" . rand(1111111111, 9999999999);
                $posted['amount'] = $order->order_total;
                $posted['firstName'] = $order->get_billing_first_name();
                $posted['email'] =  $order->get_billing_email();
                $posted['phone'] = $order->get_billing_phone();
                $posted['orderInfo'] = $order_id;
                $redirecturl = $this->siteUrl . "/wc-api/" . $this->SUCCESS_CALLBACK_URL . '/?nonce='.$nonce.'&order_id=' . $order_id;
                $string = $posted['mKey'] . '|' . $posted['txnId'] . '|' . $posted['amount'] . '|' . $posted['firstName'] . '|' . $posted['email'] . '|' . $posted['phone'] . '|' . $posted['orderInfo'] . '|' . $redirecturl . '|' . $salt;
                $hash = strtolower(hash('sha512', $string));


                // perform a click action on the submit button of the form you are going to return
                wc_enqueue_js('jQuery( "#submit-form" ).click();');


                // return your form with the needed parameters
                echo '<form action="' . $action . '" method="post" target="_top">';
                echo '<input type="text" name="mKey" placeholder="mKey" value="' . $posted['mKey'] . '"/>';
                echo '<input type="text" name="txnId" placeholder="txnId" value="' . $posted['txnId'] . '"/>';
                echo '<input type="text" name="amount" placeholder="amount" value="' . $posted['amount'] . '"/>';
                echo '<input type="text" name="firstName" placeholder="firstName" value="' . $posted['firstName'] . '"/>';
                echo '<input type="text" name="email" placeholder="email" value="' . $posted['email'] . '"/>';
                echo '<input type="text" name="phone" placeholder="phone" value="' . $posted['phone'] . '"/>';
                echo '<input type="text" name="orderInfo" placeholder="orderInfo" value="' . $posted['orderInfo'] . '"/>';
                echo '<input type="hidden" name="redirectUrl" placeholder="redirectUrl" value="' . $redirecturl . '"/>';
                echo '<input type="hidden" name="hash" placeholder="hash" value="' . $hash . '"/>';
                echo '<div class="btn-submit-payment" style="display: none;">';
                echo '<button type="submit" id="submit-form"></button>';
                echo '    </div>';
                echo ' </form>';
            }

            public function payment_success()
            {
                header('HTTP/1.1 200 OK');
				print_r($_POST);
				$order_id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : null;
                $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : null;
				if (is_null($order_id)) return;
                if (is_null($nonce)) return;
                if (wc_get_order_item_meta($order_id, 'ipn_nonce') != $nonce) return;
				$order = wc_get_order($order_id);
				
				if(strcmp($_POST['status'], "success") !== 0){
					$order->update_status('failed');
					
				}else{              
                $order->payment_complete();
                wc_reduce_stock_levels($order_id);
				
				}
				wp_redirect( $this->get_return_url( $order ) );
				exit;
                //die();
            }

            public function payment_failure()
            {
                // Getting POST data
                $postData = file_get_contents('php://input');
                $response  = json_decode($postData);
                $orderId = $_GET['orderId'];
                $order = wc_get_order($orderId);

                if ($order && $response) {
                    $order->update_meta_data('cirklepay_callback_payload', $postData);
                    $order->update_meta_data('cirklepay_event', $response->event);
                    if ($response->payload->reservations && $response->payload->reservations[0] && $response->payload->reservations[0]->reservationId) {
                        $order->update_meta_data('cirklepay_reservation_id', $response->payload->reservations[0]->reservationId);
                    }
                    $order->update_status('failed');
                }
            }


            public function webhook()
            {
            }
        }
    }
    function add_your_gateway_class($methods)
    {
        $methods[] = 'WC_Gateway_Your_Gateway';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_your_gateway_class');
