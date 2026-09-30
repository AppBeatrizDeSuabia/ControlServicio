<div>
    <h1>Login Profesores/as</h1>

    @if($errors->any())
        <div style="color:red">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login.submit') }}">
        @csrf

        <label>Email:</label>
        <input type="email" name="email" required>

        <button type="submit">Continuar</button>
    </form>

</div>
