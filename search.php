<?php
/**
  * Displays search results
  *
  * @author Jared Howland <book.usage@jaredhowland.com>
  * @version 2013-05-30
  * @since 2013-05-29
  *
  */
require_once 'config.php';

$query = $_GET['q'];
$type  = $_GET['type'];

$search    = new search($query);
$platforms = $search->format_platforms();
$vendors   = $search->format_vendors();

switch ($type) {
  case 'title':
    $results = $search->title();
    $term    = $query;
    $type    = 'title';
    break;
  
  case 'isbn':
    $results = $search->isbn();
    $term    = $query;
    $type    = 'isbn';
    break;
  
  default:
    $results = $search->title();
    $term    = $query;
    $type    = 'title';
    break;
}

$html = array('title' => 'Search Usage', 'heading' => $heading, 'type' => $type, 'term' => $term, 'platforms' => $platforms, 'vendors' => $vendors, 'html' => $results);

template::display('search.tmpl', $html);
?>