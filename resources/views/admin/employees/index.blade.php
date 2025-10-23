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
                                            {{ $employee->id }}</td>
                                        <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                            {{ $employee->name }}</td>
                                        <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                            {{ $employee->email }}</td>
                                        <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                            <span
                                                class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                                {{ $employee->role_name }}
                                            </span>
                                        </td>
                                        <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                            @if($employee->is_active)
                                                <span
                                                    class="bg-green-500 text-white text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-600">
                                                    Активен
                                                </span>
                                            @else
                                                <span
                                                    class="bg-red-500 text-white text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-600">
                                                    Неактивен
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