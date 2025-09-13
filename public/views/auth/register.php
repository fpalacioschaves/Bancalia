<?php $path = __FILE__; ?>
<section class="card auth">
<h1>Crea tu cuenta</h1>
<form id="registerForm" class="form">
<label>Nombre
<input type="text" name="nombre" required placeholder="Tu nombre" />
</label>
<label>Email
<input type="email" name="email" required placeholder="tucorreo@centro.es" />
</label>
<label>Contraseña
<input type="password" name="password" minlength="6" required placeholder="Mínimo 6 caracteres" />
</label>


<fieldset class="role">
<legend>Rol</legend>
<label><input type="radio" name="rol" value="alumno" checked> Alumno</label>
<label><input type="radio" name="rol" value="profesor"> Profesor</label>
</fieldset>


<div class="grid">
<label>Grado
<select name="grado_id" id="gradoSelect" required></select>
</label>
<label>Curso
<select name="curso_id" id="cursoSelect" required></select>
</label>
</div>


<label id="asigWrap" hidden>Asignatura
<select name="asignatura_id" id="asigSelect"></select>
</label>


<button type="submit" class="btn primary">Crear cuenta</button>
<p class="muted">¿Ya tienes cuenta? <a href="/Bancalia/public/login">Inicia sesión</a></p>
<div class="alert" id="registerAlert" hidden></div>
</form>
</section>