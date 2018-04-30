@extends('layout.index')

@section('title', 'My EveMail')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        <div class="row">

            <div class="col-lg-8 offset-lg-2">
                <div class="card">
                    <div class="card-header text-center">
                        {{ $mail->subject }}
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0">
                            <tr>
                                <td width=25%>
                                    <strong>From:</strong>
                                </td>
                                <td>
                                    {{ $mail->sender->name }}
                                </td>
                            </tr>
                            <tr>
                                <td width=25%>
                                    <strong>Recipients:</strong>
                                </td>
                                <td>
                                    <?php $y = $mail->recipients()->count(); $x=1; ?>
                                    @foreach ($mail->recipients as $recipient)
                                        {{ !is_null($recipient->info) ? $recipient->info->name : "Unknown Recipient ". $recipient->recipient_id }}{{ ($x < $y) ? "," : "" }}
                                        <?php $x++; ?>
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <td width=25%>
                                    <strong>Subject:</strong>
                                </td>
                                <td>
                                    {{ $mail->subject }}
                                </td>
                            </tr>
                            <tr>
                                <td width=25%>
                                    <strong>Date / Sent:</strong>
                                </td>
                                <td>
                                    {{ $mail->sent->toDayDateTimeString() }}
                                </td>
                            </tr>
                        </table>
                        <div class="card-body">
                            {!! nl2br($mail->body)!!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
