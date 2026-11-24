{{-- Inside the form of Evolution's second sign-in step: submitting it asks for the passkey first. --}}
<p>{{ $text }}</p>
<input type="hidden" name="two_factor_code" id="epasskeys-second-factor" value="">
<p id="epasskeys-second-factor-error" class="evo-2fa__error" role="alert" hidden></p>
<script src="{{ $assetsUrl }}/simplewebauthn.umd.js"></script>
<script src="{{ $assetsUrl }}/epasskeys.js"></script>
<script>
    window.ePasskeysSecondFactor({
        type: @json($type),
        options: @json($options),
        input: '#epasskeys-second-factor',
        error: '#epasskeys-second-factor-error',
        failed: @json(__('ePasskeys::global.two_factor_failed'))
    });
</script>
