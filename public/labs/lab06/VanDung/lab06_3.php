<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lab 6_3 - Web Scraping</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { width: 800px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        .debug { background: #333; color: #0f0; padding: 10px; overflow: auto; height: 150px; margin-bottom: 20px; font-size: 12px; }
        .warning { color: red; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2>Lab 6_3: Lấy tin tức từ VnExpress Thể Thao</h2>

    <?php
    if (!ini_get('allow_url_fopen')) {
        echo "<p class='warning'>Cảnh báo: 'allow_url_fopen' đang tắt trong php.ini. Bạn cần bật nó lên để script hoạt động.</p>";
    }

    $url = "https://vnexpress.net/the-thao"; 

    $options = array(
        'http' => array(
            'method' => "GET",
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36\r\n"
        )
    );
    $context = stream_context_create($options);

    $content = @file_get_contents($url, false, $context);

    if ($content === FALSE) {
        echo "<p class='warning'>Không thể lấy dữ liệu từ URL: $url. Vui lòng kiểm tra kết nối internet.</p>";
    } else {
        echo "<p>Đã kết nối thành công tới: <b>$url</b></p>";

        $pattern = '/<h3 class="title-news">.*?<a[^>]*href="([^"]+)"[^>]*title="([^"]+)"/ims';        
        preg_match_all($pattern, $content, $matches);

        echo "<b>Dữ liệu thô (print_r):</b>";
        echo "<div class='debug'><pre>";
        print_r($matches);
        echo "</pre></div>";

        ?>
        <h3>Danh sách tin mới nhất</h3>
        <table>
            <tr>
                <th width="5%">STT</th>
                <th width="65%">Tiêu đề tin</th>
                <th width="30%">Link gốc</th>
            </tr>
            <?php
            if (isset($matches[1]) && count($matches[1]) > 0) {
                $stt = 0;
                
                for ($i = 0; $i < count($matches[1]); $i++) {
                    $link = trim($matches[1][$i]);
                    $title = trim(strip_tags($matches[2][$i])); 

                    if (strpos($title, 'articleData') !== false || $title == "" || strpos($title, 'living_icon') !== false) {
                        continue; 
                    }

                    $stt++;
                    
                    echo "<tr>";
                    echo "<td align='center'>" . $stt . "</td>";
                    echo "<td><strong>$title</strong></td>";
                    echo "<td><a href='$link' target='_blank'>Xem bài viết</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3' align='center'>Không tìm thấy tin nào. Cấu trúc web có thể đã thay đổi.</td></tr>";
            }
            ?>
        </table>
        <?php
    }
    ?>
</div>

</body>
</html>