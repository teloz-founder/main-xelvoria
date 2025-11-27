<?php
$title = "Tutoriales - Cómo Ejecutar PHP";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', Arial, sans-serif;
            background: #0a0a0a;
            color: #e0e0e0;
            line-height: 1.6;
        }
        .container { max-width: 1000px; margin: 0 auto; padding: 2rem; }
        header { text-align: center; margin-bottom: 3rem; }
        h1 { 
            color: #a0a0ff; 
            font-size: 3rem; 
            margin-bottom: 1rem;
            text-shadow: 0 0 30px rgba(120, 120, 255, 0.3);
        }
        .subtitle { color: #b0b0b0; font-size: 1.3rem; margin-bottom: 2rem; }
        
        .video-container {
            background: rgba(30, 30, 50, 0.3);
            border: 1px solid rgba(80, 80, 120, 0.2);
            padding: 2rem;
            border-radius: 8px;
            margin: 2rem 0;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .youtube-btn {
            display: inline-block;
            background: linear-gradient(135deg, #a0a0ff, #8080ff);
            color: white;
            text-decoration: none;
            padding: 1.5rem 3rem;
            border-radius: 8px;
            font-size: 1.2rem;
            margin: 1rem 0;
            transition: all 0.3s ease;
        }
        
        .youtube-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(160, 160, 255, 0.4);
        }
        
        .credits {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            padding: 1.5rem;
            border-radius: 8px;
            margin: 2rem 0;
            text-align: center;
        }
        
        .credit-badge {
            background: linear-gradient(135deg, #ffd700, #ffa500);
            color: black;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .back-link { 
            display: inline-block; 
            color: #8888aa; 
            text-decoration: none;
            margin-top: 2rem;
            padding: 1rem 2rem;
            border: 1px solid rgba(136, 136, 170, 0.3);
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            border-color: rgba(160, 160, 255, 0.6);
            color: #a0a0ff;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .feature {
            background: rgba(40, 40, 60, 0.3);
            padding: 1.5rem;
            border-radius: 6px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🎓 Tutorial PHP</h1>
            <p class="subtitle">Aprende a ejecutar código PHP como un profesional</p>
        </header>

        <div class="video-container">
            <h2 style="color: #d0d0ff; margin-bottom: 1.5rem;">
                <i class="fab fa-youtube" style="color: #ff4444;"></i> Video Tutorial Completo
            </h2>
            <p style="margin-bottom: 1.5rem; font-size: 1.1rem;">
                Aprende desde cero cómo ejecutar archivos PHP en tu servidor local
            </p>
            
            <a href="https://www.youtube.com/watch?v=3v5v1IniSbo" class="youtube-btn" target="_blank">
                <i class="fab fa-youtube"></i> Ver Tutorial en YouTube
            </a>
        </div>

        <div class="credits">
            <div class="credit-badge">
                <i class="fas fa-crown"></i> Créditos Especiales
            </div>
            <h3 style="color: #ffd700; margin-bottom: 1rem;">divcode en YouTube</h3>
            <p>Gracias a <strong>divcode</strong> por este excelente tutorial que enseña PHP de manera clara y profesional.</p>
            <p style="margin-top: 1rem; color: #ffa500;">
                <i class="fas fa-heart" style="color: #ff4444;"></i> Suscríbete a su canal para más contenido de calidad
            </p>
        </div>

        <div class="features">
            <div class="feature">
                <i class="fas fa-play-circle" style="color: #a0a0ff; font-size: 2rem; margin-bottom: 1rem;"></i>
                <h3>Paso a Paso</h3>
                <p>Instrucciones detalladas para ejecutar PHP</p>
            </div>
            <div class="feature">
                <i class="fas fa-code" style="color: #a0a0ff; font-size: 2rem; margin-bottom: 1rem;"></i>
                <h3>Ejemplos Prácticos</h3>
                <p>Código real que puedes probar</p>
            </div>
            <div class="feature">
                <i class="fas fa-server" style="color: #a0a0ff; font-size: 2rem; margin-bottom: 1rem;"></i>
                <h3>Configuración Servidor</h3>
                <p>Apache, XAMPP y más</p>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="/resources/documentacion" class="back-link">
                <i class="fas fa-arrow-left"></i> Volver a Documentación
            </a>
            <a href="/" class="back-link" style="margin-left: 1rem;">
                <i class="fas fa-home"></i> Ir al Inicio
            </a>
        </div>
    </div>
</body>
</html>