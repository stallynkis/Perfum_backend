# 03 - Resúmenes Diarios

## Descripción
El Resumen Diario de Boletas es un documento que consolida la información de las boletas de venta y notas de crédito y débito relacionadas a estas, emitidas en un día. Es obligatorio para todos los emisores de boletas electrónicas.

## Endpoints
```
POST /api/generate-daily-summary    # Generar resumen
POST /api/get-status-summary        # Consultar estado
```

## Códigos Importantes
- **tipo_doc**: `"03"` (Boleta de Venta)
- **estado**: `"1"` (Adicionar), `"3"` (Anular)
- **cliente_tipo_doc**: `"1"` (DNI), `"0"` (Sin documento)

---

## Ejemplo 1: Resumen Diario Básico

**¿Qué es?** Un resumen que consolida todas las boletas emitidas en un día específico, incluyendo los totales por cada documento.

**Cuándo usar:** Diariamente para reportar a SUNAT todas las boletas emitidas. Es obligatorio enviar antes de las 12:00 PM del día siguiente.

### Request Body - Generar Resumen
```json
{
  "documents": [
    {
      "tipo_doc": "03",
      "serie_numero": "B001-1",
      "estado": "1",
      "cliente_tipo_doc": "1",
      "cliente_numero": "12345678",
      "total": 100.00,
      "gravadas": 84.75,
      "inafectas": 0,
      "exoneradas": 0,
      "igv": 15.25
    },
    {
      "tipo_doc": "03",
      "serie_numero": "B001-2",
      "estado": "1",
      "cliente_tipo_doc": "1",
      "cliente_numero": "87654321",
      "total": 200.00,
      "gravadas": 169.49,
      "inafectas": 0,
      "exoneradas": 0,
      "igv": 30.51
    }
  ],
  "date": "2023-10-15"
}
```

### Response Exitosa - Generar Resumen
```json
{
  "success": true,
  "ticket": "202310150001",
  "xml_path": "storage/app/sunat/summaries/20000000001-RC-20231015-1.xml"
}
```

---

## Ejemplo 2: Resumen con Anulaciones

**¿Qué es?** Resumen que incluye tanto boletas nuevas (estado "1") como boletas anuladas (estado "3") del mismo día.

**Cuándo usar:** Cuando necesitas reportar boletas nuevas y al mismo tiempo anular boletas emitidas anteriormente en el día.

### Request Body
```json
{
  "documents": [
    {
      "tipo_doc": "03",
      "serie_numero": "B001-3",
      "estado": "1",
      "cliente_tipo_doc": "0",
      "cliente_numero": "-",
      "total": 50.00,
      "gravadas": 42.37,
      "inafectas": 0,
      "exoneradas": 0,
      "igv": 7.63
    },
    {
      "tipo_doc": "03",
      "serie_numero": "B001-4",
      "estado": "3",
      "cliente_tipo_doc": "1",
      "cliente_numero": "11223344",
      "total": 0.00,
      "gravadas": 0.00,
      "inafectas": 0,
      "exoneradas": 0,
      "igv": 0.00
    }
  ],
  "date": "2023-10-15"
}
```

---

## Ejemplo 3: Resumen con Diferentes Tipos de Operación

**¿Qué es?** Resumen que incluye boletas con diferentes tipos de afectación tributaria: gravadas, exoneradas, inafectas, etc.

**Cuándo usar:** Cuando en el día se emitieron boletas con diferentes tratamientos tributarios (productos gravados, exonerados, inafectos).

### Request Body
```json
{
  "documents": [
    {
      "tipo_doc": "03",
      "serie_numero": "B001-5",
      "estado": "1",
      "cliente_tipo_doc": "1",
      "cliente_numero": "55667788",
      "total": 150.00,
      "gravadas": 127.12,
      "inafectas": 0,
      "exoneradas": 0,
      "exportacion": 0,
      "gratuitas": 0,
      "otros_cargos": 0,
      "igv": 22.88
    },
    {
      "tipo_doc": "03",
      "serie_numero": "B001-6",
      "estado": "1",
      "cliente_tipo_doc": "1",
      "cliente_numero": "99887766",
      "total": 80.00,
      "gravadas": 0.00,
      "inafectas": 0,
      "exoneradas": 80.00,
      "exportacion": 0,
      "gratuitas": 0,
      "otros_cargos": 0,
      "igv": 0.00
    }
  ],
  "date": "2023-10-15"
}
```

---

## Consultar Estado del Resumen

### Request Body - Consultar Estado
```json
{
  "ticket": "202310150001"
}
```

### Response Exitosa - Estado Procesado
```json
{
  "success": true,
  "cdr_path": "storage/app/sunat/summaries/R-202310150001.zip",
  "response": {
    "code": "0",
    "description": "El resumen diario ha sido aceptado",
    "notes": []
  }
}
```

### Response - Estado en Proceso
```json
{
  "success": false,
  "error": {
    "code": "98",
    "message": "El resumen está siendo procesado"
  }
}
```

---

## Campos Requeridos

### Documento en Resumen
- `tipo_doc`: Tipo de documento (03 para boletas)
- `serie_numero`: Serie y número del documento (B001-1)
- `estado`: Estado del documento
  - `"1"`: Adicionar
  - `"3"`: Anular
- `cliente_tipo_doc`: Tipo de documento del cliente
- `cliente_numero`: Número de documento del cliente
- `total`: Monto total del documento
- `gravadas`: Monto de operaciones gravadas
- `inafectas`: Monto de operaciones inafectas (opcional)
- `exoneradas`: Monto de operaciones exoneradas (opcional)
- `exportacion`: Monto de exportación (opcional)
- `gratuitas`: Monto de operaciones gratuitas (opcional)
- `otros_cargos`: Otros cargos (opcional)
- `igv`: Monto del IGV

### Resumen
- `documents`: Array de documentos
- `date`: Fecha del resumen (YYYY-MM-DD)

## Estados de Respuesta SUNAT
- `0`: Aceptado
- `98`: En proceso
- `99`: En proceso (verificar más tarde)
- Otros códigos: Error específico

## Proceso de Resumen Diario
1. **Generar**: Enviar resumen con `generate-daily-summary`
2. **Obtener ticket**: SUNAT devuelve un ticket de seguimiento
3. **Consultar**: Usar `get-status-summary` con el ticket
4. **Esperar**: El proceso puede tomar varios minutos
5. **Verificar**: Consultar periódicamente hasta obtener respuesta final

## Notas Importantes
- El resumen debe enviarse antes de las 12:00 PM del día siguiente
- Incluir todas las boletas emitidas en el día
- Las anulaciones se marcan con estado "3"
- El ticket permite consultar el estado del procesamiento
- SUNAT puede tomar hasta 2 horas en procesar el resumen