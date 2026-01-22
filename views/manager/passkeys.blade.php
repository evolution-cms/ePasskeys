<link rel="stylesheet" href="{{ $cssUrl }}">
<div class="epasskeys {{ $themeClass ?? '' }}" data-epasskeys-theme="{{ $themeMode ?? 'auto' }}">
    <div class="mx-auto flex max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 darkness:text-slate-500">{{ __('ePasskeys::manager.section_label') }}</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900 darkness:text-slate-100">{{ __('ePasskeys::manager.title') }}</h1>
                <p class="mt-2 max-w-xl text-sm text-slate-500 darkness:text-slate-400">
                    {{ __('ePasskeys::manager.subtitle') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full border border-slate-200/70 bg-white/70 px-3 py-1 text-xs font-medium text-slate-500 shadow-sm darkness:border-slate-700/70 darkness:bg-slate-900/60 darkness:text-slate-300">
                    {{ __('ePasskeys::manager.total', ['count' => $passkeys->count()]) }}
                </span>
            </div>
        </div>

        @if (session('epasskeys.message'))
            <div class="rounded-2xl border border-blue-200/70 bg-blue-50/80 px-4 py-3 text-sm text-blue-800 shadow-sm darkness:border-blue-900/40 darkness:bg-blue-950/40 darkness:text-blue-200">
                {{ session('epasskeys.message') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <section class="rounded-2xl border border-slate-200/70 bg-white/80 p-6 shadow-sm backdrop-blur darkness:border-slate-700/60 darkness:bg-slate-900/60">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 darkness:text-slate-100">{{ __('ePasskeys::manager.create_title') }}</h3>
                        <p class="mt-1 text-sm text-slate-500 darkness:text-slate-400">
                            {{ __('ePasskeys::manager.create_help') }}
                        </p>
                    </div>
                </div>

                <form id="epasskeys-register-form" method="POST" action="{{ $registerUrl }}" class="mt-5 space-y-4">
                    {!! csrf_field() !!}
                    <div>
                        <label for="epasskeys-name" class="text-xs font-semibold uppercase tracking-wide text-slate-500 darkness:text-slate-400">{{ __('ePasskeys::manager.device_label') }}</label>
                        <input
                            type="text"
                            id="epasskeys-name"
                            name="name"
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/20 darkness:border-slate-700 darkness:bg-slate-900/60 darkness:text-slate-100"
                            placeholder="{{ __('ePasskeys::manager.device_placeholder') }}"
                        >
                    </div>
                    <input type="hidden" name="passkey" id="epasskeys-register-response" value="">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-slate-500 darkness:text-slate-400">
                            {{ __('ePasskeys::manager.create_hint') }}
                        </p>
                        <button type="button" id="epasskeys-register-button" class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/30">
                            {{ __('ePasskeys::manager.create_button') }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200/70 bg-white/80 p-6 shadow-sm backdrop-blur darkness:border-slate-700/60 darkness:bg-slate-900/60">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-slate-900 darkness:text-slate-100">{{ __('ePasskeys::manager.existing_title') }}</h3>
                    <span class="text-xs font-medium text-slate-400 darkness:text-slate-500">{{ __('ePasskeys::manager.active', ['count' => $passkeys->count()]) }}</span>
                </div>

                @if ($passkeys->isEmpty())
                    <div class="mt-4 rounded-xl border border-dashed border-slate-200/80 bg-slate-50/70 p-4 text-sm text-slate-500 darkness:border-slate-700/70 darkness:bg-slate-900/40 darkness:text-slate-400">
                        {{ __('ePasskeys::manager.empty') }}
                    </div>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="text-xs uppercase tracking-wider text-slate-400 darkness:text-slate-500">
                                <tr>
                                    <th class="py-2 pr-4">{{ __('ePasskeys::manager.table_name') }}</th>
                                    <th class="py-2 pr-4">{{ __('ePasskeys::manager.table_last_used') }}</th>
                                    <th class="py-2 pr-4">{{ __('ePasskeys::manager.table_created') }}</th>
                                    <th class="py-2 text-right">{{ __('ePasskeys::manager.table_action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 darkness:divide-slate-800/70">
                                @foreach ($passkeys as $passkey)
                                    <tr class="text-slate-700 darkness:text-slate-200">
                                        <td class="py-3 pr-4 font-medium text-slate-900 darkness:text-slate-100">{{ $passkey->name }}</td>
                                        <td class="py-3 pr-4 text-sm text-slate-500 darkness:text-slate-400">
                                            {{ $passkey->last_used_at ? $passkey->last_used_at->diffForHumans() : __('ePasskeys::manager.not_used') }}
                                        </td>
                                        <td class="py-3 pr-4 text-sm text-slate-500 darkness:text-slate-400">
                                            {{ $passkey->created_at ? $passkey->created_at->toDateTimeString() : '' }}
                                        </td>
                                        <td class="py-3 text-right">
                                            <form method="POST" action="{{ $deleteBaseUrl }}/{{ $passkey->id }}/delete">
                                                {!! csrf_field() !!}
                                                <button type="submit" class="inline-flex items-center rounded-lg border border-red-200/70 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500/30 darkness:border-red-900/40 darkness:bg-red-950/40 darkness:text-red-200 darkness:hover:bg-red-900/40">
                                                    {{ __('ePasskeys::manager.delete_button') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

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
</div>
