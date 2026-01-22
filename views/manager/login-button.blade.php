<div class="form-group epasskeys-login">
    <script src="{{ $assetsUrl }}/simplewebauthn.umd.js"></script>
    <script src="{{ $assetsUrl }}/epasskeys.js"></script>

    @if (session('epasskeys.message'))
        <div class="alert alert-danger">{{ session('epasskeys.message') }}</div>
    @endif

    <button type="button" id="epasskeys-login-button" class="btn btn-secondary">
        Sign in with Passkey
    </button>

    <form id="epasskeys-login-form" method="POST" action="{{ $authUrl }}" style="display:none;">
        {!! csrf_field() !!}
        <input type="hidden" name="start_authentication_response" id="epasskeys-auth-response" value="">
        <input type="hidden" name="remember" id="epasskeys-remember" value="0">
    </form>

    <script>
        window.ePasskeysLogin({
            button: '#epasskeys-login-button',
            form: '#epasskeys-login-form',
            optionsUrl: @json($optionsUrl),
            responseInput: '#epasskeys-auth-response',
            rememberInput: '#epasskeys-remember',
            rememberSelector: '#rememberme'
        });
    </script>
</div>
