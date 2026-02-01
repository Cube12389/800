<?php
// 移动column文件夹中的文件到根目录
$columnDir = 'column';
$files = scandir($columnDir);

foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        $source = $columnDir . '/' . $file;
        $destination = $file;
        
        if (rename($source, $destination)) {
            echo "移动文件 $file 成功<br>";
        } else {
            echo "移动文件 $file 失败<br>";
        }
    }
}

echo "<br>所有文件移动完成！";
?>