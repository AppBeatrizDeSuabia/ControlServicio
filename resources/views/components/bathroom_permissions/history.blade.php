<div>

    <h1>Historial de permisos</h1>

    @if(session('success'))
        <p>{{ session('success') }}</p>
    @endif

    @if(session('error'))
        <p style="color:red">{{ session('error') }}</p>
    @endif

    <form method="GET" action="{{ route('permissions.history') }}">
        <label for="course_id">Curso:</label>
        <select
            id="course_id"
            name="course_id"
            onchange="
                const alumno = this.form.querySelector('[name=alumn_id]');
                if (alumno) alumno.value = '';
                this.form.submit();
            "
        >
            <option value="">Todos los cursos</option>

            @foreach($courses as $course)
                <option
                    value="{{ $course->id }}"
                    {{ (string) $courseId === (string) $course->id ? 'selected' : '' }}
                >
                    {{ $course->name }}
                </option>
            @endforeach
        </select>

        @if($courseId)
            <label for="alumn_id">Alumno:</label>
            <select
                id="alumn_id"
                name="alumn_id"
                onchange="this.form.submit()"
            >
                <option value="">Todos los alumnos del curso</option>

                @foreach($alumns as $alumn)
                    <option
                        value="{{ $alumn->id }}"
                        {{ (string) $alumnId === (string) $alumn->id ? 'selected' : '' }}
                    >
                        {{ $alumn->full_name }}
                    </option>
                @endforeach
            </select>
        @endif

        <label for="bathroom">Baño:</label>
        <select
            id="bathroom"
            name="bathroom"
            onchange="this.form.submit()"
        >
            <option value="">Todos los baños</option>

            @foreach($bathrooms as $key => $label)
                <option
                    value="{{ $key }}"
                    {{ $selectedBathroom === $key ? 'selected' : '' }}
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <noscript>
            <button type="submit">Filtrar</button>
        </noscript>
    </form>

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