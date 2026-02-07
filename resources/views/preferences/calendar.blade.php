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

                    <!-- Навигация по 2-недельным периодам -->
                    <div class="flex justify-between items-center mb-6 bg-white dark:bg-gray-900 p-4 rounded-lg shadow">
                        <a href="{{ route('preferences.calendar', ['period_start' => $prevPeriodStart]) }}"
                           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                            ← Предыдущие 2 недели
                        </a>

                        <div class="text-center">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                                Пожелания на период
                                с {{ $periodStartDate->format('d.m.Y') }}
                                по {{ $periodEndDate->format('d.m.Y') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                График для этого периода будет строиться {{ $generationDate->format('d.m.Y') }}.
                                Пожелания принимаются до начала этого дня.
                            </p>
                            @if($isDeadlinePassed)
                                <p class="mt-1 text-sm text-red-500">
                                    Дедлайн для подачи пожеланий на этот период уже прошёл. Доступен только просмотр.
                                </p>
                            @elseif($isSubmitted)
                                <p class="mt-1 text-sm text-gray-500">
                                    Пожелания зафиксированы. Изменение недоступно.
                                </p>
                            @else
                                <p class="mt-1 text-sm text-green-600">
                                    Пожелания можно менять до {{ $deadline->format('d.m.Y') }} (включительно до начала дня).
                                </p>
                            @endif
                        </div>

                        <a href="{{ route('preferences.calendar', ['period_start' => $nextPeriodStart]) }}"
                           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                            Следующие 2 недели →
                        </a>
                    </div>

                    <!-- Кнопка перехода к текущему периоду -->
                    <div class="text-center mb-6">
                        <a href="{{ route('preferences.calendar', ['period_start' => $currentPeriodStart]) }}"
                           class="inline-block px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded transition">
                            Текущий период
                        </a>
                    </div>

                    <h3 class="text-lg font-medium mb-4">Укажите пожелания на выбранный 2-недельный период</h3>

                    <!-- КОМПАКТНАЯ ТАБЛИЦА БЕЗ ПЕРВОГО СТОЛБЦА -->
                    <div class="w-full">
                        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <!-- ТОЛЬКО дни недели как заголовки столбцов -->
                                    @foreach(['ПН', 'ВТ', 'СР', 'ЧТ', 'ПТ', 'СБ', 'ВС'] as $day)
                                        <th class="px-3 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            {{ $day }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Первая неделя - компактнее -->
                                <tr>
                                    @foreach($calendarGrid[0] as $cell)
                                        <td class="px-3 py-3">
                                            <div class="h-24 flex flex-col items-center justify-between">
                                                <!-- Дата -->
                                                <div class="text-center mb-1">
                                                    <div class="text-base font-medium text-gray-900 dark:text-white">
                                                        {{ $cell['day_number'] }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ $cell['month_name'] }}
                                                    </div>
                                                </div>
                                                
                                                <!-- Пожелание или селект -->
                                                <div class="w-full">
                                                    @if(!$canEdit)
                                                        <!-- Режим просмотра -->
                                                        <div class="text-center">
                                                            @if(isset($preferences[$cell["date"]]))
                                                                <span class="inline-block px-2 py-1 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                                    {{ match ($preferences[$cell["date"]]->type) {
                                                                        'day_off' => 'Выходной',
                                                                        'prefer_morning' => 'Утро',
                                                                        'prefer_day' => 'День',
                                                                        'prefer_night' => 'Ночь',
                                                                        'avoid_morning' => 'Не утро',
                                                                        'avoid_day' => 'Не день',
                                                                        'avoid_night' => 'Не ночь',
                                                                        default => '—',
                                                                    } }}
                                                                </span>
                                                            @else
                                                                <span class="text-gray-400 text-xs">—</span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <!-- Режим редактирования -->
                                                        @if($cell['isPast'])
                                                            <div class="text-center">
                                                                <span class="text-xs text-gray-400 px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded">Прошёл</span>
                                                            </div>
                                                        @else
                                                            <select
                                                                onchange="savePreference(this)"
                                                                data-date="{{ $cell['date'] }}"
                                                                class="w-full p-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 dark:text-white">
                                                                <option value="">— Выберите —</option>
                                                                <option value="day_off" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'day_off') selected @endif>
                                                                    Выходной
                                                                </option>
                                                                <option value="prefer_morning" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'prefer_morning') selected @endif>
                                                                    Хочу утро
                                                                </option>
                                                                <option value="prefer_day" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'prefer_day') selected @endif>
                                                                    Хочу день
                                                                </option>
                                                                <option value="prefer_night" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'prefer_night') selected @endif>
                                                                    Хочу ночь
                                                                </option>
                                                                <option value="avoid_morning" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'avoid_morning') selected @endif>
                                                                    Не хочу утро
                                                                </option>
                                                                <option value="avoid_day" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'avoid_day') selected @endif>
                                                                    Не хочу день
                                                                </option>
                                                                <option value="avoid_night" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'avoid_night') selected @endif>
                                                                    Не хочу ночь
                                                                </option>
                                                            </select>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                                
                                <!-- Вторая неделя - компактнее -->
                                <tr>
                                    @foreach($calendarGrid[1] as $cell)
                                        <td class="px-3 py-3">
                                            <div class="h-24 flex flex-col items-center justify-between">
                                                <!-- Дата -->
                                                <div class="text-center mb-1">
                                                    <div class="text-base font-medium text-gray-900 dark:text-white">
                                                        {{ $cell['day_number'] }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ $cell['month_name'] }}
                                                    </div>
                                                </div>
                                                
                                                <!-- Пожелание или селект -->
                                                <div class="w-full">
                                                    @if(!$canEdit)
                                                        <!-- Режим просмотра -->
                                                        <div class="text-center">
                                                            @if(isset($preferences[$cell["date"]]))
                                                                <span class="inline-block px-2 py-1 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                                    {{ match ($preferences[$cell["date"]]->type) {
                                                                        'day_off' => 'Выходной',
                                                                        'prefer_morning' => 'Утро',
                                                                        'prefer_day' => 'День',
                                                                        'prefer_night' => 'Ночь',
                                                                        'avoid_morning' => 'Не утро',
                                                                        'avoid_day' => 'Не день',
                                                                        'avoid_night' => 'Не ночь',
                                                                        default => '—',
                                                                    } }}
                                                                </span>
                                                            @else
                                                                <span class="text-gray-400 text-xs">—</span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <!-- Режим редактирования -->
                                                        @if($cell['isPast'])
                                                            <div class="text-center">
                                                                <span class="text-xs text-gray-400 px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded">Прошёл</span>
                                                            </div>
                                                        @else
                                                            <select
                                                                onchange="savePreference(this)"
                                                                data-date="{{ $cell['date'] }}"
                                                                class="w-full p-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 dark:text-white">
                                                                <option value="">— Выберите —</option>
                                                                <option value="day_off" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'day_off') selected @endif>
                                                                    Выходной
                                                                </option>
                                                                <option value="prefer_morning" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'prefer_morning') selected @endif>
                                                                    Хочу утро
                                                                </option>
                                                                <option value="prefer_day" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'prefer_day') selected @endif>
                                                                    Хочу день
                                                                </option>
                                                                <option value="prefer_night" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'prefer_night') selected @endif>
                                                                    Хочу ночь
                                                                </option>
                                                                <option value="avoid_morning" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'avoid_morning') selected @endif>
                                                                    Не хочу утро
                                                                </option>
                                                                <option value="avoid_day" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'avoid_day') selected @endif>
                                                                    Не хочу день
                                                                </option>
                                                                <option value="avoid_night" @if(isset($preferences[$cell["date"]]) && $preferences[$cell["date"]]->type === 'avoid_night') selected @endif>
                                                                    Не хочу ночь
                                                                </option>
                                                            </select>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- КНОПКА СОХРАНЕНИЯ В САМОЙ ФОРМЕ (БЕЗ ПРОКРУТКИ) -->
                    @if($canEdit)
                        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col sm:flex-row items-center justify-between">
                                <div class="mb-3 sm:mb-0">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        💡 Изменения сохраняются автоматически при выборе
                                    </h4>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Просто выберите пожелание для дня - оно сразу сохранится
                                    </p>
                                </div>
                                
                                <form method="POST" action="{{ route('preferences.submit') }}" class="inline-block">
                                    @csrf
                                    <input type="hidden" name="period_start" value="{{ $periodStartDate->format('Y-m-d') }}">
                                    <button type="submit" 
                                            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                                        Зафиксировать пожелания
                                    </button>
                                </form>
                            </div>
                            <p class="text-xs text-gray-500 mt-3 text-center sm:text-left">
                                ⚠️ После фиксации изменить пожелания на этот период будет нельзя, 
                                но вы сможете вносить пожелания на следующие периоды
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function savePreference(selectElement) {
            const type = selectElement.value;
            if (!type) return;

            const date = selectElement.dataset.date;
            const periodStart = "{{ $periodStartDate->format('Y-m-d') }}";

            fetch("{{ route('preferences.store') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({
                    date: date,
                    type: type,
                    period_start: periodStart
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        selectElement.value = '';
                        alert(data.message || 'Ошибка сохранения');
                    } else {
                        // Показываем уведомление об успешном сохранении
                        showNotification('Пожелание сохранено', 'success');
                    }
                })
                .catch(() => {
                    selectElement.value = '';
                    alert('Ошибка сети');
                });
        }

        function showNotification(message, type) {
            // Создаем временное уведомление
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-4 py-2 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Удаляем уведомление через 3 секунды
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    </script>
</x-app-layout>