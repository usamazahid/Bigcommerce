@extends('layout.layout')

@section('style')
<style type="text/css"> 
  body{
   
    padding: 50px;
  }
  @page {
    margin: 0;
  }
  *{
    font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
    color:#333;
    font-size: 11px;
    font-weight:bold;
  }
  h1, h2, h3, h4, h5, h6 {
    font-family: inherit;
    font-weight: 500;
    line-height: 1.1;
    color: inherit;
  }
  h2{
    font-size: 25px;
  }
  h3{
    font-size: 25px;
  }

  .borders {
    border-left: 1px dashed #ccc;
    border-right: 1px dashed #ccc;
  }
  .header{

    text-align: center;
    vertical-align: middle;
    line-height: 100px;
  }
  .payment-details{
    margin: 20px 0px;
  }
  .container{
    width: 100%;
    border: 1px solid black;
    float: left;
    padding: 1%;
    margin-bottom: 20px;
    margin top: 20px;
  }
  .text-left{
    text-align: left;
    width: 50%;
    float: left;
    display: inline-block;
  }
  .text-right{
    text-align: right;
    width: 50%;
    display: inline-block;
  }
  .text-center{
    text-align: center;
    width: 50%;
    float: left;
    display: inline-block;
  }

table {
  width: 50%;
  height: 150px;

  float: left;
 
}
td, th{

  text-align: left;
}
div {
  text-align: center;
}
.heading{
    margin:0 !important;
}

@media print  
{
    .container{
        page-break-inside: avoid;
    }

  #btn_go_back, #btn_print {
    display: none;
  }
}

</style>
@endsection

@section('container')


@foreach($cndata as $index=>$cn)

<div class="wrapper">

 <div class="container">
  <div><h3>CONSIGNEE COPY</h3></div>
  <span><u>For Delivery Services, You Can Call Us On +92 320 4495499</u></span>
  <div class="leftpanel">
    <table>
     <tr>
       <td><img src="{{ asset('assets/img/logo-black.png') }}"></td>
       <td><div class="barcodeTarget" id="barcodevalue{{$index}}">{{$cn->cnno}}</div></td>   
     </tr>
     <tr>
      <td colspan="1">Shipper:</td>
      <td colspan="3">{{$cn->shippername}}</td>
    </tr>
         <tr>
      <td colspan="1">City:</td>
      <td colspan="3">{{$cn->shippercity}}</td>
    </tr>
  
     <tr>
       <td>Customer Ref.#</td>
       <td>{{$cn->consigneeref}}</td>
     </tr>

   </table>

    
  </div>
  <div class="rightpanel">
      <table>
     <tr>
       <td>Date</td>
       <td>{{$cn->bookingdate}}</td>
       <td>Time</td>
       <td>{{date('H:i',strtotime($cn->bookingtime))}}</td>
     </tr>
        <tr>
       <td>Service</td>
       <td>{{$cn->Service_Name}}</td>
       <td>Weight</td>
       <td>{{$cn->weight}}</td>
     </tr>
        <tr>
       <td>Fargile</td>
       <td>No</td>
       <td>Pieces</td>
       <td>{{$cn->pieces}}</td>
     </tr>
        <tr>
       <td>Origin</td>
       <td>{{$cn->origincity}}</td>
       <td>Destination</td>
       <td>{{$cn->CityName}}</td>
     </tr>
     <tr>
       <td>COD Amount</td>
       <td>PKR Rs.{{$cn->amount}}.00/-</td>
       <td>Decid. Ins. Value</td>
       <td>Rs. 0/-</td>
     </tr>
    <tr>
  <td colspan="1">Consignee:</td>
   <td colspan="3">{{$cn->consigneename}}</td>
     </tr>
     <tr>
       <td colspan="1">Contact:</td>
       <td colspan="3">{{$cn->consigneecell}}</td>
     </tr>
     <tr>
       <td colspan="1">Address:</td>
       <td colspan="3">{{$cn->consigneeaddress}}</td>
     </tr>
     <tr>
       <td>Remarks</td>
       <td colspan="3">{{$cn->consignmentremarks}}</td>
     </tr>
   </table>
   <table style="clear: both; width: 100% !important; height: auto; border:1px solid black">
     <tr valign="top">
       <td>Product Details:</td>
       <td width="85%">{{$cn->consignmentdescription}}</td>
     </tr>
   </table>
   
  </div>
   <div style="text-align: center; clear:both;">
     SPECIAL NOTE for CONSIGNEE: (1) Please don’t accept, if shipment is not intact. (2) Please don’t open the parcel before payment. (3)
Incase of any defects/complaints in parcel, please contact the shipper/brand. MXC is not responsible for any defect.

   </div>
    
</div>

@endforeach
<input type="submit" name="go_back" id="btn_go_back" value="Go Back" class="btn btn-dark">
<br>
<input type="submit" name="print" id="btn_print" value="Print" class="btn btn-success">
@endsection

@section('scripts')
<script type="text/javascript" src="{{ asset('assets/js/jquery-barcode.js') }}"></script>
<script type="text/javascript">
$('#btn_print').on('click', function() { window.print(); });
           function generateBarcode(element, value){
        
        
        var btype = "code128";
        var renderer = "css";

        var settings = {
          output:renderer,
          bgColor: "#FFFFFF",
          color:"#000000",
          barWidth: 1,
          barHeight: 50,
          moduleSize: 5,
          posX: 10,
          posY: 20,
          addQuietZone: 1
        };
       
     
          $('#'+element).html("").show().barcode(value, btype, settings);

      }
          

      
      $(function(){
        var counnter = 0;
        $('.barcodeTarget').each(function(){
        generateBarcode('barcodevalue' + counnter, $(this).text());
        counnter++;
        });
      });

document.getElementById('btn_go_back').addEventListener('click', event => {
    window.location.href = "/orders?context={{$context}}";
});
 </script>
@endsection
