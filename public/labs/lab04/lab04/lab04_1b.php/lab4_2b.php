<pre><?php
$a = array(1, -3, 5); 
$b = array("a"=>2, "b"=>4, "c"=>-6);
?>
Nội dung giá trị mảng a :
<?php

print_r($a);
$dem =0;
$b = array();
$length = count($a);
for($i=0;$i<$length;$i++)
{
    if($a[$i]>0)
    {
        $dem++;
        $b[] = $a[$i];
    }
}
echo "So duong cua mang la: $dem <br>";
echo "Mang gia tri duong la: "; print_r($b);
?>