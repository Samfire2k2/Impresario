<?php
include 'includes/config.php';
include 'includes/functions.php';
include 'includes/writer-functions.php';

requireLogin();

$userId = getCurrentUserId();
$projectId = $_GET['id'] ?? null;

if (!$projectId) {
    die('Projet non spécifié');
}

$project = getProject($pdo, $projectId, $userId);
if (!$project) {
    die('Accès refusé');
}

// Prepare export data
$data = prepareProjectForExport($pdo, $projectId);

if (!$data) {
    die('Erreur: impossible de préparer les données d\'export');
}

// Generate HTML content for PDF
$htmlContent = '';

// Title page
$htmlContent .= '<div style="page-break-after: always; text-align: center; padding: 100px 20px;">
    <h1 style="font-size: 36px; margin-bottom: 20px;">' . escapeHtml($project['title']) . '</h1>
    <p style="font-size: 18px; color: #666; margin-bottom: 40px;">' . escapeHtml($project['synopsis'] ?? $project['description'] ?? '') . '</p>
    <p style="font-size: 14px; color: #999; margin-top: 100px;">Par ' . escapeHtml(getCurrentUsername()) . '</p>
    <p style="font-size: 12px; color: #999; margin-top: 20px;">' . date('d/m/Y') . '</p>
</div>';

// Table of contents
$htmlContent .= '<div style="page-break-after: always;">
    <h2 style="font-size: 24px; margin-bottom: 30px;">Table des matières</h2>
    <ol style="font-size: 14px; line-height: 1.8;">';

if ($data['intrigues']) {
    foreach ($data['intrigues'] as $intrigue) {
        $htmlContent .= '<li style="margin-bottom: 12px;">' . escapeHtml($intrigue['title']) . '</li>';
    }
}

$htmlContent .= '</ol></div>';

// Content
if ($data['intrigues']) {
    foreach ($data['intrigues'] as $intrigueIndex => $intrigue) {
        $htmlContent .= '<div style="page-break-before: always;">
            <h2 style="font-size: 24px; margin-top: 0; margin-bottom: 20px; color: #2c3e50;">' 
            . ($intrigueIndex + 1) . '. ' . escapeHtml($intrigue['title']) . '</h2>';
        
        if ($intrigue['description']) {
            $htmlContent .= '<p style="font-style: italic; color: #666; margin-bottom: 20px; line-height: 1.6;">' 
            . nl2br(escapeHtml($intrigue['description'])) . '</p>';
        }
        
        // Scenes
        if (isset($data['elements'][$intrigue['id']]) && $data['elements'][$intrigue['id']]) {
            foreach ($data['elements'][$intrigue['id']] as $element) {
                $htmlContent .= '<div style="margin-top: 30px; margin-bottom: 20px;">
                    <h3 style="font-size: 18px; margin-bottom: 12px; color: #34495e;">' 
                    . escapeHtml($element['title']) . '</h3>';
                
                if ($element['pov_character'] || $element['location']) {
                    $htmlContent .= '<p style="font-size: 12px; color: #7f8c8d; margin-bottom: 12px;"><em>';
                    if ($element['pov_character']) {
                        $htmlContent .= 'POV: ' . escapeHtml($element['pov_character']) . ' | ';
                    }
                    if ($element['location']) {
                        $htmlContent .= 'Lieux: ' . escapeHtml($element['location']);
                    }
                    $htmlContent .= '</em></p>';
                }
                
                if ($element['content']) {
                    $htmlContent .= '<div style="text-align: justify; line-height: 1.8; font-size: 14px; color: #2c3e50;">' 
                    . nl2br(escapeHtml($element['content'])) . '</div>';
                }
                
                $htmlContent .= '</div>';
            }
        }
        
        $htmlContent .= '</div>';
    }
}

// Generate downloadable HTML as PDF
$filename = sanitizeFilename($project['title']) . '_' . date('Y-m-d');

header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="' . $filename . '.html"');

echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . escapeHtml($project['title']) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            font-family: "Georgia", "Times New Roman", serif;
            line-height: 1.8;
            color: #2c3e50;
            background: white;
            font-size: 12pt;
        }
        .print-header {
            background: linear-gradient(135deg, #c9a87c 0%, #a36d3a 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .print-header h2 {
            margin-bottom: 10px;
            font-size: 18pt;
        }
        .print-header p {
            font-size: 11pt;
            margin: 0;
        }
        .page {
            page-break-after: always;
            padding: 40mm 30mm;
        }
        .title-page {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            page-break-after: always;
        }
        .title-page h1 {
            font-size: 36pt;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .title-page p {
            font-size: 14pt;
            margin-bottom: 20px;
            color: #666;
        }
        .title-page .author {
            margin-top: 100px;
            font-size: 12pt;
            color: #999;
        }
        h1 { font-size: 24pt; margin-bottom: 20px; margin-top: 0; }
        h2 { font-size: 20pt; margin-top: 30px; margin-bottom: 15px; page-break-before: avoid; }
        h3 { font-size: 14pt; margin-top: 20px; margin-bottom: 10px; }
        p { margin-bottom: 12px; text-align: justify; }
        .toc { font-size: 10pt; line-height: 1.6; }
        .toc ol { margin-left: 20px; }
        .toc li { margin-bottom: 6px; }
        .metadata {
            font-size: 10pt;
            color: #999;
            font-style: italic;
            margin-bottom: 15px;
        }
        @media print {
            body { background: white; }
            .print-header { display: none; }
            .page { page-break-after: always; padding: 30mm 25mm; }
            a { color: #2c3e50; text-decoration: none; }
        }
        @media screen {
            body { background: #f0f0f0; }
            .page { background: white; margin: 20px auto; max-width: 8.5in; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <h2>📄 ' . escapeHtml($project['title']) . '</h2>
        <p>Appuyez sur <strong>Ctrl+P</strong> ou utilisez <strong>Fichier → Imprimer</strong> pour générer un PDF</p>
    </div>' . $htmlContent . '
</body>
</html>';
?>

<?php
function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $filename));
}
?>
