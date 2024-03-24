<form action="{{route('waybill.store')}}" method="post">
    @csrf
    <input type="text" name="waybill_id" id=""><br>
    <input type="text" name="courier_code" id=""><br>
    <button type="submit">track</button>
</form>
