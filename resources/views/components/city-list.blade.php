<select  name="consignee_city" id="consignee_city" class="form-control input-lg">
    <option value="{{strtoupper($cname)}}" selected>{{strtoupper($cname)}}</option>
    @foreach($CityList as $city)
    {{$selected="";}}
        @if($city->CityName == strtoupper($cname))
            @php $selected = 'selected' @endphp
        @endif

        <option value="{{$city->CityName}}" {{ $selected }}>{{$city->CityName}}</option>
    @endforeach
    
</select>
