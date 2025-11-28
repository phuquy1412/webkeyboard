<?php

/**
 * Lớp Request: Xử lý các yêu cầu HTTP
 * Giúp lấy đường dẫn, phương thức HTTP, và dữ liệu từ request
 */
class Request
{
    /**
     * Lấy đường dẫn URL (không bao gồm query string)
     * @return string Đường dẫn hiện tại
     */
    public function getPath()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position === false) {
            return $path;
        }
        return substr($path, 0, $position);
    }

    /**
     * Lấy phương thức HTTP dưới dạng chữ thường (get, post, etc.)
     * @return string Phương thức HTTP
     */
    public function method()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Kiểm tra xem request có phải là GET không
     * @return bool
     */
    public function isGet()
    {
        return $this->method() === 'get';
    }

    /**
     * Kiểm tra xem request có phải là POST không
     * @return bool
     */
    public function isPost()
    {
        return $this->method() === 'post';
    }

    /**
     * Lấy dữ liệu từ request (GET hoặc POST)
     * Dữ liệu được lọc và sanitize để bảo mật
     * @return array Dữ liệu đã sanitize
     */
    public function getBody()
    {
        $body = [];
        if ($this->method() === 'get') {
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        if ($this->method() === 'post') {
            foreach ($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        return $body;
    }
}

