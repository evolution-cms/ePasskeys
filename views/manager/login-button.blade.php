<div class="form-group epasskeys-login">
    <style>
        .epasskeys-login { margin-top: 1rem; }
        .epasskeys-login__button {
            width: 100%;
            border-radius: 20px;
            background-color: rgba(255, 255, 255, 0.15);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .epasskeys-login__button:hover,
        .epasskeys-login__button:focus {
            background-color: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.35);
        }
        .loginbox-light .epasskeys-login__button {
            color: #444;
            background-color: rgba(0, 0, 0, 0.06);
            border-color: rgba(0, 0, 0, 0.15);
        }
        .loginbox-light .epasskeys-login__button:hover,
        .loginbox-light .epasskeys-login__button:focus {
            background-color: rgba(0, 0, 0, 0.1);
            border-color: rgba(0, 0, 0, 0.25);
        }
    </style>
    <script src="{{ $assetsUrl }}/simplewebauthn.umd.js"></script>
    <script src="{{ $assetsUrl }}/epasskeys.js"></script>

    @if (session('epasskeys.message'))
        <div class="alert alert-danger">{{ session('epasskeys.message') }}</div>
    @endif

    <button type="button" id="epasskeys-login-button" class="btn epasskeys-login__button">
        Sign in with Passkey
    </button>

    <script>
        window.ePasskeysLogin({
            button: '#epasskeys-login-button',
            formAction: @json($authUrl),
            csrfToken: @json(csrf_token()),
            optionsUrl: @json($optionsUrl),
            responseInputId: 'epasskeys-auth-response',
            rememberInputId: 'epasskeys-remember',
            rememberSelector: '#rememberme'
        });
    </script>
</div>
