<?php
$title = "Código Fuente - GitHub";
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
        .container { max-width: 800px; margin: 0 auto; padding: 2rem; text-align: center; }
        h1 { 
            color: #a0a0ff; 
            font-size: 3rem; 
            margin-bottom: 2rem;
            text-shadow: 0 0 30px rgba(120, 120, 255, 0.3);
        }
        
        .github-card {
            background: rgba(30, 30, 50, 0.3);
            border: 1px solid rgba(80, 80, 120, 0.2);
            padding: 3rem;
            border-radius: 8px;
            margin: 2rem 0;
            backdrop-filter: blur(10px);
        }
        
        .github-icon {
            font-size: 4rem;
            color: #a0a0ff;
            margin-bottom: 1.5rem;
        }
        
        .github-btn {
            display: inline-block;
            background: linear-gradient(135deg, #44ff44, #00cc00);
            color: white;
            text-decoration: none;
            padding: 1.5rem 3rem;
            border-radius: 8px;
            font-size: 1.3rem;
            margin: 1rem 0;
            transition: all 0.3s ease;
        }
        
        .github-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(68, 255, 68, 0.4);
        }
        
        .license-badge {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
            border-radius: 4px;
            margin: 1.5rem 0;
            display: inline-block;
            border: 1px solid rgba(255, 255, 255, 0.2);
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
    </style>
</head>
<body>
    <div class="container">
        <h1>💻 Código Fuente</h1>
        
        <div class="github-card">
            <div class="github-icon">
                <i class="fab fa-github"></i>
            </div>
            <h2 style="color: #d0d0ff; margin-bottom: 1rem;">Repositorio Oficial</h2>
            <p style="margin-bottom: 2rem; font-size: 1.1rem;">
                Todo el código del Sistema NEA disponible en GitHub
            </p>
            
            <a href="https://github.com/teloz-founder/main-xelvoria" class="github-btn" target="_blank">
                <i class="fab fa-github"></i> Ver en GitHub
            </a>
            
            <div class="license-badge">
                <i class="fas fa-balance-scale"></i> Licencia MIT - Código Abierto
            </div>
        </div>

        <div style="margin-top: 2rem;">
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