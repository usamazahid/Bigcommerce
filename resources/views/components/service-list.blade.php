<select  name="service_type" id="service_type" class="form-control input-lg">
        @if($sname == 'Cash on Delivery')
        <option value="1" selected>COD</option>
        @else
        <option value="5" selected>Zero COD</option>
        @endif
    
</select>
