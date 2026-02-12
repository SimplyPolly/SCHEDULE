<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Настройки графика
        </h2>
    </x-slot>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Убираем активные классы у всех кнопок
                    tabButtons.forEach(btn => {
                        btn.classList.remove('bg-blue-600', 'text-white');
                        btn.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                    });

                    // Добавляем активные классы к текущей кнопке
                    this.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                    this.classList.add('bg-blue-600', 'text-white');

                    // Скрываем все вкладки
                    tabContents.forEach(content => {
                        content.classList.add('hidden');
                    });

                    // Показываем нужную вкладку
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.remove('hidden');
                });
            });
        });
    </script>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- Заголовок с табами -->
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold mb-2">Настройки алгоритма генерации графиков</h1>
                        <div class="flex space-x-2 mb-6">
                            <button type="button"
                                data-tab="parameters"
                                class="tab-button px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                Параметры алгоритма
                            </button>
                            <button type="button"
                                data-tab="requirements"
                                class="tab-button px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                Требования к штату
                            </button>
                        </div>
                    </div>

                    @if (session('success'))
                    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded dark:bg-green-800 dark:border-green-600 dark:text-green-200">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if (session('error'))
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded dark:bg-red-800 dark:border-red-600 dark:text-red-200">
                        {{ session('error') }}
                    </div>
                    @endif

                    <!-- Вкладка: Параметры алгоритма -->
                    <div id="parameters" class="tab-content">
                        <form method="POST" action="{{ route('algorithm.settings.update') }}">
                            @csrf

                            <!-- Основные параметры -->
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold mb-4">Основные параметры</h2>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Текущий сезон
                                    </label>
                                    <select name="season" class="mt-1 block w-full md:w-1/3 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="season" {{ old('season', $settingsForForm['season'] ?? 'season') == 'season' ? 'selected' : '' }}>
                                            Сезон (высокая нагрузка)
                                        </option>
                                        <option value="offseason" {{ old('season', $settingsForForm['season'] ?? 'season') == 'offseason' ? 'selected' : '' }}>
                                            Межсезонье (низкая нагрузка)
                                        </option>
                                    </select>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Влияет на минимальные требования к штату
                                    </p>
                                </div>

                                <div class="flex items-start mb-3">
                                    <input type="hidden" name="balance_workload" value="0">
                                    <input type="checkbox" id="balance_workload" name="balance_workload" value="1"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded mt-1"
                                        {{ old('balance_workload', $settingsForForm['balance_workload'] ?? true) ? 'checked' : '' }}>
                                    <label for="balance_workload" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                        <strong>Балансировка нагрузки</strong>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Равномерное распределение смен между сотрудниками</p>
                                    </label>
                                </div>

                                <div class="flex items-start mb-3">
                                    <input type="hidden" name="enable_shift_overlap" value="0">
                                    <input type="checkbox" id="enable_shift_overlap" name="enable_shift_overlap" value="1"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded mt-1"
                                        {{ old('enable_shift_overlap', $settingsForForm['enable_shift_overlap'] ?? true) ? 'checked' : '' }}>
                                    <label for="enable_shift_overlap" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                        <strong>Разрешить совмещение смен</strong>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Утро+день, день+ночь</p>
                                    </label>
                                </div>

                                <div class="flex items-start mb-3">
                                    <input type="hidden" name="auto_reassign_unfilled" value="0">
                                    <input type="checkbox" id="auto_reassign_unfilled" name="auto_reassign_unfilled" value="1"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded mt-1"
                                        {{ old('auto_reassign_unfilled', $settingsForForm['auto_reassign_unfilled'] ?? true) ? 'checked' : '' }}>
                                    <label for="auto_reassign_unfilled" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                        <strong>Автоперераспределение при недокомплекте</strong>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Автоматически назначать сотрудников при нехватке</p>
                                    </label>
                                </div>
                            </div>

                            <!-- ТК РФ -->
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold mb-4">Трудовой кодекс РФ</h2>

                                <div class="flex items-start mb-4">
                                    <input type="hidden" name="enforce_labor_law" value="0">
                                    <input type="checkbox" id="enforce_labor_law" name="enforce_labor_law" value="1"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded mt-1"
                                        {{ old('enforce_labor_law', $settingsForForm['enforce_labor_law'] ?? true) ? 'checked' : '' }}>
                                    <label for="enforce_labor_law" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                        <strong>Проверять соответствие ТК РФ</strong>
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Недельная норма (часы)
                                        </label>
                                        <input type="number" name="max_weekly_hours" value="{{ old('max_weekly_hours', $settingsForForm['max_weekly_hours'] ?? 40) }}"
                                            class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            min="1" max="60">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Отдых между сменами (часы)
                                        </label>
                                        <input type="number" name="min_rest_hours" value="{{ old('min_rest_hours', $settingsForForm['min_rest_hours'] ?? 11) }}"
                                            class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            min="1" max="24">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Утренняя смена (часы)
                                        </label>
                                        <input type="number" name="shift_hours_morning" value="{{ old('shift_hours_morning', $settingsForForm['shift_hours_morning'] ?? 6) }}"
                                            class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            min="1" max="12">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Дневная смена (часы)
                                        </label>
                                        <input type="number" name="shift_hours_day" value="{{ old('shift_hours_day', $settingsForForm['shift_hours_day'] ?? 8) }}"
                                            class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            min="1" max="12">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Ночная смена (часы)
                                        </label>
                                        <input type="number" name="shift_hours_night" value="{{ old('shift_hours_night', $settingsForForm['shift_hours_night'] ?? 7) }}"
                                            class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            min="1" max="12">
                                    </div>
                                </div>

                                <div class="flex items-start mt-4">
                                    <input type="hidden" name="no_morning_after_night" value="0">
                                    <input type="checkbox" id="no_morning_after_night" name="no_morning_after_night" value="1"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded mt-1"
                                        {{ old('no_morning_after_night', $settingsForForm['no_morning_after_night'] ?? true) ? 'checked' : '' }}>
                                    <label for="no_morning_after_night" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                        Запретить утро после ночи
                                    </label>
                                </div>
                            </div>

                            <!-- Приоритеты -->
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold mb-4">Система приоритетов</h2>

                                <div class="flex items-start mb-3">
                                    <input type="hidden" name="enable_priority_system" value="0">
                                    <input type="checkbox" id="enable_priority_system" name="enable_priority_system" value="1"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded mt-1"
                                        {{ old('enable_priority_system', $settingsForForm['enable_priority_system'] ?? true) ? 'checked' : '' }}>
                                    <label for="enable_priority_system" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                        <strong>«Раньше подал → выше приоритет»</strong>
                                    </label>
                                </div>

                                <div class="flex items-start mb-4">
                                    <input type="hidden" name="allow_forced_assignment" value="0">
                                    <input type="checkbox" id="allow_forced_assignment" name="allow_forced_assignment" value="1"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded mt-1"
                                        {{ old('allow_forced_assignment', $settingsForForm['allow_forced_assignment'] ?? false) ? 'checked' : '' }}>
                                    <label for="allow_forced_assignment" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                        <strong>Разрешить вынужденное назначение</strong>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Назначать тех, кто просил выходной</p>
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Вес: Хочу эту смену
                                        </label>
                                        <input type="number" name="priority_want_shift" value="{{ old('priority_want_shift', $settingsForForm['priority_want_shift'] ?? 100) }}"
                                            class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            min="0" max="1000">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Вес: Без пожеланий
                                        </label>
                                        <input type="number" name="priority_no_preference" value="{{ old('priority_no_preference', $settingsForForm['priority_no_preference'] ?? 50) }}"
                                            class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            min="0" max="1000">
                                    </div>
                                </div>
                            </div>

                            <!-- Уведомления -->
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold mb-4">Уведомления</h2>

                                <div class="space-y-3">
                                    <div class="flex items-start">
                                        <input type="hidden" name="notify_forced_assignment" value="0">
                                        <input type="checkbox" id="notify_forced_assignment" name="notify_forced_assignment" value="1"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded mt-1"
                                            {{ old('notify_forced_assignment', $settingsForForm['notify_forced_assignment'] ?? true) ? 'checked' : '' }}>
                                        <label for="notify_forced_assignment" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                            Уведомлять о вынужденном назначении
                                        </label>
                                    </div>

                                    <div class="flex items-start">
                                        <input type="hidden" name="notify_law_violation" value="0">
                                        <input type="checkbox" id="notify_law_violation" name="notify_law_violation" value="1"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded mt-1"
                                            {{ old('notify_law_violation', $settingsForForm['notify_law_violation'] ?? true) ? 'checked' : '' }}>
                                        <label for="notify_law_violation" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                            Уведомлять о нарушениях ТК РФ
                                        </label>
                                    </div>

                                    <div class="flex items-start">
                                        <input type="hidden" name="notify_schedule_ready" value="0">
                                        <input type="checkbox" id="notify_schedule_ready" name="notify_schedule_ready" value="1"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded mt-1"
                                            {{ old('notify_schedule_ready', $settingsForForm['notify_schedule_ready'] ?? true) ? 'checked' : '' }}>
                                        <label for="notify_schedule_ready" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                            Уведомлять о готовности графика
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Кнопка сохранения -->
                            <div class="pt-6 border-t border-gray-200 dark:border-gray-600 flex flex-col items-center">
                                <button type="submit" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition mb-2">
                                    Сохранить настройки алгоритма
                                </button>
                                <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                                    Настройки применяются при следующей генерации графика
                                </p>
                            </div>
                        </form>
                    </div>

                    <!-- Вкладка: Требования к штату -->
                    <div id="requirements" class="tab-content hidden">
                        <form method="POST" action="{{ route('staff-requirements.update') }}">
                            @csrf

                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-xl font-semibold">Требования к штату</h2>
                                    <div class="text-sm px-3 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded">
                                        Текущий сезон:
                                        <span class="font-bold">
                                            {{ isset($currentSeason) && $currentSeason == 'season' ? 'Сезон' : 'Межсезонье' }}
                                        </span>
                                    </div>
                                </div>

                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                                    Укажите минимальное количество сотрудников для каждой роли, типа смены и типа дня.
                                    <span class="text-red-500 font-semibold">Три измерения: Роль × Смена × Тип дня</span>
                                </p>
                            </div>

                            <!-- Таблица требований -->
                            <div class="mb-8">
                                @isset($roles)
                                    @foreach($roles as $roleKey => $roleName)
                                        @if(isset($shiftTypes[$roleKey]))
                                            <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                                                <!-- Заголовок роли -->
                                                <div class="bg-gray-100 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                                                    <h3 class="font-semibold text-lg">
                                                        {{ $roleName }}
                                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                                                            ({{ implode(', ', array_map(function($s) use ($shiftTypes) {
                                                                return \App\Models\ShiftRequirement::SHIFT_TYPES[$s] ?? $s;
                                                            }, $shiftTypes[$roleKey])) }})
                                                        </span>
                                                    </h3>
                                                </div>

                                                <!-- Таблица для каждой доступной смены -->
                                                @foreach($shiftTypes[$roleKey] as $shiftKey)
                                                    <div class="border-b border-gray-200 dark:border-gray-700 last:border-0">
                                                        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900">
                                                            <h4 class="font-medium">{{ \App\Models\ShiftRequirement::SHIFT_TYPES[$shiftKey] ?? $shiftKey }}</h4>
                                                        </div>

                                                        <div class="p-4">
                                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                                @isset($dayTypes)
                                                                    @foreach($dayTypes as $dayKey => $dayName)
                                                                        <div>
                                                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                                                {{ $dayName }}
                                                                            </label>
                                                                            <input type="number"
                                                                                   name="requirements[{{ $roleKey }}][{{ $shiftKey }}][{{ $dayKey }}]"
                                                                                   value="{{ old(
                                                                                       "requirements.$roleKey.$shiftKey.$dayKey",
                                                                                       isset($requirements[$roleKey][$shiftKey][$dayKey]) ? $requirements[$roleKey][$shiftKey][$dayKey] : 1
                                                                                   ) }}"
                                                                                   class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-red-500 focus:border-red-500"
                                                                                   min="0" max="10"
                                                                                   placeholder="0">
                                                                        </div>
                                                                    @endforeach
                                                                @endisset
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach
                                @endisset
                            </div>

                            <!-- Кнопка сохранения -->
                            <div class="pt-6 border-t border-gray-200 dark:border-gray-600">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Всего комбинаций:
                                            @if(isset($roles) && isset($shiftTypes) && isset($dayTypes))
                                                {{ count($roles) * array_sum(array_map('count', $shiftTypes)) * count($dayTypes) }}
                                            @else
                                                0
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex space-x-3">
                                        <button type="submit"
                                                class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                                            Сохранить все требования
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>