<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Просмотр сотрудника: {{ $employee->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <table class="min-w-full">
                                <tbody>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="py-2 px-4 font-medium">ID:</td>
                                        <td class="py-2 px-4">{{ $employee->id }}</td>
                                    </tr>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="py-2 px-4 font-medium">Имя:</td>
                                        <td class="py-2 px-4">{{ $employee->name }}</td>
                                    </tr>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="py-2 px-4 font-medium">Email:</td>
                                        <td class="py-2 px-4">{{ $employee->email }}</td>
                                    </tr>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="py-2 px-4 font-medium">Должность:</td>
                                        <td class="py-2 px-4">
                                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                                {{ $employee->role_name }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="py-2 px-4 font-medium">Статус:</td>
                                        <td class="py-2 px-4">
                                            @if($employee->is_active)
                                                <span class="bg-green-500 text-white text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-600">
                                                    Активен
                                                </span>
                                            @else
                                                <span class="bg-red-500 text-white text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-600">
                                                    Неактивен
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="py-2 px-4 font-medium">Дата создания:</td>
                                        <td class="py-2 px-4">{{ $employee->created_at->format('d.m.Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 px-4 font-medium">Последнее обновление:</td>
                                        <td class="py-2 px-4">{{ $employee->updated_at->format('d.m.Y H:i') }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="mt-6 flex space-x-4">
                                <a href="{{ route('admin.employees.edit', $employee) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                    Редактировать
                                </a>
                                <a href="{{ route('admin.employees.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                    Назад к списку
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>