<div class="form-group epasskeys-login">
    <style>
        .epasskeys-login { margin-top: 1rem; }
        .epasskeys-login__button {
            width: 100%;
            border-radius: 20px;
            background-color: rgba(255, 255, 255, 0.15);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.65rem 1rem;
            min-height: 44px;
            font-size: 0.95rem;
        }
        .epasskeys-login__button--primary {
            min-height: 50px;
            font-size: 1rem;
        }
        .epasskeys-login__button[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .epasskeys-login__hint {
            margin-top: 0.4rem;
            font-size: 0.75rem;
            color: #aab0bd;
        }
        .loginbox-light .epasskeys-login__hint { color: #666; }
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

    @php($hasPasskey = $hasPasskey ?? false)
    <button type="button" id="epasskeys-login-button" class="btn epasskeys-login__button{{ $hasPasskey ? ' epasskeys-login__button--primary' : '' }}" @if(!$hasPasskey) disabled @endif>
        {{ __('ePasskeys::login.button') }}
    </button>
    @if($hasPasskey)
        <button type="button" id="epasskeys-use-password" class="epasskeys-login__toggle">
            {{ __('ePasskeys::login.use_password') }}
        </button>
    @else
        <div class="epasskeys-login__hint">{{ __('ePasskeys::login.hint_no_passkey') }}</div>
    @endif

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
            var hasPasskey = @json((bool)($hasPasskey ?? false));
            if (!hasPasskey) {
                return;
            }

            var form = document.getElementById('loginfrm');
            if (!form) {
                return;
            }

            var username = document.getElementById('username');
            var password = document.getElementById('password');
            var submit = document.getElementById('submitButton');
            var actions = form.querySelector('.form-group--actions');
            var captcha = form.querySelector('.captcha');
            var toggle = document.getElementById('epasskeys-use-password');
            var button = document.getElementById('epasskeys-login-button');

            var groups = [];
            if (username && username.closest) {
                groups.push(username.closest('.form-group'));
            }
            if (password && password.closest) {
                groups.push(password.closest('.form-group'));
            }
            if (submit && submit.closest) {
                groups.push(submit.closest('.form-group--actions'));
            } else if (actions) {
                groups.push(actions);
            }
            if (captcha) {
                groups.push(captcha);
            }

            var hidePassword = function () {
                groups.forEach(function (el) {
                    if (el) el.style.display = 'none';
                });
                if (button) {
                    button.classList.add('epasskeys-login__button--primary');
                }
            };

            var showPassword = function () {
                groups.forEach(function (el) {
                    if (el) el.style.display = '';
                });
                if (button) {
                    button.classList.remove('epasskeys-login__button--primary');
                }
                if (toggle) {
                    toggle.style.display = 'none';
                }
            };

            hidePassword();

            if (toggle) {
                toggle.addEventListener('click', function () {
                    showPassword();
                });
            }
        })();
    </script>
</div>
