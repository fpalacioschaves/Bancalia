<?php $path = __FILE__; ?>
<section class="card auth">
<h1>Accede a tu cuenta</h1>
<form id="loginForm" class="form">
<label>Email
<input type="email" name="email" required placeholder="tucorreo@centro.es" />
</label>
<label>Contraseña
<input type="password" name="password" required placeholder="••••••••" />
</label>
<button type="submit" class="btn primary">Entrar</button>
<p class="muted">¿Aún no tienes cuenta? <a href="/Bancalia/public/register">Regístrate</a></p>
<div class="alert" id="loginAlert" hidden></div>
</form>
</section>