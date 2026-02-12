@php
    /** @var \App\Models\Employee $user */
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Информация профиля') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Обновите информацию вашего профиля и email адрес.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Имя')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Ваш email адрес не подтвержден.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Нажмите здесь, чтобы повторно отправить письмо с подтверждением.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('Новая ссылка для подтверждения была отправлена на ваш email адрес.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Контактные данные --}}
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">
                {{ __('Контактные данные') }}
            </h3>
            
            <div class="space-y-4">
                {{-- Телефон --}}
                <div>
                    <x-input-label for="phone" :value="__('Телефон')" />
                    <x-text-input 
                        id="phone" 
                        name="phone" 
                        type="tel" 
                        class="mt-1 block w-full" 
                        :value="old('phone', $user->phone)" 
                        autocomplete="tel"
                        placeholder="+7 (999) 123-45-67"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Для экстренной связи') }}
                    </p>
                </div>

                {{-- Telegram --}}
                <div>
                    <x-input-label for="telegram" :value="__('Telegram')" />
                    <x-text-input 
                        id="telegram" 
                        name="telegram" 
                        type="text" 
                        class="mt-1 block w-full" 
                        :value="old('telegram', $user->telegram)" 
                        autocomplete="off"
                        placeholder="@username или https://t.me/username"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('telegram')" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Для получения уведомлений о сменах') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Информация о роли и статусе --}}
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('Должность') }}
                        </span>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $user->role_name }}
                        </p>
                    </div>
                    
                    @if($user->hasTwoShifts())
                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('Тип смен') }}
                        </span>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ __('Длинные смены (день/ночь)') }}
                        </p>
                    </div>
                    @endif

                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('Статус') }}
                        </span>
                        <p class="mt-1">
                            @if($user->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-white-800 dark:bg-green-800 dark:text-green-100">
                                    {{ __('Активен') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ __('Неактивен') }}
                                </span>
                            @endif
                        </p>
                    </div>

                    @if($user->preferences_submitted_at)
                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('Предпочтения обновлены') }}
                        </span>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $user->preferences_submitted_at->format('d.m.Y H:i') }}
                        </p>
                    </div>
                    @endif

                    {{-- Отображение текущих контактных данных для информации --}}
                    @if($user->phone || $user->telegram)
                    <div class="md:col-span-2 border-t border-gray-200 dark:border-gray-700 pt-3 mt-1">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('Текущие контактные данные') }}
                        </span>
                        <div class="mt-2 space-y-1">
                            @if($user->phone)
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">{{ __('Телефон:') }}</span> {{ $user->phone }}
                            </p>
                            @endif
                            @if($user->telegram)
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">{{ __('Telegram:') }}</span> {{ $user->telegram }}
                            </p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Сохранить') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Сохранено.') }}</p>
            @endif
        </div>
    </form>
</section>