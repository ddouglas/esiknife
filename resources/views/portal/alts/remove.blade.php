@extends('layout.index')

@section('title', 'Welcome To ESIKnife')

@section('content')
    <div class="container">
        <div class="row mt-3">
            <div class="col-lg-8 offset-lg-2">
                <h3 class="mb-1 text-center">Remove an Alt From Your Account</h3>
                <hr />
                @include('extra.alert')
                <form action="{{ url()->full() }}" method="post">
                    <div class="card">
                        <div class="card-header">
                            Job Status
                        </div>
                        <div class="card-body">
                            Please confirm below that you would like to remove the character {{ $alt->info->name }} from your account and delete all data associated with this character. This action cannot be undone. You will have to add the character back to your account so that its data can be processed again. Are you sure you wish to continue?
                        </div>
                        <div class="card-footer text-center">
                            {{ csrf_field() }}
                            {{ method_field("DELETE") }}
                            <button type="submit" class="btn btn-danger">Yes, please delete this character</button>
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">Wrong button, get me outta here</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $('#all').on('click', function(){
            var checkboxes = $(':checkbox.item');
            checkboxes.prop('checked', !checkboxes.prop('checked'));
        });
    </script>
@endsection
