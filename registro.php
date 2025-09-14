<?php /* Registro */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Bancalia - Registro</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/Bancalia/assets/css/app.css">
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;background:#f6f7fb}
    .container{max-width:640px;margin:40px auto;padding:24px;background:#fff;border:1px solid #e5e7eb;border-radius:12px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    label{display:block;font-size:.9rem;margin:.25rem 0}
    input[type="text"],input[type="email"],input[type="password"],select{
      width:100%;padding:.6rem .7rem;border:1px solid #d1d5db;border-radius:8px;background:#fff
    }
    .help{font-size:.8rem;color:#6b7280}
    .btn{padding:.6rem 1rem;border-radius:8px;border:1px solid #d1d5db;background:#fff;cursor:pointer}
    .btn-primary{background:#2563eb;border-color:#1d4ed8;color:#fff}
    .btn-danger{background:#ef4444;border-color:#dc2626;color:#fff}
    .hidden{display:none}
    table{width:100%;font-size:.9rem;border-collapse:collapse}
    th,td{padding:.5rem;border-bottom:1px solid #e5e7eb;text-align:left}
    .actions{display:flex;gap:.5rem;flex-wrap:wrap}
    .error{color:#b91c1c;margin-top:.5rem;min-height:1.25rem}
  </style>
</head>
<body>
  <div class="container">
    <img src="/Bancalia/assets/images/logo.png" alt="Bancalia" style="height:48px">
    <h1>Crear cuenta</h1>

    <form id="registroForm" action="/Bancalia/api/registro.php" method="post">
      <!-- ocultos controlados por JS -->
      <input type="hidden" name="grado_id" id="gradoHidden">
      <input type="hidden" name="curso_id" id="cursoHidden">

      <div class="row">
        <div>
          <label>Nombre</label>
          <input type="text" name="nombre" required>
        </div>
        <div>
          <label>Email</label>
          <input type="email" name="email" required>
        </div>
      </div>

      <div class="row" style="grid-template-columns:1fr 1fr 1fr">
        <div>
          <label>Contraseña</label>
          <input type="password" name="clave" required>
        </div>
        <div>
          <label>Perfil</label>
          <div>
            <label><input type="radio" name="perfil" value="alumno" checked> Alumno</label>
            <label><input type="radio" name="perfil" value="profesor"> Profesor</label>
          </div>
        </div>
      </div>

      <!-- Alumno -->
      <div id="secAlumno" class="mt">
        <h3>Datos de alumno</h3>
        <div class="row">
          <div>
            <label>Grado</label>
            <!-- SIN name: lo pondrá el hidden -->
            <select id="aGrado"></select>
          </div>
          <div>
            <label>Curso</label>
            <!-- SIN name: lo pondrá el hidden -->
            <select id="aCurso"></select>
          </div>
        </div>
      </div>

      <!-- Profesor -->
      <div id="secProfe" class="mt hidden">
        <h3>Ámbitos que imparte</h3>
        <div class="row">
          <div>
            <label>Grado</label>
            <!-- SIN name: lo pondrá el hidden -->
            <select id="pGrado"></select>
          </div>
          <div>
            <label>Curso(s) del grado</label>
            <select id="pCursos" multiple size="6"></select>
            <div class="help">Puedes elegir varios (Ctrl/⌘ + clic)</div>
          </div>
        </div>
        <div class="row" style="grid-template-columns:1fr">
          <div>
            <label>Asignaturas (filtradas por curso)</label>
            <select id="pAsigs" multiple size="8"></select>
          </div>
        </div>
        <div class="actions" style="margin:.5rem 0 1rem">
          <!-- Opcional, no es obligatorio usarlo -->
          <button type="button" id="pAdd" class="btn">Añadir combinación</button>
          <button type="button" id="pClear" class="btn">Limpiar selección</button>
        </div>

        <table>
          <thead>
            <tr><th>Curso</th><th>Asignatura</th><th>Acción</th></tr>
          </thead>
          <tbody id="pList">
            <tr><td colspan="3" style="text-align:center;color:#6b7280">Sin asignaciones.</td></tr>
          </tbody>
        </table>
      </div>

      <div class="actions" style="margin-top:1rem">
        <button type="submit" class="btn btn-primary">Crear cuenta</button>
        <a class="btn" href="/Bancalia/index.php">Cancelar</a>
      </div>
      <div id="regError" class="error"></div>
    </form>
  </div>

  <script src="/Bancalia/assets/js/registro.js"></script>
</body>
</html>
