# 🐕 GUÍA DEL NUEVO SISTEMA DE INVENTARIO PARA GROOMERS

## 📋 Resumen de Mejoras

Tu interfaz de groomer ha sido completamente mejorada para:
1. **Seleccionar productos antes de atender** → Panel interactivo en Agenda
2. **Ver qué servicios tienes que hacer** → Tabs organizados por servicio
3. **Registrar cuánto producto usaste** → Por cada servicio de forma independiente
4. **Guardado flexible** → Pausar y continuar otro día, o completar todo

---

## 🚀 FLUJO DE TRABAJO NUEVO

### PASO 1: PREPARACIÓN (En tu Agenda)

**Ubicación:** `/petspa/public/empleado/groomer/agenda.php`

```
1. Abre "Mi Agenda"
   ↓
2. Ves todas tus citas en tarjetas
   - Mascota (nombre, especie, raza)
   - Tamaño de mascota (Pequeño/Mediano/Grande)
   - Cliente
   - Hora de la cita
   - Servicios a realizar
   ↓
3. POR CADA SERVICIO:
   └─ Haz clic en "Seleccionar productos"
      ↓
      Aparece lista de productos disponibles
      ↓
      □ Baño medicinal (1)
      □ Corte de uñas (1)
      □ Cepillado (1)
      ↓
      Haz clic en los que vas a usar
      ↓
      Establece cantidad (se ajusta por tamaño)
      ↓
      Haz clic en "Guardar Selección"
      ✓ Guardado
   ↓
4. Una vez seleccionados todos los productos
   └─ Haz clic en "Atender Cita"
```

**¿Qué es esto?** Cuando reservas productos ANTES, el sistema te los va a mostrar durante el grooming. Así no olvidas qué querías usar.

---

### PASO 2: GROOMING (Atendiendo la mascota)

**Ubicación:** `/petspa/public/empleado/groomer/grooming.php?id=<cita>`

#### Parte 1: INFO DE LA MASCOTA

En la parte superior ves:
- Nombre, especie, raza de la mascota
- **Tamaño** (para saber cantidades)
- **Peso** actual
- **Alergias** (en rojo si hay ⚠️)
- Cliente que dueño
- Hora de la cita

#### Parte 2: PROGRESO GENERAL

```
Barra con: 2/3 servicios completados (66%)
```

Esto te ayuda a saber qué tan avanzado estás.

---

#### Parte 3: SERVICIOS (LO MÁS IMPORTANTE)

Ves TABS, uno por cada servicio. Por ejemplo:

```
[● Baño Medicinal] [○ Corte de Uñas] [○ Cepillado]
```

**Cada TAB tiene:**

##### 🏷️ Información del Servicio
- Duración: 45 minutos
- Precio: $45.00

##### 📦 Productos Reservados
Los que seleccionaste en Agenda:

```
┌─────────────────────────────────────┐
│ Champú Medicinal                    │
│ Reservado: 2                        │
│ Cantidad Usada: [__2__]             │ ← Puedes cambiar esto
├─────────────────────────────────────┤
│ Toalla desechable                   │
│ Reservado: 3                        │
│ Cantidad Usada: [__3__]             │
└─────────────────────────────────────┘
```

**¿Qué significa esto?**
- **Reservado**: Lo que decidiste usar
- **Cantidad Usada**: Lo que REALMENTE usaste (puedes reducir si sobró)

Si usaste TODO lo que reservaste, déjalo igual.
Si usaste MENOS (porque fue rápido, mascota pequeña, etc.), reduce el número.

##### ☑️ Checklist del Servicio
Marca qué pasos completaste:

```
☑ Agua tibia aplicada
☑ Champú distribuido
☑ Masaje realizado
☐ Acondicionador aplicado (si no lo hiciste)
☑ Enjuague completo
```

##### ✅ Marcar como Completado
```
[Toggle] Marcar como completado
```

Si terminaste TODO este servicio, activa el toggle.

**Importante:** El sistema permite que ALGUNOS servicios estén incompletos. Si una cita tiene 3 servicios y solo terminas 2, eso está bien (guardas "Parcialmente").

---

### PASO 3: DATOS GENERALES DE LA FICHA

Después de los tabs de servicios, tienes campos para registrar:

```
Temperatura corporal: [37.5]  °C
Peso actual:          [12.3]  kg  (puede cambiar)
Raza/Talla:          [Poodle Mediano]

Estado General de la Mascota:
[La mascota se comportó bien, sin problemas de comportamiento,
 piel saludable, sin irritaciones]

Observaciones:
[Recomendé baño mensual, mascota tiende a nerviosismo]

Notas Internas:
[Para el próximo grooming: revisar pulgas]

Foto Final: [Seleccionar archivo] 📷
```

Llena esto con tus notas importantes.

---

### PASO 4: GUARDAR

**Opción 1: GUARDAR PARCIALMENTE** (Continuar otro día)

Haz clic en: **⏸️ Guardar Parcialmente**

```
✓ Se guarda TODO lo que hayas llenado
✓ Los servicios completados quedan ✓
✓ Los servicios incompletos quedan pendientes (○)
✓ Puedes volver a esta cita después
✓ La cita sigue en tu agenda como "en progreso"
```

**Cuándo usar esto:**
- La mascota se puso nerviosa, necesita descansar
- Se te acabó el tiempo
- La mascota está lastimada y necesita cuidado especial

---

**Opción 2: COMPLETAR CITA** (Finalizar todo)

Haz clic en: **✅ Completar Cita**

```
✓ Se guarda TODO
✓ Se actualiza el inventario (baja stock de lo que usaste)
✓ La cita cambia a estado "completada"
✓ La mascota está lista para recoger
✓ Desaparece de tu agenda
```

**Importante:** Debes tener AL MENOS 1 servicio completado. No puedes finalizar sin hacer nada.

---

## 🎯 CASOS DE USO

### Caso 1: Cita Simple (1 servicio, mascota pequeña)

1. En Agenda: Selecciono "Baño" → Agrego 1 champú, 2 toallas
2. Abro Grooming
3. Tab "Baño":
   - Veo: Champú (Reservado 1, Usado 1), Toallas (Reservado 2, Usado 2)
   - Completo checklist
   - Marca "Completado"
4. Hago click "Completar Cita" ✓

---

### Caso 2: Cita Compleja (3 servicios, mascota grande)

1. En Agenda: 
   - Baño: Champú premium (2), Acondicionador (2)
   - Corte: Máquina clipper, Peine especial (1)
   - Cepillado: Cepillo met (1), Spray desenredante (1)

2. Abro Grooming, veo 3 tabs

3. Tab 1 - "Baño":
   - Uso todo como planeé
   - Marca checklist completo
   - Marca "Completado" ✓

4. Tab 2 - "Corte":
   - Máquina: Usé 1 (como reservé)
   - Peine: Usé 0.5 (cambio a 0 porque casi no lo usé)
   - Completo checklist
   - Marca "Completado" ✓

5. Tab 3 - "Cepillado":
   - La mascota está cansada, solo le doy 10 min
   - Cepillo: Usé 1
   - Spray: No usé (cambio a 0)
   - Completo checklist PARCIAL
   - **NO** marco "Completado"

6. Relleno datos generales

7. Hago click "Guardar Parcialmente"
   - ✓ Baño: Hecho
   - ✓ Corte: Hecho
   - ○ Cepillado: Pendiente
   
   Mañana vuelvo a esta cita y termino el cepillado.

---

## 🔄 ¿CÓMO CONTINUAR UNA CITA INCOMPLETA?

1. Abre "Mi Agenda"
2. Busca la cita (aparecerá con estado "En progreso")
3. Haz clic en "Atender Cita"
4. Verás que los servicios completados tendrán ✓
5. Continúa con los pendientes

---

## 💾 ¿QUÉ SE GUARDA EN EL SISTEMA?

Cuando COMPLETAS una cita:

✅ Los productos que usaste se descuentan del inventario
✅ Se registra EXACTAMENTE cuánto usaste (no lo que reservaste)
✅ Se crea un registro en la ficha de grooming
✅ El cliente puede ver la cita completada

Cuando GUARDAS PARCIALMENTE:

✅ Se guarda todo, pero NO se descuenta inventario todavía
✅ Se marca cuáles servicios están hechos
✅ La cita sigue disponible para continuar
✅ El inventario se descuenta recién cuando completes la cita

---

## 📱 NOTAS IMPORTANTES

1. **Tamaño de mascota:** El sistema ajusta cantidades automáticamente. Una mascota "Grande" puede necesitar el doble de champú que una "Pequeña".

2. **Alergias:** Si la mascota tiene alergias, verás una alerta roja. LEE BIEN antes de aplicar productos.

3. **Checklist:** Es POR SERVICIO, no global. Cada servicio tiene sus pasos.

4. **Fotos:** Si terminas una cita, puedes subir una foto final de cómo quedó.

5. **Continuación:** El sistema recuerda TODO, incluso si cierras la página. Vuelve donde dejaste.

---

## ❓ PREGUNTAS FRECUENTES

**P: ¿Qué pasa si reservo productos pero luego no los uso?**
R: Cuando completes la cita, solo se descuenta lo que realmente usaste. Lo demás vuelve al inventario.

**P: ¿Puedo cambiar los productos después de que los reservé?**
R: Sí, en la Agenda puedes editar la selección antes de "Atender Cita". Durante el grooming, puedes cambiar cantidad usada.

**P: ¿Qué pasa si me faltan productos?**
R: Si en Grooming ves que falta un producto, puedes dejar cantidad usada en 0 para ese item.

**P: ¿Debo llenar TODO antes de guardar?**
R: No. Solo necesitas completar datos básicos. Observaciones y notas son opcionales.

**P: ¿Qué pasa si cierro el navegador durante un grooming?**
R: Haz clic en "Guardar Parcialmente" antes de cerrar. Si cierras sin guardar, pierdes los datos.

---

**¡Listo! Ya puedes usar el nuevo sistema. ¡Que disfrutes!** 🎉
