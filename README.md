# Smartz Monitoreo NOC - MikroHN Core

Aplicación independiente y modular de **Monitoreo en Tiempo Real para Routers MikroTik (RouterOS v6 & v7)**, desarrollada en **Laravel 11**, **Alpine.js**, **Chart.js** y **Sockets Nativos RouterOS API**.

---

## 🛡️ Regla de Oro de Aislamiento
- **Cero modificaciones en MikroHN**: El proyecto principal `mikrohn` permanece intacto.
- **Persistencia Compartida**: Conexión directa en modo lectura a la base de datos MariaDB (`mikrohn`) para consumir usuarios (`users`) y credenciales de routers (`routers`).
- **Autenticación Nativa**: Inicio de sesión automático utilizando los mismos usuarios y hashes Bcrypt existentes de MikroHN.

---

## 🚀 Despliegue con Docker

El sistema corre de forma completamente autónoma en su propio stack Docker:
- **Puerto Web**: `http://localhost:8668`
- **Contenedores**:
  - `smartzmonitoreo_app`: PHP 8.4 FPM con soporte nativo de `sockets`, `pdo_mysql`, `opcache`, `zip`, `bcmath`.
  - `smartzmonitoreo_web`: Servidor Nginx 1.25 Alpine de alta velocidad.
- **Red**: Conectado a la red Docker externa `proyectos_default` para enlazar directamente con `servidor_db:3306`.

### Comandos de Control:

```bash
# Iniciar los servicios en segundo plano
docker compose up -d

# Ver registros en vivo
docker compose logs -f

# Detener los servicios
docker compose down
```

---

## 🎯 Características Principales

1. **Dashboard NOC en Tiempo Real**:
   - Conmutación instantánea entre Routers MikroTik sin recargar pantalla.
   - Barra Bento de métricas:
     1. **Estado del Servicio**: Conexión activa, latencia (ping) en ms y jitter.
     2. **Routers en Red**: Conteo de equipos sincronizados desde MikroHN.
     3. **Puertos y Servicios**: Estado en vivo de HTTP (80), HTTPS (443), WinBox (8291), API (8728) y SSH (22).
     4. **Uso Global de Recursos**: Medidor circular reactivo de CPU % y barra de progreso de Memoria RAM.
2. **Gráficos de Tráfico Fluidos (Estilo Observium / WinBox)**:
   - Medición por socket de `/interface/monitor-traffic` con selector interactivo de interfaces (`ether1`, `ether2`, `sfp1`, `bridge`, etc.).
   - Visualización de tasas de RX (Descarga) y TX (Subida) en Mbps, Kbps y pps.
   - Indicadores de picos máximos en sesión.
3. **Registro de Eventos en Vivo (Log MikroTik)**:
   - Búfer de eventos directo desde `/log/print`.
   - Filtros por categoría (Sistema, Errores/Críticos, Advertencias, Accesos/Login).
   - Buscador en tiempo real de cadenas en logs.
4. **Hardware & Sensores de Salud**:
   - Voltaje (V) y Temperatura de placa/procesador (°C) desde `/system/health`.
   - Uptime y arquitectura RouterBOARD.
5. **Cero Alerts Nativos JS & Sistema de Sonido Web Audio API**:
   - Notificaciones y toasts flotantes translúcidos con **SweetAlert2**.
   - Pulso visual de alerta de borde neón si el CPU supera el umbral crítico (> 85%).
   - Tono de alarma sintetizado por software con Web Audio API (sin archivos de audio externos).
