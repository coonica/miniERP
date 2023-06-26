@extends('main')
@section('result')
    <div class="container mt-2">
        <table class="table border border-collapse border-gray-300">
            <thead>
            <tr>
                <th class="border">Клиент</th>
                <th class="border">Счет</th>
                <th class="border">Статус</th>
                <th class="border">Бюджет</th>
                @for($i = 1; $i <= $result['member_count']; $i++)
                    <th class="border">Mem{{$i}}, est</th>
                    <th class="border">Mem{{$i}}, часы</th>
                    <th class="border">Mem{{$i}}, итого$</th>
                @endfor
                <th class="border">Итого затраты</th>
                <th class="border">Валовая прибыль</th>
            </tr>
            </thead>
            <tbody>
            @foreach($result['invoices'] as $invoice)
                <tr>
                    <td class="border">{{$invoice['project']}}</td>
                    <td class="border">{{$invoice['invoice']}}</td>
                    <td class="border">{{$invoice['status']}}</td>
                    <td class="border">{{$invoice['budget']}}</td>
                    @foreach($invoice['members'] as $member)
                        <td class="border">{{$member['est']}}</td>
                        <td class="border">{{$member['spent']}}</td>
                        <td class="border">{{$member['total']}}</td>
                    @endforeach
                    <td class="border">{{$invoice['expenses']}}</td>
                    <td class="border">{{$invoice['profit']}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
