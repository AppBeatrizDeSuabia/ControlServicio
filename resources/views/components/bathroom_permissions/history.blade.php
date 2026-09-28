<div>

    <h1>Historial de permisos</h1>

    @if(session('success'))
        <p>{{ session('success') }}</p>
    @endif

    @if(session('error'))
        <p style="color:red">{{ session('error') }}</p>
    @endif

    <form method="POST" action="{{ route('permissions.export') }}">
        @csrf
        <button type="submit">
            Exportar a Google Sheets
        </button>
    </form>

    <a href="{{ route('dashboard') }}">
        <button type="button">
            Volver a la página principal
        </button>
    </a>

    <br>

    <table border="1">

    <thead>
    <tr>
        <th>Alumno</th>
        <th>Profesor</th>
        <th>Baño</th>
        <th>Salida</th>
        <th>Regreso</th>
    </tr>
    </thead>

    <tbody>

    @foreach($permissions as $permission)

    <tr>
        <td>{{ $permission->alumn?->full_name }}</td>
        <td>{{ $permission->teacher?->full_name }}</td>
        <td>{{ $bathrooms[$permission->bathroom] ?? 'Sin baño asignado' }}</td>
        <td>{{ $permission->created_at }}</td>
        <td>{{ $permission->returned_at ?? 'No ha vuelto' }}</td>
    </tr>

    @endforeach

    </tbody>

    </table>

</div>