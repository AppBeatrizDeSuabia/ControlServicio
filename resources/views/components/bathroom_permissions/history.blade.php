<div>

    <h1>Historial de permisos</h1>

    @if(session('success'))
        <p>{{ session('success') }}</p>
    @endif

    @if(session('error'))
        <p style="color:red">{{ session('error') }}</p>
    @endif

    @php
        $esoBachCourses = $courses->filter(
            fn ($course) => str_starts_with(strtoupper($course->name), 'ESO')
                || str_starts_with(strtoupper($course->name), 'BACH')
        );

        $cycleCourses = $courses->reject(
            fn ($course) => str_starts_with(strtoupper($course->name), 'ESO')
                || str_starts_with(strtoupper($course->name), 'BACH')
        );
    @endphp

    <form method="GET" action="{{ route('permissions.history') }}">
        <input type="hidden" name="course_id" value="{{ $courseId }}">

        <label for="course_general">ESO y Bachillerato:</label>
        <select id="course_general">
            <option value="">-- Selecciona un curso --</option>
            @foreach($esoBachCourses as $course)
                <option value="{{ $course->id }}"
                    {{ (string) $courseId === (string) $course->id ? 'selected' : '' }}>
                    {{ $course->name }}
                </option>
            @endforeach
        </select>

        <label for="course_cycle">Ciclos:</label>
        <select id="course_cycle">
            <option value="">-- Selecciona un ciclo --</option>
            @foreach($cycleCourses as $course)
                <option value="{{ $course->id }}"
                    {{ (string) $courseId === (string) $course->id ? 'selected' : '' }}>
                    {{ $course->name }}
                </option>
            @endforeach
        </select>

        @if($courseId)
            <label for="alumn_id">Alumno:</label>
            <select id="alumn_id" name="alumn_id" onchange="this.form.submit()">
                <option value="">Todos los alumnos del curso</option>
                @foreach($alumns as $alumn)
                    <option value="{{ $alumn->id }}"
                        {{ (string) $alumnId === (string) $alumn->id ? 'selected' : '' }}>
                        {{ $alumn->full_name }}
                    </option>
                @endforeach
            </select>
        @endif

        <label for="bathroom">Baño:</label>
        <select id="bathroom" name="bathroom" onchange="this.form.submit()">
            <option value="">Todos los baños</option>
            @foreach($bathrooms as $key => $label)
                <option value="{{ $key }}"
                    {{ $selectedBathroom === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <noscript>
            <button type="submit">Filtrar</button>
        </noscript>
    </form>

    <script>
        document.querySelectorAll('#course_general, #course_cycle').forEach(select => {
            select.addEventListener('change', function () {
                const form = this.form;
                const otherId = this.id === 'course_general'
                    ? 'course_cycle'
                    : 'course_general';

                form.querySelector(`#${otherId}`).value = '';
                form.querySelector('[name="course_id"]').value = this.value;

                const alumn = form.querySelector('[name="alumn_id"]');
                if (alumn) alumn.value = '';

                form.submit();
            });
        });
    </script>

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