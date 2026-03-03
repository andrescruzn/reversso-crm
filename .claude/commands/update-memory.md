# Actualizar Memory

Actualiza los archivos de memoria persistente del proyecto con el contexto de la sesión actual.

## Cuándo ejecutar
- Al terminar una sesión de trabajo con cambios importantes
- Al resolver un bug no trivial
- Al agregar una convención o patrón nuevo
- Al hacer cambios de arquitectura

## Archivos de memoria
Ubicados en el directorio de memoria del proyecto (auto-memory de Claude):

- `MEMORY.md` — resumen principal (máx ~200 líneas, siempre cargado en contexto)
- `overtime.md` — detalles del OvertimeCalculatorService
- `architecture.md` — detalles de arquitectura DDD (si existe)

## Qué actualizar en MEMORY.md

1. **Gotchas & Lessons** — agregar cualquier lección aprendida en la sesión
2. **Performance / OvertimeCalculatorService** — si se cambió la lógica de caché
3. **Tests** — si se agregaron/rompieron tests
4. **Important Files** — si se crearon archivos clave nuevos
5. **Deploy** — si cambió el proceso

## Qué NO poner en MEMORY.md
- Detalles de tareas puntuales completadas
- Contexto temporal ("hoy hicimos X")
- Información que ya está en CLAUDE.md

Revisa la sesión actual y actualiza los archivos de memoria que correspondan con información estable y reutilizable.
