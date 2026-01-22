@php($hasPasskey = $hasPasskey ?? false)
@if($hasPasskey)
<div class="form-group epasskeys-login" data-epasskeys="1">
    <style>
        .epasskeys-login { margin-top: 1rem; }
        .epasskeys-login__button {
            width: 100%;
            border-radius: 20px;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            font-size: 1rem;
            font-weight: 400;
        }
        .epasskeys-login__button--primary {
            min-height: 44px;
        }
        #epasskeys-use-password {
            color: #818a91 !important;
            display: inline-block;
            margin-top: 0.5rem;
            background: none !important;
            border: none !important;
            padding: 0 !important;
            cursor: pointer;
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

    <button type="button" id="epasskeys-login-button" class="btn btn-success epasskeys-login__button epasskeys-login__button--primary">
        {{ __('ePasskeys::login.button') }}
    </button>
    <button type="button" id="epasskeys-use-password">
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
            auto: false
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
