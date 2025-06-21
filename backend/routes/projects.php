<?php
Flight::route('/api/projects(/@id)', function($id = null) {
    $db = Flight::db();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET' && $id === null) {
        $offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT) ?? 0;
        $limit  = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?? 10;

        try {
            $stmt = $db->prepare('SELECT * FROM projects ORDER BY id DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'projects' => $projects]);
        } catch (Exception $e) {
            Flight::halt(500, json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
        return;
    }

    if ($method === 'POST' && isset($_POST['_method']) && strtoupper($_POST['_method']) === 'PUT' && $id !== null) {
        $method = 'PUT';
    }

    if ($method === 'POST' && $id === null) {
        handleCreateOrUpdate($db, null, $_POST, $_FILES);
        return;
    }

    if ($method === 'PUT' && $id !== null) {
        handleCreateOrUpdate($db, $id, $_POST, $_FILES);
        return;
    }

    if ($method === 'DELETE' && $id !== null) {
        try {
            $stmt = $db->prepare('SELECT title FROM projects WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$project) {
                Flight::halt(404, json_encode(['success' => false, 'message' => 'Project not found']));
            }

            $titleSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($project['title']));
            $uploadDir = __DIR__ . "/../public/projects/$titleSlug";

            if (!deleteDirectory($uploadDir)) {
                Flight::halt(500, json_encode(['success' => false, 'message' => 'Failed to delete images directory']));
            }

            $stmt = $db->prepare('DELETE FROM projects WHERE id = :id');
            $stmt->execute([':id' => $id]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            Flight::halt(500, json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
        return;
    }

    Flight::halt(405, json_encode(['success' => false, 'message' => 'Method Not Allowed']));
});

function handleCreateOrUpdate(PDO $db, ?int $id, array $postData, array $files) {
    $title = trim($postData['title'] ?? '');
    $link = trim($postData['link'] ?? '');
    $textRu = trim($postData['text_ru'] ?? '');
    $textEn = trim($postData['text_en'] ?? '');

    if (!$title || !$link || !$textRu || !$textEn) {
        Flight::halt(400, json_encode(['success' => false, 'message' => 'Missing required fields']));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        parse_str(file_get_contents("php://input"), $postData);
    }

    $titleSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
    $uploadDir = __DIR__ . "/../public/projects/$titleSlug";
    $webPath = "/projects/$titleSlug";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadedPaths = [];

    if (!empty($files['images']['tmp_name'])) {
        foreach ($files['images']['tmp_name'] as $index => $tmpName) {
            if (is_uploaded_file($tmpName)) {
                $originalName = $files['images']['name'][$index];
                $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                $safeName = uniqid() . '.' . $ext;
                $targetPath = "$uploadDir/$safeName";
                $webImagePath = "$webPath/$safeName";

                if (compressImage($tmpName, $targetPath, 1920, 1080, 75)) {
                    $uploadedPaths[] = $webImagePath;
                }
            }
        }
    }

    if ($id === null) {
        try {
            $imgs = implode(',', $uploadedPaths);
            $stmt = $db->prepare('INSERT INTO projects (title, link, text_ru, text_en, imgs) VALUES (:title, :link, :text_ru, :text_en, :imgs)');
            $stmt->execute([
                ':title' => $title,
                ':link' => $link,
                ':text_ru' => $textRu,
                ':text_en' => $textEn,
                ':imgs' => $imgs,
            ]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            Flight::halt(500, json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    } else {
        try {
            $stmt = $db->prepare('SELECT title, imgs FROM projects WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$project) {
                Flight::halt(404, json_encode(['success' => false, 'message' => 'Project not found']));
            }

            $oldSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($project['title']));
            $oldPath = __DIR__ . "/../public/projects/$oldSlug";
            $newPath = $uploadDir;

            if ($oldSlug !== $titleSlug && is_dir($oldPath)) {
                clearstatcache(true, $oldPath);
                clearstatcache(true, $newPath);
            
                if (is_dir($newPath)) {
                    deleteDirectory($newPath);
                }
            
                if (!is_writable(dirname($oldPath))) {
                    Flight::halt(500, json_encode(['success' => false, 'message' => "No write permission in " . dirname($oldPath)]));
                }
            
                $renameResult = @rename($oldPath, $newPath);
                if (!$renameResult) {
                    $error = error_get_last();
                    Flight::halt(500, json_encode([
                        'success' => false,
                        'message' => "Failed to rename directory: " . ($error['message'] ?? 'unknown error')
                    ]));
                }
            }            

            $imgs = [];
            $newImagesExist = false;
            if (isset($files['images']) && is_array($files['images']['tmp_name'])) {
                foreach ($files['images']['tmp_name'] as $tmpName) {
                    if (is_uploaded_file($tmpName)) {
                        $newImagesExist = true;
                        break;
                    }
                }
            }

            if ($newImagesExist) {
                $oldImgs = array_filter(explode(',', $project['imgs']));
                foreach ($oldImgs as $imgPath) {
                    $fullPath = __DIR__ . '/../public' . $imgPath;
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                }
                $imgs = $uploadedPaths;
            } else {
                $imgs = array_filter(explode(',', $project['imgs']));
                $imgs = array_map(function($imgPath) use ($oldSlug, $titleSlug) {
                    return str_replace("/projects/$oldSlug", "/projects/$titleSlug", $imgPath);
                }, $imgs);
            }

            $imgsStr = implode(',', $imgs);

            $stmt = $db->prepare('UPDATE projects SET title = :title, link = :link, text_ru = :text_ru, text_en = :text_en, imgs = :imgs WHERE id = :id');
            $stmt->execute([
                ':title' => $title,
                ':link' => $link,
                ':text_ru' => $textRu,
                ':text_en' => $textEn,
                ':imgs' => $imgsStr,
                ':id' => $id,
            ]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            Flight::halt(500, json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }
}

function compressImage($source, $destination, $maxWidth, $maxHeight, $quality) {
    list($width, $height, $type) = getimagesize($source);

    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $img = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $img = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $img = imagecreatefromgif($source);
            break;
        default:
            return false;
    }

    $tmp = imagecreatetruecolor($newWidth, $newHeight);

    if ($type == IMAGETYPE_PNG) {
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);
    }

    imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    if ($type == IMAGETYPE_JPEG) {
        $result = imagejpeg($tmp, $destination, $quality);
    } elseif ($type == IMAGETYPE_PNG) {
        $result = imagepng($tmp, $destination);
    } elseif ($type == IMAGETYPE_GIF) {
        $result = imagegif($tmp, $destination);
    } else {
        $result = false;
    }

    imagedestroy($img);
    imagedestroy($tmp);

    return $result;
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) return true;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = "$dir/$file";
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    return rmdir($dir);
}
