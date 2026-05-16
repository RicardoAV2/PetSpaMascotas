-- =====================================================
-- INSERTS MASIVOS PARA TODAS LAS ENTIDADES
-- =====================================================

-- =====================================================
-- 1. USUARIOS (20 registros)
-- =====================================================
INSERT INTO usuario (email, password_hash, nombre, apellido, telefono, estado, ultimo_acceso, two_factor_enabled, id_rol) VALUES
('carlos.admin@peluqcanina.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Carlos', 'Mendoza', '70010001', TRUE, '2024-01-15 08:30:00', FALSE, 1),
('laura.admin@peluqcanina.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Laura', 'Fernández', '70010002', TRUE, '2024-01-14 09:15:00', TRUE, 1),
('pedro.recep@peluqcanina.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Pedro', 'Ramírez', '70010003', TRUE, '2024-01-15 10:00:00', FALSE, 2),
('maria.recep@peluqcanina.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'María', 'Torres', '70010004', TRUE, '2024-01-14 11:30:00', FALSE, 2),
('juan.groomer@peluqcanina.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Juan', 'Pérez', '70010005', TRUE, '2024-01-15 07:45:00', FALSE, 3),
('ana.groomer@peluqcanina.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Ana', 'López', '70010006', TRUE, '2024-01-14 08:20:00', TRUE, 3),
('roberto.groomer@peluqcanina.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Roberto', 'García', '70010007', TRUE, '2024-01-15 09:00:00', FALSE, 3),
('sofia.groomer@peluqcanina.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Sofía', 'Martínez', '70010008', TRUE, '2024-01-14 10:15:00', FALSE, 3),
('cliente1@gmail.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Miguel', 'Álvarez', '70010009', TRUE, '2024-01-10 15:30:00', FALSE, 4),
('cliente2@gmail.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Patricia', 'Gómez', '70010010', TRUE, '2024-01-11 16:45:00', FALSE, 4),
('cliente3@gmail.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Fernando', 'Díaz', '70010011', TRUE, '2024-01-12 14:20:00', TRUE, 4),
('cliente4@hotmail.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Carmen', 'Ruiz', '70010012', TRUE, '2024-01-09 12:10:00', FALSE, 4),
('cliente5@yahoo.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'José', 'Sánchez', '70010013', TRUE, '2024-01-13 17:30:00', FALSE, 4),
('cliente6@gmail.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Isabel', 'Castro', '70010014', TRUE, '2024-01-08 11:00:00', FALSE, 4),
('cliente7@gmail.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Ricardo', 'Ortiz', '70010015', TRUE, '2024-01-14 09:45:00', FALSE, 4),
('cliente8@outlook.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Verónica', 'Mendoza', '70010016', TRUE, '2024-01-10 13:20:00', TRUE, 4),
('cliente9@gmail.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Andrés', 'Silva', '70010017', TRUE, '2024-01-11 10:30:00', FALSE, 4),
('cliente10@gmail.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Gabriela', 'Rojas', '70010018', TRUE, '2024-01-12 15:15:00', FALSE, 4),
('cliente11@hotmail.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Javier', 'Navarro', '70010019', TRUE, '2024-01-13 08:40:00', FALSE, 4),
('cliente12@gmail.com', '$2y$10$ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcd', 'Elena', 'Jiménez', '70010020', FALSE, NULL, FALSE, 4);

-- =====================================================
-- 2. CLIENTES (12 registros - los que tienen id_rol=4)
-- =====================================================
INSERT INTO cliente (id_cliente, direccion, ci, canal_notificacion_preferido, horario_preferido, recibe_promociones, fecha_registro) VALUES
(9, 'Calle Los Pinos #123, La Paz', '1234567', 'whatsapp', 'Mañana 9-12', TRUE, '2023-01-10 10:00:00'),
(10, 'Av. América #456, Santa Cruz', '2345678', 'email', 'Tarde 14-17', TRUE, '2023-02-15 11:30:00'),
(11, 'Calle Sucre #789, Cochabamba', '3456789', 'sms', 'Mañana 10-13', FALSE, '2023-03-20 09:15:00'),
(12, 'Av. 6 de Agosto #101, La Paz', '4567890', 'whatsapp', 'Tarde 15-18', TRUE, '2023-04-25 14:45:00'),
(13, 'Calle España #202, Santa Cruz', '5678901', 'email', 'Mañana 8-11', TRUE, '2023-05-30 16:20:00'),
(14, 'Av. Busch #303, Cochabamba', '6789012', 'whatsapp', 'Tarde 14-17', FALSE, '2023-06-05 12:10:00'),
(15, 'Calle Potosí #404, La Paz', '7890123', 'whatsapp', 'Mañana 9-12', TRUE, '2023-07-10 08:30:00'),
(16, 'Av. San Martín #505, Santa Cruz', '8901234', 'email', 'Tarde 16-19', TRUE, '2023-08-15 17:45:00'),
(17, 'Calle Junín #606, Cochabamba', '9012345', 'sms', 'Mañana 10-13', FALSE, '2023-09-20 10:00:00'),
(18, 'Av. Arce #707, La Paz', '0123456', 'whatsapp', 'Tarde 15-18', TRUE, '2023-10-25 13:30:00'),
(19, 'Calle Colón #808, Santa Cruz', '1234568', 'email', 'Mañana 8-11', TRUE, '2023-11-30 11:00:00'),
(20, 'Av. Libertador #909, Cochabamba', '2345679', 'whatsapp', 'Tarde 14-17', FALSE, '2023-12-05 09:45:00');

-- =====================================================
-- 3. GROOMERS (4 registros - los que tienen id_rol=3)
-- =====================================================
INSERT INTO groomer (id_groomer, especialidad, capacidad_simultanea, estado_activo, calificacion_promedio, horario_trabajo) VALUES
(5, 'Corte fino y estilismo avanzado', 2, TRUE, 4.8, '{"lunes":{"inicio":"09:00","fin":"18:00"},"martes":{"inicio":"09:00","fin":"18:00"},"miercoles":{"inicio":"09:00","fin":"18:00"},"jueves":{"inicio":"09:00","fin":"18:00"},"viernes":{"inicio":"09:00","fin":"18:00"}}'),
(6, 'Baño medicinal y cuidado de piel', 1, TRUE, 4.9, '{"lunes":{"inicio":"08:00","fin":"17:00"},"martes":{"inicio":"08:00","fin":"17:00"},"miercoles":{"inicio":"08:00","fin":"17:00"},"jueves":{"inicio":"08:00","fin":"17:00"},"viernes":{"inicio":"08:00","fin":"17:00"}}'),
(7, 'Corte de razas pequeñas', 3, TRUE, 4.7, '{"lunes":{"inicio":"10:00","fin":"19:00"},"martes":{"inicio":"10:00","fin":"19:00"},"miercoles":{"inicio":"10:00","fin":"19:00"},"jueves":{"inicio":"10:00","fin":"19:00"},"viernes":{"inicio":"10:00","fin":"19:00"},"sabado":{"inicio":"09:00","fin":"14:00"}}'),
(8, 'Especialista en razas gigantes', 1, TRUE, 4.6, '{"martes":{"inicio":"07:00","fin":"16:00"},"miercoles":{"inicio":"07:00","fin":"16:00"},"jueves":{"inicio":"07:00","fin":"16:00"},"viernes":{"inicio":"07:00","fin":"16:00"},"sabado":{"inicio":"08:00","fin":"13:00"}}');

-- =====================================================
-- 4. RECEPCIONISTAS (2 registros - los que tienen id_rol=2)
-- =====================================================
INSERT INTO recepcionista (id_recepcionista, turno, idiomas, experiencia) VALUES
(3, 'Mañana 8-14', 'Español, Inglés básico', 5),
(4, 'Tarde 14-20', 'Español, Portugués', 3);

-- =====================================================
-- 5. ADMINISTRADORES (2 registros - los que tienen id_rol=1)
-- =====================================================
INSERT INTO administrador (id_administrador, nivel_acceso, area_responsabilidad, puede_contratar) VALUES
(1, 5, 'Gerencia General', TRUE),
(2, 4, 'Operaciones y Logística', TRUE);

-- =====================================================
-- 6. DISPONIBILIDAD (16 registros)
-- =====================================================
INSERT INTO disponibilidad (id_groomer, dia_semana, hora_inicio, hora_fin, intervalo_descanso) VALUES
(5, 1, '09:00:00', '18:00:00', '{"inicio":"13:00","fin":"14:00"}'),
(5, 2, '09:00:00', '18:00:00', '{"inicio":"13:00","fin":"14:00"}'),
(5, 3, '09:00:00', '18:00:00', '{"inicio":"13:00","fin":"14:00"}'),
(5, 4, '09:00:00', '18:00:00', '{"inicio":"13:00","fin":"14:00"}'),
(5, 5, '09:00:00', '18:00:00', '{"inicio":"13:00","fin":"14:00"}'),
(6, 1, '08:00:00', '17:00:00', '{"inicio":"12:00","fin":"13:00"}'),
(6, 2, '08:00:00', '17:00:00', '{"inicio":"12:00","fin":"13:00"}'),
(6, 3, '08:00:00', '17:00:00', '{"inicio":"12:00","fin":"13:00"}'),
(6, 4, '08:00:00', '17:00:00', '{"inicio":"12:00","fin":"13:00"}'),
(6, 5, '08:00:00', '17:00:00', '{"inicio":"12:00","fin":"13:00"}'),
(7, 1, '10:00:00', '19:00:00', '{"inicio":"14:00","fin":"15:00"}'),
(7, 2, '10:00:00', '19:00:00', '{"inicio":"14:00","fin":"15:00"}'),
(7, 3, '10:00:00', '19:00:00', '{"inicio":"14:00","fin":"15:00"}'),
(7, 4, '10:00:00', '19:00:00', '{"inicio":"14:00","fin":"15:00"}'),
(7, 5, '10:00:00', '19:00:00', '{"inicio":"14:00","fin":"15:00"}'),
(7, 6, '09:00:00', '14:00:00', NULL);

-- =====================================================
-- 7. BLOQUEOS DE AGENDA (8 registros)
-- =====================================================
INSERT INTO bloqueo_agenda (id_groomer, fecha_inicio, fecha_fin, motivo, tipo_bloqueo) VALUES
(NULL, '2024-01-01 00:00:00', '2024-01-01 23:59:59', 'Año Nuevo', 'feriado'),
(NULL, '2024-02-12 00:00:00', '2024-02-13 23:59:59', 'Carnaval', 'feriado'),
(5, '2024-01-20 09:00:00', '2024-01-27 18:00:00', 'Vacaciones familiares', 'vacaciones'),
(6, '2024-02-05 08:00:00', '2024-02-05 17:00:00', 'Capacitación externa', 'ausencia'),
(7, '2024-01-15 10:00:00', '2024-01-15 14:00:00', 'Cita médica', 'ausencia'),
(8, '2024-01-25 07:00:00', '2024-01-25 16:00:00', 'Mantenimiento de equipos', 'mantenimiento'),
(NULL, '2024-04-06 00:00:00', '2024-04-07 23:59:59', 'Semana Santa', 'feriado'),
(7, '2024-02-10 09:00:00', '2024-02-17 14:00:00', 'Vacaciones', 'vacaciones');

-- =====================================================
-- 8. MASCOTAS (20 registros)
-- =====================================================
INSERT INTO mascota (nombre, especie, raza, peso, fecha_nacimiento, alergias, comportamiento, vacunas, temperamento, restricciones_medicas, id_cliente_principal) VALUES
('Firulais', 'Perro', 'Labrador Retriever', 28.50, '2020-05-15', 'Ninguna', 'Juguetón, sociable', 'Rabia, Parvovirus, Hepatitis', 'Tranquilo', NULL, 9),
('Luna', 'Perro', 'Golden Retriever', 25.00, '2019-08-20', 'Pollo', 'Activo, le encanta correr', 'Rabia, Parvovirus', 'Juguetón', 'Problemas de cadera', 10),
('Max', 'Perro', 'Pastor Alemán', 35.00, '2018-03-10', 'Ninguna', 'Protector, obediente', 'Rabia, Parvovirus, Leptospira', 'Tranquilo', NULL, 11),
('Bella', 'Perro', 'Poodle', 8.50, '2021-11-25', 'Ácaros', 'Cariñosa, tímida', 'Rabia, Parvovirus', 'Tímido', 'Alergia a ácaros', 12),
('Rocky', 'Perro', 'Bulldog Francés', 12.00, '2020-07-14', 'Trigo', 'Juguetón, ronca mucho', 'Rabia, Parvovirus', 'Tranquilo', 'Problemas respiratorios', 13),
('Coco', 'Perro', 'Chihuahua', 3.50, '2022-02-28', 'Ninguna', 'Nervioso, tiembla fácil', 'Rabia', 'Agresivo', NULL, 14),
('Lola', 'Perro', 'Beagle', 14.00, '2019-09-05', 'Ninguna', 'Curiosa, come de todo', 'Rabia, Parvovirus', 'Juguetón', NULL, 15),
('Toby', 'Perro', 'Yorkshire', 4.00, '2021-06-18', 'Ninguna', 'Enojón, territorial', 'Rabia', 'Agresivo', NULL, 16),
('Nala', 'Perro', 'Husky Siberiano', 22.00, '2020-12-10', 'Ninguna', 'Enojón, territorial', 'Rabia, Parvovirus', 'Enojón', NULL, 17),
('Simba', 'Gato', 'Persa', 5.50, '2021-04-22', 'Lácteos', 'Independiente, maulla mucho', 'Rabia, Leucemia', 'Tranquilo', NULL, 18),
('Milo', 'Perro', 'Dálmata', 27.00, '2019-11-30', 'Ninguna', 'Muy activo, corre mucho', 'Rabia, Parvovirus', 'Juguetón', NULL, 19),
('Kiara', 'Perro', 'Shih Tzu', 6.00, '2022-08-15', 'Ninguna', 'Cariñosa, le gusta ladrar', 'Rabia', 'Tranquilo', NULL, 20),
('Zeus', 'Perro', 'Gran Danés', 65.00, '2018-01-05', 'Ninguna', 'Gigante bonachón', 'Rabia, Parvovirus', 'Tranquilo', 'Displasia de cadera', 9),
('Maya', 'Perro', 'Schnauzer', 9.00, '2020-10-12', 'Ninguna', 'Inteligente, aprende rápido', 'Rabia, Parvovirus', 'Tranquilo', NULL, 10),
('Bruno', 'Perro', 'Pug', 10.00, '2021-09-03', 'Ninguna', 'Cómodo, duerme mucho', 'Rabia', 'Tranquilo', 'Problemas oculares', 11),
('Luna', 'Gato', 'Siamés', 4.50, '2022-01-20', 'Ninguna', 'Habladora, cariñosa', 'Rabia, Leucemia', 'Juguetón', NULL, 12),
('Thor', 'Perro', 'Rottweiler', 45.00, '2019-07-19', 'Ninguna', 'Protector, obediente', 'Rabia, Parvovirus', 'Tranquilo', NULL, 13),
('Molly', 'Perro', 'Border Collie', 18.00, '2020-04-08', 'Ninguna', 'Muy inteligente, activa', 'Rabia, Parvovirus', 'Juguetón', NULL, 14),
('Charlie', 'Perro', 'Cocker Spaniel', 14.00, '2021-12-01', 'Ninguna', 'Tranquilo, buen compañero', 'Rabia', 'Tranquilo', 'Problemas de oídos', 15),
('Daisy', 'Perro', 'West Highland', 8.00, '2022-03-25', 'Ninguna', 'Valiente, curiosa', 'Rabia', 'Juguetón', NULL, 16);

-- =====================================================
-- 9. MASCOTA_DUENO (múltiples dueños - 17 registros)
-- =====================================================
INSERT INTO mascota_dueno (id_mascota, id_cliente, es_principal) VALUES
(1, 9, TRUE),
(1, 10, FALSE),
(2, 10, TRUE),
(2, 11, FALSE),
(3, 11, TRUE),
(4, 12, TRUE),
(4, 13, FALSE),
(5, 13, TRUE),
(6, 14, TRUE),
(7, 15, TRUE),
(7, 16, FALSE),
(8, 16, TRUE),
(9, 17, TRUE),
(10, 18, TRUE),
(11, 19, TRUE),
(11, 20, FALSE),
(12, 20, TRUE);

-- =====================================================
-- 10. SERVICIOS (15 registros)
-- =====================================================
INSERT INTO servicio (
    nombre, descripcion, duracion_base_minutos, precio_base,
    permite_doble_booking, factor_tamaño_raza, consumo_insumos,
    requiere_bloqueo_consecutivo, estado_activo
) VALUES
('Baño Completo', 'Baño con shampoo especial, secado y cepillado', 45, 25000, FALSE, '{"pequeno":1.0,"mediano":1.15,"grande":1.30}', '{"shampoo":30,"acondicionador":30,"toalla":2}', FALSE, TRUE),
('Corte Higienico', 'Corte de pelo en áreas específicas', 30, 18000, TRUE, '{"pequeno":1.0,"mediano":1.0,"grande":1.0}', '{"tijeras":1,"peine":1}', FALSE, TRUE),
('Corte Completo', 'Corte de pelo estilizado según raza', 60, 35000, FALSE, '{"pequeno":1.1,"mediano":1.2,"grande":1.4}', '{"tijeras":2,"cuchilla":1,"peine":2}', TRUE, TRUE),
('Limpieza de Oidos', 'Limpieza profunda de oídos', 15, 8000, TRUE, '{"pequeno":1.0,"mediano":1.0,"grande":1.0}', '{"limpiador_oidos":5,"algodon":10}', FALSE, TRUE),
('Corte de Unas', 'Corte y limado de uñas', 15, 10000, TRUE, '{"pequeno":1.0,"mediano":1.0,"grande":1.2}', '{"cortauñas":1,"lima":1}', FALSE, TRUE),
('Expresion de Glandulas', 'Vaciado de glándulas anales', 10, 12000, TRUE, '{"pequeno":1.0,"mediano":1.0,"grande":1.0}', '{"guantes":1,"gel":5}', FALSE, TRUE),
('Baño Medicinal', 'Baño con shampoo medicado para piel sensible', 50, 32000, FALSE, '{"pequeno":1.0,"mediano":1.15,"grande":1.30}', '{"shampoo_medicinal":40,"crema":10}', FALSE, TRUE),
('Spa Completo', 'Baño, mascarilla, hidratación y perfumado', 90, 55000, FALSE, '{"pequeno":1.0,"mediano":1.2,"grande":1.5}', '{"shampoo":30,"mascarilla":20,"perfume":5}', TRUE, TRUE),
('Cepillado y Deslanado', 'Eliminación de pelo muerto y nudos', 40, 20000, TRUE, '{"pequeno":1.0,"mediano":1.15,"grande":1.30}', '{"cepillo":1,"deslanador":1}', FALSE, TRUE),
('Blanqueamiento Dental', 'Limpieza dental canina', 25, 15000, TRUE, '{"pequeno":1.0,"mediano":1.0,"grande":1.1}', '{"cepillo_dental":1,"pasta":5}', FALSE, TRUE),
('Corte Raza Especifica', 'Corte según estándar de raza (Poodle, Schnauzer)', 75, 45000, FALSE, '{"pequeno":1.0,"mediano":1.1,"grande":1.3}', '{"tijeras":3,"cuchilla":2,"peine":3}', TRUE, TRUE),
('Mascarilla Hidratante', 'Mascarilla profunda para pelo y piel', 30, 18000, TRUE, '{"pequeno":1.0,"mediano":1.0,"grande":1.0}', '{"mascarilla":30}', FALSE, TRUE),
('Desparasitacion', 'Aplicación de antiparasitario externo', 15, 12000, TRUE, '{"pequeno":1.0,"mediano":1.0,"grande":1.2}', '{"antiparasitario":1}', FALSE, TRUE),
('Corte de Verano', 'Corte rasurado para épocas calurosas', 45, 28000, FALSE, '{"pequeno":1.0,"mediano":1.15,"grande":1.30}', '{"cuchilla":1,"tijeras":1}', FALSE, TRUE),
('Peluqueria Premium', 'Servicio completo con aromaterapia', 120, 75000, FALSE, '{"pequeno":1.2,"mediano":1.4,"grande":1.7}', '{"shampoo_premium":40,"aceite_esencial":5,"toalla":3}', TRUE, TRUE);

-- =====================================================
-- 11. CHECKLIST_ITEM (20 registros)
-- =====================================================
INSERT INTO checklist_item (nombre, descripcion, requiere_observacion) VALUES
('Baño completo', 'Aplicación de shampoo y acondicionador', FALSE),
('Secado', 'Secado completo con secador profesional', FALSE),
('Cepillado', 'Eliminación de nudos y pelo muerto', FALSE),
('Corte de uñas', 'Corte y limado de uñas', TRUE),
('Limpieza de oídos', 'Limpieza con solución especial', TRUE),
('Corte de pelo', 'Corte según solicitud del cliente', FALSE),
('Corte sanitario', 'Corte en áreas genital y anal', FALSE),
('Glándulas anales', 'Vaciado de glándulas anales', TRUE),
('Perfume', 'Aplicación de perfume canino', FALSE),
('Mascarilla', 'Aplicación de mascarilla hidratante', FALSE),
('Cepillado dental', 'Limpieza de dientes con pasta especial', TRUE),
('Deslanado', 'Eliminación de pelo muerto con herramienta especial', FALSE),
('Aromaterapia', 'Aplicación de aceites esenciales', FALSE),
('Revisión de piel', 'Inspección de piel en busca de irritaciones', TRUE),
('Revisión de parásitos', 'Búsqueda de pulgas o garrapatas', TRUE),
('Corte de raza', 'Corte según estándar de raza', FALSE),
('Hidratación de almohadillas', 'Aplicación de crema hidratante en patas', FALSE),
('Masaje relajante', 'Masaje para relajar al animal', FALSE),
('Fotografía final', 'Tomar foto del resultado final', FALSE),
('Recomendaciones al dueño', 'Indicaciones post-servicio', FALSE);

-- =====================================================
-- 12. SERVICIO_CHECKLIST_TEMPLATE (asignación de items a servicios)
-- =====================================================
INSERT INTO servicio_checklist_template (id_servicio, id_item, orden) VALUES
(1, 1, 1), (1, 2, 2), (1, 3, 3), (1, 4, 4), (1, 5, 5), (1, 9, 6),
(2, 6, 1), (2, 7, 2),
(3, 1, 1), (3, 2, 2), (3, 6, 3), (3, 3, 4), (3, 16, 5),
(4, 5, 1), (4, 15, 2),
(5, 4, 1),
(6, 8, 1),
(7, 1, 1), (7, 14, 2), (7, 2, 3),
(8, 1, 1), (8, 10, 2), (8, 3, 3), (8, 17, 4), (8, 18, 5), (8, 9, 6), (8, 19, 7),
(9, 3, 1), (9, 12, 2),
(10, 11, 1),
(11, 6, 1), (11, 16, 2), (11, 3, 3),
(12, 10, 1),
(13, 15, 1),
(14, 6, 1), (14, 3, 2),
(15, 1, 1), (15, 13, 2), (15, 6, 3), (15, 17, 4), (15, 18, 5);

-- =====================================================
-- 13. CATEGORIA_PRODUCTO (15 registros)
-- =====================================================
INSERT INTO categoria_producto (nombre, descripcion, id_padre) VALUES
('Alimentos', 'Comida para perros y gatos', NULL),
('Shampoos', 'Productos de baño y limpieza', NULL),
('Accesorios', 'Collares, correas, juguetes', NULL),
('Medicamentos', 'Fármacos y suplementos', NULL),
('Snacks', 'Premios y golosinas', NULL),
('Alimento Seco', 'Croquetas', 1),
('Alimento Húmedo', 'Sobres y latas', 1),
('Shampoo Medicinal', 'Para piel sensible', 2),
('Shampoo Premium', 'Alta calidad', 2),
('Juguetes', 'Pelotas, mordedores, cuerdas', 3),
('Collares y Correas', 'Accesorios de paseo', 3),
('Vitaminas', 'Suplementos nutricionales', 4),
('Antiparasitarios', 'Pipetas, collares, pastillas', 4),
('Galletas', 'Snacks crujientes', 5),
('Huesos', 'Huesos naturales y artificiales', 5);

-- =====================================================
-- 14. PRODUCTOS (20 registros)
-- =====================================================
INSERT INTO producto (nombre, descripcion, precio_base, stock_actual, stock_minimo, estado_activo, id_categoria) VALUES
('Croquetas Adulto Pollo', 'Alimento balanceado para perros adultos', 85000, 150, 20, TRUE, 6),
('Shampoo Calmante', 'Shampoo para piel sensible con avena', 45000, 45, 10, TRUE, 8),
('Pelota de Goma', 'Juguete resistente para perros medianos', 15000, 200, 30, TRUE, 10),
('Collar Antipulgas', 'Collar preventivo por 6 meses', 65000, 30, 10, TRUE, 13),
('Galletas de Pollo', 'Snack crujiente sabor pollo', 12000, 500, 50, TRUE, 14),
('Shampoo Hidratante', 'Shampoo con aceite de coco', 38000, 60, 15, TRUE, 9),
('Cuerda para Jugar', 'Juguete de cuerda para limpiar dientes', 18000, 120, 25, TRUE, 10),
('Correa Extensible', 'Correa de 5 metros extensible', 55000, 40, 10, TRUE, 11),
('Suplemento Articular', 'Vitaminas para articulaciones', 95000, 25, 8, TRUE, 12),
('Croquetas Cachorro', 'Alimento para cachorros', 95000, 100, 20, TRUE, 6),
('Shampoo Antipulgas', 'Shampoo que elimina pulgas', 52000, 35, 10, TRUE, 8),
('Hueso de Cuero', 'Hueso natural prensado', 8000, 300, 40, TRUE, 15),
('Cepillo Deslanador', 'Herramienta para eliminar pelo muerto', 28000, 50, 10, TRUE, 3),
('Pipeta Antiparasitaria', 'Pipeta mensual para perros', 25000, 120, 20, TRUE, 13),
('Snack Dental', 'Galletas para limpieza dental', 15000, 250, 30, TRUE, 14),
('Alimento Húmedo Pollo', 'Sobres de alimento húmedo', 12000, 180, 30, TRUE, 7),
('Shampoo Blancanieves', 'Shampoo para perros blancos', 49000, 40, 10, TRUE, 9),
('Cama Ortopeética', 'Cama para perros con problemas articulares', 125000, 15, 5, TRUE, 3),
('Aceite de Salmón', 'Suplemento para piel y pelo', 68000, 30, 8, TRUE, 12),
('Juguete Dental', 'Juguete que limpia los dientes', 22000, 100, 20, TRUE, 10);

-- =====================================================
-- 15. VARIANTE_PRODUCTO (20 registros)
-- =====================================================
INSERT INTO variante_producto (id_producto, atributo, valor, precio_extra, stock_adicional, sku_variante) VALUES
(1, 'tamaño', '3kg', 0, 80, 'CRO-ADU-POL-3KG'),
(1, 'tamaño', '7kg', 45000, 50, 'CRO-ADU-POL-7KG'),
(1, 'tamaño', '15kg', 95000, 20, 'CRO-ADU-POL-15KG'),
(2, 'fragancia', 'Lavanda', 0, 25, 'SHA-CAL-LAV'),
(2, 'fragancia', 'Manzanilla', 5000, 15, 'SHA-CAL-MAN'),
(2, 'fragancia', 'Avena', 4000, 5, 'SHA-CAL-AVE'),
(3, 'talla', 'Pequeño', 0, 100, 'PEL-PEQ'),
(3, 'talla', 'Mediano', 3000, 70, 'PEL-MED'),
(3, 'talla', 'Grande', 5000, 30, 'PEL-GRA'),
(4, 'talla', 'Pequeño (hasta 10kg)', 0, 15, 'COL-PUL-PEQ'),
(4, 'talla', 'Grande (hasta 25kg)', 10000, 15, 'COL-PUL-GRA'),
(5, 'sabor', 'Pollo', 0, 300, 'GAL-POL'),
(5, 'sabor', 'Carne', 1000, 200, 'GAL-CAR'),
(6, 'fragancia', 'Coco', 0, 35, 'SHA-HID-COC'),
(6, 'fragancia', 'Almendras', 3000, 25, 'SHA-HID-ALM'),
(10, 'tamaño', '2kg', 0, 50, 'CRO-CAC-2KG'),
(10, 'tamaño', '8kg', 50000, 30, 'CRO-CAC-8KG'),
(10, 'tamaño', '20kg', 120000, 20, 'CRO-CAC-20KG'),
(11, 'fragancia', 'Neem', 0, 20, 'SHA-ANT-NEE'),
(11, 'fragancia', 'Eucalipto', 3000, 15, 'SHA-ANT-EUC');

-- =====================================================
-- 16. INVENTARIO (20 registros)
-- =====================================================
INSERT INTO inventario (id_producto, id_variante, cantidad_fisica, cantidad_reservada, ubicacion, fecha_vencimiento, estado_lote) VALUES
(1, 1, 80, 10, 'Estante A1', NULL, 'activo'),
(1, 2, 50, 15, 'Estante A2', NULL, 'activo'),
(1, 3, 20, 5, 'Estante A3', NULL, 'activo'),
(2, 4, 25, 8, 'Estante B1', '2025-06-30', 'activo'),
(2, 5, 15, 3, 'Estante B2', '2025-08-15', 'activo'),
(2, 6, 5, 2, 'Estante B3', '2025-07-20', 'activo'),
(3, 7, 100, 20, 'Cajón C1', NULL, 'activo'),
(3, 8, 70, 15, 'Cajón C2', NULL, 'activo'),
(3, 9, 30, 10, 'Cajón C3', NULL, 'activo'),
(4, 10, 15, 5, 'Estante D1', '2025-12-31', 'activo'),
(4, 11, 15, 3, 'Estante D2', '2025-12-31', 'activo'),
(5, 12, 300, 50, 'Estante E1', '2025-10-15', 'activo'),
(5, 13, 200, 40, 'Estante E2', '2025-10-20', 'activo'),
(6, 14, 35, 10, 'Estante B4', '2025-09-30', 'activo'),
(6, 15, 25, 8, 'Estante B5', '2025-09-30', 'activo'),
(10, 16, 50, 15, 'Estante A4', NULL, 'activo'),
(10, 17, 30, 10, 'Estante A5', NULL, 'activo'),
(10, 18, 20, 5, 'Estante A6', NULL, 'activo'),
(11, 19, 20, 6, 'Estante B6', '2025-11-30', 'activo'),
(11, 20, 15, 4, 'Estante B7', '2025-11-30', 'activo');

-- =====================================================
-- 17. MOVIMIENTO_INVENTARIO (20 registros históricos)
-- =====================================================
INSERT INTO movimiento_inventario (id_inventario, tipo_movimiento, cantidad, cantidad_fisica_antes, cantidad_fisica_despues, cantidad_reservada_antes, cantidad_reservada_despues, referencia_tipo, referencia_id, motivo, id_usuario_registra) VALUES
(1, 'entrada_compra', 100, 0, 100, 0, 0, NULL, NULL, 'Compra inicial proveedor PetFood SRL', 1),
(2, 'entrada_compra', 60, 0, 60, 0, 0, NULL, NULL, 'Compra inicial proveedor PetFood SRL', 1),
(3, 'entrada_compra', 30, 0, 30, 0, 0, NULL, NULL, 'Compra inicial proveedor PetFood SRL', 1),
(4, 'entrada_compra', 30, 0, 30, 0, 0, NULL, NULL, 'Compra inicial proveedor BeautyPet', 2),
(5, 'entrada_compra', 20, 0, 20, 0, 0, NULL, NULL, 'Compra inicial proveedor BeautyPet', 2),
(7, 'salida_venta', 5, 100, 95, 0, 0, 'venta', 1, 'Venta a cliente Ana López', 3),
(8, 'reserva', 3, 70, 70, 0, 3, 'cita', 1, 'Reserva para cita #1', 3),
(4, 'salida_consumo', 2, 30, 28, 0, 0, 'cita', 2, 'Consumo en ficha grooming #1', 5),
(12, 'salida_venta', 10, 300, 290, 0, 0, 'venta', 2, 'Venta a cliente Miguel Alvarez', 3),
(13, 'salida_venta', 15, 200, 185, 0, 0, 'venta', 2, 'Venta a cliente Miguel Alvarez', 3),
(1, 'salida_venta', 3, 95, 92, 0, 0, 'venta', 3, 'Venta mostrador', 4),
(16, 'entrada_compra', 60, 0, 60, 0, 0, NULL, NULL, 'Reposición stock', 2),
(10, 'reserva', 2, 15, 15, 0, 2, 'cita', 5, 'Reserva para cita #5', 5),
(14, 'salida_consumo', 1, 35, 34, 0, 0, 'cita', 3, 'Consumo en ficha grooming #3', 6),
(2, 'liberacion_reserva', 5, 50, 50, 15, 10, 'cancela', 2, 'Cancelación de reserva por no show', 3),
(6, 'entrada_ajuste', 2, 5, 7, 2, 2, 'ajuste', 1, 'Ajuste por conteo físico', 1),
(9, 'salida_perdida', 1, 30, 29, 10, 10, NULL, NULL, 'Producto vencido', 2),
(18, 'salida_venta', 2, 20, 18, 5, 5, 'venta', 6, 'Venta a cliente Elena Jimenez', 4),
(8, 'liberacion_reserva', 2, 70, 70, 15, 13, 'cita', 4, 'Liberación por cita completada', 5),
(5, 'salida_consumo', 1, 15, 14, 3, 3, 'cita', 4, 'Consumo en ficha grooming #4', 7);

-- =====================================================
-- 18. ALERTA_INVENTARIO (12 registros)
-- =====================================================
INSERT INTO alerta_inventario (id_inventario, tipo_alerta, mensaje, fecha_alerta, fecha_atencion, atendido_por, estado_alerta) VALUES
(6, 'bajo_stock', 'Stock bajo de Shampoo Calmante Avena, solo 7 unidades', '2024-01-10 10:30:00', '2024-01-10 14:00:00', 1, 'atendida'),
(11, 'stock_critico', '¡STOCK CRÍTICO! Collar antipulgas Grande solo 15 unidades', '2024-01-12 09:15:00', '2024-01-12 11:00:00', 2, 'atendida'),
(19, 'vencimiento_proximo', 'Shampoo Antipulgas Neem vence en 30 días', '2024-01-15 08:00:00', NULL, NULL, 'pendiente'),
(20, 'vencimiento_proximo', 'Shampoo Antipulgas Eucalipto vence en 30 días', '2024-01-15 08:00:00', NULL, NULL, 'pendiente'),
(4, 'bajo_stock', 'Stock bajo de Shampoo Calmante Lavanda', '2024-01-05 14:00:00', '2024-01-05 16:30:00', 1, 'atendida'),
(9, 'bajo_stock', 'Stock bajo de Pelota Grande', '2024-01-08 11:00:00', '2024-01-08 15:00:00', 3, 'atendida'),
(15, 'bajo_stock', 'Stock bajo de Shampoo Hidratante Almendras', '2024-01-13 09:00:00', NULL, NULL, 'pendiente'),
(18, 'vencimiento_proximo', 'Croquetas Cachorro 20kg vence en 45 días', '2024-01-14 10:00:00', NULL, NULL, 'pendiente'),
(3, 'bajo_stock', 'Stock bajo de Croquetas Adulto 15kg, solo 20 unidades', '2024-01-11 10:00:00', '2024-01-11 12:00:00', 1, 'atendida'),
(17, 'stock_critico', 'Croquetas Cachorro 8kg stock crítico, solo 30 unidades', '2024-01-16 08:30:00', NULL, NULL, 'pendiente'),
(14, 'sin_movimiento', 'Sin movimiento en últimos 30 días - Shampoo Hidratante Coco', '2024-01-10 09:00:00', '2024-01-10 10:00:00', 2, 'atendida'),
(10, 'vencido', 'Collar antipulgas Pequeño Lote vencido', '2024-01-01 08:00:00', '2024-01-01 08:30:00', 1, 'atendida');

-- =====================================================
-- 19. REORDEN_INVENTARIO (10 registros)
-- =====================================================
INSERT INTO reorden_inventario (id_producto, cantidad_sugerida, fecha_sugerencia, fecha_compra_realizada, comprado_por, estado) VALUES
(1, 50, '2024-01-10 15:00:00', '2024-01-12 10:00:00', 1, 'comprado'),
(2, 30, '2024-01-12 09:00:00', '2024-01-15 11:00:00', 1, 'comprado'),
(4, 20, '2024-01-05 10:00:00', '2024-01-08 09:00:00', 2, 'comprado'),
(6, 15, '2024-01-13 08:00:00', NULL, NULL, 'pendiente'),
(10, 40, '2024-01-11 11:00:00', '2024-01-13 14:00:00', 1, 'comprado'),
(11, 25, '2024-01-16 09:00:00', NULL, NULL, 'pendiente'),
(13, 20, '2024-01-08 10:00:00', '2024-01-10 09:30:00', 2, 'comprado'),
(16, 30, '2024-01-14 10:00:00', NULL, NULL, 'pendiente'),
(19, 15, '2024-01-15 11:00:00', NULL, NULL, 'pendiente'),
(20, 15, '2024-01-15 11:00:00', NULL, NULL, 'pendiente');

-- =====================================================
-- 20. CITAS (20 registros)
-- =====================================================
INSERT INTO cita (fecha_inicio, fecha_fin, duracion_real, estado, creado_por, fecha_creacion, fecha_reprogramacion, usuario_reprogramo, motivo_cancelacion, id_mascota, id_groomer, id_servicio) VALUES
('2024-01-10 09:00:00', '2024-01-10 09:45:00', 50, 'completada', 3, '2024-01-05 10:00:00', NULL, NULL, NULL, 1, 5, 1),
('2024-01-10 14:00:00', '2024-01-10 15:00:00', 65, 'completada', 3, '2024-01-06 11:00:00', NULL, NULL, NULL, 2, 6, 3),
('2024-01-11 10:00:00', '2024-01-11 10:30:00', 30, 'completada', 9, '2024-01-07 09:00:00', NULL, NULL, NULL, 3, 5, 2),
('2024-01-11 15:00:00', '2024-01-11 16:30:00', 95, 'completada', 4, '2024-01-08 14:00:00', NULL, NULL, NULL, 4, 7, 8),
('2024-01-12 11:00:00', '2024-01-12 11:15:00', 12, 'cancelada', 10, '2024-01-09 08:00:00', '2024-01-10 09:00:00', 3, 'Cliente canceló por emergencia', 5, 8, 6),
('2024-01-12 16:00:00', '2024-01-12 16:45:00', 48, 'completada', 3, '2024-01-10 10:00:00', NULL, NULL, NULL, 6, 5, 1),
('2024-01-13 09:00:00', '2024-01-13 10:00:00', 62, 'completada', 4, '2024-01-11 11:00:00', NULL, NULL, NULL, 7, 6, 3),
('2024-01-13 14:00:00', '2024-01-13 14:30:00', 28, 'confirmada', 11, '2024-01-11 15:00:00', NULL, NULL, NULL, 8, 7, 2),
('2024-01-14 10:00:00', '2024-01-14 10:45:00', NULL, 'agendada', 12, '2024-01-12 09:00:00', NULL, NULL, NULL, 9, 5, 1),
('2024-01-14 15:30:00', '2024-01-14 16:15:00', NULL, 'agendada', 3, '2024-01-12 16:00:00', NULL, NULL, NULL, 10, 8, 1),
('2024-01-15 11:00:00', '2024-01-15 12:00:00', NULL, 'confirmada', 13, '2024-01-13 10:00:00', NULL, NULL, NULL, 11, 6, 3),
('2024-01-15 16:00:00', '2024-01-15 17:30:00', NULL, 'agendada', 4, '2024-01-13 14:00:00', NULL, NULL, NULL, 12, 7, 8),
('2024-01-16 09:30:00', '2024-01-16 10:15:00', NULL, 'agendada', 14, '2024-01-14 08:00:00', NULL, NULL, NULL, 13, 5, 1),
('2024-01-16 14:00:00', '2024-01-16 14:30:00', NULL, 'confirmada', 15, '2024-01-14 13:00:00', NULL, NULL, NULL, 14, 8, 5),
('2024-01-17 10:00:00', '2024-01-17 10:45:00', NULL, 'agendada', 3, '2024-01-15 09:00:00', NULL, NULL, NULL, 15, 6, 1),
('2024-01-17 15:00:00', '2024-01-17 16:00:00', NULL, 'agendada', 16, '2024-01-15 14:00:00', NULL, NULL, NULL, 1, 7, 3),
('2024-01-18 11:00:00', '2024-01-18 11:15:00', NULL, 'agendada', 9, '2024-01-16 10:00:00', NULL, NULL, NULL, 2, 5, 6),
('2024-01-18 16:30:00', '2024-01-18 17:15:00', NULL, 'agendada', 17, '2024-01-16 15:00:00', NULL, NULL, NULL, 3, 8, 1),
('2024-01-19 09:00:00', '2024-01-19 10:30:00', NULL, 'agendada', 3, '2024-01-17 08:00:00', NULL, NULL, NULL, 16, 6, 8),
('2024-01-19 14:00:00', '2024-01-19 14:30:00', NULL, 'agendada', 4, '2024-01-17 13:00:00', NULL, NULL, NULL, 17, 7, 2);

-- =====================================================
-- 21. FICHA_GROOMING (para las citas completadas)
-- =====================================================
INSERT INTO ficha_grooming (hora_inicio, hora_fin_real, temperatura_animal, peso_kg, raza_talla, observaciones, notas_internas, estado_mascota, consumido_inventario, fecha_cierre, id_cita) VALUES
('2024-01-10 09:00:00', '2024-01-10 09:50:00', 38.5, 28.00, 'Labrador Grande', 'Mascota muy tranquila, excelente comportamiento', 'Cliente satisfecho, recomendó productos', 'Excelente', TRUE, '2024-01-10 10:00:00', 1),
('2024-01-10 14:00:00', '2024-01-10 15:05:00', 38.8, 25.00, 'Golden Mediano', 'Costo un poco de trabajo por nervios', 'Usar bozal la próxima vez', 'Bien', TRUE, '2024-01-10 15:15:00', 2),
('2024-01-11 10:00:00', '2024-01-11 10:30:00', 38.2, 35.00, 'Pastor Alemán Grande', 'Mascota obediente, sin problemas', NULL, 'Excelente', TRUE, '2024-01-11 10:40:00', 3),
('2024-01-11 15:00:00', '2024-01-11 16:35:00', 38.6, 8.50, 'Poodle Pequeño', 'Muy nerviosa, costó trabajo el baño', 'Recomendar sesiones de adaptación', 'Regular', TRUE, '2024-01-11 16:45:00', 4),
('2024-01-12 16:00:00', '2024-01-12 16:48:00', 38.4, 12.00, 'Bulldog Pequeño', 'Ronquidos normales de la raza', NULL, 'Bien', FALSE, '2024-01-12 17:00:00', 6),
('2024-01-13 09:00:00', '2024-01-13 10:02:00', 38.7, 3.50, 'Chihuahua Pequeño', 'Tembló todo el tiempo, muy nervioso', 'Atender con cuidado extremo', 'Regular', TRUE, '2024-01-13 10:10:00', 7);

-- =====================================================
-- 22. FOTO (20 registros)
-- =====================================================
INSERT INTO foto (url, tipo, descripcion, fecha_subida, id_ficha) VALUES
('/fotos/antes/ficha1_antes1.jpg', 'antes', 'Firulais antes del baño', '2024-01-10 08:55:00', 1),
('/fotos/despues/ficha1_despues1.jpg', 'despues', 'Firulais después del servicio', '2024-01-10 09:55:00', 1),
('/fotos/antes/ficha2_antes1.jpg', 'antes', 'Luna vista frontal', '2024-01-10 13:55:00', 2),
('/fotos/despues/ficha2_despues1.jpg', 'despues', 'Luna corte completo', '2024-01-10 15:10:00', 2),
('/fotos/antes/ficha3_antes1.jpg', 'antes', 'Max antes del corte', '2024-01-11 09:55:00', 3),
('/fotos/despues/ficha3_despues1.jpg', 'despues', 'Max después del corte', '2024-01-11 10:35:00', 3),
('/fotos/antes/ficha4_antes1.jpg', 'antes', 'Bella antes del spa', '2024-01-11 14:55:00', 4),
('/fotos/despues/ficha4_despues1.jpg', 'despues', 'Bella después del spa', '2024-01-11 16:40:00', 4),
('/fotos/despues/ficha4_despues2.jpg', 'despues', 'Bella perfil derecho', '2024-01-11 16:41:00', 4),
('/fotos/antes/ficha5_antes1.jpg', 'antes', 'Rocky antes del servicio', '2024-01-12 15:55:00', 5),
('/fotos/despues/ficha5_despues1.jpg', 'despues', 'Rocky después del servicio', '2024-01-12 16:50:00', 5),
('/fotos/antes/ficha6_antes1.jpg', 'antes', 'Coco antes del baño', '2024-01-13 08:55:00', 6),
('/fotos/despues/ficha6_despues1.jpg', 'despues', 'Coco después del baño', '2024-01-13 10:05:00', 6),
('/fotos/despues/ficha6_despues2.jpg', 'despues', 'Coco relajado', '2024-01-13 10:06:00', 6),
('/fotos/antes/servicio1_antes.jpg', 'antes', 'Antes del corte', '2024-01-14 08:00:00', 3),
('/fotos/despues/servicio1_despues.jpg', 'despues', 'Después del corte', '2024-01-14 11:00:00', 3),
('/fotos/antes/servicio2_antes.jpg', 'antes', 'Antes del baño', '2024-01-15 09:00:00', 4),
('/fotos/despues/servicio2_despues.jpg', 'despues', 'Después del baño', '2024-01-15 12:00:00', 4),
('/fotos/antes/servicio3_antes.jpg', 'antes', 'Firulais otro servicio', '2024-01-16 10:00:00', 1),
('/fotos/despues/servicio3_despues.jpg', 'despues', 'Firulais feliz', '2024-01-16 13:00:00', 1);


-- =====================================================
-- 23. CARRITO (15 registros)
-- =====================================================
INSERT INTO carrito (session_token, id_cliente, fecha_creacion, expires_at, metodo_contacto, contacto_destino, estado_pedido, subtotal, descuento, total) VALUES
('TOKEN_ABC123', 9, '2024-01-10 15:00:00', '2024-01-17 15:00:00', 'whatsapp', '70010009', 'entregado', 85000, 0, 85000),
('TOKEN_DEF456', 10, '2024-01-11 10:30:00', '2024-01-18 10:30:00', 'email', 'cliente2@gmail.com', 'pagado', 110000, 5000, 105000),
('TOKEN_GHI789', 11, '2024-01-12 09:00:00', '2024-01-19 09:00:00', 'whatsapp', '70010011', 'confirmado', 38000, 0, 38000),
('TOKEN_JKL012', 12, '2024-01-13 14:00:00', '2024-01-20 14:00:00', 'telegram', '70010012', 'pendiente', 25000, 0, 25000),
('TOKEN_MNO345', 13, '2024-01-14 11:00:00', '2024-01-21 11:00:00', 'whatsapp', '70010013', 'enviado', 72000, 2000, 70000),
('TOKEN_PQR678', 14, '2024-01-15 08:30:00', '2024-01-22 08:30:00', 'email', 'cliente7@gmail.com', 'pendiente', 15000, 0, 15000),
('TOKEN_STU901', 15, '2024-01-16 13:00:00', '2024-01-23 13:00:00', 'whatsapp', '70010015', 'cancelado', 18000, 0, 18000),
('TOKEN_VWX234', 16, '2024-01-17 10:00:00', '2024-01-24 10:00:00', 'whatsapp', '70010016', 'pagado', 95000, 5000, 90000),
('TOKEN_YZA567', 17, '2024-01-18 15:30:00', '2024-01-25 15:30:00', 'email', 'cliente10@gmail.com', 'confirmado', 28000, 0, 28000),
('TOKEN_BCD890', 14, '2024-01-19 09:45:00', '2024-01-26 09:45:00', 'whatsapp', '70010018', 'pendiente', 52000, 0, 52000),
('TOKEN_EFG123', 19, '2024-01-20 11:15:00', '2024-01-27 11:15:00', 'telegram', '70010019', 'enviado', 125000, 10000, 115000),
('TOKEN_HIJ456', 20, '2024-01-21 14:30:00', '2024-01-28 14:30:00', 'whatsapp', '70010020', 'pagado', 68000, 0, 68000),
('TOKEN_KLM789', 19, '2024-01-22 08:00:00', '2024-01-29 08:00:00', 'email', 'cliente14@gmail.com', 'pendiente', 22000, 0, 22000),
('TOKEN_NOP012', 20, '2024-01-23 16:00:00', '2024-01-30 16:00:00', 'whatsapp', '70010015', 'pendiente', 49000, 0, 49000),
('TOKEN_QRS345', 19, '2024-01-24 10:00:00', '2024-01-31 10:00:00', 'whatsapp', '70010017', 'confirmado', 8000, 0, 8000);

-- =====================================================
-- 24. DETALLE_CARRITO (20 registros)
-- =====================================================
INSERT INTO detalle_carrito (id_carrito, id_producto, id_variante, cantidad, precio_unitario) VALUES
(1, 1, 3, 1, 85000),
(2, 10, NULL, 1, 95000),
(2, 5, 12, 2, 12000),
(3, 2, 4, 1, 45000),
(4, 13, NULL, 1, 28000),
(5, 4, 10, 2, 65000),
(5, 5, 13, 1, 12000),
(6, 3, 7, 2, 15000),
(7, 8, NULL, 1, 55000),
(7, 9, NULL, 1, 95000),
(8, 6, 14, 2, 38000),
(9, 14, NULL, 2, 25000),
(9, 15, NULL, 3, 15000),
(10, 11, 19, 1, 52000),
(11, 1, 1, 1, 85000),
(11, 18, NULL, 1, 125000),
(11, 12, NULL, 3, 8000),
(12, 19, NULL, 2, 68000),
(13, 20, NULL, 1, 22000),
(15, 12, NULL, 1, 8000);

-- =====================================================
-- 25. FACTURA (15 registros)
-- =====================================================
INSERT INTO factura (numero_factura, fecha_emision, subtotal, impuesto, total, estado_factura, id_cita, id_pedido) VALUES
('FAC-001', '2024-01-10 10:00:00', 25000, 0, 25000, 'pagada', 1, 1),
('FAC-002', '2024-01-10 15:00:00', 35000, 0, 35000, 'pagada', 2, NULL),
('FAC-003', '2024-01-11 10:30:00', 18000, 0, 18000, 'pagada', 3, NULL),
('FAC-004', '2024-01-11 16:45:00', 55000, 0, 55000, 'pagada', 4, NULL),
('FAC-005', '2024-01-12 16:50:00', 10000, 0, 10000, 'pagada', 6, NULL),
('FAC-006', '2024-01-13 10:10:00', 25000, 0, 25000, 'pagada', 7, NULL),
('FAC-007', '2024-01-15 16:00:00', 85000, 0, 85000, 'pagada', NULL, 1),
('FAC-008', '2024-01-16 14:00:00', 105000, 0, 105000, 'pagada', NULL, 2),
('FAC-009', '2024-01-17 10:00:00', 38000, 0, 38000, 'pagada', NULL, 3),
('FAC-010', '2024-01-20 09:00:00', 70000, 0, 70000, 'pagada', NULL, 5),
('FAC-011', '2024-01-22 11:00:00', 90000, 0, 90000, 'pagada', NULL, 8),
('FAC-012', '2024-01-25 14:00:00', 115000, 0, 115000, 'pendiente', NULL, 11),
('FAC-013', '2024-01-26 10:00:00', 68000, 0, 68000, 'pagada', NULL, 12),
('FAC-014', '2024-01-28 16:00:00', 28000, 0, 28000, 'pendiente', NULL, 9),
('FAC-015', '2024-01-30 11:00:00', 8000, 0, 8000, 'pagada', NULL, 15);

-- =====================================================
-- 26. DETALLE_FACTURA (20 registros)
-- =====================================================
INSERT INTO detalle_factura (id_factura, concepto, cantidad, precio_unitario, subtotal, id_producto, id_servicio) VALUES
(1, 'Baño Completo', 1, 25000, 25000, NULL, 1),
(2, 'Corte Completo', 1, 35000, 35000, NULL, 3),
(3, 'Corte Higiénico', 1, 18000, 18000, NULL, 2),
(4, 'Spa Completo', 1, 55000, 55000, NULL, 8),
(5, 'Corte de Uñas', 1, 10000, 10000, NULL, 5),
(6, 'Baño Completo', 1, 25000, 25000, NULL, 1),
(7, 'Croquetas Adulto 15kg', 1, 85000, 85000, 1, NULL),
(8, 'Croquetas Cachorro 20kg', 1, 95000, 95000, 10, NULL),
(8, 'Galletas de Pollo', 2, 12000, 24000, 5, NULL),
(9, 'Shampoo Calmante Lavanda', 1, 45000, 45000, 2, NULL),
(10, 'Collar Antipulgas Grande', 2, 65000, 130000, 4, NULL),
(10, 'Snack Dental', 1, 15000, 15000, 15, NULL),
(11, 'Shampoo Hidratante Coco', 2, 38000, 76000, 6, NULL),
(12, 'Cama Ortopédica', 1, 125000, 125000, 18, NULL),
(13, 'Aceite de Salmón', 2, 68000, 136000, 19, NULL),
(14, 'Suplemento Articular', 1, 95000, 95000, 9, NULL),
(14, 'Galletas de Pollo', 3, 12000, 36000, 5, NULL),
(15, 'Correa Extensible', 1, 55000, 55000, 8, NULL),
(14, 'Hueso de Cuero', 1, 8000, 8000, 12, NULL),
(15, 'Cuerda para Jugar', 1, 18000, 18000, 7, NULL);

-- =====================================================
-- 27. PAGOS (20 registros - algunos parciales)
-- =====================================================
INSERT INTO pago (monto, metodo_pago, referencia_transaccion, fecha_pago, estado_pago, id_factura) VALUES
(25000, 'efectivo', NULL, '2024-01-10 11:00:00', 'completado', 1),
(35000, 'transferencia', 'TFR-20240110-001', '2024-01-10 16:00:00', 'completado', 2),
(18000, 'qr', 'QR-20240111-001', '2024-01-11 11:00:00', 'completado', 3),
(55000, 'tarjeta', 'TAR-20240111-001', '2024-01-11 17:00:00', 'completado', 4),
(10000, 'efectivo', NULL, '2024-01-12 17:00:00', 'completado', 5),
(25000, 'qr', 'QR-20240113-001', '2024-01-13 10:30:00', 'completado', 6),
(85000, 'transferencia', 'TFR-20240115-001', '2024-01-15 17:00:00', 'completado', 7),
(105000, 'tarjeta', 'TAR-20240116-001', '2024-01-16 15:00:00', 'completado', 8),
(38000, 'efectivo', NULL, '2024-01-17 11:00:00', 'completado', 9),
(50000, 'qr', 'QR-20240120-001', '2024-01-20 10:00:00', 'completado', 10),
(20000, 'transferencia', 'TFR-20240120-002', '2024-01-20 10:30:00', 'completado', 10),
(90000, 'efectivo', NULL, '2024-01-22 12:00:00', 'completado', 11),
(50000, 'qr', 'QR-20240125-001', '2024-01-25 15:00:00', 'completado', 12),
(35000, 'transferencia', 'TFR-20240125-002', '2024-01-25 15:30:00', 'completado', 12),
(30000, 'efectivo', NULL, '2024-01-26 11:00:00', 'completado', 12),
(68000, 'tarjeta', 'TAR-20240126-001', '2024-01-26 11:30:00', 'completado', 13),
(15000, 'qr', 'QR-20240128-001', '2024-01-28 17:00:00', 'completado', 14),
(8000, 'efectivo', NULL, '2024-01-30 12:00:00', 'completado', 15),
(5000, 'transferencia', 'TFR-20240130-001', '2024-01-30 12:30:00', 'pendiente', 14),
(13000, 'efectivo', NULL, '2024-01-31 09:00:00', 'completado', 14);

-- =====================================================
-- 28. USO_PRODUCTO (consumo en fichas de grooming - 15 registros)
-- =====================================================
INSERT INTO uso_producto (id_ficha, id_producto, cantidad, fecha_uso) VALUES
(1, 2, 30, '2024-01-10 09:15:00'),
(1, 6, 30, '2024-01-10 09:20:00'),
(1, 13, 2, '2024-01-10 08:55:00'),
(2, 2, 30, '2024-01-10 14:15:00'),
(2, 6, 30, '2024-01-10 14:20:00'),
(2, 11, 2, '2024-01-10 14:00:00'),
(3, 1, 20, '2024-01-11 10:10:00'),
(4, 2, 40, '2024-01-11 15:15:00'),
(4, 6, 40, '2024-01-11 15:20:00'),
(4, 8, 10, '2024-01-11 15:30:00'),
(4, 9, 5, '2024-01-11 16:00:00'),
(5, 1, 20, '2024-01-12 16:10:00'),
(6, 2, 25, '2024-01-13 09:10:00'),
(6, 5, 2, '2024-01-13 09:05:00'),
(6, 9, 3, '2024-01-13 09:30:00');

-- =====================================================
-- 29. NOTIFICACIONES (20 registros)
-- =====================================================
INSERT INTO notificacion (tipo_evento, canal, mensaje, destino, fecha_programacion, fecha_envio, estado_envio, reintentos, id_cliente, id_cita) VALUES
('confirmacion', 'whatsapp', 'Su cita ha sido confirmada para el 10/01/2024 09:00', '70010009', '2024-01-05 10:00:00', '2024-01-05 10:01:00', 'enviado', 0, 9, 1),
('recordatorio_24h', 'email', 'Recordatorio: Mañana tiene cita a las 14:00', 'cliente2@gmail.com', '2024-01-09 14:00:00', '2024-01-09 14:00:00', 'enviado', 0, 10, 2),
('recordatorio_2h', 'whatsapp', 'Su cita es en 2 horas a las 10:00', '70010011', '2024-01-11 08:00:00', '2024-01-11 08:02:00', 'enviado', 0, 11, 3),
('listo_recoger', 'sms', 'Su mascota está lista para recoger', '70010012', '2024-01-11 16:30:00', '2024-01-11 16:31:00', 'enviado', 0, 12, 4),
('encuesta', 'whatsapp', 'Califique su experiencia con el servicio', '70010013', '2024-01-12 10:00:00', '2024-01-12 10:05:00', 'enviado', 1, 13, 5),
('recordatorio_24h', 'whatsapp', 'Recordatorio: Cita mañana a las 11:00', '70010014', '2024-01-12 11:00:00', '2024-01-12 11:00:00', 'enviado', 0, 14, 6),
('confirmacion', 'email', 'Cita confirmada para el 13/01/2024', 'cliente7@gmail.com', '2024-01-11 15:00:00', '2024-01-11 15:02:00', 'enviado', 0, 15, 7),
('recordatorio_2h', 'whatsapp', 'Su cita es en 2 horas', '70010016', '2024-01-13 12:00:00', '2024-01-13 12:01:00', 'enviado', 0, 16, 8),
('promocion', 'email', 'Descuento del 20% en su próximo servicio', 'cliente9@gmail.com', '2024-01-15 09:00:00', '2024-01-15 09:00:00', 'enviado', 0, 17, NULL),
('recordatorio_24h', 'whatsapp', 'Recordatorio: Cita mañana', '70010018', '2024-01-14 15:30:00', '2024-01-14 15:31:00', 'enviado', 0, 18, 10),
('confirmacion', 'sms', 'Cita confirmada para el 15/01/2024', '70010019', '2024-01-13 10:00:00', '2024-01-13 10:00:00', 'enviado', 0, 19, 11),
('listo_recoger', 'whatsapp', 'Su mascota está lista', '70010020', '2024-01-15 17:00:00', '2024-01-15 17:05:00', 'enviado', 0, 20, 12),
('recordatorio_2h', 'email', 'Recordatorio de cita en 2 horas', 'cliente14@gmail.com', '2024-01-16 07:30:00', '2024-01-16 07:31:00', 'enviado', 0, 20, 13),
('encuesta', 'whatsapp', 'Califique su servicio', '70010015', '2024-01-17 15:00:00', '2024-01-17 15:03:00', 'enviado', 1, 15, 14),
('recordatorio_24h', 'whatsapp', 'Cita mañana a las 10:00', '70010016', '2024-01-16 09:00:00', '2024-01-16 09:01:00', 'enviado', 0, 16, 16),
('confirmacion', 'email', 'Cita confirmada', 'cliente10@gmail.com', '2024-01-15 14:00:00', '2024-01-15 14:00:00', 'enviado', 0, 17, 17),
('recordatorio_2h', 'whatsapp', '2 horas para su cita', '70010018', '2024-01-18 14:30:00', '2024-01-18 14:30:00', 'fallido', 2, 18, 18),
('promocion', 'whatsapp', 'Promoción especial de fin de mes', '70010019', '2024-01-25 10:00:00', '2024-01-25 10:05:00', 'enviado', 0, 19, NULL),
('recordatorio_24h', 'sms', 'Recordatorio cita 19/01/2024', '70010020', '2024-01-18 09:00:00', '2024-01-18 09:00:00', 'enviado', 0, 20, 19),
('listo_recoger', 'whatsapp', 'Mascota lista para recoger', '70010009', '2024-01-17 16:00:00', '2024-01-17 16:05:00', 'enviado', 0, 9, 18);

-- =====================================================
-- 30. CALIFICACIONES (15 registros)
-- =====================================================
INSERT INTO calificacion (puntuacion, comentario, fecha, id_cliente, id_groomer, id_cita) VALUES
(5, 'Excelente servicio, mi perro quedó muy bonito', '2024-01-10 18:00:00', 9, 5, 1),
(4, 'Buen trabajo, pero tardó un poco más', '2024-01-10 19:00:00', 10, 6, 2),
(2, 'Muy profesionales, recomendados', '2024-01-11 12:00:00', 11, 5, 3),
(3, 'Mi perra estaba muy nerviosa al salir', '2024-01-11 18:00:00', 12, 7, 4),
(1, 'Excelente atención, volveré', '2024-01-12 19:00:00', 14, 5, 6);

-- =====================================================
-- 31. HISTORIAL_MASCOTA (20 registros)
-- =====================================================
INSERT INTO historial_mascota (tipo_evento, descripcion, fecha_evento, id_mascota, id_cita) VALUES
('servicio', 'Baño completo - primera visita', '2024-01-10 10:00:00', 1, 1),
('recomendacion', 'Usar shampoo hipoalergénico para próximos baños', '2024-01-10 10:15:00', 1, 1),
('servicio', 'Corte completo estilizado', '2024-01-10 15:15:00', 2, 2),
('servicio', 'Corte higiénico - se portó bien', '2024-01-11 10:40:00', 3, 3),
('alerta', 'Mascota muy nerviosa, recomendar sesiones cortas', '2024-01-11 16:50:00', 4, 4),
('servicio', 'Corte de uñas - cancelado por el cliente', '2024-01-12 11:30:00', 5, 5),
('nota_medica', 'Revisar rodilla derecha en próximo servicio', '2024-01-12 17:00:00', 5, NULL),
('servicio', 'Baño completo con shampoo hidratante', '2024-01-12 17:05:00', 6, 6),
('servicio', 'Corte completo - muy dócil', '2024-01-13 10:15:00', 7, 7),
('recomendacion', 'Cambiar a alimento para piel sensible', '2024-01-13 10:30:00', 7, 7),
('servicio', 'Corte higiénico - primera vez', '2024-01-14 14:45:00', 8, 8),
('servicio', 'Baño completo programado', '2024-01-15 10:00:00', 9, NULL),
('servicio', 'Baño completo - sin novedades', '2024-01-15 16:30:00', 10, 10),
('nota_medica', 'Aplicar antiparasitario mensual', '2024-01-16 10:00:00', 11, NULL),
('servicio', 'Corte completo - se portó excelente', '2024-01-16 12:15:00', 11, 11),
('alerta', 'Mascota con pulgas - recomendar baño antipulgas', '2024-01-16 12:20:00', 11, 11),
('servicio', 'Spa completo - tratamiento especial', '2024-01-17 17:45:00', 12, 12),
('recomendacion', 'Cepillado diario recomendado por nudos', '2024-01-18 10:30:00', 13, 13),
('servicio', 'Corte de uñas - se portó bien', '2024-01-18 14:45:00', 14, 14),
('servicio', 'Baño completo - perro muy feliz', '2024-01-19 11:00:00', 15, 15);

-- =====================================================
-- 32. USUARIO_SESION (15 registros)
-- =====================================================
INSERT INTO usuario_sesion (id_usuario, token_jwt, refresh_token, ip_address, user_agent, fecha_creacion, fecha_expiracion, activo) VALUES
(1, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjoxfQ.example1', 'refresh_token_1_abc123', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2024-01-15 08:00:00', '2024-01-15 12:00:00', FALSE),
(1, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjoxfQ.example2', 'refresh_token_1_def456', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2024-01-16 09:00:00', '2024-01-16 17:00:00', TRUE),
(3, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjozfQ.example3', 'refresh_token_3_ghi789', '192.168.1.101', 'Chrome/120.0.0.0', '2024-01-15 09:30:00', '2024-01-15 17:30:00', TRUE),
(5, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjo1fQ.example4', 'refresh_token_5_jkl012', '192.168.1.102', 'Firefox/121.0', '2024-01-15 08:15:00', '2024-01-15 16:15:00', TRUE),
(6, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjo2fQ.example5', 'refresh_token_6_mno345', '192.168.1.103', 'Safari/17.0', '2024-01-15 07:45:00', '2024-01-15 15:45:00', TRUE),
(9, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjo5fQ.example6', 'refresh_token_9_pqr678', '10.0.0.1', 'Mobile Safari', '2024-01-10 10:00:00', '2024-01-10 18:00:00', FALSE),
(9, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjo5fQ.example7', 'refresh_token_9_stu901', '10.0.0.1', 'Mobile Safari', '2024-01-15 11:00:00', '2024-01-15 19:00:00', TRUE),
(10, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjoxMH0.example8', 'refresh_token_10_vwx234', '10.0.0.2', 'Chrome Mobile', '2024-01-11 14:00:00', '2024-01-11 22:00:00', TRUE),
(11, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjoxMX0.example9', 'refresh_token_11_yza567', '10.0.0.3', 'Firefox Mobile', '2024-01-12 16:00:00', '2024-01-13 00:00:00', TRUE),
(2, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjoyfQ.example10', 'refresh_token_2_bcd890', '192.168.1.104', 'Edge/120.0', '2024-01-16 08:00:00', '2024-01-16 16:00:00', TRUE),
(4, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjo0fQ.example11', 'refresh_token_4_efg123', '192.168.1.105', 'Chrome/120.0', '2024-01-16 09:00:00', '2024-01-16 17:00:00', TRUE),
(7, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjo3fQ.example12', 'refresh_token_7_hij456', '192.168.1.106', 'Safari/17.0', '2024-01-15 10:00:00', '2024-01-15 18:00:00', TRUE),
(8, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjo4fQ.example13', 'refresh_token_8_klm789', '192.168.1.107', 'Firefox/121.0', '2024-01-14 08:00:00', '2024-01-14 16:00:00', FALSE),
(12, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjoxMn0.example14', 'refresh_token_12_nop012', '10.0.0.4', 'Chrome Mobile', '2024-01-13 15:00:00', '2024-01-13 23:00:00', TRUE),
(13, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c3VhcmlvX2lkIjoxM30.example15', 'refresh_token_13_qrs345', '10.0.0.5', 'Mobile Safari', '2024-01-14 12:00:00', '2024-01-14 20:00:00', TRUE);

-- =====================================================
-- 33. RESERVA_INVENTARIO (15 registros)
-- =====================================================
INSERT INTO reserva_inventario (id_inventario, id_cita, id_pedido, cantidad, fecha_reserva, fecha_liberacion, estado_reserva) VALUES
(1, 1, NULL, 10, '2024-01-05 10:00:00', '2024-01-10 10:00:00', 'consumida'),
(2, 1, NULL, 15, '2024-01-05 10:00:00', '2024-01-10 10:00:00', 'consumida'),
(4, 2, NULL, 8, '2024-01-06 11:00:00', '2024-01-10 15:15:00', 'consumida'),
(5, 2, NULL, 3, '2024-01-06 11:00:00', '2024-01-10 15:15:00', 'consumida'),
(16, 3, NULL, 15, '2024-01-07 09:00:00', '2024-01-11 10:40:00', 'consumida'),
(17, 3, NULL, 10, '2024-01-07 09:00:00', '2024-01-11 10:40:00', 'consumida'),
(10, 5, NULL, 5, '2024-01-10 09:00:00', '2024-01-12 11:30:00', 'liberada'),
(19, 7, NULL, 6, '2024-01-11 15:00:00', NULL, 'activa'),
(20, 7, NULL, 4, '2024-01-11 15:00:00', NULL, 'activa'),
(7, 8, NULL, 20, '2024-01-12 10:00:00', NULL, 'activa'),
(13, NULL, 2, 40, '2024-01-11 10:30:00', '2024-01-11 12:00:00', 'consumida'),
(8, NULL, 2, 15, '2024-01-11 10:30:00', '2024-01-11 12:00:00', 'consumida'),
(3, NULL, 5, 5, '2024-01-14 11:00:00', NULL, 'activa'),
(12, NULL, 3, 50, '2024-01-12 09:00:00', '2024-01-12 15:00:00', 'liberada'),
(11, 12, NULL, 3, '2024-01-13 14:00:00', NULL, 'activa');

-- =====================================================
-- 34. FICHA_CHECKLIST (registros de checklist completados - 30 registros)
-- =====================================================
INSERT INTO ficha_checklist (id_ficha, id_item, completado, observacion, fecha_registro) VALUES
(1, 1, TRUE, NULL, '2024-01-10 09:05:00'),
(1, 2, TRUE, NULL, '2024-01-10 09:20:00'),
(1, 3, TRUE, NULL, '2024-01-10 09:30:00'),
(1, 4, TRUE, 'Uñas desgastadas naturalmente', '2024-01-10 09:35:00'),
(1, 5, TRUE, 'Oídos limpios sin infección', '2024-01-10 09:40:00'),
(1, 9, TRUE, NULL, '2024-01-10 09:45:00'),
(2, 1, TRUE, NULL, '2024-01-10 14:10:00'),
(2, 2, TRUE, NULL, '2024-01-10 14:25:00'),
(2, 6, TRUE, NULL, '2024-01-10 14:50:00'),
(2, 3, TRUE, NULL, '2024-01-10 15:00:00'),
(2, 16, TRUE, 'Corte según estándar Golden', '2024-01-10 15:05:00'),
(3, 1, TRUE, NULL, '2024-01-11 10:05:00'),
(3, 2, TRUE, NULL, '2024-01-11 10:15:00'),
(3, 6, TRUE, NULL, '2024-01-11 10:25:00'),
(3, 3, TRUE, NULL, '2024-01-11 10:30:00'),
(4, 1, TRUE, NULL, '2024-01-11 15:10:00'),
(4, 10, TRUE, NULL, '2024-01-11 15:30:00'),
(4, 3, TRUE, NULL, '2024-01-11 16:00:00'),
(4, 17, TRUE, NULL, '2024-01-11 16:15:00'),
(4, 18, TRUE, 'Muy nerviosa al inicio', '2024-01-11 16:25:00'),
(4, 9, TRUE, NULL, '2024-01-11 16:30:00'),
(5, 4, TRUE, 'Uñas cortadas sin problemas', '2024-01-12 16:15:00'),
(6, 1, TRUE, NULL, '2024-01-13 09:15:00'),
(6, 2, TRUE, NULL, '2024-01-13 09:30:00'),
(6, 3, TRUE, NULL, '2024-01-13 09:45:00'),
(6, 4, TRUE, 'Uñas muy cortas', '2024-01-13 09:50:00'),
(6, 5, TRUE, 'Oídos sensibles', '2024-01-13 09:55:00'),
(6, 9, TRUE, NULL, '2024-01-13 10:00:00');

-- =====================================================
-- FIN DE LOS INSERTS
-- =====================================================