<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __($title) }}
        </h2>
    </x-slot>

    <style>
        .schedule-cell {
            min-width: 150px;
            min-height: 150px;
            max-width: 150px;
            max-height: 150px;
            display: flex;
            flex-direction: column;
            padding: 6px;
            overflow-y: auto;
            word-break: break-word;
            border: 1px solid #4b5563;
            background-color: #1f2937;
            color: #ffffff;
            font-size: 0.75rem;
            line-height: 1.2;
            box-sizing: border-box;
        }

        .schedule-cell .shift-header {
            font-weight: bold;
            margin-bottom: 2px;
            color: #9ca3af;
            font-size: 0.7rem;
        }

        .schedule-cell .person-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 1px 0;
        }
    </style>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if(!empty($schedule))
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Роль</th>
                                        @foreach($dates as $date)
                                            <th
                                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                {{ \Carbon\Carbon::parse($date)->format('d.m') }}<br>
                                                <span
                                                    class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($date)->shortDayName }}</span>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach(['cook', 'waiter', 'hostess', 'bartender', 'admin'] as $role)
                                                                @if(auth()->user()->role !== 'admin' && $role !== auth()->user()->role)
                                                                    @continue
                                                                @endif
                                                                <tr>
                                                                    <td class="px-4 py-3 whitespace-nowrap font-medium">
                                                                        {{ match ($role) {
                                            'cook' => 'Повар',
                                            'waiter' => 'Официант',
                                            'hostess' => 'Хостес',
                                            'bartender' => 'Бармен',
                                            'admin' => 'Администратор',
                                            default => ucfirst($role),
                                        } }}
                                                                    </td>
                                                                    @foreach($dates as $date)
                                                                        <td class="px-4 py-3">
                                                                            @if(isset($schedule[$date][$role]))
                                                                                @foreach(['morning', 'day', 'night'] as $shift)
                                                                                    @if(isset($schedule[$date][$role][$shift]) && count($schedule[$date][$role][$shift]) > 0)
                                                                                        <div class="schedule-cell mb-1">
                                                                                            <div class="shift-header">
                                                                                                {{ $shift === 'morning' ? 'Утро' : ($shift === 'day' ? 'День' : 'Ночь') }}:
                                                                                            </div>
                                                                                            @foreach($schedule[$date][$role][$shift] as $person)
                                                                                                <div class="person-name">{{ $person['name'] }}</div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    @endif
                                                                                @endforeach
                                                                            @endif
                                                                        </td>
                                                                    @endforeach
                                                                </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p>График пока не сгенерирован. Нажмите кнопку выше, чтобы создать его.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>