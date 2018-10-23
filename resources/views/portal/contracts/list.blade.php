@extends('layout.index')

@section('title', Auth::user()->info->name . "'s Contracts")

@section('css')
    <style>
    td,
    th {
      padding: 0.25em;
      border: 1px solid black;
    }

    /*
    tbody:hover td[rowspan], tr:hover td {
       background: red;
    }*/

    tbody:hover,
    tr.hover,
    th.hover,
    td.hover,
    tr.hoverable:hover {
      background-color: grey;
      color: white;
    }

    </style>
@endsection

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-stripped">
                    <tbody>
                        <tr>
                            <th>
                                Type
                            </th>
                            <th>
                                Status
                            </th>
                            <th>
                                [Corp Ticker] <br />Issued By
                            </th>
                            <th>
                                Assigned To
                            </th>
                            <th>
                                Accepted By
                            </th>
                        </tr>
                        <tr>
                            <th>
                                Price / Reward
                            </th>
                            <th>
                                Collateral (If Applicable)
                            </th>
                            <th>
                                Volume
                            </th>
                            <th>
                                Start System
                            </th>
                            <th>
                                End System
                            </th>
                        </tr>
                    </tbody>
                    @foreach($contracts as $contract)
                        <tbody class="click-row" data-href="{{ route('contract.view', ['member' => $member->id, 'contract_id' => $contract->id]) }}">
                            <tr>
                                <td>
                                    {{ $contract->type }}
                                </td>
                                <td>
                                    {{ $contract->status }}
                                </td>
                                <td>
                                    @if (!is_null($contract->issuer_corp))
                                        [{{ $contract->issuer_corp->ticker }}]
                                    @endif
                                    {{ !is_null($contract->issuer) ? $contract->issuer->name : "Unknown Character ". $contract->issuer_id }}
                                </td>
                                <td>
                                    @if (!is_null($contract->assignee_type))
                                        {{ $contract->assignee->name }}
                                    @endif
                                </td>
                                <td>
                                    @if (!is_null($contract->acceptor_type))
                                        {{ $contract->acceptor->name }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    @if ($contract->getOriginal('type') === "item_exchange")
                                        {{ number_format($contract->price) }} ISK
                                    @elseif ($contract->getOriginal('type') === "courier")
                                        {{ number_format($contract->reward) }} ISK
                                    @endif
                                </td>
                                <td>
                                    @if ($contract->getOriginal('type') === "item_exchange")
                                        N/A
                                    @elseif ($contract->getOriginal('type') === "courier")
                                        {{ number_format($contract->collateral) }} ISK
                                    @endif
                                </td>
                                <td>
                                    {{ number_format($contract->volume) }} m<sup>3</sup>
                                </td>
                                <td>
                                    {{ !is_null($contract->start) ? $contract->start->name : "Unknown Location ". $contract->start_location }}
                                </td>
                                <td>
                                    {{ !is_null($contract->end) ? $contract->end->name : "Unknown Location ". $contract->end_location }}
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="justify-content-center">
                    {{ $contracts->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function ($) {
            $(".click-row").click(function () {
                window.location = $(this).data('href');
            })
        });
    </script>
@endsection
