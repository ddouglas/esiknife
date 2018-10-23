@extends('layout.index')

@section('title', Auth::user()->info->name . "'s Contracts")

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered">
                    <tr>
                        <th colspan="2" class="text-center">
                            Contract Details for {{ $contract->id }}
                        </th>
                    </tr>
                    <tr>
                        <th>
                            Type
                        </th>
                        <td>
                            {{ $contract->type }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Status
                        </th>
                        <td>
                            {{ $contract->status }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            [Corp Ticker] Issued By
                        </th>
                        <td>
                            @if (!is_null($contract->issuer_corp))
                                [{{ $contract->issuer_corp->ticker }}]
                            @endif
                            {{ !is_null($contract->issuer) ? $contract->issuer->name : "Unknown Character ". $contract->issuer_id }}
                        </td>
                    </tr>
                    <tr>

                        <th>
                            Assigned To
                        </th>
                        <td>
                            @if (!is_null($contract->assignee_type))
                                {{ $contract->assignee->name }}
                            @endif
                        </td>
                    </tr>
                    <tr>

                        <th>
                            Accepted By
                        </th>
                        <td>
                            @if (!is_null($contract->acceptor_type))
                                {{ $contract->acceptor->name }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Price / Reward
                        </th>
                        <td>
                            @if ($contract->getOriginal('type') === "item_exchange")
                                {{ number_format($contract->price) }}
                            @elseif ($contract->getOriginal('type') === "courier")
                                {{ number_format($contract->reward) }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Collateral (If Applicable)
                        </th>
                        <td>
                            @if ($contract->getOriginal('type') === "item_exchange")
                                N/A
                            @elseif ($contract->getOriginal('type') === "courier")
                                {{ number_format($contract->collateral) }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Volume
                        </th>
                        <td>
                            {{ number_format($contract->volume) }} m<sup>3</sup>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Start System / Station / Structure
                        </th>
                        <td>
                            {{ !is_null($contract->start) ? $contract->start->name : "Unknown Location ". $contract->start_location }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            End System / Station / Structure
                        </th>
                        <td>
                            {{ !is_null($contract->end) ? $contract->end->name : "Unknown Location ". $contract->end_location }}
                        </td>
                    </tr>
                </table>
                @if ($contract->getOriginal('type') === "item_exchange")
                    <hr />
                @endif
            </div>
            <div class="col-lg-12">
                <table class="table table-bordered">
                    <tr>
                        <th>
                            Item Id
                        </th>
                        <th>
                            Name
                        </th>
                        <th>
                            Quantity
                        </th>
                        <th>
                            Buying / Selling
                        </th>
                    </tr>
                    @foreach ($contract->items as $item)
                        <tr>
                            <td>
                                {{ $item->id }}
                            </td>
                            <td>
                                {{ $item->name }}
                            </td>
                            <td>
                                {{ number_format($item->pivot->quantity) }}
                            </td>
                            <td>
                                {{ $item->pivot->is_buy ? "Buying" : "Selling" }}
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
@endsection
