<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Сотрудники
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Сотрудники</h2>
                        <a href="{{ route('admin.employees.create') }}"
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Добавить сотрудника
                        </a>
                    </div>

                    @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white dark:bg-gray-800">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">ID</th>
                                    <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">Имя</th>
                                    <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">Email</th>
                                    <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">Должность</th>
                                    <th class="py-3 px-4 border-b border-gray-200 dark:border-gray-700">Статус</th>
                                    <th class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees as $employee)
                                <tr>
                                    <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                        {{ $employee->id }}
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                        {{ $employee->name }}
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                        {{ $employee->email }}
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                        {{ match($employee->role) {
                                                'cook' => '👨‍🍳 ' . $employee->role_name,
                                                'waiter' => '🍽️ ' . $employee->role_name,
                                                'hostess' => '👋 ' . $employee->role_name,
                                                'bartender' => '🍸 ' . $employee->role_name,
                                                'admin' => '👔 ' . $employee->role_name,
                                                default => $employee->role_name,
                                            } }}
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                        <!-- Для дебага -->
                                        <!-- is_active value: {{ $employee->is_active }}, type: {{ gettype($employee->is_active) }} -->

                                        @if($employee->is_active == 't' || $employee->is_active == 1 || $employee->is_active === true)
                                        <span class="bg-green-500 text-white text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-600">
                                            Активен
                                        </span>
                                        @elseif($employee->is_active == 'f' || $employee->is_active == 0 || $employee->is_active === false)
                                        <span class="bg-red-500 text-white text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-600">
                                            Неактивен
                                        </span>
                                        @else
                                        <span class="bg-gray-500 text-white text-xs font-medium px-2.5 py-0.5 rounded">
                                            Неизвестно ({{ $employee->is_active }})
                                        </span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('admin.employees.show', $employee) }}"
                                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-2 rounded text-sm">
                                                Просмотр
                                            </a>
                                            <a href="{{ route('admin.employees.edit', $employee) }}"
                                                class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded text-sm">
                                                Редактировать
                                            </a>
                                            <form action="{{ route('admin.employees.destroy', $employee) }}"
                                                method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-sm"
                                                    onclick="return confirm('Вы уверены?')">
                                                    Удалить
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="py-4 px-4 text-center">Сотрудники не найдены</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>