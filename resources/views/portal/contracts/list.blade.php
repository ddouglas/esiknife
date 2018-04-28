@extends('layout.index')

@section('title', 'My Contracts')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-stripped">
                    <tr>
                        <th rowspan="2">
                            Id
                        </th>
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
                            [Corp Ticker] <br />Assigned To
                        </th>
                        <th>
                            [Corp Ticker] <br />Accepted By
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
                    @foreach($contracts as $contract)
                        <tr>
                            <td rowspan="2" class="align-middle">
                                <a href="{{ route('contract.view', ['id' => $contract->id]) }}">{{ $contract->id }}</a>
                            </td>
                            <td>
                                {{ $contract->type }}
                            </td>
                            <td>
                                {{ $contract->status }}
                            </td>
                            <td>
                                [{{ $contract->issuer->corporation->ticker }}] {{ $contract->issuer->name }}
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
                                    {{ number_format($contract->price) }}
                                @elseif ($contract->getOriginal('type') === "courier")
                                    {{ number_format($contract->reward) }}
                                @endif
                            </td>
                            <td>
                                @if ($contract->getOriginal('type') === "item_exchange")
                                    N/A
                                @elseif ($contract->getOriginal('type') === "courier")
                                    {{ number_format($contract->collateral) }}
                                @endif
                            </td>
                            <td>
                                {{ number_format($contract->volume) }} m<sup>3</sup>
                            </td>
                            <td>
                                {{ $contract->start->name }}
                            </td>
                            <td>
                                {{ $contract->end->name }}
                            </td>
                        </tr>
                    </tr?>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
@endsection
