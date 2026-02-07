<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Ручное редактирование графика
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">
                        {{ $roleName }} —
                        {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }},
                        @php
                            $shiftLabel = match($shift) {
                                'morning' => 'Утро',
                                'day' => 'День',
                                'night' => 'Ночь',
                                default => ucfirst($shift),
                            };
                        @endphp
                        смена: {{ $shiftLabel }}
                    </h3>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Отметьте сотрудников, которые должны работать в эту смену. Текущие назначения будут
                        перезаписаны.
                    </p>

                    <form method="POST" action="{{ route('schedule.update') }}" class="space-y-4">
                        @csrf

                        <input type="hidden" name="date" value="{{ $date }}">
                        <input type="hidden" name="role" value="{{ $role }}">
                        <input type="hidden" name="shift" value="{{ $shift }}">
                        <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">

                        @if($employees->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Для этой роли нет активных сотрудников.
                            </p>
                        @else
                            <div class="border rounded-md divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($employees as $employee)
                                    <label class="flex items-center justify-between px-4 py-2 text-sm">
                                        <span>
                                            {{ $employee->name }}
                                            <span class="text-xs text-gray-400 ml-2">{{ $employee->email }}</span>
                                        </span>
                                        <input
                                            type="checkbox"
                                            name="employees[]"
                                            value="{{ $employee->id }}"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            @checked(in_array($employee->id, $assignedEmployeeIds))
                                        >
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex items-center justify-between mt-4">
                            <a href="{{ $redirectTo }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Отмена
                            </a>

                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Сохранить изменения
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

