@extends('layout.layout')

@section('style')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.css">
<link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.3/css/select.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/dataTables.checkboxes.css') }}">
@endsection

@section('container')
<div class="container-fluid">
<h2>Single Order #{{ $order_data['order_id'] }}</h2>
<hr>
<div class="row">
	<div class="col-md-4">
		<h4>Consignee Name:</h4> {{ $order_data['shipping_address']->first_name . ' ' .$order_data['shipping_address']->last_name }}
	</div>
	<div class="col-md-4">
		<h4>Address:</h4> {{ $order_data['shipping_address']->street_1 . ' ' .$order_data['shipping_address']->street_2 }}
	</div>
	<div class="col-md-4">
		<h4>City:</h4>
		<td><x-CityList cname="{{ $order_data['shipping_address']->city }}" /></td>
	</div>
</div>

<div class="row">
	<div class="col-md-4">
		<h4>Email:</h4> {{ $order_data['shipping_address']->email }}
	</div>
	<div class="col-md-4">
		<h4>Phone:</h4> {{ $order_data['shipping_address']->phone }}
	</div>
</div>
<hr>
<div class="row">
	<div class="col-md-4">
		<h4>Serive Type:</h4> <x-ServiceList sname="{{ $order_data['payment_method'] }}" />
	</div>
	<div class="col-md-4">
		<h4>Weight:</h4> {{ $order_data['product_weight'] }}
	</div>
	<div class="col-md-4">
		<h4>Items:</h4> {{ $order_data['items'] }}
	</div>
</div>

<div class="row">
	<div class="col-md-4">
		<h4>Payment Method:</h4> {{ $order_data['payment_method'] }}
	</div>
	<div class="col-md-4">
		<h4>Order Amount:</h4> {{ $order_data['total_inc_tax'] }}
	</div>
	<div class="col-md-4">
		<h4>Remarks:</h4> <input type="text" name="consignment_remarks" id="consignment_remarks" value="{{ $order_data['customer_message'] }}" placeholder="Remarks" class="form-control input-lg">
	</div>
</div>

<hr>

<h3>Procduct Details</h3>

<table id="table_products" class="display">
    <thead>
        <tr>
             <th></th>
             <th hidden>ID</th>
             <th hidden>Product ID</th>
             <th>Product Image</th>
             <th>Name</th>
             <th>Details</th>
             <th hidden>Price</th>
             <th hidden>Shipping Charges</th>
             <th hidden>Weight</th>
             <th>Quantity</th>
             <th>Quantity Shipped</th>
        </tr>
    </thead>
    <tbody>

	@foreach($order_data['products'] as $products)
	<tr>
		<td>{{ $products->id }}</td>
		<td hidden><input type="text" name="id" id="id" class="form-control" value="{{ $products->id }}" readonly></td>
		<td hidden>{{ $products->product_id }}</td>
		<td><img src="{{ $order_data['product_images'][$products->product_id]}}" name="product_image" alt="product image" width="120" /></td>
		<td><input type="text" name="consignment_description" id="consignment_description" class="form-control" value="{{ $products->name }}" readonly></td>
		<!--<td>{{ $products->sku }}</td>-->
		<td class="text-center">
		    	@foreach($products->product_options as $product_options)
						    @if($product_options->type == 'File upload field')
						    <strong>{{ $product_options->display_name }}</strong>:  <a href="https://{{ str_replace('stores/', 'store-', $store_hash) }}.mybigcommerce.com/internalapi/v1/orders/{{ $order_data['order_id'] }}/products/{{ $products->id }}/attributes/{{ $product_options->id }}/download" target="_blank">{{ $product_options->display_value }}</a><br>
						    @else
						    <strong>{{ $product_options->display_name }}</strong>:  {{ $product_options->display_value }}<br>
						    @endif
				@endforeach
		</td>	
		<td hidden><input type="text" name="amount" id="amount" class="form-control" value="{{ $products->price_inc_tax  - $order_data['discount'] - $order_data['store_credit_amount'] - $order_data['discount_amount']}}" readonly></td>
		<td hidden><input type="text" name="shipping_cost_inc_tax" id="shipping_cost_inc_tax" class="form-control" value="{{ $order_data['shipping_cost_inc_tax'] }}" readonly></td>
		<td hidden><input type="text" name="weight" id="weight" class="form-control" value="{{ $products->weight }}" readonly></td>
		<td><input type="text" name="quantity" id="quantity" class="form-control" value="{{ $products->quantity - $products->quantity_shipped }}"></td>
		<td>{{ $products->quantity_shipped }}</td>
	</tr>
	@endforeach 

<!-- 	<pre>
		{{ print_r($order_data) }}

	</pre> -->
    </tbody>
</table>
<input type="submit" class="btn btn-primary" value="POST" id="btn_submit">
<input type="submit" class="btn btn-success" value="PRINT" id="btn_print" hidden >
<a href="javascript:history.back()" name="go_back"  class="btn btn-dark">Go Back</a>


@endsection

@section('scripts')
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/select/1.3.3/js/dataTables.select.min.js"></script>
<script type="text/javascript" charset="utf8" src="{{ asset('assets/js/dataTables.checkboxes.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script type="text/javascript">


	var citiesList = [];
	

	$.ajax({
		url: "/citylist",
		method: "get",
		dataType: 'json',
		success: function(data){
		    
		citiesList = data;
		console.log(citiesList);
		console.log(citiesList.findIndex(x => x.CityName ==="ISLAMABAD"));
		},
		error: function(data){
		    console.log(data);
		}
	});

   	
   	var table = $('#table_products').DataTable({
        searching: false,
        scrollX: true,
        columnDefs: [{
        	'width':"1%",
            'checkboxes': {
            'selectRow': true
            },
            targets:   0
        },
        {
            'width': "2%",
            targets: [ 9, 10]
        }],
        "lengthMenu": [ [5, 10, 25, 50, -1], [5, 10, 25, 50, "All"] ],
         select: {
            style:    'multi',
            selector: 'td:first-child'
        }
    });

    $('#btn_submit').on('click', function(){

     	var error = false;

     	var consignee_city = $('#consignee_city').val();

	    var cityIndex = citiesList.findIndex(x => x.CityName.trim() === consignee_city.toUpperCase().trim());

	    var items = [];
	    var data = table.rows({selected:  true}).nodes().to$();
	    $.each(data, function(index, rowId){
	    let obj = Object.fromEntries(new URLSearchParams($(rowId).find('input, select').serialize()));
	    items.push(obj);
		});

	    console.log(items);

	   	console.log(items.length);

	   	console.log(cityIndex);

		if( cityIndex < 0){
		  alert("Invalid City Name: " + consignee_city);
		      error = true;
		      return false;
		}

		var consignment_remarks = $('#consignment_remarks').val();
		var consignee_city = $('#consignee_city').val();
		var service_type = $('#service_type').val();

		var bookingData = {
		'shipping_address_id':"{{ $order_data['shipping_address']->id }}",
		'consignee_name'	: "{{ $order_data['shipping_address']->first_name . ' ' .$order_data['shipping_address']->last_name }}",
		'consignee_email'	: "{{ $order_data['shipping_address']->email }}",
		'consigneeref'		: "{{ $order_data['order_id'] }}",
		'order_number'		: "{{ $order_data['order_id'] }}",
		'consignee_cell'		: "{{ $order_data['shipping_address']->phone }}",
		'consignee_address'	: "{{ $order_data['shipping_address']->street_1 . ' ' .$order_data['shipping_address']->street_2 }}",
		'consignee_city'	: consignee_city,
		'consignment_remarks': consignment_remarks,
		'service_type'		: service_type,
		}

		console.log(bookingData);

		if(!error && items.length >= 1){


	
		$(this).prop('disabled', true);
	
		$.ajax({
			url: "partial_fulfilment",
			method: "post",
			data: {"partial_fulfilment":"{{$order_data['mxc_id']}}", "data" : bookingData, "items":items},
			success: function(data){
			console.log(data);
			$('#btn_print').removeAttr('hidden');
			},
			error: function(data){
			console.log(data);
			}
		});

		console.log('hello');
		}  


    });

       $('#btn_print').on('click', function() {

   		window.location.href = "/slip?uid={{$order_data['mxc_id']}}&context={{ $store_hash }}";
   });

	document.getElementById('btn_go_back').addEventListener('click', event => {
	  $(this).prop('disabled', true);
	  window.location.href = "/orders?context={{ $store_hash }}";
	});
</script>

@endsection
