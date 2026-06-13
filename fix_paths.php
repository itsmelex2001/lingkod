<?php
// Script to replace /sdn/ with / in all relevant files

$dir = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($dir);

$extensions = ['php', 'html', 'js', 'css', 'svelte'];
$count = 0;

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
        if (in_array($ext, $extensions)) {
            $content = file_get_contents($file->getPathname());
            
            // Replace /sdn/ with / 
            $newContent = str_replace('/sdn/', '/', $content);
            
            // Fix unquoted CSS urls
            $newContent = str_replace('url(/', 'url(/', $newContent);
            
            if ($content !== $newContent) {
                file_put_contents($file->getPathname(), $newContent);
                echo "Updated: " . $file->getPathname() . "<br>\n";
                $count++;
            }
        }
    }
}

echo "<br><b>Done! Fixed paths in $count files.</b><br>";
echo "You can now upload your files to InfinityFree.";
?>