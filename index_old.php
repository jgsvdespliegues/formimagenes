<?php
// Configuración de Cloudinary
define('CLOUDINARY_CLOUD_NAME', 'dyfw7jukd');
define('CLOUDINARY_API_KEY', '318147298777683');
define('CLOUDINARY_API_SECRET', 'CMbDjWIY4TDZ71g2_dvlBGVmI0k');

// Configuración de base de datos
$host = 'localhost';
$dbname = 'publicaciones_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para subir imagen a Cloudinary
function uploadToCloudinary($file) {
    $upload_url = "https://api.cloudinary.com/v1_1/" . CLOUDINARY_CLOUD_NAME . "/image/upload";
    
    $timestamp = time();
    $signature = sha1("timestamp=$timestamp" . CLOUDINARY_API_SECRET);
    
    $post_data = array(
        'file' => new CurlFile($file['tmp_name'], $file['type'], $file['name']),
        'timestamp' => $timestamp,
        'api_key' => CLOUDINARY_API_KEY,
        'signature' => $signature
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $upload_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (isset($result['secure_url'])) {
        return $result['secure_url'];
    }
    
    return null;
}

// Función para extraer ID de YouTube
function getYouTubeId($url) {
    if (empty($url)) return null;
    
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    preg_match($pattern, $url, $matches);
    
    return isset($matches[1]) ? $matches[1] : null;
}

// Procesar formulario
if ($_POST) {
    $nombre = trim($_POST['nombre'] ?? '');
    $comentario = trim($_POST['comentario'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $mapa_iframe = trim($_POST['mapa_iframe'] ?? '');
    
    $errors = [];
    
    // Validaciones
    if (empty($nombre)) {
        $errors[] = "El nombre/título es requerido";
    }
    
    if (empty($comentario)) {
        $errors[] = "El comentario es requerido";
    }
    
    if (empty($errors)) {
        // Procesar imágenes
        $imagen1_url = null;
        $imagen2_url = null;
        $imagen3_url = null;
        
        if (!empty($_FILES['imagen1']['tmp_name'])) {
            $imagen1_url = uploadToCloudinary($_FILES['imagen1']);
        }
        
        if (!empty($_FILES['imagen2']['tmp_name'])) {
            $imagen2_url = uploadToCloudinary($_FILES['imagen2']);
        }
        
        if (!empty($_FILES['imagen3']['tmp_name'])) {
            $imagen3_url = uploadToCloudinary($_FILES['imagen3']);
        }
        
        // Guardar en base de datos (guardamos la URL completa del video)
        $stmt = $pdo->prepare("INSERT INTO publicaciones (nombre, comentario, imagen1_url, imagen2_url, imagen3_url, video_url, mapa_iframe, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        if ($stmt->execute([$nombre, $comentario, $imagen1_url, $imagen2_url, $imagen3_url, $video_url, $mapa_iframe])) {
            $success = "Publicación creada exitosamente";
            // Limpiar variables para resetear el formulario
            $nombre = $comentario = $video_url = $mapa_iframe = '';
        } else {
            $errors[] = "Error al guardar la publicación";
        }
    }
}

// Obtener publicaciones
$stmt = $pdo->query("SELECT * FROM publicaciones ORDER BY fecha_creacion DESC");
$publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Publicaciones</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            min-height: 100vh;
            color: #ecf0f1;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #ecf0f1;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .form-container {
            background: rgba(52, 73, 94, 0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 40px;
            backdrop-filter: blur(10px);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #bdc3c7;
        }
        
        input[type="text"],
        input[type="file"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #5a6c7d;
            border-radius: 8px;
            background: rgba(44, 62, 80, 0.8);
            color: #ecf0f1;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus,
        input[type="file"]:focus,
        textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 10px rgba(52, 152, 219, 0.3);
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .submit-btn {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .submit-btn:hover {
            background: linear-gradient(45deg, #c0392b, #a93226);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        
        .errors {
            background: rgba(231, 76, 60, 0.9);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success {
            background: rgba(46, 204, 113, 0.9);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .publicacion {
            background: rgba(52, 73, 94, 0.9);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        
        .publicacion h3 {
            color: #3498db;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        
        .publicacion-meta {
            color: #95a5a6;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .imagenes-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .imagen-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        
        .imagen-item img:hover {
            transform: scale(1.05);
        }
        
        .video-container {
            margin: 20px 0;
        }
        
        .video-container iframe {
            width: 100%;
            height: 315px;
            border-radius: 8px;
        }
        
        .mapa-container {
            margin: 20px 0;
        }
        
        .mapa-container iframe {
            width: 100%;
            height: 300px;
            border-radius: 8px;
        }
        
        .comentario {
            background: rgba(44, 62, 80, 0.7);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #3498db;
        }
        
        .separador {
            height: 2px;
            background: linear-gradient(90deg, transparent, #3498db, transparent);
            margin: 30px 0;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            h1 {
                font-size: 2em;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .imagenes-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistema de Publicaciones</h1>
        
        <!-- Mostrar publicaciones -->
        <?php if (!empty($publicaciones)): ?>
            <?php foreach ($publicaciones as $pub): ?>
                <div class="publicacion">
                    <h3><?php echo htmlspecialchars($pub['nombre']); ?></h3>
                    <div class="publicacion-meta">
                        Publicado el: <?php echo date('d/m/Y H:i', strtotime($pub['fecha_creacion'])); ?>
                    </div>
                    
                    <?php if ($pub['imagen1_url'] || $pub['imagen2_url'] || $pub['imagen3_url']): ?>
                        <div class="imagenes-container">
                            <?php if ($pub['imagen1_url']): ?>
                                <div class="imagen-item">
                                    <img src="<?php echo htmlspecialchars($pub['imagen1_url']); ?>" alt="Imagen 1">
                                </div>
                            <?php endif; ?>
                            <?php if ($pub['imagen2_url']): ?>
                                <div class="imagen-item">
                                    <img src="<?php echo htmlspecialchars($pub['imagen2_url']); ?>" alt="Imagen 2">
                                </div>
                            <?php endif; ?>
                            <?php if ($pub['imagen3_url']): ?>
                                <div class="imagen-item">
                                    <img src="<?php echo htmlspecialchars($pub['imagen3_url']); ?>" alt="Imagen 3">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($pub['video_url']): ?>
                        <?php $youtube_id = getYouTubeId($pub['video_url']); ?>
                        <?php if ($youtube_id): ?>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($youtube_id); ?>" 
                                        frameborder="0" allowfullscreen></iframe>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($pub['mapa_iframe']): ?>
                        <div class="mapa-container">
                            <?php echo $pub['mapa_iframe']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="comentario">
                        <?php echo nl2br(htmlspecialchars($pub['comentario'])); ?>
                    </div>
                </div>
                
                <div class="separador"></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Formulario -->
        <div class="form-container">
            <h2>Nueva Publicación</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nombre">Nombre/Título <span class="required">*</span></label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="imagen1">Imagen 1 (opcional)</label>
                    <input type="file" id="imagen1" name="imagen1" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="imagen2">Imagen 2 (opcional)</label>
                    <input type="file" id="imagen2" name="imagen2" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="imagen3">Imagen 3 (opcional)</label>
                    <input type="file" id="imagen3" name="imagen3" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="video_url">URL de YouTube (opcional)</label>
                    <input type="text" id="video_url" name="video_url" value="<?php echo htmlspecialchars($video_url ?? ''); ?>" placeholder="https://www.youtube.com/watch?v=...">
                </div>
                
                <div class="form-group">
                    <label for="mapa_iframe">Mapa de Google (iframe completo - opcional)</label>
                    <textarea id="mapa_iframe" name="mapa_iframe" placeholder="Pega aquí el código iframe completo de Google Maps"><?php echo htmlspecialchars($mapa_iframe ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="comentario">Comentario <span class="required">*</span></label>
                    <textarea id="comentario" name="comentario" required><?php echo htmlspecialchars($comentario ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="submit-btn">Publicar</button>
            </form>
        </div>
    </div>
</body>
</html>