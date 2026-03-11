# 04 - Comunicaciones de Baja

## Descripción
La Comunicación de Baja es un documento electrónico que permite anular comprobantes de pago electrónicos (facturas, boletas, notas) que fueron emitidos con errores o que por algún motivo deben ser dejados sin efecto.

## Endpoint
```
POST /api/generate-voided
Content-Type: application/json
```

## Códigos Importantes
- **tipo_doc**: `"01"` (Factura), `"03"` (Boleta), `"07"` (Nota Crédito), `"08"` (Nota Débito)
- **motivo_baja**: Descripción del motivo de anulación

---

## Ejemplo 1: Anular Facturas

**¿Qué es?** Comunicación para anular facturas que fueron emitidas con errores o que deben dejarse sin efecto tributario.

**Cuándo usar:** Cuando se detectan errores en facturas ya emitidas (datos incorrectos, montos erróneos, etc.) y necesitas anularlas dentro del plazo legal (7 días).

### Request Body
```json
{
  "documents": [
    {
      "tipo_doc": "01",
      "serie": "F001",
      "correlativo": "1",
      "motivo_baja": "Error en los datos del cliente"
    },
    {
      "tipo_doc": "01",
      "serie": "F001",
      "correlativo": "2",
      "motivo_baja": "Factura emitida por error"
    }
  ],
  "date": "2023-10-16"
}
```

### Response Exitosa
```json
{
  "success": true,
  "ticket": "202310160001",
  "xml_path": "storage/app/sunat/voided/20000000001-RA-20231016-1.xml"
}
```

---

## Ejemplo 2: Anular Boletas

**¿Qué es?** Comunicación para anular boletas de venta que fueron emitidas incorrectamente o por solicitud del cliente.

**Cuándo usar:** Para anular boletas con errores, duplicadas, o cuando el cliente solicita la anulación. Debe hacerse antes del día siguiente de la emisión.

### Request Body
```json
{
  "documents": [
    {
      "tipo_doc": "03",
      "serie": "B001",
      "correlativo": "15",
      "motivo_baja": "Cliente solicitó anulación"
    },
    {
      "tipo_doc": "03",
      "serie": "B001",
      "correlativo": "16",
      "motivo_baja": "Error en el monto"
    }
  ],
  "date": "2023-10-16"
}
```

---

## Ejemplo 3: Anular Notas de Crédito

**¿Qué es?** Comunicación para anular notas de crédito que fueron emitidas incorrectamente o con datos erróneos.

**Cuándo usar:** Cuando una nota de crédito fue emitida por error, con montos incorrectos, o no correspondía emitirla.

### Request Body
```json
{
  "documents": [
    {
      "tipo_doc": "07",
      "serie": "FC01",
      "correlativo": "1",
      "motivo_baja": "Nota de crédito incorrecta"
    }
  ],
  "date": "2023-10-16"
}
```

---

## Ejemplo 4: Anular Diferentes Tipos de Documentos

**¿Qué es?** Comunicación masiva que permite anular múltiples tipos de comprobantes (facturas, boletas, notas) en una sola operación.

**Cuándo usar:** Para optimizar el proceso cuando necesitas anular varios tipos de documentos el mismo día, enviando una sola comunicación.

### Request Body
```json
{
  "documents": [
    {
      "tipo_doc": "01",
      "serie": "F001",
      "correlativo": "10",
      "motivo_baja": "Error en el RUC del cliente"
    },
    {
      "tipo_doc": "03",
      "serie": "B001",
      "correlativo": "25",
      "motivo_baja": "Duplicado por error del sistema"
    },
    {
      "tipo_doc": "07",
      "serie": "FC01",
      "correlativo": "3",
      "motivo_baja": "Monto incorrecto en la nota"
    },
    {
      "tipo_doc": "08",
      "serie": "FD01",
      "correlativo": "1",
      "motivo_baja": "Nota de débito no corresponde"
    }
  ],
  "date": "2023-10-16"
}
```

---

## Consultar Estado de la Comunicación

Después de enviar la comunicación de baja, usar el ticket para consultar el estado:

### Request Body - Consultar Estado
```json
{
  "ticket": "202310160001"
}
```

### Response Exitosa - Estado Procesado
```json
{
  "success": true,
  "cdr_path": "storage/app/sunat/summaries/R-202310160001.zip",
  "response": {
    "code": "0",
    "description": "La comunicación de baja ha sido aceptada",
    "notes": []
  }
}
```

---

## Campos Requeridos

### Documento a Anular
- `tipo_doc`: Tipo de documento a anular
  - `"01"`: Factura
  - `"03"`: Boleta de venta
  - `"07"`: Nota de crédito
  - `"08"`: Nota de débito
- `serie`: Serie del documento
- `correlativo`: Correlativo del documento
- `motivo_baja`: Motivo de la anulación (texto libre)

### Comunicación
- `documents`: Array de documentos a anular
- `date`: Fecha de la comunicación (YYYY-MM-DD)

## Motivos Comunes de Baja
- "Error en los datos del cliente"
- "Error en el monto"
- "Documento duplicado"
- "Error en la descripción"
- "Cliente solicitó anulación"
- "Error en el tipo de documento"
- "Error en el RUC/DNI"
- "Factura emitida por error"
- "Error en el IGV"
- "Datos incorrectos del producto"

## Estados de Respuesta SUNAT
- `0`: Aceptado
- `98`: En proceso
- `99`: En proceso (verificar más tarde)
- Otros códigos: Error específico

## Proceso de Comunicación de Baja
1. **Generar**: Enviar comunicación con `generate-voided`
2. **Obtener ticket**: SUNAT devuelve un ticket de seguimiento
3. **Consultar**: Usar `get-status-summary` con el ticket
4. **Esperar**: El proceso puede tomar varios minutos
5. **Verificar**: Consultar periódicamente hasta obtener respuesta final

## Restricciones y Consideraciones

### Plazos
- **Facturas**: Pueden anularse hasta 7 días después de emitidas
- **Boletas**: Pueden anularse hasta el día siguiente de emitidas
- **Notas**: Pueden anularse hasta 7 días después de emitidas

### Limitaciones
- No se pueden anular documentos ya reportados a SUNAT como pagados
- Los documentos anulados no pueden reactivarse
- La anulación debe tener un motivo válido
- Algunos documentos pueden requerir autorización previa

## Notas Importantes
- La comunicación de baja es irreversible
- Debe enviarse dentro de los plazos establecidos
- El motivo debe ser claro y específico
- SUNAT puede rechazar la comunicación si no cumple requisitos
- Una vez aceptada, el documento queda sin efecto tributario