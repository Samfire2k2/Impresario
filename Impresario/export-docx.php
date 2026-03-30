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

// Generate DOCX file without external library
$filename = sanitizeFilename($project['title']) . '_' . date('Y-m-d') . '.docx';

// Create temporary directory for DOCX structure
$tempDir = sys_get_temp_dir() . '/' . uniqid('docx_');
@mkdir($tempDir);

// Create directory structure
@mkdir($tempDir . '/word');
@mkdir($tempDir . '/_rels');
@mkdir($tempDir . '/word/_rels');
@mkdir($tempDir . '/[Content_Types]');

// 1. Create [Content_Types].xml
$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>';

file_put_contents($tempDir . '/[Content_Types].xml', $contentTypes);

// 2. Create .rels file
$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';

file_put_contents($tempDir . '/_rels/.rels', $rels);

// 3. Create word/document.xml with content
$wordContent = generateDocxContent($project, $data);
file_put_contents($tempDir . '/word/document.xml', $wordContent);

// 4. Create word/_rels/document.xml.rels
$docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
</Relationships>';

file_put_contents($tempDir . '/word/_rels/document.xml.rels', $docRels);

// 5. Create ZIP archive (DOCX is a ZIP file)
$zip = new ZipArchive();
$zipPath = $tempDir . '.docx';
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Add [Content_Types].xml first (must be uncompressed and first)
$zip->addFile($tempDir . '/[Content_Types].xml', '[Content_Types].xml');

// Add _rels folder
$zip->addFile($tempDir . '/_rels/.rels', '_rels/.rels');

// Add word folder
$zip->addFile($tempDir . '/word/document.xml', 'word/document.xml');
$zip->addFile($tempDir . '/word/_rels/document.xml.rels', 'word/_rels/document.xml.rels');

$zip->close();

// Send file and cleanup
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);

// Cleanup
@unlink($zipPath);
@unlink($tempDir . '/[Content_Types].xml');
@unlink($tempDir . '/_rels/.rels');
@unlink($tempDir . '/word/document.xml');
@unlink($tempDir . '/word/_rels/document.xml.rels');
@rmdir($tempDir . '/word/_rels');
@rmdir($tempDir . '/word');
@rmdir($tempDir . '/_rels');
@rmdir($tempDir);

exit;

/**
 * Generate DOCX content as XML
 */
function generateDocxContent($project, $data) {
    $content = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" 
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <w:body>';
    
    // Title
    $content .= '<w:p>
      <w:pPr><w:pStyle w:val="Title"/></w:pPr>
      <w:r><w:rPr><w:b/><w:sz w:val="56"/></w:rPr><w:t>' . xmlEscape($project['title']) . '</w:t></w:r>
    </w:p>';
    
    // Subtitle
    if ($project['synopsis'] ?? $project['description']) {
        $subtitle = $project['synopsis'] ?? $project['description'];
        $content .= '<w:p>
          <w:pPr><w:pStyle w:val="Subtitle"/></w:pPr>
          <w:r><w:rPr><w:i/><w:sz w:val="24"/></w:rPr><w:t>' . xmlEscape($subtitle) . '</w:t></w:r>
        </w:p>';
    }
    
    // Table of Contents
    $content .= '<w:p><w:pPr><w:pageBreakBefore/></w:pPr></w:p>';
    $content .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="40"/></w:rPr><w:t>Table des matières</w:t></w:r></w:p>';
    
    if ($data['intrigues']) {
        foreach ($data['intrigues'] as $intrigue) {
            $content .= '<w:p>
              <w:pPr><w:ind w:left="360"/></w:pPr>
              <w:r><w:t>' . xmlEscape($intrigue['title']) . '</w:t></w:r>
            </w:p>';
        }
    }
    
    // Content
    if ($data['intrigues']) {
        foreach ($data['intrigues'] as $intrigueIndex => $intrigue) {
            $content .= '<w:p><w:pPr><w:pageBreakBefore/></w:pPr></w:p>';
            $content .= '<w:p>
              <w:r><w:rPr><w:b/><w:sz w:val="40"/></w:rPr><w:t>' . ($intrigueIndex + 1) . '. ' . xmlEscape($intrigue['title']) . '</w:t></w:r>
            </w:p>';
            
            if ($intrigue['description']) {
                $content .= '<w:p>
                  <w:r><w:rPr><w:i/><w:color w:val="666666"/></w:rPr><w:t>' . xmlEscape($intrigue['description']) . '</w:t></w:r>
                </w:p>';
            }
            
            // Scenes
            if (isset($data['elements'][$intrigue['id']]) && $data['elements'][$intrigue['id']]) {
                foreach ($data['elements'][$intrigue['id']] as $element) {
                    $content .= '<w:p>
                      <w:pPr><w:spacing w:before="200" w:after="100"/></w:pPr>
                      <w:r><w:rPr><w:b/><w:sz w:val="32"/></w:rPr><w:t>' . xmlEscape($element['title']) . '</w:t></w:r>
                    </w:p>';
                    
                    if ($element['pov_character'] || $element['location']) {
                        $meta = [];
                        if ($element['pov_character']) $meta[] = 'POV: ' . $element['pov_character'];
                        if ($element['location']) $meta[] = 'Lieux: ' . $element['location'];
                        $content .= '<w:p>
                          <w:r><w:rPr><w:i/><w:color w:val="999999"/><w:sz w:val="20"/></w:rPr><w:t>' . xmlEscape(implode(' | ', $meta)) . '</w:t></w:r>
                        </w:p>';
                    }
                    
                    if ($element['content']) {
                        // Convert HTML to simple text
                        $text = strip_tags($element['content']);
                        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
                        foreach (array_chunk($words, 50) as $chunk) {
                            $content .= '<w:p>
                              <w:r><w:t>' . xmlEscape(implode(' ', $chunk)) . '</w:t></w:r>
                            </w:p>';
                        }
                    }
                }
            }
        }
    }
    
    $content .= '</w:body></w:document>';
    return $content;
}

/**
 * Escape text for XML
 */
function xmlEscape($text) {
    return htmlspecialchars($text, ENT_XML1, 'UTF-8');
}

function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $filename));
}
?>
?>