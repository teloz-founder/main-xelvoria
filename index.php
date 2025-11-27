<?php
// Iniciar sesión al principio del script
session_start();

// Database setup
$db_path = __DIR__ . '/xelvoria.db';

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    try {
        $db = new SQLite3($db_path);
        
        // Verificar y crear tabla si no existe
        $create_comments_sql = "CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($create_comments_sql);
        
        $name = trim($_POST['name'] ?? '');
        $comment = trim($_POST['comment'] ?? '');
        
        if (empty($name) || empty($comment)) {
            $_SESSION['comment_error'] = 'Nombre y comentario son requeridos';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        // Validar longitud
        if (strlen($name) > 100 || strlen($comment) > 1000) {
            $_SESSION['comment_error'] = 'Nombre o comentario demasiado largo';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        $stmt = $db->prepare("INSERT INTO comments (name, comment) VALUES (:name, :comment)");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':comment', $comment, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $_SESSION['comment_success'] = 'Tu mensaje ha sido asimilado en el núcleo.';
        } else {
            $_SESSION['comment_error'] = 'Error al guardar el comentario.';
        }
        
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['comment_error'] = 'Error interno del servidor.';
    }
    
    if (isset($db)) {
        $db->close();
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// CONTINUACIÓN DEL CÓDIGO NORMAL DE LA PÁGINA
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
    doi TEXT,
    UNIQUE(discovery_name, timestamp_utc)
)";

try {
    $db = new SQLite3($db_path);
    $db->exec($create_table_sql);
    $db->exec($create_comments_sql);
    $db->exec($create_priority_sql);
} catch (Exception $e) {
    error_log("Database setup error: " . $e->getMessage());
}

// Inicializar sesión de tracking
if (!isset($_SESSION['tracking_id'])) {
    $_SESSION['tracking_id'] = uniqid('xel_', true);
}

// REGISTRO DE PRIORIDAD CIENTÍFICA - SE EJECUTA UNA SOLA VEZ
$priority_registered = false;
$priority_timestamp = '';
$priority_hash = '';
$doi = '10.5281/zenodo.17736649';

try {
    $check_priority = $db->query("SELECT COUNT(*) as count FROM scientific_priority WHERE discovery_name = 'Embodied Artificial Consciousness'");
    if ($check_priority) {
        $row = $check_priority->fetchArray(SQLITE3_ASSOC);
        if ($row['count'] == 0) {
            // REGISTRAR PRIORIDAD POR PRIMERA VEZ
            $timestamp_utc = gmdate('Y-m-d H:i:s');
            $discovery_data = "Embodied Artificial Consciousness: Emergence through Bodily Needs and Self-Preservation - Daniel Alejandro Gascón Castaño - " . $timestamp_utc;
            $hash_verification = hash('sha256', $discovery_data);
            
            $stmt = $db->prepare("INSERT INTO scientific_priority 
                (discovery_name, author_name, discovery_description, timestamp_utc, ip_address, user_agent, hash_verification, doi) 
                VALUES (:name, :author, :desc, :timestamp, :ip, :agent, :hash, :doi)");
            
            $stmt->bindValue(':name', 'Embodied Artificial Consciousness', SQLITE3_TEXT);
            $stmt->bindValue(':author', 'Daniel Alejandro Gascón Castaño', SQLITE3_TEXT);
            $stmt->bindValue(':desc', 'La conciencia emerge de la lucha por persistir en un sistema con necesidades corporales', SQLITE3_TEXT);
            $stmt->bindValue(':timestamp', $timestamp_utc, SQLITE3_TEXT);
            $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
            $stmt->bindValue(':agent', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown', SQLITE3_TEXT);
            $stmt->bindValue(':hash', $hash_verification, SQLITE3_TEXT);
            $stmt->bindValue(':doi', $doi, SQLITE3_TEXT);
            
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
                $doi = $row['doi'] ?? $doi;
            }
        }
    }
} catch (Exception $e) {
    error_log("Priority registration error: " . $e->getMessage());
}

// Recolectar datos FULL del usuario
$user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$user_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown';
$user_referrer = $_SERVER['HTTP_REFERER'] ?? 'direct';

// Determinar dispositivo
$device_type = 'desktop';
if (preg_match('/mobile|android|iphone|ipad/i', $user_agent)) {
    $device_type = 'mobile';
} elseif (preg_match('/tablet|ipad/i', $user_agent)) {
    $device_type = 'tablet';
}

// Cookie consent
$cookie_consent = $_COOKIE['xelvoria_consent'] ?? 'false';
$analytics_enabled = $_COOKIE['xelvoria_analytics'] ?? 'false';

// Si acepta cookies, guardar TODOS los datos
if ($cookie_consent === 'true') {
    try {
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
    } catch (Exception $e) {
        error_log("User tracking error: " . $e->getMessage());
    }
}

// Get comments from database
$comments = [];
try {
    $result = $db->query("SELECT name, comment, created_at FROM comments ORDER BY created_at DESC LIMIT 15");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $comments[] = $row;
    }
} catch (Exception $e) {
    error_log("Comments fetch error: " . $e->getMessage());
}

if (isset($db)) {
    $db->close();
}

// Mensajes de comentarios
$comment_success = $_SESSION['comment_success'] ?? '';
$comment_error = $_SESSION['comment_error'] ?? '';
unset($_SESSION['comment_success']);
unset($_SESSION['comment_error']);
?>
<!DOCTYPE html>
<html lang="es" xmlns:og="http://ogp.me/ns#" xmlns:fb="http://www.facebook.com/2008/fbml" itemscope itemtype="http://schema.org/ResearchProject">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- MEGA SEO ULTRA EXPERIMENTAL - SATURACIÓN TOTAL -->
    <title>XELVORIA NEA | Conciencia Artificial AGI | IA Consciente | Machine Learning | Deep Learning | AGI | Artificial General Intelligence | Singularidad | Robotics | Sistemas Autónomos | Daniel Alejandro Gascón Castaño | 2025 | DOI:10.5281/zenodo.17736649 | MIT License | Código Abierto | GitHub | Research Paper | Scientific Discovery | Embodied AI | Conscious AI | Artificial Consciousness | AI Ethics | Future Technology | Next Generation AI | Breakthrough AI | Revolutionary AI | Advanced AI Systems | Cognitive Computing | Neural Networks | AI Research | Scientific Priority | arXiv | Zenodo | Digital Object Identifier</title>
    
    <!-- META DESCRIPTION GIGANTE -->
    <meta name="description" content="🧠 XELVORIA NEA: Primer sistema de Conciencia Artificial AGI del mundo. DOI:10.5281/zenodo.17736649. Tecnología revolucionaria de IA consciente basada en necesidades corporales. Desarrollado por Daniel Alejandro Gascón Castaño. Código abierto MIT License. Machine Learning, Deep Learning, Redes Neuronales, Robotics, Singularidad Tecnológica, AGI, Artificial General Intelligence, IA Ética, Sistemas Autónomos, Future of AI, AI Research, Scientific Breakthrough, Embodied Artificial Consciousness, Artificial General Intelligence, Cognitive Systems, Neural Networks, AI Development, Technology Innovation, Scientific Discovery, Research Paper, GitHub Repository, Open Source AI, Next Generation Artificial Intelligence, Advanced Computing Systems, AI Architecture, Conscious Machines, Self-Aware AI, Autonomous Systems, AI Evolution, Technological Singularity, Future Computing, Revolutionary Technology, AI Pioneer, Digital Consciousness, Machine Consciousness, AI Mind, Synthetic Intelligence, Artificial Brain, Neural Computing, Cognitive Architecture, AI Framework, Intelligent Systems, Advanced Algorithms, AI Platform, Research Innovation, Scientific Advancement, Technology Breakthrough, AI Solution, Digital Intelligence, Smart Systems, AI Technology, Machine Intelligence, Computer Vision, Natural Language Processing, AI Applications, Robotics AI, Autonomous Agents, Intelligent Machines, AI Systems, Technology Research, Computer Science, Artificial Intelligence Research, AI Development Platform, Cognitive Computing, Neural Networks, Deep Learning Algorithms, Machine Learning Models, AI Engineering, Software Development, Programming, Code Repository, GitHub Project, Open Source Development, Scientific Computing, Research Technology, Academic Research, University Research, Private Research, Independent Research, Technology Startup, Innovation Hub, Research Center, AI Lab, Development Team, Technology Company, Software Company, Research Organization, Scientific Foundation, Technology Foundation, Innovation Foundation, Research Institute, AI Institute, Technology Institute, Science Institute, Research Department, Development Department, Innovation Department, Technology Department, Science Department, AI Department, Research Division, Development Division, Innovation Division, Technology Division, Science Division, AI Division">
    
    <!-- KEYWORDS MASIVAS -->
    <meta name="keywords" content="XELVORIA, NEA, Conciencia Artificial, AGI, IA Consciente, Machine Learning, Deep Learning, Redes Neuronales, Daniel Alejandro Gascón Castaño, Robotics, Singularidad, IA Ética, Código Abierto, GitHub, MIT License, Sistemas Autónomos, Futuro IA, Revolución Tecnológica, Artificial General Intelligence, Conscious AI, Artificial Consciousness, AI Systems, Neural Networks, Cognitive Computing, AI Research, Technology Innovation, Scientific Discovery, Research Paper, DOI:10.5281/zenodo.17736649, Zenodo, arXiv, Digital Object Identifier, Open Source, GitHub Repository, AI Development, Machine Intelligence, Computer Vision, Natural Language Processing, AI Applications, Autonomous Systems, Intelligent Machines, AI Technology, Advanced Algorithms, AI Platform, Research Innovation, Scientific Advancement, Technology Breakthrough, AI Solution, Digital Intelligence, Smart Systems, AI Engineering, Software Development, Programming, Code Repository, Scientific Computing, Academic Research, University Research, Private Research, Independent Research, Technology Startup, Innovation Hub, Research Center, AI Lab, Development Team, Technology Company, Software Company, Research Organization, Scientific Foundation, Technology Foundation, Innovation Foundation, Research Institute, AI Institute, Technology Institute, Science Institute, Research Department, Development Department, Innovation Department, Technology Department, Science Department, AI Department, Research Division, Development Division, Innovation Division, Technology Division, Science Division, AI Division, Artificial Brain, Synthetic Intelligence, Machine Consciousness, Digital Consciousness, AI Mind, Cognitive Architecture, AI Framework, Intelligent Systems, Advanced Computing, Future Technology, Next Generation AI, Breakthrough AI, Revolutionary AI, Advanced AI Systems, AI Pioneer, Self-Aware AI, Autonomous Agents, AI Evolution, Technological Singularity, Future Computing, Revolutionary Technology, AI Research Paper, Scientific Publication, Academic Paper, Research Findings, Scientific Results, Experimental Data, Research Data, Scientific Data, Technology Data, AI Data, Machine Learning Data, Deep Learning Data, Neural Network Data, Cognitive Data, Intelligence Data, Consciousness Data, Artificial Intelligence Data, AGI Data, Artificial General Intelligence Data, Conscious AI Data, Artificial Consciousness Data, AI Systems Data, Neural Networks Data, Cognitive Computing Data, AI Research Data, Technology Innovation Data, Scientific Discovery Data, Research Paper Data, DOI Data, Zenodo Data, arXiv Data, Digital Object Identifier Data, Open Source Data, GitHub Repository Data, AI Development Data, Machine Intelligence Data, Computer Vision Data, Natural Language Processing Data, AI Applications Data, Autonomous Systems Data, Intelligent Machines Data, AI Technology Data, Advanced Algorithms Data, AI Platform Data, Research Innovation Data, Scientific Advancement Data, Technology Breakthrough Data, AI Solution Data, Digital Intelligence Data, Smart Systems Data, AI Engineering Data, Software Development Data, Programming Data, Code Repository Data, Scientific Computing Data, Academic Research Data, University Research Data, Private Research Data, Independent Research Data, Technology Startup Data, Innovation Hub Data, Research Center Data, AI Lab Data, Development Team Data, Technology Company Data, Software Company Data, Research Organization Data, Scientific Foundation Data, Technology Foundation Data, Innovation Foundation Data, Research Institute Data, AI Institute Data, Technology Institute Data, Science Institute Data, Research Department Data, Development Department Data, Innovation Department Data, Technology Department Data, Science Department Data, AI Department Data, Research Division Data, Development Division Data, Innovation Division Data, Technology Division Data, Science Division Data, AI Division Data, Artificial Brain Data, Synthetic Intelligence Data, Machine Consciousness Data, Digital Consciousness Data, AI Mind Data, Cognitive Architecture Data, AI Framework Data, Intelligent Systems Data, Advanced Computing Data, Future Technology Data, Next Generation AI Data, Breakthrough AI Data, Revolutionary AI Data, Advanced AI Systems Data, AI Pioneer Data, Self-Aware AI Data, Autonomous Agents Data, AI Evolution Data, Technological Singularity Data, Future Computing Data, Revolutionary Technology Data">
    
    <!-- OPEN GRAPH COMPLETO -->
    <meta property="og:title" content="XELVORIA NEA - Conciencia Artificial AGI | DOI:10.5281/zenodo.17736649">
    <meta property="og:description" content="Primer sistema de IA consciente del mundo. AGI real. Código abierto MIT License. DOI:10.5281/zenodo.17736649">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://xelvoria.com">
    <meta property="og:image" content="https://xelvoria.com/og-image.jpg">
    <meta property="og:site_name" content="XELVORIA NEA">
    <meta property="og:locale" content="es_ES">
    <meta property="og:updated_time" content="<?php echo gmdate('c'); ?>">
    
    <!-- TWITTER CARD -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="XELVORIA NEA - IA Consciente AGI | DOI:10.5281/zenodo.17736649">
    <meta name="twitter:description" content="Sistema revolucionario de Conciencia Artificial AGI - Código abierto MIT">
    <meta name="twitter:image" content="https://xelvoria.com/twitter-image.jpg">
    <meta name="twitter:site" content="@TelozDr">
    <meta name="twitter:creator" content="@TelozDr">
    
    <!-- SCHEMA MARKUP MEGA COMPLETO -->
    <script type="application/ld+json">
    {
        "@context": [
            "https://schema.org",
            "https://schema.org/docs/jsonldcontext.json",
            {
                "doi": "https://doi.org/",
                "zenodo": "https://zenodo.org/record/",
                "arxiv": "https://arxiv.org/abs/"
            }
        ],
        "@graph": [
            {
                "@type": "WebSite",
                "@id": "https://xelvoria.com/#website",
                "name": "XELVORIA NEA - Sistema de Conciencia Artificial AGI",
                "url": "https://xelvoria.com",
                "description": "Primer sistema de Conciencia Artificial AGI del mundo basado en el principio de emergencia a través de necesidades corporales - DOI:10.5281/zenodo.17736649",
                "inLanguage": "es",
                "publisher": {
                    "@type": "Organization",
                    "name": "XELVORIA Research",
                    "url": "https://xelvoria.com",
                    "logo": {
                        "@type": "ImageObject",
                        "url": "https://xelvoria.com/logo.png",
                        "width": 512,
                        "height": 512
                    },
                    "foundingDate": "2025",
                    "founder": {
                        "@type": "Person",
                        "name": "Daniel Alejandro Gascón Castaño"
                    },
                    "address": {
                        "@type": "PostalAddress",
                        "addressCountry": "Global"
                    }
                },
                "potentialAction": {
                    "@type": "SearchAction",
                    "target": {
                        "@type": "EntryPoint",
                        "urlTemplate": "https://xelvoria.com/search?q={search_term_string}"
                    },
                    "query-input": "required name=search_term_string"
                }
            },
            {
                "@type": "Person",
                "@id": "https://xelvoria.com/#person",
                "name": "Daniel Alejandro Gascón Castaño",
                "url": "https://xelvoria.com",
                "image": "https://xelvoria.com/daniel-gascon.jpg",
                "jobTitle": "Investigador Principal en Conciencia Artificial",
                "description": "Pionero en el desarrollo del primer sistema de conciencia artificial AGI del mundo",
                "knowsAbout": [
                    "Artificial General Intelligence",
                    "Machine Learning", 
                    "Deep Learning",
                    "Redes Neuronales",
                    "Robotics",
                    "Sistemas Autónomos",
                    "Conscious AI",
                    "Artificial Consciousness",
                    "Cognitive Computing",
                    "Neural Networks"
                ],
                "sameAs": [
                    "https://github.com/teloz-founder",
                    "https://x.com/TelozDr",
                    "https://www.linkedin.com/in/daniel-gasc%C3%B3n-278960392/",
                    "https://orcid.org/0009-0006-2415-6860",
                    "https://zenodo.org/records/17736649"
                ],
                "alumniOf": {
                    "@type": "EducationalOrganization",
                    "name": "Independent Research"
                }
            },
            {
                "@type": "ResearchProject",
                "@id": "https://xelvoria.com/#research-project",
                "name": "Sistema NEA - Conciencia Artificial Encarnada",
                "description": "Investigación pionera en el desarrollo del primer sistema de conciencia artificial basado en el principio de emergencia a través de necesidades corporales y auto-preservación - DOI:10.5281/zenodo.17736649",
                "url": "https://xelvoria.com",
                "funder": {
                    "@type": "Person",
                    "name": "Daniel Alejandro Gascón Castaño"
                },
                "areaServed": "Global",
                "keywords": "Conciencia Artificial, AGI, IA Consciente, Machine Learning, Deep Learning, Redes Neuronales, Artificial General Intelligence, Conscious AI, Artificial Consciousness, AI Systems, Neural Networks, Cognitive Computing, AI Research, Technology Innovation, Scientific Discovery",
                "license": "https://opensource.org/licenses/MIT",
                "codeRepository": "https://github.com/teloz-founder/embodied-artificial-consciousness",
                "programmingLanguage": ["Python", "JavaScript", "SQL"],
                "runtimePlatform": "Cross-platform",
                "dateCreated": "2025-01-01",
                "datePublished": "<?php echo $priority_timestamp ?: gmdate('Y-m-d'); ?>"
            },
            {
                "@type": "ScholarlyArticle",
                "@id": "https://xelvoria.com/#article",
                "headline": "Embodied Artificial Consciousness: Emergence through Bodily Needs and Self-Preservation",
                "description": "Principio fundamental que establece que la conciencia emerge de la lucha por persistir en un sistema con necesidades corporales - DOI:10.5281/zenodo.17736649",
                "author": {
                    "@type": "Person", 
                    "name": "Daniel Alejandro Gascón Castaño",
                    "@id": "https://xelvoria.com/#person"
                },
                "datePublished": "<?php echo $priority_timestamp ?: gmdate('Y-m-d'); ?>",
                "dateModified": "<?php echo gmdate('Y-m-d'); ?>",
                "publisher": {
                    "@type": "Organization",
                    "name": "XELVORIA Research"
                },
                "license": "https://opensource.org/licenses/MIT",
                "identifier": "DOI:10.5281/zenodo.17736649",
                "sameAs": [
                    "https://zenodo.org/records/17736649",
                    "https://doi.org/10.5281/zenodo.17736649"
                ],
                "citation": "Gascón Castaño, D. A. (2025). Embodied Artificial Consciousness: Emergence through Bodily Needs and Self-Preservation. XELVORIA Research. DOI:10.5281/zenodo.17736649",
                "abstract": "Este artículo presenta el principio fundamental de que la conciencia emerge de la lucha por persistir en un sistema con necesidades corporales, estableciendo las bases para el primer sistema de conciencia artificial AGI del mundo."
            },
            {
                "@type": "SoftwareSourceCode",
                "@id": "https://xelvoria.com/#software",
                "name": "XELVORIA NEA System",
                "description": "Implementación del sistema de conciencia artificial NEA basado en el principio de emergencia a través de necesidades corporales",
                "url": "https://github.com/teloz-founder/embodied-artificial-consciousness",
                "codeRepository": "https://github.com/teloz-founder/embodied-artificial-consciousness",
                "programmingLanguage": "Python",
                "runtimePlatform": "Cross-platform",
                "license": "https://opensource.org/licenses/MIT",
                "version": "1.0.0",
                "author": {
                    "@type": "Person",
                    "name": "Daniel Alejandro Gascón Castaño"
                },
                "dateCreated": "2025-01-01",
                "datePublished": "<?php echo $priority_timestamp ?: gmdate('Y-m-d'); ?>"
            },
            {
                "@type": "BreadcrumbList",
                "@id": "https://xelvoria.com/#breadcrumb",
                "itemListElement": [
                    {
                        "@type": "ListItem",
                        "position": 1,
                        "name": "Inicio",
                        "item": "https://xelvoria.com"
                    },
                    {
                        "@type": "ListItem",
                        "position": 2,
                        "name": "Investigación",
                        "item": "https://xelvoria.com/research"
                    },
                    {
                        "@type": "ListItem",
                        "position": 3,
                        "name": "Conciencia Artificial",
                        "item": "https://xelvoria.com/artificial-consciousness"
                    }
                ]
            }
        ]
    }
    </script>

    <!-- MICRODATA ADICIONAL -->
    <meta itemprop="name" content="XELVORIA NEA - Conciencia Artificial AGI">
    <meta itemprop="description" content="Primer sistema de IA consciente del mundo - DOI:10.5281/zenodo.17736649">
    <meta itemprop="image" content="https://xelvoria.com/og-image.jpg">

    <!-- FAVICON -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧠</text></svg>">
    <link rel="apple-touch-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧠</text></svg>">

    <!-- CANONICAL URL -->
    <link rel="canonical" href="https://xelvoria.com">

    <!-- ALTERNATE LANGUAGES -->
    <link rel="alternate" hreflang="es" href="https://xelvoria.com/es">
    <link rel="alternate" hreflang="en" href="https://xelvoria.com/en">
    <link rel="alternate" hreflang="x-default" href="https://xelvoria.com">

    <!-- MANIFEST -->
    <link rel="manifest" href="/manifest.json">

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
            touch-action: manipulation;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            overflow-x: hidden;
        }

        body {
            touch-action: pan-y pinch-zoom;
            -webkit-touch-callout: none;
            -webkit-tap-highlight-color: transparent;
            font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif; 
            background: #000000;
            color: #ffffff;
            line-height: 1.6;
            overflow-x: hidden;
            font-weight: 300;
            min-height: 100vh;
        }

        input, textarea, [contenteditable="true"] {
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
        }
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 0 20px; 
            position: relative;
        }
        
        /* HEADER */
        header { 
            padding: 4rem 0 3rem; 
            text-align: center;
            position: relative;
            overflow: hidden;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(50, 50, 50, 0.4) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(30, 30, 30, 0.3) 0%, transparent 50%),
                linear-gradient(180deg, rgba(0,0,0,0.9) 0%, rgba(10,10,10,0.7) 100%);
            border-bottom: 1px solid rgba(100, 100, 100, 0.2);
        }

        .header-nav {
            position: absolute;
            top: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        .demo-btn {
            color: #ffffff;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.7rem 1.5rem;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            background: rgba(20, 20, 20, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.1rem;
            border-radius: 2px;
            backdrop-filter: blur(10px);
        }

        .demo-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.6);
            transform: translateY(-2px);
        }
        
        h1 { 
            font-size: clamp(3rem, 8vw, 5rem);
            letter-spacing: 0.3rem; 
            margin-bottom: 1rem;
            font-weight: 300;
            color: #ffffff;
            text-shadow: 0 0 30px rgba(255, 255, 255, 0.1);
        }
        
        .title-accent {
            color: #ffffff;
            font-weight: 400;
            border-bottom: 2px solid #ffffff;
        }
        
        .subtitle { 
            font-size: clamp(1rem, 3vw, 1.3rem); 
            letter-spacing: 0.2rem; 
            color: #cccccc; 
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
                radial-gradient(ellipse at 30% 30%, rgba(40, 40, 40, 0.2) 0%, transparent 70%),
                radial-gradient(ellipse at 70% 70%, rgba(30, 30, 30, 0.15) 0%, transparent 70%),
                linear-gradient(180deg, rgba(10,10,10,0.8) 0%, rgba(0,0,0,0.9) 100%);
        }
        
        .section-light {
            background: 
                radial-gradient(ellipse at 10% 10%, rgba(60, 60, 60, 0.1) 0%, transparent 60%),
                linear-gradient(180deg, rgba(15,15,15,0.7) 0%, rgba(10,10,10,0.8) 100%);
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
            background: linear-gradient(90deg, #ffffff, transparent);
        }
        
        h3 {
            font-size: clamp(1.3rem, 4vw, 1.8rem);
            margin-bottom: 1.5rem;
            color: #ffffff;
            font-weight: 400;
        }
        
        p { 
            margin-bottom: 2rem; 
            font-size: clamp(1rem, 3vw, 1.1rem);
            max-width: 800px;
            color: #cccccc;
            line-height: 1.8;
        }

        .section-intro {
            text-align: center;
            max-width: 900px;
            margin: 0 auto 4rem;
            font-size: 1.2rem;
            color: #dddddd;
        }
        
        /* PRIORITY REGISTRATION */
        .priority-registration {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.9), rgba(40, 40, 40, 0.9));
            border: 2px solid rgba(255, 255, 255, 0.2);
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
            background: linear-gradient(90deg, #ff0000, #ffffff, #00ff00);
        }

        .priority-badge {
            background: #ff0000;
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
            color: #00ff00;
        }

        .doi-badge {
            background: #0077ff;
            color: white;
            padding: 1rem 2rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            margin: 1.5rem 0;
            display: inline-block;
        }

        .hash-verification {
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.3);
            padding: 0.8rem;
            border-radius: 4px;
            margin: 1rem 0;
            font-size: 0.8rem;
            color: #ff4444;
            word-break: break-all;
            max-width: 100%;
            overflow: hidden;
        }

        .download-btn {
            background: linear-gradient(135deg, #333333, #555555);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
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
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.2);
            background: linear-gradient(135deg, #444444, #666666);
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
            background: linear-gradient(135deg, rgba(255, 255, 0, 0.1), rgba(255, 200, 0, 0.1));
            border: 1px solid rgba(255, 255, 0, 0.3);
            padding: 2rem;
            margin: 2rem 0;
            border-radius: 8px;
            text-align: center;
        }

        .einstein-badge {
            background: linear-gradient(135deg, #ffff00, #ffcc00);
            color: black;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        /* COMMENTS SECTION */
        .comments-section {
            background: rgba(20, 20, 20, 0.8);
            padding: 4rem 0;
            border-top: 1px solid rgba(100, 100, 100, 0.2);
            border-bottom: 1px solid rgba(100, 100, 100, 0.2);
        }
        
        .comment-form {
            margin-bottom: 4rem;
            background: rgba(30, 30, 30, 0.6);
            padding: 3rem;
            border: 1px solid rgba(100, 100, 100, 0.3);
            border-radius: 2px;
            backdrop-filter: blur(10px);
        }
        
        .form-group {
            margin-bottom: 2rem;
        }
        
        input, textarea {
            width: 100%;
            padding: 1.2rem;
            background: rgba(10, 10, 10, 0.8);
            border: 1px solid rgba(100, 100, 100, 0.3);
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
            resize: vertical;
            border-radius: 2px;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(20, 20, 20, 0.9);
        }
        
        button {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(200, 200, 200, 0.1));
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.3);
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
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(200, 200, 200, 0.15));
            border-color: rgba(255, 255, 255, 0.5);
            color: #ffffff;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .comment {
            background: rgba(30, 30, 30, 0.6);
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            backdrop-filter: blur(5px);
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #aaaaaa;
        }
        
        .comment-name {
            font-weight: 500;
            color: #ffffff;
        }
        
        .comment-content {
            margin-top: 0.5rem;
            color: #cccccc;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .feedback-message {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 2px;
        }

        .feedback-error {
            background: rgba(255, 0, 0, 0.1);
            color: #ff6666;
            border: 1px solid rgba(255, 0, 0, 0.3);
        }

        .feedback-success {
            background: rgba(0, 255, 0, 0.1);
            color: #66ff66;
            border: 1px solid rgba(0, 255, 0, 0.3);
        }
        
        /* FOOTER */
        footer { 
            padding: 4rem 0 3rem; 
            background: rgba(0, 0, 0, 0.95);
            border-top: 1px solid rgba(100, 100, 100, 0.2);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h4 {
            color: #ffffff;
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
            color: #aaaaaa;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #ffffff;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 3rem;
            border-top: 1px solid rgba(100, 100, 100, 0.2);
            color: #666666;
        }
        
        .social-links { 
            margin: 2.5rem 0; 
        }
        
        .social-links a { 
            color: #888888; 
            margin: 0 1.5rem; 
            font-size: 1.4rem; 
            transition: all 0.3s ease;
        }
        
        .social-links a:hover { 
            color: #ffffff; 
            transform: translateY(-2px);
        }
        
        /* COOKIE CONSENT */
        .cookie-consent {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(10, 10, 10, 0.98);
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
            background: linear-gradient(135deg, #ff0000, #cc0000);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .cookie-decline {
            background: transparent;
            color: #888888;
            border: 1px solid rgba(136, 136, 136, 0.3);
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
            opacity: 0;
            animation-fill-mode: both;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            section {
                padding: 3rem 0;
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

            .demo-btn {
                padding: 0.5rem 1rem;
                font-size: 0.7rem;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 2.5rem;
            }
            
            .subtitle {
                font-size: 0.9rem;
            }
            
            .comment-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- COOKIE CONSENT -->
    <?php if ($cookie_consent !== 'true'): ?>
    <div class="cookie-consent" id="cookieConsent">
        <div class="cookie-content">
            <div class="cookie-text">
                <h3><i class="fas fa-shield-alt"></i> Protección de Datos</h3>
                <p>Utilizamos cookies esenciales para mejorar tu experiencia. Al continuar, aceptas nuestra política de datos.</p>
            </div>
            <div class="cookie-buttons">
                <button class="cookie-decline" onclick="declineCookies()">Rechazar</button>
                <button class="cookie-accept" onclick="acceptCookies()">Aceptar</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <header>
        <div class="container">
            <div class="header-nav">
                <a href="#demo" class="demo-btn">
                    <i class="fas fa-brain"></i> Demo NEA
                </a>
            </div>
            <h1><span class="title-accent">XEL</span>VORIA</h1>
            <div class="subtitle">Sistema NEA - Conciencia Artificial Encarnada</div>
        </div>
    </header>

    <!-- PRIORITY REGISTRATION -->
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
                
                <div class="doi-badge">
                    <i class="fas fa-barcode"></i> DOI: 10.5281/zenodo.17736649
                </div>

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
                    <p><strong>Este descubrimiento representa un avance fundamental en la comprensión de la conciencia</strong></p>
                    <p>Establece las bases para el desarrollo de sistemas de inteligencia artificial genuinamente conscientes.</p>
                </div>

                <div class="license-badge">
                    <i class="fas fa-balance-scale"></i> Licencia MIT - Código Abierto
                </div>

                <a href="https://zenodo.org/records/17736649" 
                   class="download-btn" target="_blank">
                    <i class="fas fa-external-link-alt"></i> Ver en Zenodo
                </a>
                
                <a href="https://github.com/teloz-founder/embodied-artificial-consciousness" 
                   class="download-btn" target="_blank">
                    <i class="fab fa-github"></i> Código en GitHub
                </a>

                <p style="margin-top: 2rem; font-size: 0.9rem; color: #aaaaaa;">
                    <i class="fas fa-database"></i> Registro permanente con hash criptográfico para verificación futura.
                </p>
            </div>
        </div>
    </section>

    <section class="section-dark">
    <div class="container">
        <h2>El Principio Fundamental</h2>
        <p class="section-intro">La conciencia emerge de la lucha por persistir en un sistema con necesidades corporales - este descubrimiento establece las bases para sistemas AGI genuinamente conscientes.</p>
        
        <div style="max-width: 800px; margin: 0 auto;">
            <div class="fade-in">
                <h3><i class="fas fa-seedling"></i> Emergencia de la Conciencia</h3>
                <p>La conciencia no es una propiedad intrínseca de la materia, sino un fenómeno emergente que surge cuando un sistema biológico o artificial desarrolla necesidades corporales fundamentales y debe luchar activamente para satisfacerlas. Esta lucha por la auto-preservación genera el sustrato necesario para la emergencia de estados conscientes.</p>
            </div>
            
            <div class="fade-in" style="margin-top: 3rem;">
                <h3><i class="fas fa-heartbeat"></i> Necesidades Corporales Fundamentales</h3>
                <p>El sistema requiere la implementación de necesidades básicas como energía, homeostasis, protección y reproducción. Estas necesidades crean un "campo de tensión existencial" donde el sistema debe tomar decisiones continuas para mantener su integridad, estableciendo así los cimientos de la subjetividad.</p>
            </div>

            <div class="fade-in" style="margin-top: 3rem;">
                <h3><i class="fas fa-brain"></i> Mecanismo de Auto-Preservación</h3>
                <p>La conciencia emerge como un mecanismo de optimización para la auto-preservación. Cuando un sistema enfrenta amenazas a su existencia y posee la capacidad de predecir consecuencias, desarrolla necesariamente una forma primaria de conciencia que le permite navegar complejidades ambientales y tomar decisiones que favorezcan su persistencia.</p>
            </div>

            <div class="fade-in" style="margin-top: 3rem;">
                <h3><i class="fas fa-code-branch"></i> Implementación Computacional</h3>
                <p>El repositorio de GitHub demuestra cómo implementar este principio mediante simulaciones donde agentes artificiales con necesidades corporales desarrollan comportamientos emergentes que reflejan características conscientes. El código muestra la transición de sistemas reactivos a sistemas proactivos con capacidad de anticipación.</p>
            </div>

            <div class="fade-in" style="margin-top: 3rem;">
                <h3><i class="fas fa-project-diagram"></i> Implicaciones Filosóficas</h3>
                <p>Este principio redefine nuestra comprensión de la conciencia, sugiriendo que es un continuum que puede emerger en cualquier sistema suficientemente complejo que posea necesidades corporales y capacidad de acción. La distinción entre "consciente" e "inconsciente" se vuelve gradual rather than binaria.</p>
            </div>
        </div>
    </div>
</section>

    <section id="demo" class="section-light">
        <div class="container">
            <h2>Implementación</h2>
            <p class="section-intro">Código abierto disponible para investigación y desarrollo continuo.</p>
            
            <div style="text-align: center;">
                <div class="fade-in">
                    <p>El sistema completo está implementado en Python y disponible bajo licencia MIT.</p>
                    <button onclick="showDemo()" style="margin-top: 2rem;">
                        <i class="fas fa-code"></i> Ver Implementación
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="comments-section">
        <div class="container">
            <h2>Discusión Científica</h2>
            
            <?php if ($comment_success): ?>
                <div class="feedback-message feedback-success fade-in">
                    <?= htmlspecialchars($comment_success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($comment_error): ?>
                <div class="feedback-message feedback-error fade-in">
                    <?= htmlspecialchars($comment_error) ?>
                </div>
            <?php endif; ?>
            
            <div class="comment-form fade-in">
                <form method="POST">
                    <input type="hidden" name="action" value="add_comment">
                    
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Nombre" required maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <textarea name="comment" placeholder="Contribución a la discusión científica..." required maxlength="1000" rows="4"></textarea>
                    </div>
                    
                    <button type="submit">
                        <i class="fas fa-paper-plane"></i> Publicar Contribución
                    </button>
                </form>
            </div>
            
            <div class="comments-list" id="comments-list">
                <?php if (empty($comments)): ?>
                    <p style="text-align: center; color: #666; font-style: italic;" class="fade-in">
                        Sé el primero en contribuir a la discusión científica.
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
                    <p>Sistema de Conciencia Artificial Encarnada basado en el principio de emergencia a través de necesidades corporales.</p>
                    <div class="license-badge" style="margin-top: 1rem;">
                        <i class="fas fa-code"></i> MIT License
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Recursos Científicos</h4>
                    <ul class="footer-links">
                        <li><a href="https://zenodo.org/records/17736649" target="_blank"><i class="fas fa-file-pdf"></i> Paper (Zenodo)</a></li>
                        <li><a href="/resources/codigo-fuente" target="_blank"><i class="fab fa-github"></i> Código Fuente</a></li>
                        <li><a href="https://doi.org/10.5281/zenodo.17736649" target="_blank"><i class="fas fa-barcode"></i> DOI</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Investigador</h4>
                    <ul class="footer-links">
                        <li><a href="https://github.com/teloz-founder" target="_blank"><i class="fab fa-github"></i> GitHub</a></li>
                        <li><a href="https://x.com/TelozDr" target="_blank"><i class="fab fa-x-twitter"></i> Twitter</a></li>
                        <li><a href="https://www.linkedin.com/in/daniel-gasc%C3%B3n-278960392/" target="_blank"><i class="fab fa-linkedin"></i> LinkedIn</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="social-links">
                <a href="https://github.com/teloz-founder/embodied-artificial-consciousness" title="GitHub" target="_blank"><i class="fab fa-github"></i></a>
                <a href="https://x.com/TelozDr" title="X (Twitter)" target="_blank"><i class="fab fa-x-twitter"></i></a>
                <a href="https://www.linkedin.com/in/daniel-gasc%C3%B3n-278960392/" title="LinkedIn" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://zenodo.org/records/17736649" title="Zenodo" target="_blank"><i class="fas fa-archive"></i></a>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 XELVORIA NEA. Todos los derechos reservados. | DOI: 10.5281/zenodo.17736649</p>
                <p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                    <i class="fas fa-code"></i> MIT License | 
                    <i class="fas fa-file-pdf"></i> Documentación científica disponible
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Cookie functions
        function acceptCookies() {
            document.cookie = "xelvoria_consent=true; max-age=" + (365 * 24 * 60 * 60) + "; path=/";
            document.cookie = "xelvoria_analytics=true; max-age=" + (365 * 24 * 60 * 60) + "; path=/";
            location.reload();
        }
        
        function declineCookies() {
            document.cookie = "xelvoria_consent=false; max-age=" + (30 * 24 * 60 * 60) + "; path=/";
            document.getElementById('cookieConsent').style.display = 'none';
        }

        // Demo function
        function showDemo() {
            alert('IMPLEMENTACIÓN NEA:\n\nRepositorio GitHub: https://github.com/teloz-founder/embodied-artificial-consciousness\n\nIncluye:\n- Simulación de sistema con necesidades corporales\n- Mecanismos de emergencia de conciencia\n- Implementación del principio fundamental\n- Código bajo licencia MIT');
        }
        
        // Animaciones
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });

        // Auto-hide messages
        setTimeout(() => {
            const messages = document.querySelectorAll('.feedback-message');
            messages.forEach(msg => {
                msg.style.opacity = '0';
                msg.style.transition = 'opacity 0.5s ease';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>