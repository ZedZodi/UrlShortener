
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Форма отправки</title>
    <link href="style.css" rel="stylesheet">
</head>
<form class="decor" action="post.php" method="post">
    <div class="form-left-decoration"></div>
    <div class="form-right-decoration"></div>
    <div class="circle"></div>
    <div class="form-inner">
        <h3>Ваша короткая ссылка</h3>
        <h4>
            <?php
            $url_long = htmlspecialchars($_POST['url_long']);//получаем от формы длинный url
            require_once 'DataBase.php';//подключаем класс с объектами для получения короткой ссылки
            require_once 'config.php';//подключаем конфиг с данными для подключения к mysql

            $db = new DataBase($username, $password, $host, $dbname);

            $short_url = $db->shortURL($url_long);//получаем короткую ссылку

            echo $short_url;
            ?>
        </h4>
        <a href="index.html">вернуться назад</a>
    </div>
</body>
</html>
