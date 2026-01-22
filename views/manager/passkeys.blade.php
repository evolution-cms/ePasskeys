<div class="epasskeys-manager">
    <h1>Passkeys</h1>

    @if (session('epasskeys.message'))
        <div class="alert alert-info">{{ session('epasskeys.message') }}</div>
    @endif

    <div class="epasskeys-create">
        <h3>Create passkey</h3>
        <form id="epasskeys-register-form" method="POST" action="{{ $registerUrl }}">
            {!! csrf_field() !!}
            <div class="form-group">
                <label for="epasskeys-name">Name</label>
                <input type="text" id="epasskeys-name" name="name" class="form-control" placeholder="My device">
            </div>
            <input type="hidden" name="passkey" id="epasskeys-register-response" value="">
            <button type="button" id="epasskeys-register-button" class="btn btn-primary">Create</button>
        </form>
    </div>

    <hr>

    <h3>Existing passkeys</h3>
    @if ($passkeys->isEmpty())
        <p>No passkeys yet.</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Last used</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($passkeys as $passkey)
                    <tr>
                        <td>{{ $passkey->name }}</td>
                        <td>{{ $passkey->last_used_at ? $passkey->last_used_at->diffForHumans() : 'Not used yet' }}</td>
                        <td>{{ $passkey->created_at ? $passkey->created_at->toDateTimeString() : '' }}</td>
                        <td>
                            <form method="POST" action="{{ $deleteBaseUrl }}/{{ $passkey->id }}/delete">
                                {!! csrf_field() !!}
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <script src="{{ $assetsUrl }}/simplewebauthn.umd.js"></script>
    <script src="{{ $assetsUrl }}/epasskeys.js"></script>
    <script>
        (function () {
            window.ePasskeysRegister({
                button: '#epasskeys-register-button',
                form: '#epasskeys-register-form',
                optionsUrl: @json($optionsUrl),
                responseInput: '#epasskeys-register-response',
                nameInput: '#epasskeys-name'
            });
        })();
    </script>
</div>
