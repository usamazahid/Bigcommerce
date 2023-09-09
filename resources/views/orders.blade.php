@extends('layout.layout')

@section('style')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.css">
<link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.3/css/select.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/dataTables.checkboxes.css') }}">
<style type="text/css">
	.form-control{
		width: auto !important;
	}
</style>
@endsection

@section('container')
<div class="container-fluid">
<h2>Orders</h2>
<a href="/orders?context={{$store_hash}}&status_id=9" class="btn btn-outline-danger">Packing Department</a>
<a href="/orders?context={{$store_hash}}&status_id=3" class="btn btn-outline-primary">Partially Shipped</a>
<hr>
<table id="table_orders" class="display">
    <thead>
        <tr>

            <th>Order#</th>
            <th>Options</th>
            <th>Name</th>
            <th hidden>Email</th>
            <th hidden>Phone</th>
            <th hidden>Address</th>
            <th>City</th>
            <th hidden>Reference</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Items</th>
            <th hidden>Weight</th>
            <th hidden>Service</th>
            <th hidden>Remarks</th>
            <th hidden>Order ID</th>
            
        </tr>
    </thead>
    <tbody>

	@foreach($order_data as $order)
	<tr>

		<td>{{ $order['order_id'] }}</td>
		<td><input type="submit" name="btn_partial_shipment"  class="btn btn-info btn_partial_shipment" class="form-control" value="Ship Order" data-order-id="{{ $order['order_id'] }}"></td>
		<td><input type="text" name="consignee_name" id="consignee_name" class="form-control" value="{{ $order['shipping_address']->first_name . ' ' .$order['shipping_address']->last_name }}"></td>
		<td hidden><input type="text" name="consignee_email" id="consignee_email" class="form-control" value="{{ $order['shipping_address']->email }}"></td>
		<td hidden><input type="text" name="consignee_cell" id="consignee_cell" class="form-control" value="{{ $order['shipping_address']->phone }}"></td>
		<td hidden><input type="text" name="consignee_address" id="consignee_address" class="form-control" value="{{ $order['shipping_address']->street_1 . ' ' . $order['shipping_address']->street_2 }}"></td>
		<td>
		<select  name="consignee_city" id="consignee_city" class="form-control input-lg">
            <option value="{{strtoupper($order['shipping_address']->city)}}" selected>{{strtoupper($order['shipping_address']->city)}}</option>
            @foreach($CityList as $city)
            {{$selected="";}}
                @if($city->CityName == strtoupper($order['shipping_address']->city))
                    @php $selected = 'selected' @endphp
                @endif
        
                <option value="{{$city->CityName}}" {{ $selected }}>{{$city->CityName}}</option>
            @endforeach
            
        </select>
        </td>
		<td hidden><input type="text" name="consignee_reference" id="consignee_reference" class="form-control" value="{{ $order['order_id'] }}"></td>
		<td><input type="text" name="consignment_description" id="consignment_description" class="form-control" value="{{ $order['product_description'] }}"></td>
		<td><input type="text" name="amount" id="amount" class="form-control" value="{{ $order['total_inc_tax'] }}"></td>
		<td><input type="text" name="pieces" id="pieces" class="form-control" value="{{ $order['items'] }}"></td>
		<td hidden><input type="text" name="weight" id="weight" class="form-control" value="{{ $order['product_weight'] }}"></td>
		<td hidden></td>
		<td hidden><input type="text" name="consignment_remarks" id="consignment_remarks" class="form-control" value=""></td>
		<td hidden><input type="text" name="order_id" id="order_id" class="form-control" value="{{ $order['order_id'] }}"></td>
	</tr>
	@endforeach 


    </tbody>
</table>

<input type="submit" class="btn btn-primary" value="POST" id="btn_submit" hidden>
<input type="submit" class="btn btn-success" value="PRINT" id="btn_print" hidden>
</div>

@endsection

@section('scripts')
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/select/1.3.3/js/dataTables.select.min.js"></script>
<script type="text/javascript" charset="utf8" src="{{ asset('assets/js/dataTables.checkboxes.min.js') }}"></script>
<script>
$(document).ready( function () {
	
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

   	
   	var table = $('#table_orders').DataTable({
        scrollX: true,
         select: {
            style:    'multi',
            selector: 'td:first-child'
        },
        aLengthMenu: [
        [-1, 50, 100, 200],
        ["All", 50, 100, 200]
        ],
        order: [[1, 'desc']],
    });

   $('#btn_submit').on('click', function(){
	var data = table.rows({selected:  true}).nodes().to$();
	var bookingData = [];
	var error = false;


	$.each(data, function(index, rowId){

	let obj = Object.fromEntries(new URLSearchParams($(rowId).find('input, select').serialize()));

	var cityIndex = citiesList.findIndex(x => x.CityName.trim() === obj.consignee_city.toUpperCase().trim());

	if( cityIndex < 0){
	  alert("Invalid City Name: " + obj.consignee_city);
	      error = true;
	      return false;
	}else if(obj.weight  == "" || obj.weight <= 0){
	  alert("Weight Cannot be Empty or 0");
	      error = true;
	      return false;
	 }
	 
	bookingData.push(obj);

	});

	if(!error && bookingData.length >= 1){
	
	$(this).prop('disabled', true);
	

		$.ajax({
			url: "fulfilment",
			method: "post",
			data: {"bulk":"{{$mxc_id}}", "data" : bookingData},
			success: function(data){
			console.log(data);
			$('#btn_print').removeAttr('hidden');
			},
			error: function(data){
			console.log(data);
			}
		});
	}  

	console.log(bookingData);
	});

   $('#btn_print').on('click', function() {

   		window.location.href = "/slip?uid={{$mxc_id}}&context={{$store_hash}}";
   });


   	const divs = document.querySelectorAll('.btn_partial_shipment');
	divs.forEach(el => el.addEventListener('click', event => {
	    console.log(this);
	$(this).prop('disabled', true);
	  window.location.href = "/PartialShipment?order_id="+event.target.getAttribute('data-order-id')+"&action=partial_shipment&context={{$store_hash}}";
	}));

} );

</script>
@endsection
