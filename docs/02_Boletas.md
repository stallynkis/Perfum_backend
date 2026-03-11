# 02 - Boletas de Venta

## Descripción
La boleta de venta es un comprobante de pago que se emite en operaciones con consumidores finales. No permite sustentar gasto o costo para efectos tributarios del adquirente. Se emite a personas naturales sin RUC o con RUC que no requieren sustentar gasto.

## Endpoint
```
POST /api/generate-invoice
Content-Type: application/json
```

## Códigos Importantes
- **tipo_doc**: `"03"` (Boleta de Venta)
- **tipo_operacion**: `"0101"` (Venta interna)
- **tipo_afe_igv**: `"10"` (Gravado - Operación Onerosa)
- **client.tipo_doc**: `"1"` (DNI) o `"0"` (Sin documento)

---

## Ejemplo 1: Boleta Básica

**¿Qué es?** Una boleta estándar emitida a un consumidor final con DNI, para productos gravados con IGV al 18%.

**Cuándo usar:** Para ventas al público en general, personas naturales con DNI, productos o servicios gravados con IGV estándar.

### Request Body
```json
{
  "client": {
    "tipo_doc": "1",
    "num_doc": "12345678",
    "rzn_social": "CLIENTE FINAL"
  },
  "items": [
    {
      "cod_producto": "P001",
      "unidad": "NIU",
      "cantidad": 1,
      "valor_unitario": 84.75,
      "descripcion": "Producto 1",
      "base_igv": 84.75,
      "porcentaje_igv": 18,
      "igv": 15.25,
      "tipo_afe_igv": "10",
      "total_impuestos": 15.25,
      "valor_venta": 84.75,
      "precio_unitario": 100.00
    }
  ],
  "tipo_operacion": "0101",
  "tipo_doc": "03",
  "serie": "B001",
  "correlativo": "1",
  "fecha_emision": "2023-10-15",
  "tipo_moneda": "PEN",
  "mto_oper_gravadas": 84.75,
  "mto_igv": 15.25,
  "total_impuestos": 15.25,
  "valor_venta": 84.75,
  "sub_total": 100.00,
  "mto_imp_venta": 100.00,
  "legend_value": "CIEN Y 00/100 SOLES"
}
```

### Response Exitosa
```json
{
  "success": true,
  "xml_path": "storage/app/sunat/xmls/20000000001-03-B001-1.xml",
  "cdr_path": "storage/app/sunat/cdrs/R-20000000001-03-B001-1.zip",
  "response": {
    "code": 0,
    "status": "ACEPTADA",
    "description": "La Boleta numero B001-1, ha sido aceptada",
    "notes": []
  }
}
```

---

## Ejemplo 2: Boleta sin Documento de Cliente

**¿Qué es?** Boleta emitida a un cliente que no proporciona documento de identidad o no lo requiere por el monto de la venta.

**Cuándo usar:** Para ventas menores a S/ 700.00 donde no es obligatorio identificar al cliente, o cuando el cliente no desea proporcionar sus datos.

### Request Body
```json
{
  "client": {
    "tipo_doc": "0",
    "num_doc": "-",
    "rzn_social": "CLIENTE SIN DOCUMENTO"
  },
  "items": [
    {
      "cod_producto": "P002",
      "unidad": "NIU",
      "cantidad": 2,
      "valor_unitario": 25.42,
      "descripcion": "Producto económico",
      "base_igv": 50.84,
      "porcentaje_igv": 18,
      "igv": 9.15,
      "tipo_afe_igv": "10",
      "total_impuestos": 9.15,
      "valor_venta": 50.84,
      "precio_unitario": 29.99
    }
  ],
  "tipo_operacion": "0101",
  "tipo_doc": "03",
  "serie": "B001",
  "correlativo": "2",
  "fecha_emision": "2023-10-15",
  "tipo_moneda": "PEN",
  "mto_oper_gravadas": 50.84,
  "mto_igv": 9.15,
  "total_impuestos": 9.15,
  "valor_venta": 50.84,
  "sub_total": 59.99,
  "mto_imp_venta": 59.99,
  "legend_value": "CINCUENTA Y NUEVE Y 99/100 SOLES"
}
```

---

## Ejemplo 3: Boleta con Productos Exonerados

**¿Qué es?** Boleta para productos o servicios que están exonerados del IGV según la legislación tributaria peruana.

**Cuándo usar:** Para venta de productos exonerados como medicinas, libros, servicios educativos, productos de la canasta básica, etc.

### Request Body
```json
{
  "client": {
    "tipo_doc": "1",
    "num_doc": "87654321",
    "rzn_social": "JUAN PEREZ LOPEZ"
  },
  "items": [
    {
      "cod_producto": "P003",
      "unidad": "NIU",
      "cantidad": 1,
      "valor_unitario": 50.00,
      "descripcion": "Producto exonerado",
      "base_igv": 0.00,
      "porcentaje_igv": 0,
      "igv": 0.00,
      "tipo_afe_igv": "20",
      "total_impuestos": 0.00,
      "valor_venta": 50.00,
      "precio_unitario": 50.00
    }
  ],
  "tipo_operacion": "0101",
  "tipo_doc": "03",
  "serie": "B001",
  "correlativo": "3",
  "fecha_emision": "2023-10-15",
  "tipo_moneda": "PEN",
  "mto_oper_gravadas": 0.00,
  "mto_oper_exoneradas": 50.00,
  "mto_igv": 0.00,
  "total_impuestos": 0.00,
  "valor_venta": 50.00,
  "sub_total": 50.00,
  "mto_imp_venta": 50.00,
  "legend_value": "CINCUENTA Y 00/100 SOLES"
}
```

---

## Campos Específicos para Boletas

### Cliente (client)
- `tipo_doc`: 
  - `"1"` (DNI)
  - `"0"` (Sin documento)
  - `"4"` (Carnet de extranjería)
  - `"7"` (Pasaporte)
- `num_doc`: Número de documento o "-" si no tiene
- `rzn_social`: Nombre del cliente

### Tipos de Afectación IGV
- `"10"`: Gravado - Operación Onerosa
- `"20"`: Exonerado - Operación Onerosa
- `"30"`: Inafecto - Operación Onerosa
- `"40"`: Exportación

## Diferencias con Facturas
1. **Cliente**: Puede ser persona natural sin RUC
2. **Serie**: Debe comenzar con "B" (B001, B002, etc.)
3. **Sustento tributario**: No permite sustentar gastos
4. **Resumen diario**: Las boletas deben incluirse en resumen diario

## Notas Importantes
- Las boletas deben enviarse en resumen diario a SUNAT
- No es necesario envío individual como las facturas
- El monto máximo para boletas sin identificar al cliente es S/ 700.00
- Para montos mayores a S/ 700.00 es obligatorio identificar al cliente