<?php
/**
  * Class to browse by platform or vendor
  *
  * @author Jared Howland <book.usage@jaredhowland.com>
  * @version 2013-06-07
  * @since 2013-05-15
  *
  */

class browse {
  /**
    * Performs the browse by vendor
    *
    * @access public
    * @param int vendor_id
    * @return array Usage array for specified vendor
    *
    */
  public function vendor($vendor_id) {
    return $this->get_vendor_usage($vendor_id);
  }

  /**
    * Performs the browse by platform
    *
    * @access public
    * @param int platform_id
    * @return array Usage array for specified platform
    *
    */
  public function platform($platform_id) {
    return $this->get_platform_usage($platform_id);
  }
  
  /**
    * Performs the browse by subject librarian
    *
    * @access public
    * @param int lib_id
    * @return array Usage array for books under fund codes assigned to specified librarian
    *
    */
  public function lib($lib_id) {
    return $this->get_librarian_usage($lib_id);
  }
  
  /**
    * Performs the browse by fund code
    *
    * @access public
    * @param int fund_id
    * @return array Usage array for books under specified fund code
    *
    */
  public function fund($fund_id) {
    return $this->get_fund_usage($fund_id);
  }
  
  /**
    * Performs the browse by call number
    *
    * @access public
    * @param call_num_id
    * @return array Usage array for books in specified call number range
    *
    */
  public function call_num($call_num_id) {
    return $this->get_call_num_usage($call_num_id);
  }
  
  /**
    * Formats the usage from $this->get_vendor_usage() or $this->get_platform_usage
    *
    * @access private
    * @param array Usage data from database query
    * @return array Formatted array for input into Twig template
    *
    */
  private function format_usage($usage) {
    foreach($usage as $key => $result) {
      // Reset variables
      $title         = NULL;
      $author        = NULL;
      $publisher     = NULL;
      $isbn          = NULL;
      $call_num      = NULL;
      $platforms     = NULL;
      $platform_list = NULL;
      $current_br1   = NULL;
      $previous_br1  = NULL;
      $current_br2   = NULL;
      $previous_br2  = NULL;
      $book_id       = $key;
      $title         = $result[0]['title'];
      $author        = $result[0]['author'];
      $publisher     = $result[0]['publisher'];
      $isbn          = $result[0]['isbn'];
      $call_num      = $result[0]['call_num'];
      $platforms     = explode('|', $result[0]['platforms']);
      foreach($platforms as $platform) {
        $platform_list .= '<li>' . $platform . '</li>';
      }
      $current_br1  = $result[0]['current_br1'];
      $previous_br1 = $result[0]['previous_br1'];
      $current_br2  = $result[0]['current_br2'];
      $previous_br2 = $result[0]['previous_br2'];
      if(is_null($current_br1) AND is_null($current_br2) AND is_null($previous_br1) AND is_null($previous_br2)) {
        // Do not add to $usages array if there is no usage in the past 2 years
      } else {
      $usages[] = array('book_id' => $book_id, 'title' => $title, 'author' => $author, 'publisher' => $publisher, 'isbn' => $isbn, 'call_num' => $call_num, 'platforms' => $platform_list, 'latest_br1' => $current_br1, 'previous_br1' => $previous_br1, 'latest_br2' => $current_br2, 'previous_br2' => $previous_br2);
      }
    }
    return array('current_year' => config::$current_year, 'previous_year' => config::$previous_year, 'search_term' => htmlspecialchars($this->term), 'results' => $usages);
  }
  
  /**
    * Retrieves vendor usage from the database for the previous 2 years
    *
    * @access private
    * @param int vendor_id
    * @return array Usage data formatted by $this->format_usage()
    *
    */
  private function get_vendor_usage($vendor_id) {
    // Connect to database
    $database = new db;
    $db       = $database->connect();
    $sql      = "SELECT bv.book_id, b.title, b.author, b.publisher, b.isbn, b.call_num, CAST(GROUP_CONCAT(DISTINCT o.platforms ORDER BY o.platforms SEPARATOR '|') AS CHAR CHARSET UTF8) AS platforms, (SELECT SUM(cbr2.counter_br2) FROM current_br2 cbr2 WHERE cbr2.book_id = b.id) AS current_br2, (SELECT SUM(pbr2.counter_br2) FROM previous_br2 pbr2 WHERE pbr2.book_id = b.id) AS previous_br2, (SELECT SUM(cbr1.counter_br1) FROM current_br1 cbr1 WHERE cbr1.book_id = b.id) AS current_br1, (SELECT SUM(pbr1.counter_br1) FROM previous_br1 pbr1 WHERE pbr1.book_id = b.id) AS previous_br1 FROM books_vendors bv LEFT JOIN books b ON bv.book_id = b.id LEFT JOIN overlap o ON bv.book_id = o.book_id WHERE bv.vendor_id = :vendor_id GROUP BY bv.book_id";
    $query = $db->prepare($sql);
    $query->bindParam(':vendor_id', $vendor_id);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
    $db = NULL;
    return $this->format_usage($results);
  }
  
  /**
    * Retrieves platform usage from the database for the previous 2 years
    *
    * @access private
    * @param int platform_id
    * @return array Usage data formatted by $this->format_usage()
    *
    */
  private function get_platform_usage($platform_id) {
    // Connect to database
    $database = new db;
    $db       = $database->connect();
    $sql      = "SELECT bp.book_id, b.title, b.author, b.publisher, b.isbn, b.call_num, CAST(GROUP_CONCAT(DISTINCT o.platforms ORDER BY o.platforms SEPARATOR '|') AS CHAR CHARSET UTF8) AS platforms, (SELECT SUM(cbr2.counter_br2) FROM current_br2 cbr2 WHERE cbr2.book_id = b.id) AS current_br2, (SELECT SUM(pbr2.counter_br2) FROM previous_br2 pbr2 WHERE pbr2.book_id = b.id) AS previous_br2, (SELECT SUM(cbr1.counter_br1) FROM current_br1 cbr1 WHERE cbr1.book_id = b.id) AS current_br1, (SELECT SUM(pbr1.counter_br1) FROM previous_br1 pbr1 WHERE pbr1.book_id = b.id) AS previous_br1 FROM books_platforms bp LEFT JOIN books b ON bp.book_id = b.id LEFT JOIN overlap o ON bp.book_id = o.book_id WHERE bp.platform_id = :platform_id GROUP BY bp.book_id";
    $query = $db->prepare($sql);
    $query->bindParam(':platform_id', $platform_id);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
    $db = NULL;
    return $this->format_usage($results);
  }
  
  /**
    * Retrieves usage for books assigned to fund codes of specified librarian for the previous 2 years
    *
    * @access private
    * @param int lib_id
    * @return array Usage data formatted by $this->format_usage()
    *
    */
  private function get_librarian_usage($lib_id) {
    $in = $this->get_fund_ids_by_librarian($lib_id);
    // Connect to database
    $database = new db;
    $db       = $database->connect();
    $sql      = "SELECT b.id, b.title, b.author, b.publisher, b.isbn, b.call_num, CAST(GROUP_CONCAT(DISTINCT o.platforms ORDER BY o.platforms SEPARATOR '|') AS CHAR CHARSET UTF8) AS platforms, (SELECT SUM(cbr2.counter_br2) FROM current_br2 cbr2 WHERE cbr2.book_id = b.id) AS current_br2, (SELECT SUM(pbr2.counter_br2) FROM previous_br2 pbr2 WHERE pbr2.book_id = b.id) AS previous_br2, (SELECT SUM(cbr1.counter_br1) FROM current_br1 cbr1 WHERE cbr1.book_id = b.id) AS current_br1, (SELECT SUM(pbr1.counter_br1) FROM previous_br1 pbr1 WHERE pbr1.book_id = b.id) AS previous_br1 FROM books b LEFT JOIN overlap o ON b.id = o.book_id WHERE b.fund_id IN (" . $in . ") GROUP BY b.id";
    $query = $db->prepare($sql);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
    $db = NULL;
    return $this->format_usage($results);
  }
  
  /**
    * Retrieves usage for books assigned to fund code
    *
    * @access private
    * @param int fund_id
    * @return array Usage data formatted by $this->format_usage()
    *
    */
  private function get_fund_usage($fund_id) {
    // Connect to database
    $database = new db;
    $db       = $database->connect();
    $sql      = "SELECT b.id, b.title, b.author, b.publisher, b.isbn, b.call_num, CAST(GROUP_CONCAT(DISTINCT o.platforms ORDER BY o.platforms SEPARATOR '|') AS CHAR CHARSET UTF8) AS platforms, (SELECT SUM(cbr2.counter_br2) FROM current_br2 cbr2 WHERE cbr2.book_id = b.id) AS current_br2, (SELECT SUM(pbr2.counter_br2) FROM previous_br2 pbr2 WHERE pbr2.book_id = b.id) AS previous_br2, (SELECT SUM(cbr1.counter_br1) FROM current_br1 cbr1 WHERE cbr1.book_id = b.id) AS current_br1, (SELECT SUM(pbr1.counter_br1) FROM previous_br1 pbr1 WHERE pbr1.book_id = b.id) AS previous_br1 FROM books b LEFT JOIN overlap o ON b.id = o.book_id WHERE b.fund_id = :fund_id GROUP BY b.id";
    $query = $db->prepare($sql);
    $query->bindParam(':fund_id', $fund_id);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
    $db = NULL;
    return $this->format_usage($results);
  }
  
  /**
    * Retrieves usage for books in specified call number range
    *
    * @access private
    * @param int call_num_id
    * @return array Usage data formatted by $this->format_usage()
    *
    */
  private function get_call_num_usage($call_num_id) {
    $fund_id        = $this->get_fund_ids_by_call_num($call_num_id);
    $call_num_range = $this->get_call_num_range($call_num_id);
    $start_range    = $call_num_range['start_range'];
    $end_range      = $call_num_range['end_range'];
    $adjust_start_range = $this->normalize_call_num($start_range);
    if(is_null($adjust_start_range['class_number'])) {
      $start_range = $start_range . '1';
    }
    $end_range   = $call_num_range['end_range'];
    $adjust_end_range = $this->normalize_call_num($end_range);
    if(is_null($adjust_end_range['class_number'])) {
      $end_range = $end_range . '9999.9999';
    } else if($adjust_end_range['decimal_number'] == '            ') {
      $end_range = $end_range . '.9999';
    }
    // Connect to database
    $database = new db;
    $db       = $database->connect();
    $sql      = "SELECT b.id, b.title, b.author, b.publisher, b.isbn, b.call_num, CAST(GROUP_CONCAT(DISTINCT o.platforms ORDER BY o.platforms SEPARATOR '|') AS CHAR CHARSET UTF8) AS platforms, (SELECT SUM(cbr2.counter_br2) FROM current_br2 cbr2 WHERE cbr2.book_id = b.id) AS current_br2, (SELECT SUM(pbr2.counter_br2) FROM previous_br2 pbr2 WHERE pbr2.book_id = b.id) AS previous_br2, (SELECT SUM(cbr1.counter_br1) FROM current_br1 cbr1 WHERE cbr1.book_id = b.id) AS current_br1, (SELECT SUM(pbr1.counter_br1) FROM previous_br1 pbr1 WHERE pbr1.book_id = b.id) AS previous_br1 FROM books b LEFT JOIN overlap o ON b.id = o.book_id WHERE b.fund_id = :fund_id GROUP BY b.id";
    $query = $db->prepare($sql);
    $query->bindParam(':fund_id', $fund_id);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
    $db = NULL;
    foreach($results as $key => $result) {
      $call_num    = $result[0]['call_num'];
      $array       = array($start_range, $end_range, $call_num);
      $sort        = new sort_lc($array);
      $sorted      = $sort->call_nums();
      // If $call_num falls between the start and end range then return the fund_id
      if($call_num !== $sorted[1]) {
        unset($results[$key]);
      }
    }
    // print_r($usage);
    // print_r($results);die();
    return $this->format_usage($results);
  }
  
  private function normalize_call_num($call_num) {
    //Convert all alpha to uppercase
    $lc_call_no = strtoupper($call_num);

    // define special trimmings that indicate integer
    $integer_markers = array("C.","BD.","DISC","DISK","NO.","PT.","V.","VOL.");
    foreach ($integer_markers as $mark) {
      $mark = str_replace(".", "\.", $mark);
      $lc_call_no = preg_replace("/$mark(\d+)/","$mark$1;",$lc_call_no);
    } // end foreach int marker

    // Remove any inital white space
    $lc_call_no = preg_replace ("/\s*/","",$lc_call_no);

    if (preg_match("/^([A-Z]{1,3})\s*(\d+)\s*\.*(\d*)\s*\.*\s*([A-Z]*)(\d*)\s*([A-Z]*)(\d*)\s*(.*)$/",$lc_call_no,$m)) {
      $initial_letters = $m[1];
      $class_number    = $m[2];
      $decimal_number  = $m[3];
      $cutter_1_letter = $m[4];
      $cutter_1_number = $m[5];
      $cutter_2_letter = $m[6];
      $cutter_2_number = $m[7];
      $the_trimmings   = $m[8];
    } //end if call number match

    if ($class_number) {
      $class_number = sprintf("%5s", $class_number);
    }
    $decimal_number = sprintf("%-12s", $decimal_number);
    return array('initial_letters' => $initial_letters, 'class_number' => $class_number, 'decimal_number' => $decimal_number);
  }
  
  private function get_fund_ids_by_librarian($lib_id) {
    // Connect to database
    $database = new db;
    $db       = $database->connect();
    $sql      = 'SELECT fund_id FROM funds_libs WHERE lib_id = :lib_id';
    $query = $db->prepare($sql);
    $query->bindParam(':lib_id', $lib_id);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    $fund_ids = NULL;
    foreach($results as $result) {
      $fund_ids[] = $result['fund_id'];
    }
    return implode(',', $fund_ids);
  }
  
  private function get_fund_ids_by_call_num($call_num_id) {
    // Connect to database
    $database = new db;
    $db       = $database->connect();
    $sql      = 'SELECT fund_id FROM call_nums WHERE id = :call_num_id LIMIT 1';
    $query = $db->prepare($sql);
    $query->bindParam(':call_num_id', $call_num_id);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    return $results[0]['fund_id'];
  }
  
  private function get_call_num_range($call_num_id) {
    // Connect to database
    $database = new db;
    $db       = $database->connect();
    $sql      = 'SELECT start_range, end_range FROM call_nums WHERE id = :call_num_id LIMIT 1';
    $query = $db->prepare($sql);
    $query->bindParam(':call_num_id', $call_num_id);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    return array('start_range' => $results[0]['start_range'], 'end_range' => $results[0]['end_range']);
  }
}
?>