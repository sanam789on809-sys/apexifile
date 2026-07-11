<?php
$dir = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($iterator, '/^.+\.(php|html|po|pot|md|txt)$/i', RecursiveRegexIterator::GET_MATCH);

$count = 0;
foreach ($files as $file) {
    $path = $file[0];
    
    if (
        strpos($path, 'vendor') !== false || 
        strpos($path, 'node_modules') !== false || 
        strpos($path, '.git') !== false ||
        strpos($path, '.system_generated') !== false ||
        basename($path) === 'refactor.php'
    ) {
        continue;
    }
    
    $content = file_get_contents($path);
    if ($content === false) continue;
    $original = $content;
    
    // Replace "ProjectSend" with "CGT" if it's isolated (not part of namespace, class name, or variable)
    $content = preg_replace('/(?<![a-zA-Z0-9_\\\\])ProjectSend(?![a-zA-Z0-9_\\\\])/', 'CGT', $content);
    
    if ($content !== $original) {
        file_put_contents($path, $content);
        $count++;
    }
}
echo "Updated $count files.\n";
