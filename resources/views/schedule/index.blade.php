@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>График смен (ближайшие 2 недели)</h2>

        @if($assignments->isEmpty())
            <p>График пока не сгенерирован.</p>
        @else
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Смена</th>
                        <th>Роль</th>
                        <th>Сотрудник</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignments as $assign)
                        <tr>
                            <td>{{ $assign->date->format('d.m.Y') }}</td>
                            <td>
                                @if($assign->shift_type === 'morning') Утро
                                @elseif($assign->shift_type === 'day') День
                                @else Ночь
                                @endif
                            </td>
                            <td>{{ ucfirst($assign->employee->role) }}</td>
                            <td>{{ $assign->employee->name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(Auth::user()->role === 'admin')
            <form method="POST" action="{{ route('schedule.generate') }}">
                @csrf
                <button type="submit" class="btn btn-success">Сгенерировать график</button>
            </form>
        @endif
    </div>
@endsection