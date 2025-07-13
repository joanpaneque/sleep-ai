import os
import subprocess
import threading
import time
import re
import sys
import urllib.request
import urllib.parse
from multiprocessing import Pool
from functools import partial

# Verificar argumentos de línea de comandos
if len(sys.argv) < 3:
    print("❌ Debes proporcionar la ruta del directorio raíz y el video_id como argumentos.")
    print("Uso: python3 main.py /ruta/del/directorio VIDEO_ID [INTRO_FILENAME_OPCIONAL] [BACKGROUND_FILENAME_OPCIONAL] [BORDER_FILENAME_OPCIONAL]")
    sys.exit(1)

root_dir = sys.argv[1]
video_id = sys.argv[2]
intro_url = None
if len(sys.argv) > 3:
    intro_filename = sys.argv[3]
    intro_url = f"https://sleepai.online/storage/intros/{intro_filename}"

# Video de fondo (parámetro opcional)
video_fondo_path = "/home/private/loop.mp4"  # Valor por defecto
background_url = None
if len(sys.argv) > 4:
    background_filename = sys.argv[4]
    background_url = f"https://sleepai.online/storage/backgrounds/{background_filename}"

# Border (parámetro opcional)
border_path = "/home/private/border.png"  # Valor por defecto
border_url = None
if len(sys.argv) > 5:
    border_filename = sys.argv[5]
    border_url = f"https://sleepai.online/storage/frames/{border_filename}"

# Verificar que existan los archivos necesarios
if not os.path.exists(video_fondo_path):
    print(f"❌ No se encontró el video de fondo en: {video_fondo_path}")
    sys.exit(1)

if not os.path.exists(border_path):
    print(f"❌ No se encontró el border en: {border_path}")
    sys.exit(1)

if not os.path.exists(root_dir):
    print(f"❌ No existe el directorio raíz: {root_dir}")
    sys.exit(1)

def download_intro_video(url, output_dir):
    """Descarga el video intro desde la URL proporcionada"""
    if not url:
        return None

    try:
        print(f"🔽 Descargando video intro desde: {url}")

        # Determinar la extensión del archivo desde la URL
        parsed_url = urllib.parse.urlparse(url)
        path = parsed_url.path

        # Buscar extensión común de video
        video_extensions = ['.mp4', '.mov', '.avi', '.mkv', '.webm', '.flv']
        file_extension = None

        for ext in video_extensions:
            if path.lower().endswith(ext):
                file_extension = ext
                break

        # Si no se encuentra extensión, usar .mp4 por defecto
        if not file_extension:
            file_extension = '.mp4'

        intro_filename = f"intro{file_extension}"
        intro_path = os.path.join(output_dir, intro_filename)

        # Descargar el archivo
        urllib.request.urlretrieve(url, intro_path)

        print(f"✅ Video intro descargado: {intro_path}")
        return intro_path

    except Exception as e:
        print(f"❌ Error descargando video intro: {e}")
        return None

def download_background_video(url, output_dir):
    """Descarga el video de fondo desde la URL proporcionada"""
    if not url:
        return None

    try:
        print(f"🔽 Descargando video de fondo desde: {url}")

        # Determinar la extensión del archivo desde la URL
        parsed_url = urllib.parse.urlparse(url)
        path = parsed_url.path

        # Buscar extensión común de video
        video_extensions = ['.mp4', '.mov', '.avi', '.mkv', '.webm', '.flv']
        file_extension = None

        for ext in video_extensions:
            if path.lower().endswith(ext):
                file_extension = ext
                break

        # Si no se encuentra extensión, usar .mp4 por defecto
        if not file_extension:
            file_extension = '.mp4'

        background_filename = f"background{file_extension}"
        background_path = os.path.join(output_dir, background_filename)

        # Descargar el archivo
        urllib.request.urlretrieve(url, background_path)

        print(f"✅ Video de fondo descargado: {background_path}")
        return background_path

    except Exception as e:
        print(f"❌ Error descargando video de fondo: {e}")
        return None

def download_border_image(url, output_dir):
    """Descarga la imagen de border desde la URL proporcionada"""
    if not url:
        return None

    try:
        print(f"🔽 Descargando border desde: {url}")

        # Determinar la extensión del archivo desde la URL
        parsed_url = urllib.parse.urlparse(url)
        path = parsed_url.path

        # Buscar extensión común de imagen
        image_extensions = ['.png', '.jpg', '.jpeg', '.gif', '.bmp', '.webp']
        file_extension = None

        for ext in image_extensions:
            if path.lower().endswith(ext):
                file_extension = ext
                break

        # Si no se encuentra extensión, usar .png por defecto
        if not file_extension:
            file_extension = '.png'

        border_filename = f"border{file_extension}"
        border_path = os.path.join(output_dir, border_filename)

        # Descargar el archivo
        urllib.request.urlretrieve(url, border_path)

        print(f"✅ Border descargado: {border_path}")
        return border_path

    except Exception as e:
        print(f"❌ Error descargando border: {e}")
        return None

def get_video_duration(file_path):
    """Obtiene la duración de un archivo de video en segundos usando ffprobe"""
    try:
        result = subprocess.run([
            'ffprobe', '-i', file_path,
            '-show_entries', 'format=duration',
            '-v', 'quiet',
            '-of', 'csv=p=0'
        ], capture_output=True, text=True)

        if result.returncode == 0:
            return float(result.stdout.strip())
        else:
            return 0
    except:
        return 0

def format_time(seconds):
    """Convierte segundos a formato HH:MM:SS"""
    hours = int(seconds // 3600)
    minutes = int((seconds % 3600) // 60)
    seconds = int(seconds % 60)
    return f"{hours:02d}:{minutes:02d}:{seconds:02d}"

def get_mp3_duration(file_path):
    """Obtiene la duración de un archivo MP3 en segundos usando ffprobe"""
    try:
        result = subprocess.run([
            'ffprobe', '-i', file_path,
            '-show_entries', 'format=duration',
            '-v', 'quiet',
            '-of', 'csv=p=0'
        ], capture_output=True, text=True)

        if result.returncode == 0:
            return float(result.stdout.strip())
        else:
            return 0
    except:
        return 0

def monitor_simple_progress(progress_file, total_duration, operation_name, start_time):
    """Monitorea el progreso y muestra porcentajes que suben gradualmente"""
    last_printed_progress = 0
    while True:
        try:
            if os.path.exists(progress_file):
                with open(progress_file, 'r') as f:
                    content = f.read()

                # Buscar el tiempo actual en el archivo de progreso
                time_match = re.search(r'out_time_ms=(\d+)', content)
                if time_match:
                    current_time_ms = int(time_match.group(1))
                    current_time_s = current_time_ms / 1000000  # Convertir microsegundos a segundos

                    if total_duration > 0:
                        progress = min((current_time_s / total_duration) * 100, 100)

                        # Mostrar progreso cada 1% de incremento
                        current_progress_int = int(progress)
                        if current_progress_int > last_printed_progress and current_progress_int <= 100:
                            print(f"{operation_name}: {current_progress_int}%")
                            last_printed_progress = current_progress_int

                # Verificar si terminó
                if 'progress=end' in content:
                    if last_printed_progress < 100:
                        print(f"{operation_name}: 100%")
                    break

        except:
            pass

        time.sleep(0.3)

def monitor_global_progress(progress_file, total_duration, operation_name, start_time):
    """Monitorea el progreso global del video completo"""
    last_progress = 0
    while True:
        try:
            if os.path.exists(progress_file):
                with open(progress_file, 'r') as f:
                    content = f.read()

                # Buscar el tiempo actual en el archivo de progreso
                time_match = re.search(r'out_time_ms=(\d+)', content)
                if time_match:
                    current_time_ms = int(time_match.group(1))
                    current_time_s = current_time_ms / 1000000  # Convertir microsegundos a segundos

                    if total_duration > 0:
                        progress = min((current_time_s / total_duration) * 100, 100)

                        # Calcular tiempo transcurrido y ETA
                        elapsed_time = time.time() - start_time
                        if progress > 0:
                            eta = (elapsed_time / progress) * (100 - progress)
                            eta_str = format_time(int(eta))
                        else:
                            eta_str = "--:--:--"

                        elapsed_str = format_time(int(elapsed_time))

                        # Solo actualizar si hay cambio significativo (cada 0.1%)
                        if abs(progress - last_progress) >= 0.1:
                            print(f"\r🎬 {operation_name}: {progress:.1f}% | ⏱️  {elapsed_str} | 🏁 ETA: {eta_str}", end='', flush=True)
                            last_progress = progress

                # Verificar si terminó
                if 'progress=end' in content:
                    elapsed_time = time.time() - start_time
                    elapsed_str = format_time(int(elapsed_time))
                    print(f"\r🎬 {operation_name}: 100% | ⏱️  {elapsed_str} | ✅ Completado!")
                    break

        except:
            pass

        time.sleep(0.2)  # Actualizar más frecuentemente para mejor respuesta

def monitor_progress(progress_file, total_duration, operation_name):
    """Monitorea el progreso de ffmpeg y muestra el porcentaje (versión simple)"""
    while True:
        try:
            if os.path.exists(progress_file):
                with open(progress_file, 'r') as f:
                    content = f.read()

                # Buscar el tiempo actual en el archivo de progreso
                time_match = re.search(r'out_time_ms=(\d+)', content)
                if time_match:
                    current_time_ms = int(time_match.group(1))
                    current_time_s = current_time_ms / 1000000  # Convertir microsegundos a segundos

                    if total_duration > 0:
                        progress = min((current_time_s / total_duration) * 100, 100)
                        print(f"\r{operation_name}: {progress:.1f}% completado", end='', flush=True)

                # Verificar si terminó
                if 'progress=end' in content:
                    print(f"\r{operation_name}: 100% completado")
                    break

        except:
            pass

        time.sleep(0.5)

def concatenate_audio():
    """Concatena todos los audios usando ffmpeg con progreso y optimizaciones"""
    parent_dir = os.path.dirname(root_dir)
    progress_file = os.path.join(parent_dir, "progress_concat.txt")
    audios_file = os.path.join(parent_dir, "audios.txt")
    output_file = os.path.join(parent_dir, "concat_audio.mp3")

    try:
        # Limpiar archivo de progreso anterior
        if os.path.exists(progress_file):
            os.remove(progress_file)

        print("Concatenando audio...")

        # Usar filter_complex para normalizar y concatenar audios con diferentes formatos
        # Esto soluciona el problema de mezclar WAV, MP3 y otros formatos
        process = subprocess.Popen([
            'ffmpeg', '-threads', '0', '-f', 'concat', '-safe', '0',
            '-i', audios_file,
            '-filter_complex', '[0:0]anull[out]',  # Normalizar el stream de audio
            '-map', '[out]',
            '-c:a', 'mp3',  # Forzar codec MP3
            '-b:a', '128k',  # Bitrate consistente
            '-ar', '44100',  # Sample rate consistente
            '-ac', '2',      # Stereo consistente
            '-progress', progress_file,
            '-y',  # Sobrescribir sin preguntar
            output_file
        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)

        # Esperar a que termine
        stdout, stderr = process.communicate()

        # Limpiar archivo de progreso
        if os.path.exists(progress_file):
            os.remove(progress_file)

        if process.returncode == 0:
            print(f"\nAudio concatenado exitosamente: {output_file}")
            return True
        else:
            print(f"\nError al concatenar audio: {stderr}")
            return False
    except Exception as e:
        print(f"Error ejecutando ffmpeg: {e}")
        return False

def create_single_segment(segment_data):
    """Crea un segmento individual de video - función para paralelizar"""
    i, folder_name, duration = segment_data
    assets_path = root_dir
    folder_path = os.path.join(assets_path, folder_name)

    # Buscar la imagen en la carpeta
    image_file = None
    for file_name in os.listdir(folder_path):
        if file_name.lower().endswith(('.jpg', '.jpeg', '.png')):
            image_file = os.path.join(folder_path, file_name)
            break

    # Leer el título de la carpeta
    title_file = os.path.join(folder_path, "title.txt")
    title = ""
    if os.path.exists(title_file):
        try:
            with open(title_file, 'r', encoding='utf-8') as f:
                title = f.read().strip()
        except UnicodeDecodeError:
            # Si falla UTF-8, intentar con latin-1
            with open(title_file, 'r', encoding='latin-1') as f:
                title = f.read().strip()

        # Escapar caracteres para ffmpeg drawtext
        # Reemplazar comillas simples y dobles
        title = title.replace("'", "'\\\\\\''").replace('"', '\\"')
        # Envolver el título en comillas simples para ffmpeg
        title = f"'{title}'"

    if not image_file:
        return None

    parent_dir = os.path.dirname(root_dir)
    segment_output = os.path.join(parent_dir, f"segment_{i:02d}.mp4")

    print(f"Creando segmento {i+1} con duración {format_time(duration)}...")

    try:
        # Comando optimizado - framerate normal para loop, 1 FPS para imágenes estáticas
        result = subprocess.run([
            'ffmpeg',
            '-threads', '0', '-filter_complex_threads', '0',  # Multihilo
            '-stream_loop', '-1', '-i', video_fondo_path,       # Loop con framerate original
            '-loop', '1', '-r', '1', '-i', image_file,           # Imagen estática a 1 FPS
            '-loop', '1', '-r', '1', '-i', border_path,          # Border estático a 1 FPS
            '-filter_complex',
            f'[0:v]scale=1920:1080[bg];'
            f'[1:v]scale=iw*0.97:ih*0.97[img];'
            f'[2:v]scale=iw*0.97:ih*0.97[border];'
            f'[bg][img]overlay=(W-w)/2:(H-h)/2[temp];'
            f'[temp][border]overlay=(W-w)/2:(H-h)/2,drawtext=text={title}:fontcolor=white:fontsize=60:x=(w-text_w)/2:y=70:shadowcolor=black:shadowx=3:shadowy=3:borderw=2:bordercolor=white',
            '-t', str(duration),
            '-c:v', 'libx264', '-preset', 'ultrafast',  # Preset ultrafast
            '-pix_fmt', 'yuv420p',
            '-r', '25',  # Framerate de salida
            '-y', segment_output
        ], capture_output=True, text=True)

        if result.returncode == 0:
            print(f"✓ Segmento {i+1} completado")
            return segment_output
        else:
            print(f"Error creando segmento {i+1}: {result.stderr}")
            return None

    except Exception as e:
        print(f"Error creando segmento {i+1}: {e}")
        return None

def create_video_segments_parallel(folder_durations):
    """Crea segmentos de video en paralelo usando multiprocessing"""
    print("Creando segmentos de video en paralelo...")

    # Preparar datos para procesamiento paralelo
    segment_data = [(i, folder_name, duration) for i, (folder_name, duration) in enumerate(folder_durations)]

    # Usar multiprocessing para crear segmentos en paralelo
    with Pool(processes=4) as pool:  # Ajustar según tu CPU
        video_segments = pool.map(create_single_segment, segment_data)

    # Filtrar segmentos exitosos
    successful_segments = [seg for seg in video_segments if seg is not None]

    if len(successful_segments) != len(folder_durations):
        print(f"Error: Solo se crearon {len(successful_segments)} de {len(folder_durations)} segmentos")
        return None

    return successful_segments

def create_video_with_audio(audio_duration, folder_durations, intro_path=None, intro_duration=0):
    """Crea video con imágenes de cada carpeta y lo combina con el audio concatenado"""
    total_duration = audio_duration + intro_duration
    print(f"Creando video final con duración de {format_time(total_duration)}...")

    # Variables para archivos temporales
    parent_dir = os.path.dirname(root_dir)
    normalized_intro_path = None

    # Paso 1: Crear segmentos de video en paralelo
    video_segments = create_video_segments_parallel(folder_durations)

    if not video_segments:
        print("Error: No se pudieron crear los segmentos de video")
        return False

    # Paso 2: Normalizar el intro si existe para que tenga los mismos parámetros que los segmentos

    if intro_path and os.path.exists(intro_path):
        print("Normalizando video intro para compatibilidad...")
        normalized_intro_path = os.path.join(parent_dir, "intro_normalized.mp4")

        # Normalizar el intro con los mismos parámetros que los segmentos (CON AUDIO)
        normalize_result = subprocess.run([
            'ffmpeg', '-threads', '0',
            '-i', intro_path,
            '-c:v', 'libx264', '-preset', 'ultrafast',
            '-pix_fmt', 'yuv420p',
            '-r', '25',  # Mismo framerate que los segmentos
            '-s', '1920x1080',  # Misma resolución que los segmentos
            '-c:a', 'aac',  # Mantener y normalizar el audio de la intro
            '-y', normalized_intro_path
        ], capture_output=True, text=True)

        if normalize_result.returncode != 0:
            print(f"Error normalizando intro: {normalize_result.stderr}")
            normalized_intro_path = None
        else:
            print("✓ Intro normalizado correctamente")

    # Crear lista de concatenación para los segmentos
    segments_list = os.path.join(parent_dir, "segments.txt")
    with open(segments_list, 'w') as f:
        # Si hay intro normalizado, añadirlo primero
        if normalized_intro_path and os.path.exists(normalized_intro_path):
            f.write(f"file '{os.path.basename(normalized_intro_path)}'\n")

        for segment in video_segments:
            f.write(f"file '{os.path.basename(segment)}'\n")

    # Paso 3: Concatenar todos los segmentos de video (optimizado)
    progress_file = os.path.join(parent_dir, "progress_concat_video.txt")
    video_concatenado = os.path.join(parent_dir, "video_concatenado.mp4")

    try:
        if os.path.exists(progress_file):
            os.remove(progress_file)

        print("\nConcatenando segmentos de video...")

        # Iniciar monitoreo de progreso
        progress_thread = threading.Thread(
            target=monitor_progress,
            args=(progress_file, total_duration, "Concatenando video")
        )
        progress_thread.daemon = True
        progress_thread.start()

        process = subprocess.Popen([
            'ffmpeg', '-threads', '0',
            '-f', 'concat', '-safe', '0',
            '-i', segments_list,
            '-c:v', 'copy',  # Copy video sin recodificar
            '-an',  # Sin audio en el video concatenado
            '-progress', progress_file,
            '-y', video_concatenado
        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)

        stdout, stderr = process.communicate()

        # Limpiar archivo de progreso
        if os.path.exists(progress_file):
            os.remove(progress_file)

        if process.returncode != 0:
            print(f"\nError concatenando video: {stderr}")
            return False

        print(f"Video concatenado creado: {video_concatenado}")
    except Exception as e:
        print(f"Error concatenando video: {e}")
        return False

    # Paso 4: Combinar video concatenado con audio (CON PROGRESO SIMPLE)
    progress_file = os.path.join(parent_dir, "progress_final.txt")
    # El video final va en la carpeta render
    render_dir = os.path.join(parent_dir, "render")
    os.makedirs(render_dir, exist_ok=True)
    final_video = os.path.join(render_dir, "render.mp4")
    concat_audio = os.path.join(parent_dir, "concat_audio.mp3")

    try:
        if os.path.exists(progress_file):
            os.remove(progress_file)

        print("\n" + "="*50)
        print("CREANDO VIDEO FINAL")
        print("="*50)

        # Guardar tiempo de inicio
        start_time = time.time()

        # Iniciar monitoreo de progreso SIMPLE
        progress_thread = threading.Thread(
            target=monitor_simple_progress,
            args=(progress_file, total_duration, "Progreso", start_time)
        )
        progress_thread.daemon = True
        progress_thread.start()

        # Si hay intro, necesitamos combinar el audio de manera diferente
        if intro_duration > 0 and normalized_intro_path:
            # Extraer el audio de la intro normalizada
            intro_audio = os.path.join(parent_dir, "intro_audio.mp3")
            subprocess.run([
                'ffmpeg', '-i', normalized_intro_path,
                '-vn', '-c:a', 'mp3', '-y', intro_audio
            ], capture_output=True)

            # Concatenar el audio de la intro con el audio principal
            final_audio_list = os.path.join(parent_dir, "final_audio.txt")
            with open(final_audio_list, 'w') as f:
                f.write(f"file '{os.path.basename(intro_audio)}'\n")
                f.write(f"file '{os.path.basename(concat_audio)}'\n")

            # Crear audio final concatenado
            final_concat_audio = os.path.join(parent_dir, "final_concat_audio.mp3")
            subprocess.run([
                'ffmpeg', '-f', 'concat', '-safe', '0', '-i', final_audio_list,
                '-c', 'copy', '-y', final_concat_audio
            ], capture_output=True)

            # Usar el audio final concatenado
            audio_to_use = final_concat_audio
        else:
            audio_to_use = concat_audio

        process = subprocess.Popen([
            'ffmpeg', '-threads', '0',
            '-i', video_concatenado,
            '-i', audio_to_use,
            '-c:v', 'copy',  # Copy video sin recodificar
            '-c:a', 'aac',
            '-map', '0:v',  # Tomar video del primer input
            '-map', '1:a',  # Tomar audio del segundo input
            '-progress', progress_file,
            '-y',
            final_video
        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)

        stdout, stderr = process.communicate()

        # Limpiar archivo de progreso
        if os.path.exists(progress_file):
            os.remove(progress_file)

        if process.returncode == 0:
            print(f"\n¡VIDEO FINAL CREADO EXITOSAMENTE!")
            print(f"Ubicación: {final_video}")
            print(f"Duración: {format_time(total_duration)}")

            # Limpiar archivos temporales
            for segment in video_segments:
                if os.path.exists(segment):
                    os.remove(segment)
            if os.path.exists(segments_list):
                os.remove(segments_list)
            if os.path.exists(video_concatenado):
                os.remove(video_concatenado)

            # Limpiar archivo intro si existe
            if intro_path and os.path.exists(intro_path):
                os.remove(intro_path)

            # Limpiar intro normalizado si existe
            if normalized_intro_path and os.path.exists(normalized_intro_path):
                os.remove(normalized_intro_path)

            # Limpiar video de fondo descargado si existe
            if background_url and video_fondo_path != "/home/private/loop.mp4" and os.path.exists(video_fondo_path):
                os.remove(video_fondo_path)

            # Limpiar border descargado si existe
            if border_url and border_path != "/home/private/border.png" and os.path.exists(border_path):
                os.remove(border_path)

            # Limpiar archivos temporales de audio si se crearon
            if intro_duration > 0:
                temp_files = [
                    os.path.join(parent_dir, "intro_audio.mp3"),
                    os.path.join(parent_dir, "final_audio.txt"),
                    os.path.join(parent_dir, "final_concat_audio.mp3")
                ]
                for temp_file in temp_files:
                    if os.path.exists(temp_file):
                        os.remove(temp_file)

            return True
        else:
            print(f"\nError al combinar video y audio: {stderr}")
            return False
    except Exception as e:
        print(f"Error combinando video y audio: {e}")
        return False

def create_audio_list_and_timestamps():
    global video_fondo_path, border_path
    
    # Rutas - root_dir ya apunta a la carpeta assets
    assets_path = root_dir
    # Los archivos de salida van en el directorio padre de assets
    parent_dir = os.path.dirname(root_dir)
    audio_output = os.path.join(parent_dir, "audios.txt")
    timestamps_output = os.path.join(parent_dir, "timestamps.txt")

    # Manejar video de fondo si se proporciona
    if background_url:
        downloaded_background = download_background_video(background_url, parent_dir)
        if downloaded_background:
            video_fondo_path = downloaded_background
            print(f"🎬 Video de fondo personalizado descargado: {video_fondo_path}")
        else:
            print("⚠️ No se pudo descargar el video de fondo, usando el predeterminado")

    # Manejar border si se proporciona
    if border_url:
        downloaded_border = download_border_image(border_url, parent_dir)
        if downloaded_border:
            border_path = downloaded_border
            print(f"🖼️ Border personalizado descargado: {border_path}")
        else:
            print("⚠️ No se pudo descargar el border, usando el predeterminado")

    # Manejar video intro si se proporciona
    intro_path = None
    intro_duration = 0

    if intro_url:
        intro_path = download_intro_video(intro_url, parent_dir)
        if intro_path:
            intro_duration = get_video_duration(intro_path)
            print(f"📹 Intro descargado con duración: {format_time(intro_duration)}")
        else:
            print("⚠️ No se pudo descargar el video intro, continuando sin él")

    # Listas para almacenar información
    audio_files = []
    timestamps = []
    folder_durations = []  # Nueva lista para almacenar duraciones por carpeta
    current_time = 0  # Empezar en 0 para el primer segmento
    is_first_segment = True

    # Recorrer todas las carpetas en assets (ordenadas)
    for folder_name in sorted(os.listdir(assets_path)):
        folder_path = os.path.join(assets_path, folder_name)

        # Verificar que sea una carpeta
        if os.path.isdir(folder_path):
            # Leer el título de la carpeta
            title_file = os.path.join(folder_path, "title.txt")
            title = ""
            if os.path.exists(title_file):
                try:
                    with open(title_file, 'r', encoding='utf-8') as f:
                        title = f.read().strip()
                except UnicodeDecodeError:
                    # Si falla UTF-8, intentar con latin-1
                    with open(title_file, 'r', encoding='latin-1') as f:
                        title = f.read().strip()

                # Para los timestamps, mantener el título original (sin limpiar tanto)
                # Solo limpiar caracteres que puedan causar problemas en archivos
                title = title.replace('\n', ' ').replace('\r', ' ').replace('\t', ' ')

            # Agregar timestamp para esta sección
            if is_first_segment:
                # El primer segmento siempre empieza en 00:00:00
                timestamps.append(f"00:00:00 {title}")
                is_first_segment = False
            else:
                # Los siguientes segmentos tienen en cuenta la intro
                actual_time = current_time + intro_duration
                timestamps.append(f"{format_time(actual_time)} {title}")

            # Buscar archivos MP3 en la carpeta
            mp3_files = []
            for file_name in os.listdir(folder_path):
                if file_name.endswith('.mp3'):
                    mp3_files.append(file_name)

            # Ordenar los archivos MP3 por nombre
            mp3_files.sort()

            # Calcular duración total de esta carpeta
            folder_duration = 0

            # Procesar cada archivo MP3
            for mp3_file in mp3_files:
                # Agregar a la lista de audios (ruta relativa desde root_dir)
                relative_path = os.path.join("assets", folder_name, mp3_file)
                audio_files.append(f"file '{relative_path}'")

                # Calcular duración y sumar al tiempo total
                full_path = os.path.join(folder_path, mp3_file)
                duration = get_mp3_duration(full_path)
                current_time += duration
                folder_duration += duration

            # Agregar duración de esta carpeta a la lista
            if folder_duration > 0:
                folder_durations.append((folder_name, folder_duration))

    # Escribir archivo audios.txt
    with open(audio_output, 'w') as f:
        for audio_line in audio_files:
            f.write(audio_line + '\n')

    # Escribir archivo timestamps.txt con BOM para mejor compatibilidad
    with open(timestamps_output, 'w', encoding='utf-8-sig') as f:
        for timestamp_line in timestamps:
            f.write(timestamp_line + '\n')

    print(f"Archivo {audio_output} creado con {len(audio_files)} archivos de audio")
    print(f"Archivo {timestamps_output} creado con {len(timestamps)} timestamps")
    print(f"Duración total: {format_time(current_time)}")

    # Concatenar el audio
    if concatenate_audio():
        # Crear video final con las imágenes y el audio
        # current_time ahora es solo la duración del audio (sin intro)
        create_video_with_audio(current_time, folder_durations, intro_path, intro_duration)

if __name__ == "__main__":
    print(f"🔍 Procesando directorio: {root_dir}")
    print(f"📹 Video ID: {video_id}")
    print(f"🎬 Video de fondo: {video_fondo_path}")
    print(f"🖼️ Border: {border_path}")
    if intro_url:
        print(f"🎥 URL Intro: {intro_url}")
    else:
        print("🎥 Sin video intro")
    if background_url:
        print(f"🎬 URL Video de fondo: {background_url}")
    else:
        print("🎬 Usando video de fondo predeterminado")
    if border_url:
        print(f"🖼️ URL Border: {border_url}")
    else:
        print("🖼️ Usando border predeterminado")
    create_audio_list_and_timestamps()