# API REST de tareas (Laravel)

API para gestionar tareas: crear, listar, ver, editar y borrar, con filtros por estado y responsable y paginación. Laravel 12, PHP 8.4, SQLite.

## Cómo ejecutarlo

### Con Docker (lo único que necesitas)

```bash
docker compose up --build
```

En el primer arranque el contenedor instala las dependencias, crea el `.env`, la clave de la app y la base SQLite, y ejecuta las migraciones. La API queda en `http://localhost:8000/api`.

> Si `docker compose up --build` te pide un permiso extra de *bake* (me pasó con Docker 29 en Windows), construye la imagen a mano y levanta sin `--build`:
> `docker build -t qabit-task-api .` y después `docker compose up`.

Tests:

```bash
docker compose exec app php artisan test
```

### Sin Docker (PHP 8.4 + Composer)

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
php artisan test
```

## Endpoints

| Método | Ruta | Descripción | Respuestas |
|---|---|---|---|
| GET | `/api/tasks` | Listado paginado con filtros | 200, 422 |
| POST | `/api/tasks` | Crear tarea | 201 (+ `Location`), 422 |
| GET | `/api/tasks/{id}` | Ver tarea | 200, 404 |
| PUT | `/api/tasks/{id}` | Editar (el título es obligatorio) | 200, 404, 422 |
| PATCH | `/api/tasks/{id}` | Editar solo los campos enviados | 200, 404, 422 |
| DELETE | `/api/tasks/{id}` | Borrar | 204, 404 |

**Campos de una tarea**

| Campo | Tipo | Reglas |
|---|---|---|
| `title` | string | obligatorio, máx. 255 |
| `description` | string | opcional, máx. 5000 |
| `status` | string | `pending` (por defecto), `in_progress` o `done` |
| `assignee` | string | opcional, máx. 255 |
| `due_date` | fecha `YYYY-MM-DD` | opcional; al crear no puede estar en el pasado |

**Filtros del listado** (`GET /api/tasks`)

| Parámetro | Descripción |
|---|---|
| `status` | `pending`, `in_progress` o `done`. Un valor desconocido responde 422. |
| `assignee` | Coincidencia exacta con el responsable. |
| `per_page` | De 1 a 100 (15 por defecto). |
| `page` | Número de página. |

El listado se ordena por fecha límite (las tareas sin fecha van al final) y desempata por `id`, para que la paginación sea estable. Los enlaces de `links` conservan los filtros activos.

### Ejemplos

```bash
# Crear
curl -i -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -d '{"title":"Preparar la demo","status":"in_progress","assignee":"Ana","due_date":"2026-12-15"}'

# Listar con filtros y paginación
curl "http://localhost:8000/api/tasks?status=in_progress&assignee=Ana&per_page=5"

# Ver, editar parcialmente y borrar
curl http://localhost:8000/api/tasks/1
curl -X PATCH http://localhost:8000/api/tasks/1 -H "Content-Type: application/json" -d '{"status":"done"}'
curl -X DELETE http://localhost:8000/api/tasks/1
```

Respuesta de una tarea:

```json
{
  "data": {
    "id": 1,
    "title": "Preparar la demo",
    "description": null,
    "status": "in_progress",
    "assignee": "Ana",
    "due_date": "2026-12-15",
    "created_at": "2026-11-18T19:28:31+00:00",
    "updated_at": "2026-11-18T19:28:31+00:00"
  }
}
```

Error de validación (422):

```json
{
  "message": "The title field is required. (and 1 more error)",
  "errors": {
    "title": ["The title field is required."],
    "status": ["The selected status is invalid."]
  }
}
```

## Decisiones que tomé

- **Laravel en vez de Symfony.** Es el que domino más. Form Requests, API Resources, paginación y factories vienen resueltos y me dejan centrarme en el comportamiento.
- **SQLite.** Quien revise no tiene que montar ninguna base de datos. Las migraciones son estándar y valen igual para MySQL o PostgreSQL. Los tests usan SQLite en memoria.
- **Docker para ejecutarlo.** Así el único requisito es Docker. La imagen usa PHP 8.4 porque el `composer.lock` resolvió paquetes que exigen `>= 8.4.1`.
- **Responsable como texto (`assignee`).** El enunciado solo habla de "responsable" y no pide usuarios ni autenticación. Una tabla `users` con clave foránea habría añadido bastante superficie sin que se pida. Lo anoto abajo como mejora.
- **Estado como enum de PHP** (`TaskStatus`), con valores en inglés en la API y validado con `Rule::enum`.
- **Fecha límite:** al crear no puede ser pasada, pero al editar sí, para poder seguir tocando tareas vencidas (marcarlas como hechas, por ejemplo).
- **PUT vs PATCH.** PUT exige el título; PATCH valida solo lo que llega. En ambos, lo que no se envía conserva su valor.
- **Errores siempre en JSON** para todo lo que cuelga de `/api/*`, aunque el cliente no mande `Accept: application/json`. El 404 no filtra nombres de modelos ni clases de excepción.
- **Filtros estrictos:** un `status` inexistente o un `per_page` fuera de rango devuelven 422 en lugar de ignorarse, para no devolver resultados que el cliente no esperaba.
- **Sin capas extra.** Controlador delgado, validación en Form Requests, filtros como *scopes* del modelo. Un repositorio o una capa de servicios no aportarían nada en este tamaño.
- **Sin autenticación**, porque no se pide.
- **Coincidencia exacta en `assignee`.** Mantiene el uso del índice y se comporta igual en cualquier base de datos. Una búsqueda sin distinguir mayúsculas se comporta distinto en SQLite y MySQL, y no quise esconder esa diferencia.

## Qué haría con más tiempo

- Usuarios y autenticación (Sanctum), con `assignee_id` como clave foránea y filtro por usuario.
- Búsqueda de texto en título y descripción, filtro por rango de fechas y ordenación configurable.
- Borrado lógico (*soft deletes*) en vez de borrado definitivo.
- Documentación OpenAPI y colección de Postman.
- Integración continua con GitHub Actions (tests y análisis estático con PHPStan/Larastan).
- Probar las migraciones contra MySQL y PostgreSQL en la CI, no solo SQLite.
- Límite de peticiones (*rate limiting*) y versionado de la API.
- Seeders con datos de ejemplo.

## Cómo usé la IA

Trabajé con Claude Code (en VS Code) como asistente de programación. Yo decidí el alcance y el flujo de trabajo; la IA escribió gran parte del código y de los tests, y yo revisé cada paso.

**Qué le pedí**

- Leer el enunciado y proponer un plan con decisiones y riesgos. De ahí salió el plan que seguí.
- Montar el proyecto, el entorno Docker, el modelo, los endpoints, la validación, los filtros y los tests, una rama de funcionalidad cada vez, con commits pequeños y merges a `develop`.
- Redactar este README a partir de lo que realmente se hizo y se probó.

**Qué decidí yo**

- Alcance y tiempo: 3 horas, como pide el enunciado, aunque llegué a plantear ampliarlo.
- Estrategia de ramas: una `feature/*` por bloque, fusionadas en `develop` con `--no-ff`, y sin borrar las ramas para que se vea el proceso.
- Responsable como texto y sin autenticación.

**Qué acepté tal cual**

- La estructura general (Form Requests, Resources, scopes, enum), la forma de los errores 422 y la batería de tests de escritura.

**Qué se corrigió por el camino**

- **Versión de PHP en Docker.** La primera imagen era PHP 8.3 y la app no arrancaba: el `composer.lock` exigía `>= 8.4.1`. Subí la imagen a 8.4 en vez de forzar el lock.
- **Errores en HTML.** Escribí primero el test del 404 sin la cabecera `Accept` y falló: Laravel devolvía una página HTML. Lo dejé como commit `fix:` propio.
- **Fuga de información en el 404.** Al probar con curl vi que el mensaje incluía el nombre del modelo y la clase de la excepción. Lo sustituí por un mensaje genérico, con test.
- **Regla redundante** en `UpdateTaskRequest` (una condición que devolvía siempre lo mismo). La simplifiqué.
- **`install:api`.** Descarté ese comando porque instala Sanctum, que no necesito; registré `routes/api.php` a mano.
- **Docker Compose.** El `build` fallaba en mi máquina por un permiso de *bake*; construí la imagen con `docker build` y lo dejé documentado arriba.

**Cómo lo verifiqué**

- Ejecuté la suite completa tras cada bloque y probé la API real con `curl` (crear, filtrar, editar, borrar, errores) antes de documentar los ejemplos.

## Tiempo

Implementación medida con reloj: de las 15:09 a las 15:31 del 18/11/2026, unos **25 minutos** de trabajo con la IA escribiendo el código y yo revisando. No cuenta la lectura del enunciado ni la planificación previa, que hice antes de empezar a medir. Cada bloque de ramas quedó muy por debajo del tiempo que había presupuestado, así que prefiero reportar la cifra real antes que ajustarla a las 3 horas.
