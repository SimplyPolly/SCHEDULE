<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }
    
    public function index()
    {
        $employees = Employee::latest()->get();
        return view('admin.employees.index', compact('employees'));
    }

    public function create()
    {
        return view('admin.employees.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'role' => 'required|in:cook,waiter,hostess,bartender,admin',
            'is_active' => 'boolean',
            'phone' => 'nullable|string|max:255',
            'telegram' => 'nullable|string|max:255',
        ]);

        // Генерируем случайный пароль, чтобы админ его не знал,
        // а сотрудник установил свой через письмо-ссылку для установки пароля
        $validated['password'] = Hash::make(Str::random(32));
        $validated['is_active'] = $request->has('is_active');

        $employee = Employee::create($validated);

        // Отправляем сотруднику письмо для установки пароля
        Password::sendResetLink(['email' => $employee->email]);

        return redirect()->route('admin.employees.index')
            ->with('success', 'Сотрудник успешно создан.');
    }

    public function show(Employee $employee)
    {
        return view('admin.employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        return view('admin.employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('employees')->ignore($employee->id),
            ],
            'role' => 'required|in:cook,waiter,hostess,bartender,admin',
            'is_active' => 'boolean',
            'phone' => 'nullable|string|max:255',
            'telegram' => 'nullable|string|max:255',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $employee->update($validated);

        return redirect()->route('admin.employees.index')
            ->with('success', 'Сотрудник успешно обновлен.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('admin.employees.index')
            ->with('success', 'Сотрудник успешно удален.');
    }
}