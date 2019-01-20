<?php

    class DataBase {
        public $isConn;
        protected $datab;
        protected static $table = "shorturl_table";
        protected $timestamp;
        protected static $chars = "123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ";

        // подключение к бд
        public function __construct($username = "root", $password = "", $host = "localhost", $dbname = "shorturl_DB", $options = [])
        {
            $this->isConn = TRUE;
            try {
                $this->datab = new PDO("mysql:host=$host;charset=utf8", $username, $password, $options);

                //осуществление запроса на создание бд, если ее нет
                $this->datab->exec("CREATE DATABASE IF NOT EXISTS `$dbname`;
                    CREATE USER '$username'@'localhost' IDENTIFIED BY '$password';
                    GRANT ALL ON `$dbname`.* TO '$username'@'localhost';
                    FLUSH PRIVILEGES;")
                or die(print_r($this->datab->errorInfo(), true));

                $this->datab->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->datab->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                $this->datab = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

                // sql-запрос на создание таблицы, если ее не было
                $sql = "CREATE TABLE IF NOT EXISTS ".self::$table." (
                  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
                  long_url VARCHAR(255) NOT NULL,
                  short_code VARCHAR(6) NOT NULL,
                  PRIMARY KEY (id),
                  KEY short_code (short_code) )";

                // осуществление запроса
                $this->datab->exec($sql);
            } catch (PDOException $e) {
                throw new Exception($e->getMessage());
            }
        }

        //отключение от бд
        public function Disconnect()
        {
            $this->datab = NULL;
            $this-> isConn = FALSE;
        }

        //получение короткой ссылки
        public function shortURL($url_long)
        {
            if (empty($url_long))
            {
                echo ("Не получен адрес URL.");
            }
            elseif ($this->validateUrlFormat($url_long) == false)
            {
                echo ("Адрес URL имеет неправильный формат.");
            }
            else
            {
                $shortCode = $this->urlExistsInDb($url_long);
                if ($shortCode == false)
                {
                    $shortCode = $this->createShortCode($url_long);
                }
                return "http://".$_SERVER['HTTP_HOST'].'/'.$shortCode;
            }


        }

        //проверка на правильность ввода url
        protected function validateUrlFormat($url_long)
        {
            return filter_var($url_long, FILTER_VALIDATE_URL,FILTER_FLAG_HOST_REQUIRED);
        }

        //проверка на то, есть url уже в таблице
        protected function urlExistsInDb($url_long)
        {
            $query = "SELECT * FROM shorturl_table WHERE long_url = '$url_long'";
            $stmt = $this->datab->prepare($query);
            $stmt -> execute($params);
            $result = $stmt->fetch();
            return (empty($result)) ? false : $result["short_code"];
        }

        //получаем короткую ссылку
        protected function createShortCode($url_long)
        {
            $id = $this->insertUrlInDb($url_long);
            $shortCode = $this->convertIntToShortCode($id);
            $this->insertShortCodeInDb($id, $shortCode);
            return $shortCode;
        }

        //вставляем урл в таблицу
        protected function insertUrlInDb($url_long)
        {
            $query = "INSERT INTO " . self::$table .
                " (long_url) " .
                " VALUES (:long_url)";
            $stmnt = $this->datab->prepare($query);
            $params = array(
                "long_url" => $url_long,
            );
            $stmnt->execute($params);

            return $this->datab->lastInsertId();
        }

        //создание короткой ссылки
        protected function convertIntToShortCode($id)
        {
            $id = intval($id);
            if ($id < 1)
            {
                throw new \Exception("ID не является некорректным целым числом.");
            }

            $letters = 'qwertyuiopasdfghjklzxcvbnm1234567890';//ключ для создания короткой ссылки
            $count = strlen($letters);
            $intval = time();//для уникальности каждого короткого кода
            for ($i = 0; $i < 4; $i++)
            {
                $last = $intval%$count;
                $intval = ($intval-$last)/$count;
                $code.=$letters[$last];
            }

            return $code;
        }

        //вставляем короткую ссылку в таблицу
        protected function insertShortCodeInDb($id, $code)
        {
            if ($id == null || $code == null) {
                throw new \Exception("Параметры ввода неправильные.");
            }
            $query = "UPDATE " . self::$table .
                " SET short_code = :short_code WHERE id = :id";
            $stmnt = $this->datab->prepare($query);
            $params = array(
                "short_code" => $code,
                "id" => $id
            );
            $stmnt->execute($params);

            if ($stmnt->rowCount() < 1)
            {
                throw new \Exception("Строка не обновляется коротким кодом.");

            }

            return true;
        }
    }
?>