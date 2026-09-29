<div>
    <h1>Control de Baños</h1>

    <h2>Permisos activos por baño</h2>

    @foreach($bathrooms as $key => $label)
        <p>
            {{ $label }}:
            {{ $currentCountByBathroom->get($key, 0) }}/{{ $maxPermissionsByBathroom[$key] }}
        </p>
    @endforeach

    @if(session('error'))
        <p style="color:red">{{ session('error') }}</p>
    @endif

    @if(session('success'))
        <p style="color:green">{{ session('success') }}</p>
    @endif

    {{-- Selección de curso --}}
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

    <form method="GET" action="{{ route('dashboard') }}">
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

        <button type="submit">Filtrar</button>
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
                form.submit();
            });
        });
    </script>

    {{-- Mensaje alumnos que han llegado al límite diario --}}
    @if($courseId)
        @php
            $alumnosMaxHoy = $alumns->filter(fn($a) => ($salidasHoy[$a->id] ?? 0) >= $maxDailyPerAlumn);
        @endphp

        @if($alumnosMaxHoy->isNotEmpty())
            <p style="color:red; font-weight:bold;">
                ⚠️ Los siguientes alumnos ya han alcanzado el máximo diario de permisos ({{ $maxDailyPerAlumn }}):
                <ul>
                    @foreach($alumnosMaxHoy as $a)
                        <li>{{ $a->full_name }} ({{ $salidasHoy[$a->id] ?? 0 }} hoy)</li>
                    @endforeach
                </ul>
            </p>
        @endif
    @endif

    {{-- Formulario de permisos --}}
    <form method="POST" action="{{ route('give.permission') }}">
        @csrf

        @if($alumns->isNotEmpty())
            <select name="alumn_id">
                @foreach($alumns as $alumn)
                    @php
                        $dailyCount = $salidasHoy[$alumn->id] ?? 0;
                    @endphp
                    <option value="{{ $alumn->id }}">
                        {{ $alumn->full_name }} ({{ $dailyCount }} hoy)
                    </option>
                @endforeach
            </select>
            <br><br>
            <select name="bathroom" required>
                <option value="">Selecciona un baño</option>
                @foreach($bathrooms as $key => $label)
                    <option value="{{ $key }}" {{ old('bathroom') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <button type="submit">Dar permiso</button>

        @else
            <p>Selecciona un Curso:</p>
        @endif
    </form>

    <hr>

    <h3>Permisos activos:</h3>
    @foreach($activePermissions as $permission)
        <div style="margin-bottom: 15px; font-size: 18px;">

            <div>
                <strong>Profesor:</strong> {{ $permission->teacher->full_name }}
            </div>

            <div>
                <strong>Alumno:</strong> {{ $permission->alumn?->full_name ?? 'Sin alumno' }}
            </div>

            <div>
                <strong>Baño:</strong>
                {{ $bathrooms[$permission->bathroom] ?? 'Sin baño asignado' }}
            </div>

            <div>
                Salió hace: {{ $permission->created_at->diffForHumans() }}
            </div>

            <form method="POST" action="{{ route('mark.returned', $permission->id) }}" style="display:inline;">
                @csrf
                <button type="submit">Ha regresado</button>
            </form>

        </div>
    @endforeach

    <br>

    @if(session()->has('profesor') && session('profesor')->is_admin)
        <hr>
        <h2>Importaciones masivas</h2>
        <p>Importa los datos directamente desde Google Sheets.</p>

        <a href="https://docs.google.com/spreadsheets/d/1rzYtu8DZxf4Op_aAyzoT9ehEQBSYgd8lj3puMv6Q5FA/edit?usp=sharing" target="_blank">
            <button type="button">Abrir Google Sheets</button>
        </a>

        <br><br>

        <form action="{{ route('import', ['type' => 'teachers']) }}" method="POST" style="display:inline-block;">
            @csrf
            <button type="submit">Importar Profesores</button>
        </form>

        <form action="{{ route('import', ['type' => 'alumns']) }}" method="POST" style="display:inline-block;">
            @csrf
            <button type="submit">Importar Alumnos</button>
        </form>

        <h2>Edición Individual</h2>
        <a href="{{ route('teachers.index') }}">
            <button type="button">Ver Profesores</button>
        </a>

        <a href="{{ route('alumns.index') }}">
            <button type="button">Ver Alumnos</button>
        </a>

        <br><br>

        <h2>Historial de Permisos</h2>
        <a href="{{ route('permissions.history') }}">
            <button type="button">Ver historial de permisos</button>
        </a>

        <br><br>

        <h2>Configuración</h2>
        <form method="POST" action="{{ route('settings.update') }}">
            @csrf

            @foreach($bathrooms as $key => $label)
                <label>Límite de permisos activos — {{ $label }}:</label>
                <input
                    type="number"
                    name="max_permissions_{{ $key }}"
                    min="1"
                    required
                    value="{{ $maxPermissionsByBathroom[$key] }}"
                >
                <br>
            @endforeach

            <label>Máximo diario por alumno:</label>
            <input type="number" name="max_daily_per_alumn" value="{{ $maxDailyPerAlumn }}">

            <label>Duración permiso (minutos):</label>
            <input type="number" name="permission_duration_minutes" value="{{ $permissionDuration }}">


            <br><br>
            <button type="submit">Guardar</button>
        </form>

        <br><br>

        <h2>Cambiar contraseña</h2>

        <form method="POST" action="{{ route('teachers.change-password') }}">
            @csrf

            <input type="password" name="password" placeholder="Nueva contraseña">

            <button type="submit">Cambiar</button>
        </form>
    @endif

    <br><br>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Cerrar Sesión</button>
    </form>

</div>