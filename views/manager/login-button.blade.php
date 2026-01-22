@php($hasPasskey = $hasPasskey ?? false)
@if($hasPasskey)
<div class="form-group epasskeys-login" data-epasskeys="1">
    <style>
        .epasskeys-login { margin-top: 1rem; }
        .epasskeys-login__button {
            width: 100%;
            border-radius: 20px;
            background-color: #449d44;
            color: #fff;
            border: 1px solid #419641;
            padding: 0.65rem 1rem;
            min-height: 44px;
            font-size: 0.95rem;
        }
        .epasskeys-login__button--primary {
            min-height: 50px;
            font-size: 1rem;
        }
        .epasskeys-login__toggle {
            display: inline-block;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: #cbd5f5;
            text-decoration: underline;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }
        .loginbox-light .epasskeys-login__toggle { color: #4b5a78; }
        .epasskeys-login__button:hover,
        .epasskeys-login__button:focus {
            background-color: #5cb85c;
            border-color: #5cb85c;
        }
        .loginbox-light .epasskeys-login__button {
            color: #444;
            background-color: #449d44;
            border-color: #419641;
        }
        .loginbox-light .epasskeys-login__button:hover,
        .loginbox-light .epasskeys-login__button:focus {
            background-color: #5cb85c;
            border-color: #5cb85c;
        }
        #loginfrm.epasskeys-passkey-only .form-group:not(.form-group--logo):not(.epasskeys-login) {
            display: none;
        }
        #loginfrm.epasskeys-passkey-only .form-group--actions,
        #loginfrm.epasskeys-passkey-only .captcha {
            display: none;
        }
    </style>
    <script src="{{ $assetsUrl }}/simplewebauthn.umd.js"></script>
    <script src="{{ $assetsUrl }}/epasskeys.js"></script>

    @if (session('epasskeys.message'))
        <div class="alert alert-danger">{{ session('epasskeys.message') }}</div>
    @endif

    <button type="button" id="epasskeys-login-button" class="btn epasskeys-login__button epasskeys-login__button--primary">
        {{ __('ePasskeys::login.button') }}
    </button>
    <button type="button" id="epasskeys-use-password" class="epasskeys-login__toggle">
        {{ __('ePasskeys::login.use_password') }}
    </button>

    <script>
        window.ePasskeysLogin({
            button: '#epasskeys-login-button',
            formAction: @json($authUrl),
            csrfToken: @json(csrf_token()),
            optionsUrl: @json($optionsUrl),
            responseInputId: 'epasskeys-auth-response',
            rememberInputId: 'epasskeys-remember',
            rememberSelector: '#rememberme',
            auto: true
        });
    </script>
    <script>
        (function () {
            var input = document.querySelector('#username') || document.querySelector('input[name="username"]');
            if (!input) {
                return;
            }
            var current = input.getAttribute('autocomplete') || '';
            if (current.indexOf('webauthn') === -1) {
                input.setAttribute('autocomplete', (current ? current + ' ' : '') + 'webauthn');
            }
        })();
    </script>
    <script>
        (function () {
            var form = document.getElementById('loginfrm');
            if (!form) {
                return;
            }
            form.classList.add('epasskeys-passkey-only');

            var logo = form.querySelector('.form-group--logo');
            var block = form.querySelector('.epasskeys-login');
            if (logo && block && logo.parentNode) {
                logo.parentNode.insertBefore(block, logo.nextSibling);
            }

            var toggle = document.getElementById('epasskeys-use-password');

            var showPassword = function () {
                form.classList.remove('epasskeys-passkey-only');
                if (toggle) {
                    toggle.style.display = 'none';
                }
            };

            if (toggle) {
                toggle.addEventListener('click', function () {
                    showPassword();
                });
            }
        })();
    </script>
</div>
@endif
