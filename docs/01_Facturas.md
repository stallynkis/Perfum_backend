# 01 - Facturas Electrónicas

## Descripción
La factura electrónica es un comprobante de pago emitido a través del sistema de emisión electrónica desarrollado por la SUNAT. Este documento se emite a favor de empresas o personas con RUC y sustenta costo o gasto para efectos tributarios.

## Endpoint
```
POST /api/generate-invoice
Content-Type: application/json
```

## Códigos Importantes
- **tipo_doc**: `"01"` (Factura)
- **tipo_operacion**: `"0101"` (Venta interna)
- **tipo_afe_igv**: `"10"` (Gravado - Operación Onerosa)
- **client.tipo_doc**: `"6"` (RUC)

---

## Ejemplo 1: Factura Básica

**¿Qué es?** Una factura estándar con múltiples productos gravados con IGV al 18%. Es el caso más común de facturación electrónica para ventas B2B.

**Cuándo usar:** Para ventas normales a empresas con RUC, productos gravados con IGV, sin descuentos ni casos especiales.

### Request Body
```json
{
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
    },
    {
      "cod_producto": "P002",
      "unidad": "NIU",
      "cantidad": 1,
      "valor_unitario": 50.00,
      "descripcion": "Producto 2",
      "base_igv": 50.00,
      "porcentaje_igv": 18,
      "igv": 9.00,
      "tipo_afe_igv": "10",
      "total_impuestos": 9.00,
      "valor_venta": 50.00,
      "precio_unitario": 59.00
    }
  ],
  "tipo_operacion": "0101",
  "tipo_doc": "01",
  "serie": "F001",
  "correlativo": "1",
  "fecha_emision": "2023-10-15",
  "tipo_moneda": "PEN",
  "mto_oper_gravadas": 250.00,
  "mto_igv": 45.00,
  "total_impuestos": 45.00,
  "valor_venta": 250.00,
  "sub_total": 295.00,
  "mto_imp_venta": 295.00,
  "legend_value": "DOSCIENTOS NOVENTA Y CINCO Y 00/100 SOLES"
}
```

### Response Exitosa
```json
{
  "success": true,
  "xml_path": "storage/app/sunat/xmls/20000000001-01-F001-1.xml",
  "cdr_path": "storage/app/sunat/cdrs/R-20000000001-01-F001-1.zip",
  "response": {
    "code": 0,
    "status": "ACEPTADA",
    "description": "La Factura numero F001-1, ha sido aceptada",
    "notes": []
  }
}
```

---

## Ejemplo 2: Factura con Descuento

> **⚠️ PENDIENTE DE IMPLEMENTACIÓN**: Los campos `descuento` (por item) y `descuentos` (globales) aún no están implementados en el backend. El JSON será aceptado pero los descuentos NO se incluirán en el XML enviado a SUNAT.

**¿Qué es?** Factura que incluye descuentos aplicados a los productos, mostrando el precio original y el descuento aplicado.

**Cuándo usar:** Cuando se otorgan descuentos comerciales, promociones, o reducciones de precio. El descuento se detalla tanto a nivel de ítem como en el documento.

### Request Body
```json
{
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
      "base_igv": 180.00,
      "porcentaje_igv": 18,
      "igv": 32.40,
      "tipo_afe_igv": "10",
      "total_impuestos": 32.40,
      "valor_venta": 180.00,
      "precio_unitario": 106.20,
      "descuento": {
        "tipo": "00",
        "monto": 20.00,
        "factor": 0.10,
        "base": 200.00
      }
    }
  ],
  "tipo_operacion": "0101",
  "tipo_doc": "01",
  "serie": "F001",
  "correlativo": "2",
  "fecha_emision": "2023-10-15",
  "tipo_moneda": "PEN",
  "mto_oper_gravadas": 180.00,
  "mto_igv": 32.40,
  "total_impuestos": 32.40,
  "valor_venta": 180.00,
  "sub_total": 212.40,
  "mto_imp_venta": 212.40,
  "descuentos": [
    {
      "tipo": "00",
      "monto": 20.00,
      "factor": 0.10,
      "base": 200.00
    }
  ],
  "legend_value": "DOSCIENTOS DOCE Y 40/100 SOLES"
}
```

---

> **Nota:** Para facturas con detracción, ver [06_Facturas_Detraccion.md](06_Facturas_Detraccion.md) con ejemplos completos y campos correctos.

---

## Ejemplo 3: Factura con Percepción

> **⚠️ PENDIENTE DE IMPLEMENTACIÓN**: Los campos `percepcion.*` aún no están implementados en el backend. El JSON será aceptado pero la percepción NO se incluirá en el XML.

**¿Qué es?** Factura donde el vendedor actúa como agente de percepción del IGV, cobrando un porcentaje adicional que luego entrega a SUNAT.

**Cuándo usar:** Cuando la empresa está designada como agente de percepción por SUNAT, generalmente para ventas de bienes gravados por montos significativos.

### Request Body
```json
{
  "client": {
    "tipo_doc": "6",
    "num_doc": "20000000001",
    "rzn_social": "EMPRESA CLIENTE S.A.C."
  },
  "items": [
    {
      "cod_producto": "P001",
      "unidad": "NIU",
      "cantidad": 10,
      "valor_unitario": 100.00,
      "descripcion": "Producto 1",
      "base_igv": 1000.00,
      "porcentaje_igv": 18,
      "igv": 180.00,
      "tipo_afe_igv": "10",
      "total_impuestos": 180.00,
      "valor_venta": 1000.00,
      "precio_unitario": 118.00
    }
  ],
  "tipo_operacion": "0101",
  "tipo_doc": "01",
  "serie": "F001",
  "correlativo": "3",
  "fecha_emision": "2023-10-15",
  "tipo_moneda": "PEN",
  "mto_oper_gravadas": 1000.00,
  "mto_igv": 180.00,
  "total_impuestos": 180.00,
  "valor_venta": 1000.00,
  "sub_total": 1180.00,
  "mto_imp_venta": 1180.00,
  "percepcion": {
    "codigo": "01",
    "porcentaje": 2,
    "monto_base": 1180.00,
    "monto": 23.60,
    "monto_total": 1203.60
  },
  "legend_value": "MIL CIENTO OCHENTA Y 00/100 SOLES"
}
```

---

## Ejemplo 4: Factura con Retención

> **⚠️ PENDIENTE DE IMPLEMENTACIÓN**: Los campos `retencion.*` aún no están implementados en el backend. El JSON será aceptado pero la retención NO se incluirá en el XML.

**¿Qué es?** Factura donde se indica que el cliente retendrá un porcentaje del pago como adelanto del Impuesto a la Renta del proveedor.

**Cuándo usar:** Para servicios profesionales, consultoría, o cuando el cliente está obligado a actuar como agente de retención del Impuesto a la Renta.

### Request Body
```json
{
  "client": {
    "tipo_doc": "6",
    "num_doc": "20000000001",
    "rzn_social": "EMPRESA CLIENTE S.A.C."
  },
  "items": [
    {
      "cod_producto": "SRV001",
      "unidad": "ZZ",
      "cantidad": 1,
      "valor_unitario": 1000.00,
      "descripcion": "Servicio profesional",
      "base_igv": 1000.00,
      "porcentaje_igv": 18,
      "igv": 180.00,
      "tipo_afe_igv": "10",
      "total_impuestos": 180.00,
      "valor_venta": 1000.00,
      "precio_unitario": 1180.00
    }
  ],
  "tipo_operacion": "0101",
  "tipo_doc": "01",
  "serie": "F001",
  "correlativo": "4",
  "fecha_emision": "2023-10-15",
  "tipo_moneda": "PEN",
  "mto_oper_gravadas": 1000.00,
  "mto_igv": 180.00,
  "total_impuestos": 180.00,
  "valor_venta": 1000.00,
  "sub_total": 1180.00,
  "mto_imp_venta": 1180.00,
  "retencion": {
    "porcentaje": 8,
    "monto": 94.40,
    "monto_neto": 1085.60
  },
  "legend_value": "MIL CIENTO OCHENTA Y 00/100 SOLES"
}
```

---

## Ejemplo 5: Factura con Anticipo

> **⚠️ PENDIENTE DE IMPLEMENTACIÓN**: Los campos `anticipos.*` aún no están implementados en el backend. El JSON será aceptado pero los anticipos NO se incluirán en el XML.

**¿Qué es?** Factura final que descuenta pagos adelantados (anticipos) previamente recibidos del cliente, mostrando solo el saldo pendiente.

**Cuándo usar:** Cuando se recibieron pagos adelantados y se emite la factura final descontando esos anticipos. Común en proyectos o ventas con pagos escalonados.

### Request Body
```json
{
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
      "valor_unitario": 500.00,
      "descripcion": "Producto 1",
      "base_igv": 1000.00,
      "porcentaje_igv": 18,
      "igv": 180.00,
      "tipo_afe_igv": "10",
      "total_impuestos": 180.00,
      "valor_venta": 1000.00,
      "precio_unitario": 590.00
    }
  ],
  "tipo_operacion": "0101",
  "tipo_doc": "01",
  "serie": "F001",
  "correlativo": "5",
  "fecha_emision": "2023-10-15",
  "tipo_moneda": "PEN",
  "mto_oper_gravadas": 1000.00,
  "mto_igv": 180.00,
  "total_impuestos": 180.00,
  "valor_venta": 1000.00,
  "sub_total": 1180.00,
  "mto_imp_venta": 590.00,
  "anticipos": [
    {
      "tipo_doc": "02",
      "numero": "F001-5",
      "monto": 590.00
    }
  ],
  "legend_value": "QUINIENTOS NOVENTA Y 00/100 SOLES"
}
```

---

## Campos Requeridos

### Cliente (client)
- `tipo_doc`: Tipo de documento (6=RUC)
- `num_doc`: Número de documento
- `rzn_social`: Razón social

### Items
- `cod_producto`: Código del producto
- `unidad`: Unidad de medida (NIU, KGM, etc.)
- `cantidad`: Cantidad
- `valor_unitario`: Valor unitario sin IGV
- `descripcion`: Descripción del producto
- `base_igv`: Base imponible para IGV
- `porcentaje_igv`: Porcentaje de IGV (18)
- `igv`: Monto del IGV
- `tipo_afe_igv`: Tipo de afectación IGV
- `total_impuestos`: Total de impuestos
- `valor_venta`: Valor de venta sin IGV
- `precio_unitario`: Precio unitario con IGV

### Documento
- `tipo_operacion`: Tipo de operación
- `tipo_doc`: Tipo de documento (01)
- `serie`: Serie del documento
- `correlativo`: Correlativo
- `fecha_emision`: Fecha de emisión
- `tipo_moneda`: Tipo de moneda (PEN, USD)
- `mto_oper_gravadas`: Monto operaciones gravadas
- `mto_igv`: Monto total IGV
- `total_impuestos`: Total impuestos
- `valor_venta`: Valor venta total
- `sub_total`: Subtotal
- `mto_imp_venta`: Monto importe venta
- `legend_value`: Monto en letras

## Códigos de Respuesta SUNAT
- `0`: Aceptada
- `2000-3999`: Rechazada
- Otros: Excepción

## Casos Especiales Explicados

### Descuentos
- **Estructura**: Incluir objeto `descuento` en items y array `descuentos` en documento
- **Tipos**: `"00"` (Descuento global), `"01"` (Descuento por ítem)
- **Cálculo**: Base original - descuento = valor final

### Detracción
- **Ver `06_Facturas_Detraccion.md`** para documentación completa con campos correctos
- **Tipo operación**: `"1001"` (Operación sujeta a detracción)
- **Códigos**: Ver catálogo 54 de SUNAT

### Percepción
- **Aplicable**: Ventas de bienes gravados
- **Agente**: El vendedor actúa como agente de percepción
- **Códigos**: `"01"` (Venta interna), `"02"` (Venta de combustible)
- **Porcentaje**: Generalmente 2%

### Retención
- **Aplicable**: Servicios profesionales y otros
- **Agente**: El comprador actúa como agente de retención
- **Porcentaje**: Generalmente 8%
- **Efecto**: Reduce el monto a pagar al proveedor

### Anticipos
- **Uso**: Cuando se recibieron pagos adelantados
- **Efecto**: Reduce el monto final a pagar
- **Referencia**: Debe indicar el documento del anticipo

## Códigos de Tipo de Operación

| Código | Descripción |
|--------|-------------|
| `0101` | Venta interna |
| `0102` | Venta interna - Anticipos |
| `0103` | Venta interna - Itinerante |
| `0104` | Venta interna - Otros |
| `1001` | Operación sujeta a detracción |
| `2001` | Operación sujeta a percepción |

## Códigos de Detracción Comunes

Ver tabla completa en [06_Facturas_Detraccion.md](06_Facturas_Detraccion.md).

## Notas Importantes
- La factura se emite solo a contribuyentes con RUC
- El IGV estándar en Perú es 18%
- La serie debe tener formato F### para facturas
- El correlativo debe ser secuencial
- Los casos especiales requieren configuración adicional en SUNAT
- Verificar si la empresa está autorizada para cada tipo de operación