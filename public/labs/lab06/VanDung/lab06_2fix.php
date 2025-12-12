<?php
function postIndex($index, $value="")
{
    if (!isset($_POST[$index])) return $value;
    return trim($_POST[$index]);
}

function checkUserName($string)
{
    if (preg_match("/^[a-zA-Z0-9._-]*$/", $string)) 
        return true;
    return false;
}

function checkEmail($string)
{   
    if (preg_match("/^[a-zA-Z0-9._-]+@[a-zA-Z0-9-]+\.[a-zA-Z.]{2,5}$/", $string))
        return true;
    return false; 
}

function checkPassword($string)
{
    if (preg_match("/^.*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/", $string))
        return true;
    return false;
}

function checkPhone($string)
{
    if (preg_match("/^[0-9]+$/", $string))
        return true;
    return false;
}

function checkUserDate($string)
{
    if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])[\/-](0[1-9]|1[0-2])[\/-][0-9]{4}$/", $string))
        return true;
    return false;
}

$sm = postIndex("submit");
$username = postIndex("username");
$password = postIndex("password");
$email = postIndex("email");
$date = postIndex("date");
$phone = postIndex("phone");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Lab6_3 - Validation</title>
<style>
    body { font-family: Arial, sans-serif; }
    fieldset { width: 50%; margin: 50px auto; border: 1px solid #ccc; box-shadow: 2px 2px 5px #ccc; padding: 20px;}
    legend { font-weight: bold; color: #006; padding: 5px; }
    .info { width: 600px; color: red; background: #fff0f0; margin: 20px auto; padding: 10px; border: 1px solid red; }
    .success { width: 600px; color: green; background: #f0fff0; margin: 20px auto; padding: 10px; border: 1px solid green; }
    #frm1 input { width: 300px; padding: 5px; margin: 5px 0; }
    td { padding: 5px; }
</style>
</head>

<body>
<fieldset>
<legend>Đăng ký thông tin</legend>
<form action="" method="post" enctype="multipart/form-data" id='frm1'>
<table align="center">
    <tr>
        <td width="100">UserName</td>
        <td><input type="text" name="username" value="<?php echo htmlspecialchars($username);?>"/> *</td>
    </tr>
    <tr>
        <td>Mật khẩu</td>
        <td><input type="password" name="password" value="<?php echo htmlspecialchars($password);?>"/> *</td>
    </tr>
    <tr>
        <td>Email</td>
        <td><input type="text" name="email" value="<?php echo htmlspecialchars($email);?>"/> *</td>
    </tr>
    <tr>
        <td>Ngày sinh</td>
        <td><input type="text" name="date" value="<?php echo htmlspecialchars($date);?>" placeholder="dd/mm/yyyy" /> *</td>
    </tr>
    <tr>
        <td>Điện thoại</td>
        <td><input type="text" name="phone" value="<?php echo htmlspecialchars($phone);?>" /> *</td>
    </tr>
      
    <tr><td colspan="2" align="center"><input type="submit" value="Đăng ký" name="submit"></td></tr>
</table>
</form>
</fieldset>

<?php
if ($sm != "")
{
    $errors = "";

    if (checkUserName($username) == false) 
        $errors .= "- Username: Các ký tự được phép: a-z, A-Z, số 0-9, ký tự ., _ và - <br>";
    
    if (checkEmail($email) == false) 
        $errors .= "- Email: Định dạng email sai!<br>";

    if (checkPassword($password) == false)
        $errors .= "- Mật khẩu: Phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số.<br>";
    
    if (checkPhone($phone) == false)
        $errors .= "- Điện thoại: Chỉ được nhập số.<br>";
    
    if (checkUserDate($date) == false)
        $errors .= "- Ngày sinh: Sai định dạng (yêu cầu: dd/mm/yyyy hoặc dd-mm-yyyy).<br>";

    if ($errors != "") {
        echo "<div class='info'><b>Có lỗi xảy ra:</b><br />$errors</div>";
    } else {
        echo "<div class='success'><b>Đăng ký thành công!</b><br />";
        echo "Xin chào, $username ($email)</div>";
    }
}
?>
</body>
</html>