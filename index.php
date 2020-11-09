<?php
//http://vk.new-dating.com/car-parser/
include_once('carParser.php');


if (isset($_POST['url'])) {
    new carParser($_POST['url']);
    exit;


}

?>
<style>
    input {
        width: 600px;
    }
</style>


<h1>COPART.com или iaai.com</h1>
<form method="POST">
    <input name="url" value="">
    <input type="submit">
</form>
