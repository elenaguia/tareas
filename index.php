
<?php
session_start();
require_once "config.php";

function escapar($texto) {
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Token de protección para formularios
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = "";

// Procesar acciones
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals(
        $_SESSION['csrf'],
        $_POST['csrf'] ?? ''
    )) {
        http_response_code(403);
        die("Solicitud no válida.");
    }

    $accion = $_POST["accion"] ?? "";
    $id = filter_var(
        $_POST["id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($accion === "crear" || $accion === "editar") {

        $titulo = trim($_POST["titulo"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $estado = $_POST["estado"] ?? "pendiente";

        if ($titulo === "" || mb_strlen($titulo) > 200) {
            $error = "El título debe tener entre 1 y 200 caracteres.";
        } elseif (!in_array(
            $estado,
            ["pendiente", "completada"],
            true
        )) {
            $error = "Estado no válido.";
        } elseif ($accion === "editar" && !$id) {
            $error = "ID de tarea no válido.";
        } else {

            if ($accion === "crear") {
                $stmt = $pdo->prepare(
                    "INSERT INTO tareas
                     (titulo, descripcion, estado)
                     VALUES (?, ?, ?)"
                );
                $stmt->execute([
                    $titulo, $descripcion, $estado
                ]);
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE tareas
                     SET titulo = ?,
                         descripcion = ?,
                         estado = ?
                     WHERE id = ?"
                );
                $stmt->execute([
                    $titulo, $descripcion, $estado, $id
                ]);
            }

            header("Location: index.php");
            exit;
        }
    }

    if ($accion === "eliminar") {
        if (!$id) {
            $error = "ID de tarea no válido.";
        } else {
            $stmt = $pdo->prepare(
                "DELETE FROM tareas WHERE id = ?"
            );
            $stmt->execute([$id]);

            header("Location: index.php");
            exit;
        }
    }
}

// Cargar tarea para editar
$tareaEditar = null;

$idEditar = filter_input(
    INPUT_GET,
    "editar",
    FILTER_VALIDATE_INT
);

if ($idEditar) {
    $stmt = $pdo->prepare(
        "SELECT * FROM tareas WHERE id = ?"
    );
    $stmt->execute([$idEditar]);
    $tareaEditar = $stmt->fetch();
}

// Obtener todas las tareas
$tareas = $pdo->query(
    "SELECT * FROM tareas
     ORDER BY fecha_creacion DESC, id DESC"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>Gestor de tareas</title>

<style>
* {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f3f5f9;
    margin: 0;
    padding: 30px 15px;
    color: #263245;
}

.contenedor {
    max-width: 900px;
    margin: auto;
}

h1 {
    margin-bottom: 25px;
}

.panel {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 2px 12px #00000010;
}

input, textarea, select {
    width: 100%;
    padding: 11px;
    margin: 8px 0 15px;
    border: 1px solid #ccd3dd;
    border-radius: 6px;
    font: inherit;
}

textarea {
    min-height: 90px;
}

button, .boton {
    display: inline-block;
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    font: inherit;
}

.guardar {
    background: #2563eb;
    color: white;
}

.editar {
    background: #e7efff;
    color: #1d4ed8;
}

.eliminar {
    background: #fee2e2;
    color: #b91c1c;
}

.cancelar {
    background: #e5e7eb;
    color: #374151;
}

.tarea {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px #0000000c;
}

.tarea h3 {
    margin: 0 0 8px;
}

.descripcion {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.estado {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 13px;
    margin-bottom: 12px;
}

.pendiente {
    background: #fef3c7;
    color: #92400e;
}

.completada {
    background: #dcfce7;
    color: #166534;
}

.acciones {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.acciones form {
    margin: 0;
}

.error {
    color: #b91c1c;
}
</style>
</head>

<body>
<div class="contenedor">

<h1>Gestor de tareas</h1>

<div class="panel">

<h2>
<?= $tareaEditar ? "Editar tarea" : "Nueva tarea" ?>
</h2>

<?php if ($error): ?>
    <p class="error"><?= escapar($error) ?></p>
<?php endif; ?>

<form method="POST" action="index.php">

<input type="hidden" name="csrf"
       value="<?= escapar($_SESSION['csrf']) ?>">

<input type="hidden" name="accion"
       value="<?= $tareaEditar ? 'editar' : 'crear' ?>">

<?php if ($tareaEditar): ?>
<input type="hidden" name="id"
       value="<?= (int)$tareaEditar['id'] ?>">
<?php endif; ?>

<label>Título</label>
<input type="text" name="titulo"
       maxlength="200"
       required
       value="<?= escapar(
           $tareaEditar['titulo'] ?? ''
       ) ?>">

<label>Descripción</label>
<textarea name="descripcion"><?= escapar(
    $tareaEditar['descripcion'] ?? ''
) ?></textarea>

<label>Estado</label>
<select name="estado">
    <option value="pendiente"
        <?= ($tareaEditar['estado'] ?? '') ===
            'pendiente' ? 'selected' : '' ?>>
        Pendiente
    </option>

    <option value="completada"
        <?= ($tareaEditar['estado'] ?? '') ===
            'completada' ? 'selected' : '' ?>>
        Completada
    </option>
</select>

<button class="guardar" type="submit">
    <?= $tareaEditar ? "Guardar cambios" : "Crear tarea" ?>
</button>

<?php if ($tareaEditar): ?>
    <a href="index.php" class="boton cancelar">
        Cancelar
    </a>
<?php endif; ?>

</form>
</div>

<h2>Mis tareas (<?= count($tareas) ?>)</h2>

<?php if (empty($tareas)): ?>
    <p>No hay tareas todavía.</p>
<?php endif; ?>

<?php foreach ($tareas as $tarea): ?>

<div class="tarea">

    <h3><?= escapar($tarea["titulo"]) ?></h3>

    <span class="estado <?= escapar($tarea["estado"]) ?>">
        <?= ucfirst(escapar($tarea["estado"])) ?>
    </span>

    <p class="descripcion"><?= escapar(
        $tarea["descripcion"]
    ) ?></p>

    <small>
        Creada:
        <?= escapar($tarea["fecha_creacion"]) ?>
    </small>

    <div class="acciones" style="margin-top:15px">

        <a class="boton editar"
           href="index.php?editar=<?= (int)$tarea['id'] ?>">
            Editar
        </a>

        <form method="POST" action="index.php"
              onsubmit="return confirm('¿Eliminar esta tarea?')">

            <input type="hidden" name="csrf"
                   value="<?= escapar($_SESSION['csrf']) ?>">

            <input type="hidden" name="accion"
                   value="eliminar">

            <input type="hidden" name="id"
                   value="<?= (int)$tarea['id'] ?>">

            <button type="submit" class="eliminar">
                Eliminar
            </button>
        </form>

    </div>
</div>

<?php endforeach; ?>

</div>
</body>
</html>
