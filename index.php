<?php

$input = [
    "submit_url" => "https://something/submit.php",
    "auth_url"   => "https://home/auth.php",
];

/**
 * runs $sql in your DB
 *
 * @param $sql
 *
 * @return array 2-dimensional array of data
 */
function query( $sql )
{}

/**
 * The key may contain non-alphanumeric chars
 *
 * @param $service_type
 * @param $customer_id
 *
 * @return string
 */
function secure_key( $service_type, $customer_id )
{}

/**
 * makes a GET http request
 *
 *
 * @param $url
 *
 * @throws Exception
 *
 * @return integer invoice_id
 */
function order_submit( $url )
{}

//* // Start of my code

/* !!! Please, read the README nearby, to see my notes about the task. !!! */

$orders_data = query(
    'SELECT
            orders.order_id AS order_id,
            orders.service_id AS service_id,
            orders.customer_id AS customer_id,
            SUM(order_charges.value) AS amount,
            (
                SELECT
                    GROUP_CONCAT(
                        DISTINCT order_charges.price_entity_id
                    )
                FROM order_charges
                WHERE order_charges.order_id = orders.order_id
                GROUP BY orders.order_id
            ) AS prices
        FROM orders, order_charges
        WHERE orders.order_id = order_charges.order_id
        GROUP BY orders.order_id, orders.service_id, orders.customer_id
        ORDER BY orders.order_id ASC
        LIMIT 0,100;
    ' );

foreach( $orders_data as $orders_datum ){
    
    // Building order URL with auth URL within
    $order_url = $input['submit_url'] . '?' . http_build_query(
        array(
            'order_id' => $orders_datum['order_id'],
            'amount'   => $orders_datum['amount'],
            'prices'   => $orders_datum['prices'],
            'auth_url' => $input['auth_url'] . '?' . http_build_query(
                array(
                    'order_id'   => $orders_datum['order_id'],
                    'secure_key' => secure_key( $orders_datum['service_id'], $orders_datum['customer_id'] ),
                )
            ),
        )
    );
    
    try{
        $invoice_id = order_submit( $order_url );
    }catch(Exception $e){
        $has_error = true;
        $invoice_id = 0; // Set the ID to zero if the error occurred
        error_log(
            date('d-m-Y h:i:s')
            . ': Exception thrown at ' . $e->getFile() . '.'
            . ' Line: ' . $e->getLine() . '.'
            . ' Code: ' . $e->getCode() . '.'
            . ' Message: ' . $e->getMessage()
        );
    }
    
    // Initializing output array for the current service_id
    $current_service_id = $orders_datum['service_id'];
    $json[ $current_service_id ] = isset( $json[ $current_service_id ] )
        ? $json[ $current_service_id ]
        : array(
            'sum'        => 0,
            'invoice_id' => array(),
            'has_error'  => false,
        );
    
    // Adding the values to JSON
    $json[ $current_service_id ]['sum']          += $orders_datum['amount'];
    $json[ $current_service_id ]['invoice_id'][] = $invoice_id;
    $json[ $current_service_id ]['has_error']    |= ! empty( $has_error );
    
    // Removing the the error flag
    $has_error = false;
}

$json = json_encode( $json );

//*/ // End of my code

return $json;