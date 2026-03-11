# 05 - Notas de Crédito y Débito

## Descripción
Las notas de crédito y débito son documentos que modifican comprobantes de pago ya emitidos. La nota de crédito disminuye el importe de la operación original, mientras que la nota de débito lo aumenta.

## Endpoint
```
POST /api/generate-note
Content-Type: application/json
```

## Códigos Importantes
- **tipo_doc**: `"07"` (Nota de Crédito), `"08"` (Nota de Débito)
- **tipo_doc_afectado**: `"01"` (Factura), `"03"` (Boleta)
- **cod_motivo**: Código del catálogo 09 (NC) o 10 (ND)

---

## Ejemplo 1: Nota de Crédito por Anulación

**¿Qué es?** Nota de crédito que anula completamente una factura, devolviendo el 100% del importe al cliente.

**Cuándo usar:** Para anular totalmente una factura por error en los datos, cancelación de la venta, o devolución completa de productos.

### Request Body
```json
{
  "tipo_doc": "07",
  "serie": "FC01",
  "correlativo": "1",
  "fecha_emision": "2023-10-16",
  "tipo_doc_afectado": "01",
  "num_doc_afectado": "F001-1",
  "cod_motivo": "01",
  "des_motivo": "Anulación de la operación",
  "tipo_moneda": "PEN",
  "client": {
    "tipo_doc": "6",
    "num_doc": "20000000001",
    "rzn_social": "EMPRESA CLIENTE S.A.C."
  },
  "items": [
    {
      "cod_producto": "P001",
      "unidad": "NIU",
      "cantidad": 2,
      "valor_unitario": 100.00,
      "descripcion": "Producto 1",
      "base_igv": 200.00,
      "porcentaje_igv": 18,
      "igv": 36.00,
      "tipo_afe_igv": "10",
      "total_impuestos": 36.00,
      "valor_venta": 200.00,
      "precio_unitario": 118.00
    }
  ],
  "mto_oper_gravadas": 200.00,
  "mto_igv": 36.00,
  "total_impuestos": 36.00,
  "mto_imp_venta": 236.00
}
```

### Response Exitosa
```json
{
  "success": true,
  "xml_path": "storage/app/sunat/notes/20000000001-07-FC01-1.xml",
  "cdr_path": "storage/app/sunat/notes/R-20000000001-07-FC01-1.zip",
  "response": {
    "code": 0,
    "status": "ACEPTADA",
    "description": "La Nota de Credito numero FC01-1, ha sido aceptada",
    "notes": []
  }
}
```

---

## Ejemplo 2: Nota de Crédito por Descuento

**¿Qué es?** Nota de crédito que otorga un descuento parcial sobre una factura ya emitida, por error en el RUC o datos del cliente.

**Cuándo usar:** Para corregir errores en datos del cliente, aplicar descuentos posteriores, o ajustar precios acordados después de la emisión.

### Request Body
```json
{
  "tipo_doc": "07",
  "serie": "FC01",
  "correlativo": "2",
  "fecha_emision": "2023-10-16",
  "tipo_doc_afectado": "01",
  "num_doc_afectado": "F001-5",
  "cod_motivo": "02",
  "des_motivo": "Anulación por error en el RUC",
  "tipo_moneda": "PEN",
  "client": {
    "tipo_doc": "6",
    "num_doc": "20000000001",
    "rzn_social": "EMPRESA CLIENTE S.A.C."
  },
  "items": [
    {
      "cod_producto": "P002",
      "unidad": "NIU",
      "cantidad": 1,
      "valor_unitario": 50.00,
      "descripcion": "Descuento aplicado",
      "base_igv": 50.00,
      "porcentaje_igv": 18,
      "igv": 9.00,
      "tipo_afe_igv": "10",
      "total_impuestos": 9.00,
      "valor_venta": 50.00,
      "precio_unitario": 59.00
    }
  ],
  "mto_oper_gravadas": 50.00,
  "mto_igv": 9.00,
  "total_impuestos": 9.00,
  "mto_imp_venta": 59.00
}
```

---

## Ejemplo 3: Nota de Débito por Intereses

**¿Qué es?** Nota de débito que incrementa el monto de una factura por conceptos adicionales como intereses por mora o penalidades.

**Cuándo usar:** Para cobrar intereses por pago tardío, penalidades contractuales, o aumentar el valor por conceptos no incluidos en la factura original.

### Request Body
```json
{
  "tipo_doc": "08",
  "serie": "FD01",
  "correlativo": "1",
  "fecha_emision": "2023-10-16",
  "tipo_doc_afectado": "01",
  "num_doc_afectado": "F001-3",
  "cod_motivo": "01",
  "des_motivo": "Intereses por mora",
  "tipo_moneda": "PEN",
  "client": {
    "tipo_doc": "6",
    "num_doc": "20000000001",
    "rzn_social": "EMPRESA CLIENTE S.A.C."
  },
  "items": [
    {
      "cod_producto": "INT001",
      "unidad": "NIU",
      "cantidad": 1,
      "valor_unitario": 25.00,
      "descripcion": "Intereses por pago tardío",
      "base_igv": 25.00,
      "porcentaje_igv": 18,
      "igv": 4.50,
      "tipo_afe_igv": "10",
      "total_impuestos": 4.50,
      "valor_venta": 25.00,
      "precio_unitario": 29.50
    }
  ],
  "mto_oper_gravadas": 25.00,
  "mto_igv": 4.50,
  "total_impuestos": 4.50,
  "mto_imp_venta": 29.50
}
```

---

## Ejemplo 4: Nota de Crédito para Boleta

**¿Qué es?** Nota de crédito que modifica una boleta de venta, típicamente por devolución de productos o anulación de la venta.

**Cuándo usar:** Para procesar devoluciones de productos vendidos con boleta, anular ventas a consumidores finales, o corregir errores en boletas.

### Request Body
```json
{
  "tipo_doc": "07",
  "serie": "BC01",
  "correlativo": "1",
  "fecha_emision": "2023-10-16",
  "tipo_doc_afectado": "03",
  "num_doc_afectado": "B001-10",
  "cod_motivo": "01",
  "des_motivo": "Anulación de la operación",
  "tipo_moneda": "PEN",
  "client": {
    "tipo_doc": "1",
    "num_doc": "12345678",
    "rzn_social": "JUAN PEREZ LOPEZ"
  },
  "items": [
    {
      "cod_producto": "P003",
      "unidad": "NIU",
      "cantidad": 1,
      "valor_unitario": 84.75,
      "descripcion": "Producto devuelto",
      "base_igv": 84.75,
      "porcentaje_igv": 18,
      "igv": 15.25,
      "tipo_afe_igv": "10",
      "total_impuestos": 15.25,
      "valor_venta": 84.75,
      "precio_unitario": 100.00
    }
  ],
  "mto_oper_gravadas": 84.75,
  "mto_igv": 15.25,
  "total_impuestos": 15.25,
  "mto_imp_venta": 100.00
}
```

---

## Códigos de Motivo

### Notas de Crédito (Catálogo 09)
- `"01"`: Anulación de la operación
- `"02"`: Anulación por error en el RUC
- `"03"`: Corrección por error en la descripción
- `"04"`: Descuento global
- `"05"`: Descuento por ítem
- `"06"`: Devolución total
- `"07"`: Devolución por ítem
- `"08"`: Bonificación
- `"09"`: Disminución en el valor
- `"10"`: Otros conceptos

### Notas de Débito (Catálogo 10)
- `"01"`: Intereses por mora
- `"02"`: Aumento en el valor
- `"03"`: Penalidades/otros conceptos
- `"10"`: Otros conceptos

## Campos Requeridos

### Documento Afectado
- `tipo_doc_afectado`: Tipo del documento original
- `num_doc_afectado`: Número del documento original (Serie-Correlativo)
- `cod_motivo`: Código del motivo (catálogo 09 o 10)
- `des_motivo`: Descripción del motivo

### Nota
- `tipo_doc`: `"07"` (NC) o `"08"` (ND)
- `serie`: Serie de la nota
- `correlativo`: Correlativo de la nota
- `fecha_emision`: Fecha de emisión
- `tipo_moneda`: Moneda
- `client`: Datos del cliente (igual al documento original)
- `items`: Items de la nota
- Totales: Montos correspondientes

## Series Recomendadas
- **Notas de Crédito para Facturas**: FC01, FC02, etc.
- **Notas de Crédito para Boletas**: BC01, BC02, etc.
- **Notas de Débito para Facturas**: FD01, FD02, etc.
- **Notas de Débito para Boletas**: BD01, BD02, etc.

## Restricciones
- Solo se pueden emitir notas para documentos ya aceptados por SUNAT
- El cliente debe ser el mismo del documento original
- La fecha de emisión debe ser posterior al documento original
- Los montos no pueden exceder el documento original (en NC)
- Debe existir una justificación válida para la emisión

## Notas Importantes
- Las notas de crédito reducen la deuda tributaria
- Las notas de débito aumentan la deuda tributaria
- Ambas deben enviarse individualmente a SUNAT (como facturas)
- No requieren resumen diario
- Afectan directamente al documento original
- Deben conservar la misma moneda del documento original