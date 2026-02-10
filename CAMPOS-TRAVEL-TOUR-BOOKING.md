# MAPEO DE CAMPOS - TRAVEL TOUR BOOKING

## 📋 Campos Mapeados Correctamente

El plugin usa los siguientes nombres de campo que son 100% compatibles con Travel Tour Booking:

| Campo en Checkout | Nombre en Backend | Tipo | Requerido |
|-------------------|-------------------|------|-----------|
| **Nombre** | `guest_first_name_INDEX` | text | ✅ Sí |
| **Apellido** | `guest_last_name_INDEX` | text | ✅ Sí |
| **Teléfono** | `guest_phone_INDEX` | tel | ❌ No |
| **Documento** | `guest_passport_INDEX` | text | ✅ Sí |
| **Nacionalidad** | `guest_nationality_INDEX` | select | ✅ Sí |
| **Preferencias alimenticias** | `guest_diet_INDEX` | select | ❌ No |

> **INDEX** = Número único de pasajero (ej: 101, 102, 201, 202)

## 🎯 Cómo Funciona el Índice

```
Tour 1 → Pasajero 1 → INDEX = 101
      → Pasajero 2 → INDEX = 102
      → Pasajero 3 → INDEX = 103

Tour 2 → Pasajero 1 → INDEX = 201
      → Pasajero 2 → INDEX = 202
```

## 💾 Cómo se Guardan en la Base de Datos

Los datos se guardan de 3 formas para máxima compatibilidad:

### 1. Formato Individual (Travel Tour Booking)
```
guest_first_name_1 = "Juan Pablo"
guest_last_name_1 = "Misat"
guest_passport_1 = "1234567890"
guest_nationality_1 = "CO"
guest_phone_1 = "3114928790"
guest_diet_1 = "vegetarian"
```

### 2. Formato Array Completo
```php
wiwa_passengers_data = [
    [
        'first_name' => 'Juan Pablo',
        'last_name' => 'Misat',
        'passport' => '1234567890',
        'nationality' => 'CO',
        'phone' => '3114928790',
        'diet' => 'vegetarian'
    ],
    // ... más pasajeros
]
```

### 3. Formato OVA Booking (Compatibilidad)
```php
ovabrw_passenger_info = [Array con todos los pasajeros]
```

## ✅ Validación en el Cliente

Los campos requeridos se validan con JavaScript antes de enviar:

```javascript
// Campos que DEBEN tener valor:
- guest_first_name_*
- guest_last_name_*
- guest_passport_*
- guest_nationality_*

// Campos opcionales:
- guest_phone_*
- guest_diet_*
```

## 🔧 Configuración en Travel Tour Booking

Para que funcione correctamente, asegúrate de tener estos campos HABILITADOS en:

**WooCommerce → Settings → Tour Booking → Guest Information**

✅ First Name (Enabled)
✅ Last Name (Enabled)
✅ Phone (Enabled)
✅ Passport (Enabled)
✅ Nationality (Enabled)
✅ Food preferences (Enabled)

**IMPORTANTE:** Marca todos como "Optional" en el backend de Travel Tour Booking, porque la validación se hace en el cliente con JavaScript.

## 🎨 Ejemplo Visual

```
┌─────────────────────────────────────┐
│ Pasajero 1 (Adulto)                 │
├─────────────────────────────────────┤
│ Nombre: [Juan Pablo      ] ← Req   │
│ Apellido: [Misat         ] ← Req   │
│ Teléfono: [3114928790    ] ← Opc   │
│ Documento: [1234567890   ] ← Req   │
│ Nacionalidad: [Colombia ▼] ← Req   │
│ Preferencias: [Vegetaria▼] ← Opc   │
└─────────────────────────────────────┘
```

## 🚫 Lo Que NO Debe Hacer

❌ **NO** cambiar los nombres de los campos en Travel Tour Booking
❌ **NO** marcar los campos como "Required" en el backend (la validación es en el cliente)
❌ **NO** deshabilitar campos que el plugin usa

## ✅ Lo Que SÍ Debe Hacer

✅ Mantener todos los campos habilitados en Travel Tour Booking
✅ Marcarlos como "Optional" en el backend
✅ Dejar que el plugin maneje la validación en el cliente
✅ Verificar que los datos se guarden correctamente después de una compra de prueba

## 🧪 Verificar que los Datos se Guardan

1. Haz una compra de prueba
2. Ve a **WooCommerce → Pedidos**
3. Abre el pedido
4. Baja a "Custom Fields"
5. Deberías ver:
   ```
   guest_first_name_1 = Juan Pablo
   guest_last_name_1 = Misat
   guest_passport_1 = 1234567890
   ...etc
   ```

---

Desarrollado por: Juan Pablo Misat - Connexis
Web: http://connexis.co/
