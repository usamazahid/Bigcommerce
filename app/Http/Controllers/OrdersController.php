<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class OrdersController extends BaseController
{
    public function index(Request $request){

        $store_data = DB::table('bigcommerce_app_installs')->where('store_hash', $request->get('context'))->get();
        $store_data = $store_data[0];
        
        $status_id = $request->get('status_id', 9);
        
       $requestConfig = [
            'headers' => [
                'X-Auth-Client' => $store_data->app_client_id,
                'X-Auth-Token'  => $store_data->access_token,
                'Content-Type'  => 'application/json',
                'Accept' => 'application/json',
            ],
            'query' => [
                'status_id' => $status_id,
                'limit' => '250',
                'sort' => 'date_created:desc',
                'page' => 1
            ],
            
        ];

        $client = new Client(['verify'=>false]);
        $main_order_data = $client->request('GET', 'https://api.bigcommerce.com/'.$store_data->store_hash.'/v2/orders', $requestConfig);
        $main_order_data_response = json_decode($main_order_data->getBody());

        $order_data = [];
        
        
        //$pending_statuses = array(1, 7, 11, 8, 10, 2, 5, 6, 4, 13, 12, 14);
        if(count((array)$main_order_data_response)>0){
            foreach($main_order_data_response as $data){
                    //if(!in_array($data->status_id, $pending_statuses)){
                    // $shpping_address = $client->request('GET', 'https://api.bigcommerce.com/'.$store_data->store_hash.'/v2/orders/'.$data->id.'/shipping_addresses', $requestConfig);
                    // $shpping_address_response = $shpping_address->getBody();
    
                    // $products = $client->request('GET', 'https://api.bigcommerce.com/'.$store_data->store_hash.'/v2/orders/'.$data->id.'/products', $requestConfig);
                    // $products_response = json_decode($products->getBody());
    
                    //  $products_response = array_filter($products_response, function ($item) {
                    //     return $item->quantity-$item->quantity_shipped >= 1;
                    // });
    
                    $product_weight = 0;
                    $product_description = '';
                    $amount = 0;
                    
                    // foreach($products_response as $products){
                    //     $remaining_quantity = $products->quantity - $products->quantity_shipped;
                    //     $product_weight += $products->weight * $remaining_quantity;
                    //     $product_description .= $products->name . ',';
                    //     $amount += $remaining_quantity * $products->price_inc_tax;
                    // }
    
                    $remaining_items = $data->items_total - $data->items_shipped;
    
    
                    if($data->payment_method != 'Cash on Delivery'){
                        $amount = 0;
                    }
                    $order_data[$data->id]['mxc_id'] = $store_data->mxc_id;
                    $order_data[$data->id]['order_id'] = $data->id;
                    $order_data[$data->id]['total_inc_tax'] = $data->total_inc_tax;
                    $order_data[$data->id]['items'] = $remaining_items;
                    $order_data[$data->id]['product_weight'] = $product_weight;
                    $order_data[$data->id]['product_description'] = $product_description;
                    $order_data[$data->id]['payment_method'] = $data->payment_method;
                    // $order_data[$data->id]['products'] = $products_response;
                     $order_data[$data->id]['products'] = $data->billing_address;
                    // $order_data[$data->id]['shipping_address'] = json_decode($shpping_address_response)[0];  
                    $order_data[$data->id]['shipping_address'] = $data->billing_address;  
                    
               // }
            }

        }

        $CityList = DB::table('city')->get();

        //print_r(json_decode($response)[0]);
        return view('orders', ['order_data' => $order_data, 'CityList'=>$CityList, 'mxc_id'=>$store_data->mxc_id, 'store_hash'=>$request->get('context')]);

    }

    public function CityList(){
        $CityList = DB::table('city')->get();
        return $CityList;
    }


    public function fulfilment(Request $request)
    {

        $orders = $request->post('data');
        $store_data = DB::table('bigcommerce_app_installs')->where('mxc_id', $request->post('bulk'))->get();
        $store_data = $store_data[0];

        $cndata = array();
        $bc_order_num = array();
        $print_cn = array();

        $users = DB::table('users')->where('mid', $request->post('bulk'))->get()[0];

        $pricing = DB::table('newpricing')->where('userId', $users->User_id)->get();

        $lastentry = DB::table('bookings')
        ->selectRaw('if(max(cnno) is NULL, 10000000, SUBSTRING_INDEX(max(cnno), "-", -1))  + 1 AS cnno')
        ->where('account', $users->mid)
        ->where('cnno', 'like', '%-02-%')
        ->get();
        $varcnno = $lastentry[0]->cnno;
        foreach($orders as $order){
            
        $destcity = DB::table('city')->where('CityName', $order['consignee_city'])->get('CityID');
        $servicetype = $order['service_type'];
        $vactoprint = $users->mid."-02-0".$varcnno;
        $orderDate = date("Y-m-d");
        $weight = $order['weight'];
        $inOrigin = true;
        $serviceCharges = 0;

        foreach($pricing as $price){
            if($price->cityId == $destcity[0]->CityID->CityID){
                if($weight <= $price->wFirst){
                    $serviceCharges = $price->pFirst;
                }elseif($weight <= $price->wSecond){
                    $serviceCharges = $price->pSecond;
                }elseif($weight <= $price->wThird){
                    $serviceCharges = $price->pThird;
                }else{
                    $thirdPrice = $price->pThird;
                    $remainingPrice = ceil($weight - $price->wThird) * $price->addKg;
                    $serviceCharges = $thirdPrice + $remainingPrice;
                }
            $inOrigin = false;
            }
        }

        if($inOrigin){
            $generalRates = DB::table('newpricing')->where('userId', $users->User_id)->where('cityId', 0)->get()[0];
            if($weight <= $generalRates->wFirst){
                $serviceCharges = $generalRates->pFirst;
            }elseif($weight <= $generalRates->wSecond){
                $serviceCharges = $generalRates->pSecond;
            }elseif($weight <= $generalRates->wThird){
                $serviceCharges = $generalRates->pThird;
            }else{
                $thirdPrice = $generalRates->pThird;
                $remainingPrice = ceil($weight - $generalRates->wThird) * $generalRates->addKg;
                $serviceCharges = $thirdPrice + $remainingPrice;
            }
        }
        array_push($cndata, array(
                                'account' => $users->mid,
                                'bookingdate' => $orderDate,
                                'cnno' => $vactoprint,
                                'shippername' => $users->fname . ' ' .$users->lname,
                                'origincity' => $users->city,
                                'shippercity' => $users->city,
                                'shipperarea' => $users->area,
                                'shippercell' => $users->mobile,
                                'shipperlandline' => $users->mobile,
                                'shipperemail' => $users->email,
                                'pickupaddress' => $users->pickupaddress,
                                'returnaddress' => $users->returnaddress,
                                'consigneename' => $order['consignee_name'],
                                'consigneeemail' => $order['consignee_email'],
                                'consigneeref' => $order['order_number'],
                                'consigneecell' => $order['consignee_cell'],
                                'consigneeaddress' => $order['consignee_address'],
                                'destcity' => $destcity[0]->CityID,
                                'servicetype' => $servicetype,
                                'pieces' => $order['pieces'],
                                'weight' => $order['weight'],
                                'consignmentdescription' => $order['consignment_description'],
                                'consignmentremarks' => $order['consignment_remarks'],
                                'amount' => $order['amount'],
                                'serviceCharges' => $serviceCharges,
                                ));


        array_push($bc_order_num, array(
                                        "mid"=>$users->mid, 
                                        "bigcommerce_order_number"=>$order['order_number'], 
                                        "cnno"=> $vactoprint
                                    ));

        array_push($print_cn, array(
                                "account"=>$users->mid, 
                                "cnno"=> $vactoprint
                            ));

            $client = new Client(['verify'=>false]);
            $requestConfig = [
                    'headers' => [
                        'X-Auth-Client' => $store_data->app_client_id,
                        'X-Auth-Token'  => $store_data->access_token,
                        'Content-Type'  => 'application/json',
                        'Accept' => 'application/json',
                    ]
                ];

            $shpping_address = $client->request('GET', 'https://api.bigcommerce.com/'.$store_data->store_hash.'/v2/orders/'.$order['order_number'].'/shipping_addresses', $requestConfig);
            $shpping_address_response = json_decode($shpping_address->getBody())[0];

            $products = $client->request('GET', 'https://api.bigcommerce.com/'.$store_data->store_hash.'/v2/orders/'.$order['order_number'].'/products', $requestConfig);
            $products_response = json_decode($products->getBody());

            $products_response = array_filter($products_response, function ($item) {
                    return $item->quantity-$item->quantity_shipped >= 1;
            });

            $products_id_qty = array();

            foreach($products_response as $products){
                array_push($products_id_qty, array('order_product_id'=>$products->id, 'quantity'=>$products->quantity - $products->quantity_shipped));
            }

            $body = array(
                          'order_address_id' => $shpping_address_response->id,
                          'tracking_number' => $vactoprint,
                          'shipping_method' => 'MXC',
                          'shipping_provider' => '',
                          'tracking_carrier' => '',
                          'comments' => '',
                          'items' => $products_id_qty,
                    );

    
            $requestConfig['body'] = json_encode($body);
          
            $main_order_data = $client->request('POST', 'https://api.bigcommerce.com/'.$store_data->store_hash.'/v2/orders/'.$order['order_number'].'/shipments', $requestConfig);

            $main_order_data_response = $main_order_data->getBody();


            $varcnno += 1;
            

        }

        DB::table('bookings')->insert($cndata);
        DB::table('bigcommerce_orders')->insert($bc_order_num);
        DB::table('printcns')->where('account', $users->mid)->delete();
        DB::table('printcns')->insert($print_cn);
        // $data= $request->post('data');
         return $cndata;

    }

    public function PartialFulfilment(Request $request)
    {
        $order = $request->data;
        $store_data = DB::table('bigcommerce_app_installs')->where('mxc_id', $request->partial_fulfilment)->get();
        $store_data = $store_data[0];

        $cndata = array();
        $bc_order_num = array();
        $print_cn = array();
        $products_id_qty = array();

        $users = DB::table('users')->where('mid', $request->partial_fulfilment)->get()[0];

        $pricing = DB::table('newpricing')->where('userId', $users->User_id)->get();

        $lastentry = DB::table('bookings')
        ->selectRaw('if(max(cnno) is NULL, 10000000, SUBSTRING_INDEX(max(cnno), "-", -1))  + 1 AS cnno')
        ->where('account', $users->mid)
        ->where('cnno', 'like', '%-02-%')
        ->get();
        $varcnno = $lastentry[0]->cnno;
        $destcity = DB::table('city')->where('CityName', $order['consignee_city'])->get('CityID');
        $servicetype = $order['service_type'];
        $vactoprint = $users->mid."-02-0".$varcnno;
        $orderDate = date("Y-m-d");
        $weight = 0;
        $pieces = 0;
        $amount = 0;
        $shipping_charges = 0;
        $consignment_description = '';
        $inOrigin = true;
        $serviceCharges = 0;

        foreach($request->items as $item){
            $weight += $item['weight'] * $item['quantity'];
            $pieces += $item['quantity'];
            $consignment_description .= $item['consignment_description'] . ' | ';
            $shipping_charges += $item['shipping_cost_inc_tax'] * $item['quantity'];
            if($servicetype != 5){
                $amount += $item['amount'] * $item['quantity'];
            }
            array_push($products_id_qty, array('order_product_id'=>$item['id'], 'quantity'=>$item['quantity']));
        }
        
        $amount = round($amount + $shipping_charges);
        
        if($servicetype == 5){
        $amount = 0;
        }

        foreach($pricing as $price){
            if($price->cityId == $destcity[0]->CityID){
                if($weight <= $price->wFirst){
                    $serviceCharges = $price->pFirst;
                }elseif($weight <= $price->wSecond){
                    $serviceCharges = $price->pSecond;
                }elseif($weight <= $price->wThird){
                    $serviceCharges = $price->pThird;
                }else{
                    $thirdPrice = $price->pThird;
                    $remainingPrice = ceil($weight - $price->wThird) * $price->addKg;
                    $serviceCharges = $thirdPrice + $remainingPrice;
                }
            $inOrigin = false;
            }
        }
        

        if($inOrigin){
            $generalRates = DB::table('newpricing')->where('userId', $users->User_id)->where('cityId', 0)->get()[0];
            if($weight <= $generalRates->wFirst){
                $serviceCharges = $generalRates->pFirst;
            }elseif($weight <= $generalRates->wSecond){
                $serviceCharges = $generalRates->pSecond;
            }elseif($weight <= $generalRates->wThird){
                $serviceCharges = $generalRates->pThird;
            }else{
                $thirdPrice = $generalRates->pThird;
                $remainingPrice = ceil($weight - $generalRates->wThird) * $generalRates->addKg;
                $serviceCharges = $thirdPrice + $remainingPrice;
            }
        }

        array_push($cndata, array(
                                'account' => $request->partial_fulfilment,
                                'bookingdate' => $orderDate,
                                'cnno' => $vactoprint,
                                'shippername' => $users->fname . ' ' .$users->lname,
                                'origincity' => $users->city,
                                'shippercity' => $users->city,
                                'shipperarea' => $users->area,
                                'shippercell' => $users->mobile,
                                'shipperlandline' => $users->mobile,
                                'shipperemail' => $users->email,
                                'pickupaddress' => $users->pickupaddress,
                                'returnaddress' => $users->returnaddress,
                                'consigneename' => $order['consignee_name'],
                                'consigneeemail' => $order['consignee_email'],
                                'consigneeref' => $order['order_number'],
                                'consigneecell' => $order['consignee_cell'],
                                'consigneeaddress' => $order['consignee_address'],
                                'destcity' => $destcity[0]->CityID,
                                'servicetype' => $servicetype,
                                'pieces' => $pieces,
                                'weight' => $weight,
                                'consignmentdescription' => 'Misc. Custom Items',
                                'consignmentremarks' => $order['consignment_remarks'],
                                'amount' => $amount,
                                'serviceCharges' => $serviceCharges,
                                ));


        array_push($bc_order_num, array(
                                        "mid"=>$users->mid, 
                                        "bigcommerce_order_number"=>$order['order_number'], 
                                        "cnno"=> $vactoprint
                                    ));

        array_push($print_cn, array(
                                "account"=>$users->mid, 
                                "cnno"=> $vactoprint
                            ));

            $client = new Client(['verify'=>false]);
            $requestConfig = [
                    'headers' => [
                        'X-Auth-Client' => $store_data->app_client_id,
                        'X-Auth-Token'  => $store_data->access_token,
                        'Content-Type'  => 'application/json',
                        'Accept' => 'application/json',
                    ]
                ];

            $body = array(
                          'order_address_id' => $order['shipping_address_id'],
                          'tracking_number' => $vactoprint,
                          'shipping_method' => 'MXC',
                          'shipping_provider' => '',
                          'tracking_carrier' => '',
                          'comments' => '',
                          'items' => $products_id_qty,
                    );

            $requestConfig['body'] = json_encode($body);
          
            $main_order_data = $client->request('POST', 'https://api.bigcommerce.com/'.$store_data->store_hash.'/v2/orders/'.$order['order_number'].'/shipments', $requestConfig);

            $main_order_data_response = $main_order_data->getBody();


            


        DB::table('bookings')->insert($cndata);
        DB::table('bigcommerce_orders')->insert($bc_order_num);
        DB::table('printcns')->where('account', $users->mid)->delete();
        DB::table('printcns')->insert($print_cn);

        return $cndata;
    }

    public function PrintSlip(Request $request){
        
        $cns = DB::table('printcns as pc')->select(
        'b.account',
        'b.bookingdate',
        'b.cnno',
        'b.shippername',
        'b.shippercity',
        'b.shipperarea',
        'b.shippercell',
        'b.shipperlandline',
        'b.shipperemail',
        'b.pickupaddress',
        'b.returnaddress',
        'b.consigneename',
        'b.consigneeref',
        'b.consigneeemail',
        'b.consigneecell',
        'b.consigneeaddress',
        'b.origincity',
        'b.servicetype',
        'b.pieces',
        'b.weight',
        'b.consignmenttype',
        'b.consignmentdescription',
        'b.consignmentremarks',
        'b.holiday',
        'b.specialhandling',
        'b.returnservice',
        'b.handcarry',
        'b.timespecified',
        'b.greenflyer',
        'b.greenbox',
        'b.bookingtime',
        'b.amount',
        'b.status',
        'b.destcity',
        'st.Service_Name',
        'c.CityName'
        )
        ->leftjoin('bookings as b', 'b.cnno', '=', 'pc.cnno')
        ->leftjoin('servicetype as st', 'st.Service_ID', '=', 'b.servicetype')
        ->leftjoin('city as c', 'c.CityID', '=', 'b.destcity')
        ->where('pc.account', $request->get('uid'))->get();

        return view('slip')->with(['cndata'=> $cns, 'context'=>$request->get('context')]);
    }

    public function PartialShipment(Request $request){
        $store_data = DB::table('bigcommerce_app_installs')->where('store_hash', $request->get('context'))->get();
        $store_data = $store_data[0];

        $view = "orders";
        $order_id = '';
        
        if(isset($request->order_id)){
            $order_id = '/'.$request->order_id;
        }        

        if(isset($request->action)){
            $view = "partial_shipment";
        }

       $requestConfig = [
            'headers' => [
                'X-Auth-Client' => $store_data->app_client_id,
                'X-Auth-Token'  => $store_data->access_token,
                'Content-Type'  => 'application/json',
                'Accept' => 'application/json',
            ]
        ];

        $client = new Client(['verify'=>false]);
        $main_order_data = $client->request('GET', 'https://api.bigcommerce.com/'.$store_data->store_hash.'/v2/orders'.$order_id, $requestConfig);
        $main_order_data_response = json_decode($main_order_data->getBody());
        
        
        $order_data = [];
        $product_images_array = [];
        
        $pending_statuses = array(2, 4, 5, 6, 10);

            if(!in_array($main_order_data_response->status_id, $pending_statuses)){
                $shpping_address = $client->request('GET', 'https://api.bigcommerce.com/'.$store_data->store_hash.'/v2/orders/'.$main_order_data_response->id.'/shipping_addresses', $requestConfig);
                $shpping_address_response = $shpping_address->getBody();

                $products = $client->request('GET', 'https://api.bigcommerce.com/'.$store_data->store_hash.'/v2/orders/'.$main_order_data_response->id.'/products', $requestConfig);
                $products_response = json_decode($products->getBody());

                $products_response = array_filter($products_response, function ($item) {
                    return $item->quantity-$item->quantity_shipped >= 1;
                });

                $product_weight = 0;
                $product_description = '';

                foreach($products_response as $products){
                    
                    $product_images = $client->request('GET', 'https://api.bigcommerce.com/'.$store_data->store_hash.'/v3/catalog/products/'.$products->product_id.'/images', $requestConfig);
                    $product_images_response = json_decode($product_images->getBody());
                    
                    $image_thumbnail = array_filter($product_images_response->data, function ($item) {
                    return $item->sort_order == 0;
                    });
                   
                    if(count($image_thumbnail) == 0){
                    $product_images_array[$products->product_id] = "https://microapps.bigcommerce.com/ng-products/ccb6f6c6783e9da0750774e13b08a50f5f995395/svg/default-product.svg";
                    }else{
                    $product_images_array[$image_thumbnail[key($image_thumbnail)]->product_id] = $image_thumbnail[key($image_thumbnail)]->url_thumbnail;
                    }
                    
                    
                    $product_weight += $products->weight * ($products->quantity - $products->quantity_shipped);
                    $product_description .= $products->name . ',';
                }
                    
                $remaining_items = $main_order_data_response->items_total - $main_order_data_response->items_shipped;

                $amount = $main_order_data_response->total_inc_tax;
                if($main_order_data_response->payment_method != 'Cash on Delivery'){
                    $amount = 0;
                }
                $order_data['mxc_id'] = $store_data->mxc_id;
                $order_data['order_id'] = $main_order_data_response->id;
                $order_data['total_inc_tax'] = $amount;
                $order_data['shipping_cost_inc_tax'] = $main_order_data_response->shipping_cost_inc_tax / $main_order_data_response->items_total;
                $order_data['discount'] = $main_order_data_response->coupon_discount / $main_order_data_response->items_total;
                $order_data['discount_amount'] = $main_order_data_response->discount_amount / $main_order_data_response->items_total;
                $order_data['store_credit_amount'] = $main_order_data_response->store_credit_amount / $main_order_data_response->items_total;
                $order_data['items'] = $remaining_items;
                $order_data['product_weight'] = $product_weight;
                $order_data['product_description'] = $product_description;
                $order_data['product_images'] = $product_images_array;
                $order_data['payment_method'] = $main_order_data_response->payment_method;
                $order_data['products'] = $products_response;
                $order_data['customer_message'] = $main_order_data_response->customer_message;
                $order_data['shipping_address'] = json_decode($shpping_address_response)[0];  
            }
        

        


        //print_r(json_decode($response)[0]);
        return view($view, ['order_data' => $order_data, 'mxc_id'=>$store_data->mxc_id, 'store_hash'=>$request->get('context')]);
    }

}
