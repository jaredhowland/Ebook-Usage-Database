<?php
/**
  * Class to search OCLC Classify Web Service
  * http://classify.oclc.org/classify2/api_docs/classify.html
  *
  * @author Jared Howland <book.usage@jaredhowland.com>
  * @version 2013-06-08
  * @since 2013-04-30
  *
  */

class classify {
  private $type;
  private $terms;
  private $base_url;

  /**
   * Passes the content into the specified Twig template
   *
   * @access public
   * @param string Search type to perform; Valid values: standard_number, title; Default: standard_number
   * @param string|array String or array of search term(s)
   * @return array Array of title, author, isbn, call_num for all search terms
   */
  public function search($type, $terms) {
    $this->set_type($type);
    $this->set_terms($terms);
    $this->set_base_url();
    return $this->get_results();
  }

  /**
    * Determine and set the type of search to run on Classify service
    *
    * @access private
    * @param string title or standard_number are only acceptable values
    * @return NULL
    *
    */
  private function set_type($type) {
    if($type == 'title') {
      $this->type = 'title';
    } else {
      $this->type = 'standard_number';
    }
  }
  
  /**
    * Retrieve $type variable
    *
    * @access private
    * @param NULL
    * @return string title or standard_number
    *
    */
  private function get_type() {
    return $this->type;
  }
  
  /**
    * Set the query string—should always be array so set it as array if it is not one
    *
    * @access private
    * @param string Search term(s) passed to class
    * @return NULL
    *
    */
  private function set_terms($terms) {
    if(is_array($terms)) {
      $this->terms = $terms;
    } else {
      $this->terms = array($terms);
    }
  }
  
  /**
    * Set the $terms variable
    *
    * @access private
    * @param NULL
    * @return string Search term(s) passed to class
    *
    */
  private function get_terms() {
    return $this->terms;
  }
  
  /**
    * Sets base URL depending on type of search being performed
    *
    * @access private
    * @param NULL
    * @return NULL
    *
    */
  private function set_base_url() {
    if($this->get_type() == 'title') {
      $this->base_url = 'http://classify.oclc.org/classify2/Classify?title=';
    } else {
      $this->base_url = 'http://classify.oclc.org/classify2/Classify?stdnbr=';
    }
  }
  
  /**
    * Retrieves the $base_url variable
    *
    * @access private
    * @param NULL
    * @return string Base URL for classify service depending on type of search being performed
    *
    */
  private function get_base_url() {
    return $this->base_url;
  }
  
  /**
    * Loop through $terms array to perform search on Classify service
    *
    * @access private
    * @param NULL
    * @return array Response from Classify service (Title, Author, OCLC Number, Call Number, Response Code)
    *
    */
  private function get_results() {
    $titles = array();
    foreach($this->get_terms() as $term) {
      $url           = urlencode($this->get_base_url() . $term . '&orderBy=mancount desc');
      $file          = simplexml_load_file($url);
      $response_code = (string) $file->response->attributes()->code;
      // 0: Success. Single-work summary response provided.
      // 2: Success. Single-work detail response provided.
      // 4: Success. Multi-work response provided.
      // 100: No input. The method requires an input argument.
      // 101: Invalid input. The standard number argument is invalid.
      // 102: Not found. No data found for the input argument.
      // 200: Unexpected error.
      if($response_code == '0' || $response_code == '2') {
        $metadata = $file->editions->edition[0]->attributes();
        $title    = (string) $metadata->title;
        $author   = (string) $metadata->author;
        $oclc     = (string) $metadata->oclc;
        // Handle missing LCC Call Numbers
        if($file->recommendations->lcc) {
          $call_num = (string) $file->recommendations->lcc->mostPopular->attributes()->nsfa;
        } else {
          $call_num = NULL;
        }
        $titles[] = array('title' => $title, 'author' => $author, 'oclc' => $oclc, 'call_num' => $call_num, 'response_code' => $response_code);
      } else {
        $titles[] = array('title' => null, 'author' => null, 'oclc' => null, 'call_num' => null, 'response_code' => $response_code);
      }
    }
    return $titles;
  }
  
}
?>
