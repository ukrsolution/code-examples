<?php

class ImportUsers
{

    private $PDO = null;

    public function __construct()
    {

        try {

            // init databse connection
            $this->PDO = new PDO("mysql:host=localhost;dbname=csv_data", "root", "123", array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        } catch (Exception $e) {

            print_r($e);
        }
    }

    // Load users' data from CSV file
    public function FromCSV($file_name)
    {

        $file = fopen($file_name, 'r');
        // Reading line by line  - a little bit slower but doesn't require a lot of memory.
        while (($line = fgetcsv($file)) !== false) {

            // Insert user into DB
            $this->InsertUser($line);
        }
        fclose($file);
    }

    // Inserting user into database
    private function InsertUser($user)
    {

        try {

            // Insert into "Users" table
            $statement = $this->PDO->prepare("INSERT INTO `users` (`first_name`, `last_name`) VALUES (?, ?);");
            $statement->bindParam(1, $user[0], PDO::PARAM_STR);
            $statement->bindParam(2, $user[1], PDO::PARAM_STR);
            $statement->execute();

            // Geting user ID
            $user_id = $this->PDO->lastInsertId();

            // Inserting additional user's data into "users_data" table
            $statement = $this->PDO->prepare("INSERT INTO `users_data` (`user_id`, `email`, `address`) VALUES (?, ?, ?);");
            $statement->bindParam(1, $user_id, PDO::PARAM_INT);
            $statement->bindParam(2, $user[4], PDO::PARAM_STR);
            $statement->bindParam(3, $user[9], PDO::PARAM_STR);
            $statement->execute();
        } catch (Exception $e) {

            print_r($e);
        }
    }
}

$ImportUsers = new ImportUsers();
$ImportUsers->FromCSV('contacts.csv');
