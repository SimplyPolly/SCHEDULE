<?php

namespace App\Http\Requests;

use App\Models\Employee; // Изменено с User на Employee
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(Employee::class)->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'], // Валидация для телефона
            'telegram' => ['nullable', 'string', 'max:100', 'regex:/^[@a-zA-Z0-9_\.\:\/]+$/'], // Валидация для Telegram
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Пожалуйста, введите корректный номер телефона',
            'telegram.regex' => 'Пожалуйста, введите корректный Telegram username или ссылку',
        ];
    }
}