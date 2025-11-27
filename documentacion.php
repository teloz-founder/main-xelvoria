<?php
$title = "Documentación Sistema NEA";
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
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        header { text-align: center; margin-bottom: 3rem; }
        h1 { 
            color: #a0a0ff; 
            font-size: 3rem; 
            margin-bottom: 1rem;
            text-shadow: 0 0 30px rgba(120, 120, 255, 0.3);
        }
        .subtitle { color: #b0b0b0; font-size: 1.3rem; margin-bottom: 2rem; }
        
        .buttons-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }
        
        .doc-btn {
            background: rgba(30, 30, 50, 0.3);
            border: 1px solid rgba(80, 80, 120, 0.2);
            padding: 2.5rem;
            text-align: center;
            text-decoration: none;
            color: #e0e0e0;
            border-radius: 8px;
            transition: all 0.4s ease;
            backdrop-filter: blur(10px);
        }
        
        .doc-btn:hover {
            border-color: rgba(160, 160, 255, 0.4);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .btn-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #a0a0ff;
        }
        
        .btn-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #d0d0ff;
        }
        
        .back-link { 
            display: inline-block; 
            color: #8888aa; 
            text-decoration: none;
            margin-top: 3rem;
            padding: 1rem 2rem;
            border: 1px solid rgba(136, 136, 170, 0.3);
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            border-color: rgba(160, 160, 255, 0.6);
            color: #a0a0ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Documentación NEA</h1>
            <p class="subtitle">Todo lo que necesitas para dominar la Conciencia Artificial</p>
        </header>

        <div class="buttons-grid">
            <a href="https://github.com/teloz-founder/main-xelvoria" class="doc-btn" target="_blank">
                <div class="btn-icon"><i class="fab fa-github"></i></div>
                <div class="btn-title">Código Fuente</div>
                <p>Accede al repositorio completo en GitHub</p>
            </a>
            
            <a href="/resources/Embodied_Artificial_Consciousness__Emergence_through_Bodily_Needs_and_Self_Preservation.pdf" class="doc-btn" target="_blank">
                <div class="btn-icon"><i class="fas fa-file-pdf"></i></div>
                <div class="btn-title">Paper Científico</div>
                <p>Descarga el paper completo en PDF</p>
            </a>
            
            <a href="/resources/tutoriales" class="doc-btn">
                <div class="btn-icon"><i class="fas fa-graduation-cap"></i></div>
                <div class="btn-title">Video Tutorial</div>
                <p>Aprende a ejecutar el código PHP</p>
            </a>
        </div>

        <div style="text-align: center;">
            <a href="/" class="back-link">
                <i class="fas fa-arrow-left"></i> Volver al Inicio
            </a>
        </div>
    </div>
</body>
</html>