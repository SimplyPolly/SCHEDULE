<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __($title) }}
        </h2>
    </x-slot>

    <style>
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .schedule-table th {
            background-color: #374151;
            color: white;
            padding: 12px 8px;
            text-align: center;
            border: 1px solid #4b5563;
            font-weight: 600;
        }

        .schedule-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
            background-color: white;
        }

        .schedule-table .role-cell {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
            min-width: 150px;
            position: sticky;
            left: 0;
            z-index: 10;
        }

        .date-header {
            font-weight: bold;
            color: #1f2937;
        }

        .weekday {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 2px;
        }

        .shift-block {
            margin-bottom: 8px;
            padding: 6px;
            border-radius: 4px;
            background-color: #f8fafc;
            border-left: 3px solid #3b82f6;
        }

        .shift-header {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 4px;
            font-size: 0.8rem;
        }

        .employee-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .employee-item {
            padding: 2px 0;
            font-size: 0.75rem;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }

        .employee-item:last-child {
            border-bottom: none;
        }

        .empty-cell {
            color: #9ca3af;
            font-style: italic;
            font-size: 0.75rem;
            text-align: center;
            padding: 20px;
        }

        .shift-morning {
            border-left-color: #f59e0b;
        }

        .shift-day {
            border-left-color: #10b981;
        }

        .shift-night {
            border-left-color: #8b5cf6;
        }

        .empty-shift {
            opacity: 0.7;
        }
    </style>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            @php
            $isRoleView = request()->routeIs('schedule.role');
            $isPersonalView = request()->routeIs('schedule.personal');
            $currentUserRole = auth()->user()->role ?? null;
            $hasSchedule = !empty($schedule) && count($schedule) > 0;
            $isAdmin = auth()->user()->role === 'admin';
            @endphp

            <!-- Навигация по периодам -->
            <div class="flex justify-between items-center mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <a href="{{ request()->fullUrlWithQuery(['period_start' => $prevPeriodStart]) }}"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    ← Предыдущий график
                </a>

                <div class="text-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                        График с {{ $periodStartDate->format('d.m.Y') }} по {{ $periodEndDate->format('d.m.Y') }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Период: 14 дней</p>
                    <!-- Кнопка текущего периода -->
                    <div class="text-center mb-4">
                        <a href="{{ request()->fullUrlWithQuery(['period_start' => $currentPeriodStart]) }}"
                            class="inline-block px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded transition">
                            Перейти к текущему периоду
                        </a>
                    </div>
                </div>

                <a href="{{ request()->fullUrlWithQuery(['period_start' => $nextPeriodStart]) }}"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    Следующий график →
                </a>
            </div>

            <!-- Кнопка генерации с уведомлениями -->
            @if($isAdmin)
            <div class="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <!-- Сообщение о текущих нехватках (если есть график) -->
                @if($hasSchedule && isset($shortagesInfo) && $shortagesInfo)
                <div class="mb-6 text-gray-700 dark:text-gray-300 text-sm">
                    {{ $shortagesInfo['message'] }}
                </div>
                @else
                <!-- Сессионные уведомления показываем только если нет текущих нехваток -->
                @if(session('warning'))
                <div class="mb-6 text-gray-700 dark:text-gray-300 text-sm">
                    {{ session('warning') }}
                </div>
                @endif

                @if(session('success'))
                <div class="mb-6 text-gray-700 dark:text-gray-300 text-sm">
                    {{ session('success') }}
                </div>
                @endif

                @if($hasSchedule && session('generation_message'))
                <div class="mb-6 text-gray-700 dark:text-gray-300 text-sm">
                    {{ session('generation_message') }}
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 text-gray-700 dark:text-gray-300 text-sm">
                    {{ session('error') }}
                </div>
                @endif
                @endif

                <form method="POST" action="{{ route('schedule.generate') }}" class="flex items-center gap-4 flex-wrap"
                    id="generateForm">
                    @csrf
                    <label for="generate_start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        Дата начала (понедельник):
                    </label>
                    <input
                        type="date"
                        name="start_date"
                        id="generate_start_date"
                        value="{{ $periodStartDate->format('Y-m-d') }}"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        required>
                    <button type="submit"
                        class="px-6 py-2 {{ $hasSchedule ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' }} text-white font-medium rounded transition"
                        {{ $hasSchedule ? 'disabled' : '' }}>
                        {{ $hasSchedule ? 'График сгенерирован' : 'Сгенерировать график' }}
                    </button>
                </form>
            </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if($hasSchedule)
                    <div class="overflow-x-auto">
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th class="role-cell">Роль / Дата</th>
                                    @foreach($dates as $dateStr)
                                    @php
                                    $date = \Carbon\Carbon::parse($dateStr);
                                    $isWeekend = in_array($date->dayOfWeek, [0, 6]);
                                    $cellClass = $isWeekend ? 'bg-yellow-50' : '';
                                    @endphp
                                    <th class="{{ $cellClass }}">
                                        <div class="date-header">{{ $date->format('d.m') }}</div>
                                        <div class="weekday">{{ $date->isoFormat('ddd') }}</div>
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(['cook', 'waiter', 'hostess', 'bartender', 'admin'] as $role)
                                @if($currentUserRole !== 'admin' && $role !== $currentUserRole)
                                @continue
                                @endif
                                @if($currentUserRole === 'admin' && ($isRoleView || $isPersonalView) && $role !== $currentUserRole)
                                @continue
                                @endif
                                <tr>
                                    <td class="role-cell">
                                        {{ match($role) {
                                            'cook' => '👨‍🍳 Повара',
                                            'waiter' => '🍽️ Официанты',
                                            'hostess' => '👋 Хостес',
                                            'bartender' => '🍸 Бармены',
                                            'admin' => '👔 Администраторы',
                                            default => ucfirst($role),
                                        } }}
                                    </td>
                                    @foreach($dates as $dateStr)
                                    @php
                                    $date = \Carbon\Carbon::parse($dateStr);
                                    $isWeekend = in_array($date->dayOfWeek, [0, 6]);
                                    $cellClass = $isWeekend ? 'bg-yellow-50' : '';

                                    // Определяем доступные смены для роли
                                    $availableShifts = in_array($role, ['waiter', 'hostess'])
                                    ? ['morning', 'day', 'night']
                                    : ['day', 'night'];

                                    $hasMorning = isset($schedule[$dateStr][$role]['morning']) && count($schedule[$dateStr][$role]['morning']) > 0;
                                    $hasDay = isset($schedule[$dateStr][$role]['day']) && count($schedule[$dateStr][$role]['day']) > 0;
                                    $hasNight = isset($schedule[$dateStr][$role]['night']) && count($schedule[$dateStr][$role]['night']) > 0;
                                    @endphp
                                    <td class="{{ $cellClass }}">
                                        <div class="space-y-1">
                                            <!-- Утренняя смена (только для официантов и хостес) -->
                                            @if(in_array('morning', $availableShifts))
                                            @if($hasMorning)
                                            <div class="shift-block shift-morning">
                                                <div class="shift-header flex items-center justify-between">
                                                    <span>🌅 Утро</span>
                                                    @if($isAdmin)
                                                    <a href="{{ route('schedule.edit', [
                                                                'date' => $dateStr,
                                                                'role' => $role,
                                                                'shift' => 'morning',
                                                                'redirect_to' => request()->fullUrl(),
                                                            ]) }}"
                                                        class="text-[11px] text-blue-600 dark:text-blue-300 underline">
                                                        Править
                                                    </a>
                                                    @endif
                                                </div>
                                                <ul class="employee-list">
                                                    @foreach($schedule[$dateStr][$role]['morning'] as $person)
                                                    <li class="employee-item">{{ $person['name'] }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            @else
                                            <div class="shift-block shift-morning empty-shift">
                                                <div class="shift-header flex items-center justify-between">
                                                    <span>🌅 Утро</span>
                                                    @if($isAdmin)
                                                    <a href="{{ route('schedule.edit', [
                                                                'date' => $dateStr,
                                                                'role' => $role,
                                                                'shift' => 'morning',
                                                                'redirect_to' => request()->fullUrl(),
                                                            ]) }}"
                                                        class="text-[11px] text-blue-600 dark:text-blue-300 underline">
                                                        Добавить
                                                    </a>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-400 dark:text-gray-500 py-1 text-center">
                                                    — Нет назначений —
                                                </div>
                                            </div>
                                            @endif
                                            @endif

                                            <!-- Дневная смена -->
                                            @if(in_array('day', $availableShifts))
                                            @if($hasDay)
                                            <div class="shift-block shift-day">
                                                <div class="shift-header flex items-center justify-between">
                                                    <span>☀️ День</span>
                                                    @if($isAdmin)
                                                    <a href="{{ route('schedule.edit', [
                                                                'date' => $dateStr,
                                                                'role' => $role,
                                                                'shift' => 'day',
                                                                'redirect_to' => request()->fullUrl(),
                                                            ]) }}"
                                                        class="text-[11px] text-blue-600 dark:text-blue-300 underline">
                                                        Править
                                                    </a>
                                                    @endif
                                                </div>
                                                <ul class="employee-list">
                                                    @foreach($schedule[$dateStr][$role]['day'] as $person)
                                                    <li class="employee-item">{{ $person['name'] }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            @else
                                            <div class="shift-block shift-day empty-shift">
                                                <div class="shift-header flex items-center justify-between">
                                                    <span>☀️ День</span>
                                                    @if($isAdmin)
                                                    <a href="{{ route('schedule.edit', [
                                                                'date' => $dateStr,
                                                                'role' => $role,
                                                                'shift' => 'day',
                                                                'redirect_to' => request()->fullUrl(),
                                                            ]) }}"
                                                        class="text-[11px] text-blue-600 dark:text-blue-300 underline">
                                                        Добавить
                                                    </a>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-400 dark:text-gray-500 py-1 text-center">
                                                    — Нет назначений —
                                                </div>
                                            </div>
                                            @endif
                                            @endif

                                            <!-- Ночная смена -->
                                            @if(in_array('night', $availableShifts))
                                            @if($hasNight)
                                            <div class="shift-block shift-night">
                                                <div class="shift-header flex items-center justify-between">
                                                    <span>🌙 Ночь</span>
                                                    @if($isAdmin)
                                                    <a href="{{ route('schedule.edit', [
                                                                'date' => $dateStr,
                                                                'role' => $role,
                                                                'shift' => 'night',
                                                                'redirect_to' => request()->fullUrl(),
                                                            ]) }}"
                                                        class="text-[11px] text-blue-600 dark:text-blue-300 underline">
                                                        Править
                                                    </a>
                                                    @endif
                                                </div>
                                                <ul class="employee-list">
                                                    @foreach($schedule[$dateStr][$role]['night'] as $person)
                                                    <li class="employee-item">{{ $person['name'] }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            @else
                                            <div class="shift-block shift-night empty-shift">
                                                <div class="shift-header flex items-center justify-between">
                                                    <span>🌙 Ночь</span>
                                                    @if($isAdmin)
                                                    <a href="{{ route('schedule.edit', [
                                                                'date' => $dateStr,
                                                                'role' => $role,
                                                                'shift' => 'night',
                                                                'redirect_to' => request()->fullUrl(),
                                                            ]) }}"
                                                        class="text-[11px] text-blue-600 dark:text-blue-300 underline">
                                                        Добавить
                                                    </a>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-400 dark:text-gray-500 py-1 text-center">
                                                    — Нет назначений —
                                                </div>
                                            </div>
                                            @endif
                                            @endif
                                        </div>
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400 text-lg">График пока не сгенерирован.</p>
                        @if($isAdmin)
                        <p class="text-gray-400 dark:text-gray-500 mt-2">Нажмите кнопку выше, чтобы создать график.</p>
                        @else
                        <p class="text-gray-400 dark:text-gray-500 mt-2">Обратитесь к администратору для генерации графика.</p>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const generateForm = document.getElementById('generateForm');
            const submitButton = generateForm?.querySelector('button[type="submit"]');

            if (submitButton && submitButton.disabled) {
                // Добавляем обработчик для предотвращения отправки формы
                generateForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });

                // Также делаем поле даты неактивным
                const dateInput = document.getElementById('generate_start_date');
                if (dateInput) {
                    dateInput.disabled = true;
                    dateInput.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }
        });
    </script>
    @endpush
</x-app-layout>