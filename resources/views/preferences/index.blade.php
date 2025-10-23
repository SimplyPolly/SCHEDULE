<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Мои пожелания') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <h3 class="text-lg font-medium mb-4">Укажите пожелания на ближайшие 2 недели</h3>

                    <!-- Заголовки дней недели -->
                    <div class="grid grid-cols-7 gap-2 mb-2">
                        @foreach(['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $day)
                            <div class="text-center font-medium text-sm text-gray-500 py-1">{{ $day }}</div>
                        @endforeach
                    </div>

                    <!-- Календарная сетка -->
                    <div class="space-y-2">
                        @foreach($calendarGrid as $week)
                            <div class="grid grid-cols-7 gap-2">
                                @foreach($week as $cell)
                                    <div class="relative">
                                        <div class="w-full h-24 p-2 bg-white dark:bg-gray-700 border rounded-md flex flex-col items-center justify-between text-sm">
                                            <!-- Дата: число + месяц + день недели -->
                                            <div class="text-center">
                                                <div class="font-medium">{{ $cell['day_number'] }}</div>
                                                <div class="text-xs text-gray-500">{{ $cell['month_name'] }}</div>
                                                <div class="text-xs text-gray-400 mt-0.5">{{ $cell['weekday_short'] }}</div>
                                            </div>

                                            <!-- Отображение или выбор -->
                                            @if($isSubmitted)
                                                <div class="text-xs text-gray-600 dark:text-gray-300 text-center mt-1">
                                                    @if(isset($preferences[$cell['date']]))
                                                        {{ getPreferenceLabel($preferences[$cell['date']]->type) }}
                                                    @else
                                                        —
                                                    @endif
                                                </div>
                                            @else
                                                @if($cell['isPast'])
                                                    <span class="text-xs text-gray-400">Прошёл</span>
                                                @else
                                                    <select onchange="savePreference(this, '{{ $cell['date'] }}')"
                                                        class="w-full text-xs border rounded px-1 py-0.5 mt-1 bg-white dark:bg-gray-600 dark:text-white">
                                                        <option value="">— Выберите —</option>
                                                        <option value="day_off" @if(isset($preferences[$cell['date']]) && $preferences[$cell['date']]->type === 'day_off') selected @endif>
                                                            Выходной
                                                        </option>
                                                        <option value="prefer_morning" @if(isset($preferences[$cell['date']]) && $preferences[$cell['date']]->type === 'prefer_morning') selected @endif>
                                                            Хочу утро
                                                        </option>
                                                        <option value="prefer_day" @if(isset($preferences[$cell['date']]) && $preferences[$cell['date']]->type === 'prefer_day') selected @endif>
                                                            Хочу день
                                                        </option>
                                                        <option value="prefer_night" @if(isset($preferences[$cell['date']]) && $preferences[$cell['date']]->type === 'prefer_night') selected @endif>
                                                            Хочу ночь
                                                        </option>
                                                        <option value="avoid_morning" @if(isset($preferences[$cell['date']]) && $preferences[$cell['date']]->type === 'avoid_morning') selected @endif>
                                                            Не хочу утро
                                                        </option>
                                                        <option value="avoid_day" @if(isset($preferences[$cell['date']]) && $preferences[$cell['date']]->type === 'avoid_day') selected @endif>
                                                            Не хочу день
                                                        </option>
                                                        <option value="avoid_night" @if(isset($preferences[$cell['date']]) && $preferences[$cell['date']]->type === 'avoid_night') selected @endif>
                                                            Не хочу ночь
                                                        </option>
                                                    </select>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!$isSubmitted)
        <div class="mt-6 text-center">
            <form method="POST" action="{{ route('preferences.submit') }}" class="inline">
                @csrf
                <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition">
                    Сохранить пожелания окончательно
                </button>
            </form>
            <p class="mt-2 text-sm text-gray-500">После сохранения редактирование будет недоступно</p>
        </div>
    @else
        <div class="mt-6 text-center">
            <span class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">Пожелания сохранены и зафиксированы</span>
        </div>
    @endif

    @php
        function getPreferenceLabel($type) {
            return match($type) {
                'day_off' => 'Выходной',
                'prefer_morning' => 'Хочу утро',
                'prefer_day' => 'Хочу день',
                'prefer_night' => 'Хочу ночь',
                'avoid_morning' => 'Не хочу утро',
                'avoid_day' => 'Не хочу день',
                'avoid_night' => 'Не хочу ночь',
                default => $type,
            };
        }
    @endphp

    <script>
        function savePreference(selectElement, date) {
            const type = selectElement.value;
            if (!type) return;

            fetch("{{ route('preferences.store') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ date, type })
            })
            .then(response => {
                if (!response.ok) throw new Error('Ошибка сервера');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Успешно — ничего не меняем
                }
            })
            .catch(error => {
                selectElement.value = '';
                alert('Не удалось сохранить пожелание. Обновите страницу и попробуйте снова.');
                console.error('Ошибка:', error);
            });
        }
    </script>
</x-app-layout>