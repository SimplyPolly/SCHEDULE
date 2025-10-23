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
        
        /* Цвета для разных смен */
        .shift-morning {
            border-left-color: #f59e0b; /* Оранжевый для утра */
        }
        
        .shift-day {
            border-left-color: #10b981; /* Зеленый для дня */
        }
        
        .shift-night {
            border-left-color: #8b5cf6; /* Фиолетовый для ночи */
        }
    </style>

    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">

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
                </div>
                
                <a href="{{ request()->fullUrlWithQuery(['period_start' => $nextPeriodStart]) }}" 
                   class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    Следующий график →
                </a>
            </div>

            <!-- Кнопка текущего периода -->
            <div class="text-center mb-4">
                <a href="{{ request()->fullUrlWithQuery(['period_start' => $currentPeriodStart]) }}" 
                   class="inline-block px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded transition">
                    Текущий период
                </a>
            </div>

            @if (session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Кнопка генерации (только для админа) -->
            @if(auth()->user()->role === 'admin')
                <div class="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                    <form method="POST" action="{{ route('schedule.generate') }}" class="flex items-center gap-4 flex-wrap">
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
                            required
                        >
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">
                            Сгенерировать график
                        </button>
                    </form>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if(!empty($schedule) && count($schedule) > 0)
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
                                        @if(auth()->user()->role !== 'admin' && $role !== auth()->user()->role)
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
                                                @endphp
                                                <td class="{{ $cellClass }}">
                                                    @if(isset($schedule[$dateStr][$role]))
                                                        <!-- Утро -->
                                                        @if(isset($schedule[$dateStr][$role]['morning']) && count($schedule[$dateStr][$role]['morning']) > 0)
                                                            <div class="shift-block shift-morning">
                                                                <div class="shift-header">🌅 Утро</div>
                                                                <ul class="employee-list">
                                                                    @foreach($schedule[$dateStr][$role]['morning'] as $person)
                                                                        <li class="employee-item">{{ $person['name'] }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        @endif

                                                        <!-- День -->
                                                        @if(isset($schedule[$dateStr][$role]['day']) && count($schedule[$dateStr][$role]['day']) > 0)
                                                            <div class="shift-block shift-day">
                                                                <div class="shift-header">☀️ День</div>
                                                                <ul class="employee-list">
                                                                    @foreach($schedule[$dateStr][$role]['day'] as $person)
                                                                        <li class="employee-item">{{ $person['name'] }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        @endif

                                                        <!-- Ночь -->
                                                        @if(isset($schedule[$dateStr][$role]['night']) && count($schedule[$dateStr][$role]['night']) > 0)
                                                            <div class="shift-block shift-night">
                                                                <div class="shift-header">🌙 Ночь</div>
                                                                <ul class="employee-list">
                                                                    @foreach($schedule[$dateStr][$role]['night'] as $person)
                                                                        <li class="employee-item">{{ $person['name'] }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        @endif

                                                        <!-- Если нет назначений -->
                                                        @if((!isset($schedule[$dateStr][$role]['morning']) || count($schedule[$dateStr][$role]['morning']) === 0) &&
                                                            (!isset($schedule[$dateStr][$role]['day']) || count($schedule[$dateStr][$role]['day']) === 0) &&
                                                            (!isset($schedule[$dateStr][$role]['night']) || count($schedule[$dateStr][$role]['night']) === 0))
                                                            <div class="empty-cell">—</div>
                                                        @endif
                                                    @else
                                                        <div class="empty-cell">—</div>
                                                    @endif
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
                            @if(auth()->user()->role === 'admin')
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
</x-app-layout>