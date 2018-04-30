@extends('layout.index')

@section('title', 'My EveMail')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-hover">
                    <tr>
                        <th>
                            Date
                        </th>
                        <th>
                            Label
                        </th>
                        <th>
                            From
                        </th>

                        <th>
                            Subject
                        </th>
                        <th>
                            Read
                        </th>
                    </tr>
                    @foreach($mails as $mail)
                        <tr>
                            <td>
                                <a href="{{ route('mail', ['id' => $mail->id]) }}">{{ $mail->sent->toDateString() }}</a>
                            </td>
                            <td>
                                {{ $mail->pivot->labels }}
                            </td>
                            <td>
                                {{ !is_null($mail->sender) ? $mail->sender->name : "Unknown Sender " . $mail->sender_id }}
                            </td>
                            <td>
                                {{ str_limit($mail->subject, 50) }}
                            </td>
                            <td>
                                {{ $mail->pivot->is_read ? "" : "Not" }} Read
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
@endsection
