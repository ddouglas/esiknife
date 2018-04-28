@extends('layout.index')

@section('title', 'My Wallet Transactions')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        @include('portal.wallet.extra.nav')
        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-stripped">
                    <tr>
                        <th>
                            When
                        </th>
                        <th>
                            Type
                        </th>
                        <th>
                            Price
                        </th>
                        <th>
                            Quantity
                        </th>
                        <th>
                            Credit
                        </th>
                        <th>
                            Client
                        </th>
                        <th>
                            Where
                        </th>
                    </tr>
                    @foreach($transactions as $transaction)
                        <tr>
                            <td>
                                {{ $transaction->date->format("Y.m.d H:i \EVE") }}
                            </td>
                            <td>
                                {{ !is_null($transaction->type) ? $transaction->type->name : "Unknown Type ". $transaction->type_id }}
                            </td>
                            <td>
                                {{ $transaction->unit_price }}
                            </td>
                            <td>
                                {{ $transaction->quantity }}
                            </td>

                            <td>
                                {{ $transaction->credit }}
                            </td>

                            <td>
                                {{ !is_null($transaction->client) ? $transaction->client->name : "Unknown Client ". $transaction->client_id }}
                            </td>

                            <td>
                                {{ !is_null($transaction->location) ? $transaction->location->name : "Unknown Location ". $transaction->location_id }}
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <hr />
        </div>
        <div class="row">
            <div class="col-lg-12">
                <hr />
                <div class="d-flex justify-content-center">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
