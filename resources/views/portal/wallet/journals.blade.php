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
                            Date
                        </th>
                        <th>
                            Type
                        </th>
                        <th>
                            Amount
                        </th>
                        <th>
                            Balance
                        </th>
                        <th>
                            Description
                        </th>
                    </tr>
                    @foreach($journal as $entry)
                        <tr>
                            <td>
                                {{ $entry->date->format("Y.m.d H:i \EVE") }}
                            </td>
                            <td>
                                {{ $entry->ref_type }}
                            </td>
                            <td>
                                {{ $entry->amount }}
                            </td>
                            <td>
                                {{  $entry->balance }}
                            </td>
                            <td>
                                {{ $entry->description }}
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
                    {{ $journal->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
