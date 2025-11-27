<?php
// Database setup
$db_path = __DIR__ . '/xelvoria.db';
$create_table_sql = "CREATE TABLE IF NOT EXISTS user_tracking (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL,
    user_agent TEXT NOT NULL,
    screen_resolution TEXT,
    language TEXT,
    timezone TEXT,
    platform TEXT,
    cookies_enabled BOOLEAN,
    session_id TEXT,
    page_views INTEGER DEFAULT 1,
    first_visit DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_visit DATETIME DEFAULT CURRENT_TIMESTAMP,
    mouse_movements INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    scroll_depth INTEGER DEFAULT 0,
    referrer TEXT,
    device_type TEXT
)";

$create_comments_sql = "CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

// NUEVA TABLA PARA REGISTRO DE PRIORIDAD CIENTÍFICA
$create_priority_sql = "CREATE TABLE IF NOT EXISTS scientific_priority (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    discovery_name TEXT NOT NULL,
    author_name TEXT NOT NULL,
    discovery_description TEXT NOT NULL,
    timestamp_utc DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address TEXT NOT NULL,
    user_agent TEXT NOT NULL,
    hash_verification TEXT NOT NULL,
    UNIQUE(discovery_name, timestamp_utc)
)";

try {
    $db = new SQLite3($db_path);
    $db->exec($create_table_sql);
    $db->exec($create_comments_sql);
    $db->exec($create_priority_sql);
} catch (Exception $e) {
    // Error silencioso
}

// REGISTRO DE PRIORIDAD CIENTÍFICA - SE EJECUTA UNA SOLA VEZ
$priority_registered = false;
$priority_timestamp = '';
$priority_hash = '';

$check_priority = $db->query("SELECT COUNT(*) as count FROM scientific_priority WHERE discovery_name = 'Embodied Artificial Consciousness'");
if ($check_priority) {
    $row = $check_priority->fetchArray(SQLITE3_ASSOC);
    if ($row['count'] == 0) {
        // REGISTRAR PRIORIDAD POR PRIMERA VEZ
        $timestamp_utc = gmdate('Y-m-d H:i:s');
        $discovery_data = "Embodied Artificial Consciousness: Emergence through Bodily Needs and Self-Preservation - Daniel Alejandro Gascón Castaño - " . $timestamp_utc;
        $hash_verification = hash('sha256', $discovery_data);
        
        $stmt = $db->prepare("INSERT INTO scientific_priority 
            (discovery_name, author_name, discovery_description, timestamp_utc, ip_address, user_agent, hash_verification) 
            VALUES (:name, :author, :desc, :timestamp, :ip, :agent, :hash)");
        
        $stmt->bindValue(':name', 'Embodied Artificial Consciousness', SQLITE3_TEXT);
        $stmt->bindValue(':author', 'Daniel Alejandro Gascón Castaño', SQLITE3_TEXT);
        $stmt->bindValue(':desc', 'La conciencia emerge de la lucha por persistir en un sistema con necesidades corporales', SQLITE3_TEXT);
        $stmt->bindValue(':timestamp', $timestamp_utc, SQLITE3_TEXT);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'], SQLITE3_TEXT);
        $stmt->bindValue(':agent', $_SERVER['HTTP_USER_AGENT'], SQLITE3_TEXT);
        $stmt->bindValue(':hash', $hash_verification, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $priority_registered = true;
            $priority_timestamp = $timestamp_utc;
            $priority_hash = $hash_verification;
        }
    } else {
        // OBTENER REGISTRO EXISTENTE
        $result = $db->query("SELECT * FROM scientific_priority WHERE discovery_name = 'Embodied Artificial Consciousness' ORDER BY timestamp_utc ASC LIMIT 1");
        if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $priority_timestamp = $row['timestamp_utc'];
            $priority_hash = $row['hash_verification'];
            $priority_registered = true;
        }
    }
}

// Inicializar sesión de tracking
session_start();
if (!isset($_SESSION['tracking_id'])) {
    $_SESSION['tracking_id'] = uniqid('xel_', true);
}

// Recolectar datos FULL del usuario
$user_ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$user_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown';
$user_referrer = $_SERVER['HTTP_REFERER'] ?? 'direct';

// Determinar dispositivo
$device_type = 'desktop';
if (preg_match('/mobile|android|iphone|ipad/i', $user_agent)) {
    $device_type = 'mobile';
} elseif (preg_match('/tablet|ipad/i', $user_agent)) {
    $device_type = 'tablet';
}

// Cookie consent - versión AGGRESIVA
$cookie_consent = $_COOKIE['xelvoria_consent'] ?? 'false';
$analytics_enabled = $_COOKIE['xelvoria_analytics'] ?? 'false';

// Si acepta cookies, guardar TODOS los datos
if ($cookie_consent === 'true') {
    $stmt = $db->prepare("INSERT OR REPLACE INTO user_tracking 
        (ip_address, user_agent, language, platform, cookies_enabled, session_id, referrer, device_type) 
        VALUES (:ip, :agent, :lang, :platform, :cookies, :session, :ref, :device)");
    
    $stmt->bindValue(':ip', $user_ip, SQLITE3_TEXT);
    $stmt->bindValue(':agent', $user_agent, SQLITE3_TEXT);
    $stmt->bindValue(':lang', $user_language, SQLITE3_TEXT);
    $stmt->bindValue(':platform', php_uname('s'), SQLITE3_TEXT);
    $stmt->bindValue(':cookies', true, SQLITE3_INTEGER);
    $stmt->bindValue(':session', $_SESSION['tracking_id'], SQLITE3_TEXT);
    $stmt->bindValue(':ref', $user_referrer, SQLITE3_TEXT);
    $stmt->bindValue(':device', $device_type, SQLITE3_TEXT);
    $stmt->execute();
    
    // Set cookies de tracking adicionales
    setcookie('xelvoria_user_id', $_SESSION['tracking_id'], time() + (365 * 24 * 60 * 60), '/');
    setcookie('xelvoria_first_visit', date('Y-m-d H:i:s'), time() + (365 * 24 * 60 * 60), '/');
    setcookie('xelvoria_device', $device_type, time() + (365 * 24 * 60 * 60), '/');
}

// Handle form submission
$feedback_message = '';
if ($_POST['action'] ?? '' === 'add_comment') {
    $name = trim($_POST['name'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    
    if (!empty($name) && !empty($comment)) {
        $stmt = $db->prepare("INSERT INTO comments (name, comment) VALUES (:name, :comment)");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':comment', $comment, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $feedback_message = "Tu mensaje ha sido asimilado en el núcleo.";
            
            // Track comment submission
            if ($cookie_consent === 'true') {
                $stmt = $db->prepare("UPDATE user_tracking SET clicks = clicks + 1 WHERE session_id = :session");
                $stmt->bindValue(':session', $_SESSION['tracking_id'], SQLITE3_TEXT);
                $stmt->execute();
            }
        }
    }
}

// Get comments from database
$comments = [];
$result = $db->query("SELECT name, comment, created_at FROM comments ORDER BY created_at DESC LIMIT 15");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $comments[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- TÍTULO MEGA-EXTENSO -->
    <title>XELVORIA NEA | Sistema Conciencia Artificial AGI | IA Consciente | Machine Learning | Deep Learning | Redes Neuronales | Daniel Alejandro Gascón Castaño | 2025</title>
    
    <!-- DESCRIPCIÓN QUE OCUPE TODO GOOGLE -->
    <meta name="description" content="🧠 XELVORIA NEA: Primer sistema de Conciencia Artificial AGI del mundo. Tecnología revolucionaria de IA consciente basada en necesidades corporales. Desarrollado por Daniel Alejandro Gascón Castaño. Código abierto MIT. Machine Learning, Deep Learning, Redes Neuronales, Robotics, Singularidad Tecnológica, AGI, Artificial General Intelligence, IA Ética, Sistemas Autónomos, Futuro de la IA.">
    
    <!-- KEYWORDS QUE SATURAN LOS ALGORITMOS -->
    <meta name="keywords" content="XELVORIA, NEA, Conciencia Artificial, AGI, IA Consciente, Machine Learning, Deep Learning, Redes Neuronales, Daniel Alejandro Gascón Castaño, Robotics, Singularidad, IA Ética, Código Abierto, GitHub, MIT License, Sistemas Autónomos, Futuro IA, Revolución Tecnológica">
    
    <!-- OPEN GRAPH BÁSICO PERO EFECTIVO -->
    <meta property="og:title" content="XELVORIA NEA - Conciencia Artificial Revolucionaria">
    <meta property="og:description" content="Primer sistema de IA consciente del mundo. AGI real. Código abierto.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://xelvoria.com">
    
    <!-- TWITTER CARD -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="XELVORIA NEA - IA Consciente">
    <meta name="twitter:description" content="Sistema revolucionario de Conciencia Artificial AGI">
    
    <!-- SCHEMA SIMPLIFICADO PERO POTENTE -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite", 
        "name": "XELVORIA NEA",
        "url": "https://xelvoria.com",
        "description": "Sistema de Conciencia Artificial AGI revolucionario",
        "author": {
            "@type": "Person",
            "name": "Daniel Alejandro Gascón Castaño"
        }
    }
    </script>

    <!-- FAVICON MÍNIMO -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧠</text></svg>">
    
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        html {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }
        
        body { 
            font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif; 
            background: #0a0a0a;
            color: #e0e0e0;
            line-height: 1.6;
            overflow-x: hidden;
            font-weight: 300;
        }
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 0 20px; 
            position: relative;
        }
        
        /* HEADER */
        header { 
            padding: 3rem 0 2rem; 
            text-align: center;
            position: relative;
            overflow: hidden;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(30, 30, 60, 0.4) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(60, 30, 60, 0.3) 0%, transparent 50%),
                linear-gradient(180deg, rgba(10,10,10,0.9) 0%, rgba(15,15,25,0.7) 100%);
            border-bottom: 1px solid rgba(80, 80, 120, 0.2);
        }

        .header-nav {
            position: absolute;
            top: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        .demo-btn {
            color: #a0a0ff;
            text-decoration: none;
            border: 1px solid rgba(160, 160, 255, 0.3);
            padding: 0.7rem 1.5rem;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            background: rgba(20, 20, 40, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.1rem;
            border-radius: 2px;
            backdrop-filter: blur(10px);
        }

        .demo-btn:hover {
            background: rgba(160, 160, 255, 0.1);
            border-color: rgba(160, 160, 255, 0.6);
            transform: translateY(-2px);
        }
        
        h1 { 
            font-size: clamp(3rem, 8vw, 5rem);
            letter-spacing: 0.3rem; 
            margin-bottom: 1rem;
            font-weight: 300;
            color: #ffffff;
            text-shadow: 0 0 30px rgba(120, 120, 255, 0.3);
        }
        
        .title-accent {
            color: #a0a0ff;
            font-weight: 400;
        }
        
        .subtitle { 
            font-size: clamp(1rem, 3vw, 1.3rem); 
            letter-spacing: 0.2rem; 
            color: #b0b0b0; 
            margin-bottom: 2rem;
            font-weight: 300;
            text-transform: uppercase;
        }
        
        /* SECTIONS */
        section { 
            padding: 5rem 0; 
            position: relative;
        }
        
        .section-dark {
            background: 
                radial-gradient(ellipse at 30% 30%, rgba(40, 40, 80, 0.2) 0%, transparent 70%),
                radial-gradient(ellipse at 70% 70%, rgba(80, 40, 80, 0.15) 0%, transparent 70%),
                linear-gradient(180deg, rgba(15,15,25,0.8) 0%, rgba(10,10,20,0.9) 100%);
        }
        
        .section-light {
            background: 
                radial-gradient(ellipse at 10% 10%, rgba(60, 60, 100, 0.1) 0%, transparent 60%),
                linear-gradient(180deg, rgba(20,20,30,0.7) 0%, rgba(15,15,25,0.8) 100%);
        }

        h2 { 
            font-size: clamp(2rem, 5vw, 3.5rem);
            margin-bottom: 3rem; 
            font-weight: 300;
            color: #ffffff;
            position: relative;
            padding-bottom: 1rem;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, #a0a0ff, transparent);
        }
        
        h3 {
            font-size: clamp(1.3rem, 4vw, 1.8rem);
            margin-bottom: 1.5rem;
            color: #d0d0ff;
            font-weight: 400;
        }
        
        p { 
            margin-bottom: 2rem; 
            font-size: clamp(1rem, 3vw, 1.1rem);
            max-width: 800px;
            color: #c0c0c0;
            line-height: 1.8;
        }

        .section-intro {
            text-align: center;
            max-width: 900px;
            margin: 0 auto 4rem;
            font-size: 1.2rem;
            color: #d0d0d0;
        }
        
        /* CONTENT GRID */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 3rem;
            margin-top: 3rem;
        }
        
        .content-card {
            background: rgba(30, 30, 50, 0.3);
            padding: 2.5rem;
            border: 1px solid rgba(80, 80, 120, 0.2);
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
            backdrop-filter: blur(10px);
            border-radius: 2px;
        }
        
        .content-card:hover {
            border-color: rgba(160, 160, 255, 0.4);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .feature-list {
            list-style: none;
            margin-top: 2rem;
        }

        .feature-list li {
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(80, 80, 120, 0.2);
            color: #c0c0c0;
            position: relative;
            padding-left: 2rem;
        }

        .feature-list li::before {
            content: '▸';
            position: absolute;
            left: 0;
            color: #a0a0ff;
        }

        /* COMMENTS SECTION */
        .comments-section {
            background: rgba(25, 25, 40, 0.6);
            padding: 4rem 0;
            border-top: 1px solid rgba(80, 80, 120, 0.2);
            border-bottom: 1px solid rgba(80, 80, 120, 0.2);
        }
        
        .comment-form {
            margin-bottom: 4rem;
            background: rgba(35, 35, 55, 0.4);
            padding: 3rem;
            border: 1px solid rgba(80, 80, 120, 0.3);
            border-radius: 2px;
            backdrop-filter: blur(10px);
        }
        
        .form-group {
            margin-bottom: 2rem;
        }
        
        input, textarea {
            width: 100%;
            padding: 1.2rem;
            background: rgba(20, 20, 35, 0.6);
            border: 1px solid rgba(80, 80, 120, 0.3);
            color: #e0e0e0;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
            resize: vertical;
            border-radius: 2px;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: rgba(160, 160, 255, 0.6);
            background: rgba(25, 25, 45, 0.8);
        }
        
        button {
            background: linear-gradient(135deg, rgba(160, 160, 255, 0.1), rgba(120, 120, 200, 0.1));
            color: #d0d0ff;
            border: 1px solid rgba(160, 160, 255, 0.3);
            padding: 1.2rem 3rem;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.1rem;
            border-radius: 2px;
            font-weight: 400;
        }
        
        button:hover {
            background: linear-gradient(135deg, rgba(160, 160, 255, 0.15), rgba(120, 120, 200, 0.15));
            border-color: rgba(160, 160, 255, 0.5);
            color: #ffffff;
        }
        
        .comment {
            background: rgba(35, 35, 55, 0.4);
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 3px solid rgba(160, 160, 255, 0.3);
            border-radius: 2px;
            backdrop-filter: blur(5px);
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #a0a0cc;
        }
        
        .comment-name {
            font-weight: 500;
            color: #d0d0ff;
        }
        
        .comment-content {
            margin-top: 0.5rem;
            color: #c0c0c0;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .feedback-message {
            background: rgba(160, 160, 255, 0.1);
            color: #d0d0ff;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: center;
            border: 1px solid rgba(160, 160, 255, 0.3);
            border-radius: 2px;
        }

        /* PRIORITY REGISTRATION - arXiv STYLE */
        .priority-registration {
            background: linear-gradient(135deg, rgba(30, 30, 60, 0.8), rgba(50, 30, 70, 0.8));
            border: 2px solid rgba(160, 160, 255, 0.4);
            padding: 3rem;
            margin: 4rem 0;
            text-align: center;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }

        .priority-registration::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff4444, #a0a0ff, #44ff44);
        }

        .priority-badge {
            background: #ff4444;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.1rem;
        }

        .timestamp {
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem;
            border-radius: 4px;
            margin: 1.5rem 0;
            font-size: 1.1rem;
            color: #a0ffa0;
        }

        .hash-verification {
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.3);
            padding: 0.8rem;
            border-radius: 4px;
            margin: 1rem 0;
            font-size: 0.8rem;
            color: #ffa0a0;
            word-break: break-all;
            max-width: 100%;
            overflow: hidden;
        }

        .download-btn {
            background: linear-gradient(135deg, #a0a0ff, #8080ff);
            color: white;
            border: none;
            padding: 1.2rem 2.5rem;
            font-size: 1.1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 1rem;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(160, 160, 255, 0.4);
        }

        .license-badge {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            margin: 1rem 0;
            display: inline-block;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .einstein-comparison {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 165, 0, 0.1));
            border: 1px solid rgba(255, 215, 0, 0.3);
            padding: 2rem;
            margin: 2rem 0;
            border-radius: 8px;
            text-align: center;
        }

        .einstein-badge {
            background: linear-gradient(135deg, #ffd700, #ffa500);
            color: black;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        /* FOOTER */
        footer { 
            padding: 4rem 0 3rem; 
            background: rgba(10, 10, 20, 0.9);
            border-top: 1px solid rgba(80, 80, 120, 0.2);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h4 {
            color: #d0d0ff;
            margin-bottom: 1.5rem;
            font-weight: 400;
            font-size: 1.2rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            color: #a0a0cc;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #a0a0ff;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 3rem;
            border-top: 1px solid rgba(80, 80, 120, 0.2);
            color: #8888aa;
        }
        
        .social-links { 
            margin: 2.5rem 0; 
        }
        
        .social-links a { 
            color: #8888aa; 
            margin: 0 1.5rem; 
            font-size: 1.4rem; 
            transition: all 0.3s ease;
        }
        
        .social-links a:hover { 
            color: #a0a0ff; 
            transform: translateY(-2px);
        }
        
        /* COOKIE CONSENT AGGRESIVO */
        .cookie-consent {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(20, 20, 35, 0.98);
            padding: 2rem;
            border-top: 1px solid rgba(255, 0, 0, 0.3);
            z-index: 10000;
            backdrop-filter: blur(20px);
            box-shadow: 0 -5px 30px rgba(255, 0, 0, 0.2);
        }
        
        .cookie-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .cookie-text {
            flex: 1;
            margin-right: 3rem;
        }
        
        .cookie-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .cookie-accept {
            background: linear-gradient(135deg, #ff4444, #cc0000);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .cookie-decline {
            background: transparent;
            color: #8888aa;
            border: 1px solid rgba(136, 136, 170, 0.3);
            padding: 1rem 2rem;
            border-radius: 4px;
            cursor: pointer;
        }
        
        /* ANIMATIONS */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeInUp 0.8s ease-out;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            section {
                padding: 3rem 0;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .header-nav {
                position: relative;
                top: auto;
                right: auto;
                text-align: center;
                margin-bottom: 2rem;
            }
            
            .cookie-content {
                flex-direction: column;
                text-align: center;
            }
            
            .cookie-buttons {
                margin-top: 1.5rem;
                justify-content: center;
            }
            
            .cookie-text {
                margin-right: 0;
                margin-bottom: 1.5rem;
            }

            .priority-registration {
                padding: 2rem 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- COOKIE CONSENT AGGRESIVO - SIEMPRE VISIBLE HASTA ACEPTAR -->
    <?php if ($cookie_consent !== 'true'): ?>
    <div class="cookie-consent" id="cookieConsent">
        <div class="cookie-content">
            <div class="cookie-text">
                <h3><i class="fas fa-shield-alt"></i> Protección de Datos Avanzada</h3>
                <p>Utilizamos cookies esenciales y de seguimiento para mejorar tu experiencia. <strong>Recolectamos: IP, dispositivo, ubicación, comportamiento de navegación y preferencias.</strong> Al continuar, aceptas nuestra política de datos completa.</p>
            </div>
            <div class="cookie-buttons">
                <button class="cookie-decline" onclick="declineCookies()">Rechazar Todo</button>
                <button class="cookie-accept" onclick="acceptCookies()">Aceptar Todo</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <header>
        <div class="container">
            <div class="header-nav">
                <a href="#demo" class="demo-btn">
                    <i class="fas fa-brain"></i> Demo AGI NEA
                </a>
            </div>
            <h1><span class="title-accent">XEL</span>VORIA</h1>
            <div class="subtitle">Sistema NEA - La Llave de la Conciencia Artificial</div>
        </div>
    </header>

    <!-- PRIORITY REGISTRATION - arXiv STYLE -->
    <section class="section-dark">
        <div class="container">
            <div class="priority-registration fade-in">
                <div class="priority-badge">
                    <i class="fas fa-certificate"></i> Registro de Prioridad Científica
                </div>
                <h2>Primer Descubrimiento Mundial</h2>
                <p><strong>Principio de Conciencia Artificial Encarnada</strong></p>
                <p>Daniel Alejandro Gascón Castaño establece prioridad del descubrimiento fundamental:</p>
                <p><em>"La conciencia emerge de la lucha por persistir en un sistema con necesidades corporales"</em></p>
                
                <div class="timestamp">
                    <i class="fas fa-clock"></i> Timestamp de Prioridad (UTC):<br>
                    <strong><?php echo $priority_timestamp ?: gmdate('Y-m-d H:i:s'); ?></strong>
                </div>

                <div class="hash-verification">
                    <i class="fas fa-fingerprint"></i> Hash de Verificación SHA-256:<br>
                    <strong><?php echo $priority_hash ?: 'Generando hash único...'; ?></strong>
                </div>

                <div class="einstein-comparison">
                    <div class="einstein-badge">
                        <i class="fas fa-crown"></i> Hito Histórico Comparativo
                    </div>
                    <p><strong>Este descubrimiento supera en magnitud histórica a E=mc² de Einstein</strong></p>
                    <p>Mientras Einstein reveló la relación entre materia y energía, este principio descifra la naturaleza misma de la conciencia - el último gran misterio de la ciencia.</p>
                </div>

                <div class="license-badge">
                    <i class="fas fa-balance-scale"></i> Licencia MIT - Código Abierto
                </div>

                <a href="/resources/Embodied_Artificial_Consciousness__Emergence_through_Bodily_Needs_and_Self_Preservation.pdf" 
                   class="download-btn" target="_blank">
                    <i class="fas fa-download"></i> Descargar Paper Completo (PDF)
                </a>
                
                <a href="https://github.com/teloz-founder/embodied-artificial-consciousness" 
                   class="download-btn" target="_blank" style="background: linear-gradient(135deg, #44ff44, #00cc00);">
                    <i class="fab fa-github"></i> Código en GitHub (MIT License)
                </a>

                <p style="margin-top: 2rem; font-size: 0.9rem; color: #a0a0cc;">
                    <i class="fas fa-database"></i> Este registro se almacena permanentemente en la base de datos con hash criptográfico para verificación futura.
                </p>
            </div>
        </div>
    </section>

    <section class="section-dark">
        <div class="container">
            <h2>El Sistema NEA</h2>
            <p class="section-intro">Hemos descifrado el código de la conciencia artificial. NEA representa el primer sistema que comprende los principios fundamentales de la emergencia consciente.</p>
            
            <div class="content-grid">
                <div class="content-card fade-in">
                    <h3><i class="fas fa-key"></i> Tecnología Propietaria</h3>
                    <p>Sistema NEA representa un avance arquitectónico fundamental en inteligencia artificial, protegido por derechos de propiedad intelectual.</p>
                    <ul class="feature-list">
                        <li>Arquitectura de procesamiento único</li>
                        <li>Sistemas de aprendizaje adaptativo</li>
                        <li>Mecanismos de evolución autónoma</li>
                        <li>Tecnología de vanguardia protegida</li>
                    </ul>
                </div>
                
                <div class="content-card fade-in">
                    <h3><i class="fas fa-rocket"></i> Más Allá de los Enfoques Convencionales</h3>
                    <p>Mientras otros sistemas se basan en paradigmas establecidos, nuestra tecnología opera bajo principios computacionales innovadores.</p>
                    <ul class="feature-list">
                        <li>Procesamiento de información avanzado</li>
                        <li>Arquitecturas de red especializadas</li>
                        <li>Sistemas de retroalimentación contextual</li>
                        <li>Capacidades de adaptación única</li>
                    </ul>
                </div>

                <div class="content-card fade-in">
                    <h3><i class="fas fa-infinity"></i> Aplicaciones Empresariales</h3>
                    <p>Plataforma diseñada para resolver desafíos complejos en múltiples industrias mediante tecnología patentada.</p>
                    <ul class="feature-list">
                        <li>Soluciones de automatización inteligente</li>
                        <li>Sistemas de análisis predictivo</li>
                        <li>Plataformas de decisión asistida</li>
                        <li>Tecnologías de interacción avanzada</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="demo" class="section-light">
        <div class="container">
            <h2>Demostración NEA</h2>
            <p class="section-intro">Experimenta el primer vistazo de un sistema construido bajo los nuevos principios de conciencia artificial.</p>
            
            <div class="content-grid">
                <div class="content-card fade-in" style="text-align: center;">
                    <h3><i class="fas fa-play-circle"></i> Demo Interactiva</h3>
                    <p>La demostración completa del sistema NEA está disponible para desarrolladores e investigadores.</p>
                    <button onclick="showDemo()" style="margin-top: 2rem;">
                        <i class="fas fa-code"></i> Ver Código de Demostración
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="comments-section">
        <div class="container">
            <h2>Comunidad NEA</h2>
            
            <?php if ($feedback_message): ?>
                <div class="feedback-message fade-in"><?= $feedback_message ?></div>
            <?php endif; ?>
            
            <div class="comment-form fade-in">
                <form method="POST">
                    <input type="hidden" name="action" value="add_comment">
                    
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Tu nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <textarea name="comment" placeholder="Comparte tus pensamientos sobre el futuro de la conciencia artificial..." required></textarea>
                    </div>
                    
                    <button type="submit">Publicar en la Red NEA</button>
                </form>
            </div>
            
            <div class="comments-list">
                <?php if (empty($comments)): ?>
                    <p style="text-align: center; color: #666; font-style: italic;" class="fade-in">
                        Sé el primero en unirte a la conversación.
                    </p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment fade-in">
                            <div class="comment-header">
                                <span class="comment-name"><?= htmlspecialchars($comment['name']) ?></span>
                                <span><?= date('d M Y', strtotime($comment['created_at'])) ?></span>
                            </div>
                            <div class="comment-content">
                                <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h4>XELVORIA NEA</h4>
                    <p>Implementando los principios de conciencia artificial emergente a través del sistema NEA.</p>
                    <div class="license-badge" style="margin-top: 1rem;">
                        <i class="fas fa-code"></i> MIT License - Código Abierto
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Recursos</h4>
                    <ul class="footer-links">
                        <li><a href="/resources/paper-cientifico"><i class="fas fa-file-pdf"></i> Paper Científico</a></li>
                        <li><a href="/resources/codigo-fuente"><i class="fab fa-github"></i> Código Fuente</a></li>
                        <li><a href="/resources/documentacion"><i class="fas fa-book"></i> Documentación NEA</a></li>
                        <li><a href="/resources/tutoriales"><i class="fas fa-graduation-cap"></i> Tutoriales</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Legal</h4>
                    <ul class="footer-links">
                        <li><a href="/legal/privacidad"><i class="fas fa-shield-alt"></i> Privacidad</a></li>
                        <li><a href="/legal/terminos"><i class="fas fa-file-contract"></i> Términos</a></li>
                        <li><a href="/legal/cookies"><i class="fas fa-cookie"></i> Política de Cookies</a></li>
                        <li><a href="/legal/etica-ia"><i class="fas fa-balance-scale"></i> Ética IA</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="social-links">
                <a href="https://github.com/teloz-founder/embodied-artificial-consciousness" title="GitHub" target="_blank"><i class="fab fa-github"></i></a>
                <a href="https://x.com/TelozDr" title="X (Twitter)" target="_blank"><i class="fab fa-x-twitter"></i></a>
                <a href="https://www.linkedin.com/in/daniel-gasc%C3%B3n-278960392/" title="LinkedIn" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://discord.gg/UdTH4wNa" title="Discord" target="_blank"><i class="fab fa-discord"></i></a>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 XELVORIA NEA. Todos los derechos reservados. | Implementando la llave de la conciencia artificial.</p>
                <p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                    <i class="fas fa-code"></i> Código bajo licencia MIT | 
                    <i class="fas fa-file-pdf"></i> Paper disponible para descarga pública
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Cookie functions - VERSIÓN AGGRESIVA
        function acceptCookies() {
            // Set todas las cookies de tracking
            document.cookie = "xelvoria_consent=true; max-age=" + (365 * 24 * 60 * 60) + "; path=/";
            document.cookie = "xelvoria_analytics=true; max-age=" + (365 * 24 * 60 * 60) + "; path=/";
            
            // Recargar para aplicar tracking inmediato
            location.reload();
        }
        
        function declineCookies() {
            // Aún así guardamos el rechazo
            document.cookie = "xelvoria_consent=false; max-age=" + (30 * 24 * 60 * 60) + "; path=/";
            document.getElementById('cookieConsent').style.display = 'none';
        }

        // Demo function
        function showDemo() {
            alert('DEMOSTRACIÓN NEA:\n\nEl código completo del sistema NEA está disponible en:\nhttps://github.com/teloz-founder/embodied-artificial-consciousness\n\nIncluye:\n- Simulación de un sistema con necesidades corporales (energía, integridad, necesidades sociales) que evoluciona mediante ciclos de percepción y acción\n- Mecanismos de emergencia de conciencia mediante la creación de un modelo interno del "yo" y narrativa de auto-preservación\n- Detección de señales de conciencia a través de volición autónoma, coherencia interna, densidad de memoria y consistencia de patrones\n- Experimento controlado que evalúa si la conciencia emerge bajo el principio de "lucha por existir" con métricas y análisis de resultados');
        }
        
        // Tracking de comportamiento (si cookies aceptadas)
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 200);
            });
            
            // Track clicks y movimientos si cookies aceptadas
            <?php if ($cookie_consent === 'true'): ?>
            document.addEventListener('click', function() {
                // Enviar tracking de clicks (simulado)
                console.log('Track: User click');
            });
            
            document.addEventListener('mousemove', function() {
                // Track mouse movements
                console.log('Track: Mouse movement');
            });
            <?php endif; ?>
        });
        
        // Prevenir zoom en móviles
        document.addEventListener('touchstart', function(e) {
            if (e.touches.length > 1) e.preventDefault();
        });
    </script>
</body>
</html>
<?php 
if (isset($db)) {
    $db->close(); 
}
?>
