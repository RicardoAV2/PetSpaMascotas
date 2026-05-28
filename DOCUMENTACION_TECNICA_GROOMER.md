# 🔧 DOCUMENTACIÓN TÉCNICA - SISTEMA DE INVENTARIO PARA GROOMER

## 📊 CAMBIOS EN LA BASE DE DATOS

### Nuevas Tablas Creadas

#### 1. `servicio_producto_reserva`
Rastrea qué productos se reservan para cada servicio de una cita.

```sql
CREATE TABLE servicio_producto_reserva (
    id_reserva_servicio INT AUTO_INCREMENT PRIMARY KEY,
    id_cita INT NOT NULL,
    id_servicio INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad_reservada INT NOT NULL DEFAULT 1,
    tamano_mascota ENUM('pequeno', 'mediano', 'grande') DEFAULT 'mediano',
    estado ENUM('reservado', 'usado', 'cancelado') DEFAULT 'reservado',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cita) REFERENCES cita(id_cita) ON DELETE CASCADE,
    FOREIGN KEY (id_servicio) REFERENCES servicio(id_servicio) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT,
    KEY idx_cita_servicio (id_cita, id_servicio),
    CHECK (cantidad_reservada > 0)
);
```

**Propósito:** Uno-a-muchos entre cita+servicio y productos. Permite:
- Múltiples servicios por cita
- Diferentes productos por servicio
- Rastrear tamaño de mascota para ajustar cantidades

---

#### 2. `ficha_servicio_registro`
Registra qué servicios se completaron en cada ficha de grooming.

```sql
CREATE TABLE ficha_servicio_registro (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_ficha INT NOT NULL,
    id_servicio INT NOT NULL,
    completado BOOLEAN DEFAULT FALSE,
    porcentaje_avance INT DEFAULT 0, -- 0-100
    notas TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_ficha) REFERENCES ficha_grooming(id_ficha) ON DELETE CASCADE,
    FOREIGN KEY (id_servicio) REFERENCES servicio(id_servicio) ON DELETE CASCADE,
    UNIQUE KEY unique_ficha_servicio (id_ficha, id_servicio)
);
```

**Propósito:** Permite marcar qué servicios están hechos vs pendientes en una ficha.

---

#### 3. `ficha_servicio_productos_usados`
Registra cuánto producto se usó realmente en cada servicio.

```sql
CREATE TABLE ficha_servicio_productos_usados (
    id_uso INT AUTO_INCREMENT PRIMARY KEY,
    id_ficha INT NOT NULL,
    id_servicio INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad_usada INT NOT NULL,
    cantidad_reservada INT NOT NULL,
    fecha_uso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_ficha) REFERENCES ficha_grooming(id_ficha) ON DELETE CASCADE,
    FOREIGN KEY (id_servicio) REFERENCES servicio(id_servicio) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT,
    CHECK (cantidad_usada > 0 AND cantidad_usada <= cantidad_reservada)
);
```

**Propósito:** Diferencia entre "reservado" y "usado", permitiendo sobrantes.

---

### Cambios a Tablas Existentes

#### `ficha_grooming`
Se agregó el campo `estado_cierre`:

```sql
ALTER TABLE ficha_grooming 
ADD COLUMN estado_cierre ENUM('completada', 'parcial', 'pausada') DEFAULT 'parcial';
```

**Propósito:** 
- `completada`: Todos los servicios listos, inventario descargado
- `parcial`: Algunos servicios hechos, inventario NO descargado aún
- `pausada`: Guardada manualmente por el groomer

---

## 📁 ARCHIVOS CREADOS/MODIFICADOS

### Creados

1. **`/petspa/api/empleado/helpers_grooming.php`** (Nuevo)
   - Helpers reutilizables para operaciones de grooming
   - Funciones de reserva, consumo y registro

2. **`/petspa/api/empleado/reservar_productos_servicio.php`** (Nuevo)
   - API POST para reservar productos
   - Endpoint: `/petspa/api/empleado/reservar_productos_servicio.php`

3. **`/petspa/GUIA_NUEVO_SISTEMA_GROOMER.md`** (Nuevo)
   - Documentación de usuario

### Modificados

1. **`/petspa/public/empleado/groomer/agenda.php`**
   - Interfaz completamente reescrita
   - Panel interactivo de selección de productos
   - Soporte para múltiples servicios por cita

2. **`/petspa/public/empleado/groomer/grooming.php`**
   - Interfaz con tabs por servicio
   - Registro dinámico de productos usados
   - Guardado parcial vs completada

3. **`/petspa/api/empleado/grooming.php`**
   - Lógica de guardado mejorada
   - Soporte para guardado parcial
   - Gestión de inventarios por servicio

4. **`/petspa/bd.sql`**
   - Nuevas tablas
   - Renumeración de secciones

---

## 🔄 FLUJO DE DATOS

### 1. Reserva de Productos (Agenda)

```
Usuario POST /petspa/api/empleado/reservar_productos_servicio.php
    ↓
    [Validar: permisos, cita existe, servicio existe]
    ↓
    Eliminar reservas previas para este servicio
    ↓
    Insertar nuevas reservas en servicio_producto_reserva
    ↓
    Response JSON { success: true }
    ↓
    Frontend: reload página o AJAX update
```

### 2. Atender Cita (Grooming)

```
Usuario abre grooming.php?id=<cita>
    ↓
    Backend carga:
    ├─ cita + mascota
    ├─ servicios de la cita
    ├─ ficha_grooming existente (si hay)
    ├─ ficha_servicio_registro (servicios hechos)
    └─ productos reservados por servicio
    ↓
    Frontend: UI con tabs, checklist, registro de productos
```

### 3. Guardar Grooming

#### Ruta A: Guardar Parcialmente

```
Usuario clicks "Guardar Parcialmente"
    ↓
    POST /petspa/api/empleado/grooming.php
    Datos: id_cita, servicios_completados[], productos_usados[], tipo_guardado=parcial
    ↓
    Backend:
    ├─ Crear/actualizar ficha_grooming (estado_cierre='parcial')
    ├─ Por cada servicio:
    │  ├─ registrarServicioCompletado() → ficha_servicio_registro
    │  └─ SI completado:
    │     └─ registrarConsumoServicio() → ficha_servicio_productos_usados
    └─ NO actualizar inventario
    ↓
    Redirect: grooming.php?id=<cita>&success=saved_partial
    ↓
    Cita sigue "en_progreso", puede volver después
```

#### Ruta B: Completar Cita

```
Usuario clicks "Completar Cita"
    ↓
    POST /petspa/api/empleado/grooming.php
    Datos: id_cita, servicios_completados[], tipo_guardado=completada
    ↓
    Backend:
    ├─ Crear/actualizar ficha_grooming (estado_cierre='completada')
    ├─ Por cada servicio:
    │  ├─ registrarServicioCompletado()
    │  └─ SI completado:
    │     ├─ registrarConsumoServicio()
    │     └─ aplicarConsumoProductosServicio()
    │        └─ Actualizar inventario (descuento)
    ├─ Actualizar cita: estado='completada'
    ├─ Actualizar ficha: hora_fin_real=NOW()
    └─ Subir foto (si existe)
    ↓
    Redirect: agenda.php?success=completed
    ↓
    Cita desaparece de agenda del groomer
```

---

## 📝 FUNCIONES DE HELPERS

### Ubicación: `/petspa/api/empleado/helpers_grooming.php`

```php
// Obtener servicios con detalles
getServiciosCita($conn, $id_cita) → Array

// Obtener productos disponibles
getProductosDisponibles($conn) → Array

// Reservar productos para servicio
reservarProductosServicio($conn, $id_cita, $id_servicio, $productos, $tamano_mascota) → bool

// Obtener productos reservados
getProductosReservadosServicio($conn, $id_cita, $id_servicio) → Array

// Registrar consumo
registrarConsumoServicio($conn, $id_ficha, $id_servicio, $productos_usados) → bool

// Marcar servicio completado
registrarServicioCompletado($conn, $id_ficha, $id_servicio, $completado, $porcentaje) → bool

// Obtener servicios completados
getServiciosCompletados($conn, $id_ficha) → Array

// Aplicar cambios de inventario
aplicarConsumoProductosServicio($conn, $id_ficha, $id_cita, $id_servicio, $usuario_id) → bool

// Calcular cantidad por tamaño
calcularCantidadPorTamano($cantidad_base, $tamano) → int
```

---

## 🔐 CONSIDERACIONES DE SEGURIDAD

1. **Validación de permisos:**
   - Todo endpoint valida que el groomer solo acceda a SUS citas
   - Utiliza `Middleware::requireRole(ROLE_GROOMER)`
   - Verifica `WHERE id_groomer = ?`

2. **CSRF Protection:**
   - Todo formulario POST valida CSRF token
   - `Security::validateCSRFToken($csrf)`

3. **Validación de datos:**
   - Conversión de tipos (intval, floatval)
   - Límites de cantidad (max, min)
   - Validación de existencia en BD

4. **Transacciones:**
   - Los cambios de inventario se aplican atomáticamente
   - Si algo falla, se registra error y no se actualiza inventario

---

## 🧪 PRUEBAS RECOMENDADAS

1. **Reserva de productos:**
   - Reservar productos válidos
   - Intentar reservar más de lo disponible (debe fallar)
   - Editar reserva existente

2. **Grooming parcial:**
   - Completar 1 de 3 servicios
   - Volver y completar otro
   - Verificar que inventario NO se descargó

3. **Grooming completo:**
   - Completar todos los servicios
   - Verificar que inventario se descargó correctamente
   - Verificar registro de movimientos

4. **Edge cases:**
   - Usar menos producto que lo reservado
   - No usar ningún producto
   - Mascota con alergias

---

## 📈 MEJORAS FUTURAS SUGERIDAS

1. **Historial de cambios:**
   - Guardar versión anterior de citas editadas
   - Auditoría de cambios

2. **Notificaciones:**
   - Alert cuando el inventario queda bajo mínimo
   - Notificar cliente cuando cita está lista

3. **Reportes:**
   - Reporte de productos más usados
   - Reporte de groomeers más eficientes
   - Análisis de tiempo por servicio

4. **Automatización:**
   - Crear compras automáticas cuando stock es bajo
   - Programar alertas de vencimiento

5. **Mobile:**
   - Interfaz responsive mejorada
   - App móvil nativa

---

## 🐛 TROUBLESHOOTING

### Problema: Productos no aparecen en Grooming
**Verificar:**
1. ¿Existen reservas en `servicio_producto_reserva`?
2. ¿El groomer reservó productos en Agenda?
3. ¿El producto existe en `producto`?

### Problema: Inventario no se descarga
**Verificar:**
1. ¿Estado_cierre es 'completada'?
2. ¿El servicio está marcado como completado?
3. Revisar `movimiento_inventario` para logs

### Problema: No puedo ver la cita
**Verificar:**
1. ¿El groomer está asignado a la cita?
2. ¿La cita está en estado correcto?
3. Revisar logs de erro

---

## 📞 CONTACTO PARA SOPORTE

Para reportar bugs o solicitar mejoras, contactar al equipo de desarrollo.
