# 🎯 Sistema de Sincronización Automática de YouTube

Este sistema sincroniza automáticamente todos los datos de YouTube para canales con tokens OAuth válidos y los almacena en la base de datos local para consultas rápidas.

## 📊 **Funcionalidades Implementadas**

### **Comando Principal**
```bash
php artisan youtube:sync-all
```

**Opciones:**
- `--force` - Forzar sincronización aunque haya sido reciente
- `--channel=ID` - Sincronizar solo un canal específico

### **Datos Sincronizados**

#### **Estadísticas de Canal:**
- Información básica (título, descripción, país)
- Estadísticas (suscriptores, videos, visualizaciones)
- Imágenes (perfil, banner)
- Métricas calculadas (promedio de vistas, engagement)

#### **Estadísticas de Videos:**
- Información básica (título, descripción, duración)
- Estadísticas (vistas, likes, comentarios)
- Métricas calculadas (engagement rate, performance score)
- Miniaturas en todas las resoluciones

## 🚀 **Configuración para Producción**

### **1. Programación Automática (Cron)**

El comando ya está configurado para ejecutarse cada 5 minutos. Para activarlo en producción:

```bash
# Editar crontab
crontab -e

# Añadir esta línea (ajustar la ruta según tu instalación)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

### **2. Con Laravel Sail (Docker)**

```bash
# En el contenedor
./vendor/bin/sail artisan schedule:work

# O programar en el host
* * * * * cd /path/to/project && ./vendor/bin/sail artisan schedule:run >> /dev/null 2>&1
```

### **3. Configuración de Frecuencia**

En `routes/console.php` puedes cambiar la frecuencia:

```php
// Cada minuto (más frecuente)
Schedule::command('youtube:sync-all')->everyMinute()

// Cada 5 minutos (recomendado)
Schedule::command('youtube:sync-all')->everyFiveMinutes()

// Cada hora
Schedule::command('youtube:sync-all')->hourly()

// Cada día a las 2:00 AM
Schedule::command('youtube:sync-all --force')->dailyAt('02:00')
```

## 📡 **Nuevos Endpoints API**

### **Datos desde Base de Datos (Rápidos)**

```bash
# Estadísticas del canal
GET /channels/{id}/stats-db

# Videos del canal
GET /channels/{id}/videos-db?limit=20&order_by=view_count&days=30

# Videos con mejor rendimiento
GET /channels/{id}/top-performing-db?metric=view_count&limit=10

# Dashboard completo
GET /channels/{id}/dashboard-db

# Sincronización manual
POST /channels/{id}/sync
```

### **Datos Directos de YouTube (Tiempo Real)**

```bash
# Información del canal
GET /channels/{id}/youtube/info

# Videos trending
GET /channels/{id}/youtube/trending?days=30

# Detalles de video específico
GET /channels/{id}/videos/{videoId}/details

# Comentarios de video
GET /channels/{id}/videos/{videoId}/comments?order=relevance
```

## 🛠️ **Uso del Sistema**

### **1. Ejecutar Sincronización Manual**

```bash
# Todos los canales
./vendor/bin/sail artisan youtube:sync-all

# Solo un canal específico
./vendor/bin/sail artisan youtube:sync-all --channel=1

# Forzar sincronización (ignorar cache)
./vendor/bin/sail artisan youtube:sync-all --force
```

### **2. Ver Logs de Sincronización**

```bash
# Logs del comando
tail -f storage/logs/youtube-sync.log

# Logs generales de Laravel
tail -f storage/logs/laravel.log
```

### **3. Consultar Datos Sincronizados**

```bash
# Estadísticas del canal desde DB
curl "http://localhost/channels/1/stats-db"

# Videos con mejor engagement
curl "http://localhost/channels/1/top-performing-db?metric=engagement_rate"

# Dashboard completo
curl "http://localhost/channels/1/dashboard-db"
```

## 📈 **Métricas Calculadas**

### **Canal:**
- **Promedio de vistas por video**
- **Videos últimos 30 días**
- **Tasa de crecimiento**

### **Videos:**
- **Engagement Rate**: (Likes + Comentarios) / Vistas × 100
- **Like Rate**: Likes / Vistas × 100
- **Comment Rate**: Comentarios / Vistas × 100
- **Vistas por día**: Desde fecha de publicación
- **Performance Score**: Puntuación 0-100 basada en engagement

## 🔧 **Configuración Avanzada**

### **Cambiar Frecuencia de Sincronización**

Edita `routes/console.php`:

```php
// Para canales muy activos (cada minuto)
Schedule::command('youtube:sync-all')
    ->everyMinute()
    ->withoutOverlapping();

// Para uso normal (cada 5 minutos)
Schedule::command('youtube:sync-all')
    ->everyFiveMinutes()
    ->withoutOverlapping();
```

### **Configurar Límites de Videos**

En `app/Console/Commands/SyncAllYoutubeData.php`, línea ~200:

```php
// Cambiar de 50 a más videos por sincronización
$videosResponse = $this->youtubeService->getChannelVideos($channel, 100);
```

### **Configurar Timeout de Sincronización**

En `routes/console.php`:

```php
Schedule::command('youtube:sync-all')
    ->everyFiveMinutes()
    ->withoutOverlapping(10) // Timeout en minutos
    ->runInBackground();
```

## 🚨 **Monitoreo y Alertas**

### **Verificar Estado de Sincronización**

```sql
-- Ver última sincronización por canal
SELECT 
    c.name,
    ycs.last_synced_at,
    ycs.sync_successful,
    ycs.sync_error
FROM channels c
LEFT JOIN youtube_channel_stats ycs ON c.id = ycs.channel_id
WHERE ycs.id IN (
    SELECT MAX(id) FROM youtube_channel_stats GROUP BY channel_id
);
```

### **Logs de Errores**

```bash
# Ver errores de sincronización
grep "ERROR" storage/logs/youtube-sync.log

# Ver resumen de sincronizaciones
grep "YouTube sync completed" storage/logs/laravel.log
```

## 📊 **Estructura de Base de Datos**

### **youtube_channel_stats**
- Estadísticas históricas del canal
- Una entrada por sincronización
- Métricas calculadas

### **youtube_video_stats**
- Estadísticas de cada video
- Se actualiza en cada sincronización
- Métricas de engagement

## 🎯 **Mejores Prácticas**

1. **Frecuencia**: Cada 5 minutos es óptimo para la mayoría de casos
2. **Monitoreo**: Revisar logs regularmente
3. **Límites**: No exceder límites de API de YouTube
4. **Backup**: Respaldar datos de estadísticas regularmente
5. **Indexación**: Las tablas ya tienen índices optimizados

## 🔍 **Troubleshooting**

### **Comando no se ejecuta**
```bash
# Verificar que el scheduler esté activo
php artisan schedule:list

# Ejecutar manualmente para debug
php artisan youtube:sync-all -vvv
```

### **Errores de OAuth**
```bash
# Verificar tokens
SELECT name, google_access_token_expires_at FROM channels WHERE google_access_token IS NOT NULL;

# Regenerar tokens desde la interfaz web
```

### **Performance**
```bash
# Ver estadísticas de base de datos
SHOW TABLE STATUS LIKE 'youtube_%';

# Optimizar tablas si es necesario
OPTIMIZE TABLE youtube_channel_stats, youtube_video_stats;
```

---

**¡El sistema está listo para funcionar automáticamente!** 🚀

Solo asegúrate de configurar el cron job y el sistema sincronizará todos los datos de YouTube automáticamente cada 5 minutos. 
