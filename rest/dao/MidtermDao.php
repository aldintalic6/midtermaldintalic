<?php

class MidtermDao {

    private $conn;

    /**
    * constructor of dao class
    */
    public function __construct(){
        try {

          $host = "burch-test-db-web-do-user-14103948-0.b.db.ondigitalocean.com";
          $port = 25060;
          $dbname = "midterm-2023-test";
          $user = "doadmin";
          $pass = "AVNS_Nzw-rNS2t2ScuR64P8u";


        /*options array neccessary to enable ssl mode - do not change*/
        $options = array(
        	PDO::MYSQL_ATTR_SSL_CA => 'https://drive.google.com/file/d/1g3sZDXiWK8HcPuRhS0nNeoUlOVSWdMAg/view?usp=share_link',
        	PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,

        );
        
        $this->conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass, $options);

        // set the PDO error mode to exception
          $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          echo "Connected successfully";
        } catch(PDOException $e) {
          echo "Connection failed: " . $e->getMessage();
        }
    }

    /** TODO
    * Implement DAO method used to get cap table
    */
    public function cap_table(){
      $stmt = $this->conn->prepare("
          SELECT
              sc.description AS class,
              JSON_ARRAYAGG(
                  JSON_OBJECT(
                      'category', scc.description,
                      'investors', investors
                  )
              ) AS categories
          FROM
              share_classes AS sc
              INNER JOIN share_class_categories AS scc ON sc.id = scc.share_class_id
              INNER JOIN (
                  SELECT
                      ct.share_class_category_id AS category_id,
                      JSON_ARRAYAGG(
                          JSON_OBJECT(
                              'diluted_shares', ct.diluted_shares,
                              'investor', CONCAT(i.first_name, ' ', i.last_name)
                          )
                      ) AS investors
                  FROM
                      cap_table AS ct
                      INNER JOIN investors AS i ON ct.investor_id = i.id
                  GROUP BY
                      ct.share_class_category_id
              ) AS ct ON scc.id = ct.category_id
          GROUP BY
              sc.id, sc.description;
      ");
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
      // Remove backslashes from JSON string
      $result = array_map(function($row) {
          $row['categories'] = json_decode($row['categories'], true);
          return $row;
      }, $result);
  
      return $result;
  }
  
    /** TODO
    * Implement DAO method used to get summary
    */
    public function summary(){
      $stmt = $this->conn->prepare("SELECT COUNT(DISTINCT investors.id) as total_investors, SUM(cap_table.diluted_shares) as total_shares
                                    FROM investors JOIN cap_table ON 
                                    investors.id = cap_table.investor_id");
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** TODO
    * Implement DAO method to return list of investors with their total shares amount
    */
    public function investors(){
      $stmt = $this->conn->prepare("SELECT investors.first_name as first_name, investors.last_name as last_name, investors.company as company, SUM(cap_table.diluted_shares) as total_shares
                                      FROM investors JOIN cap_table ON 
                                    investors.id = cap_table.investor_id
                                    GROUP BY first_name, last_name, company");
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
