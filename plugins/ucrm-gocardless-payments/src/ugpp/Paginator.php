<?php
 
class Paginator {
 
  private $_conn;
  private $_limit;
  private $_page;
  private $_query;
  private $_total;


  public function __construct() {
    $this->log = new \Ubnt\UcrmPluginSdk\Service\PluginLogManager();
    $this->api = \Ubnt\UcrmPluginSdk\Service\UcrmApi::create();
       
  } 

  public function getData( $limit = 10, $page = 1 ) {
    $counts_result = $this->api->get('mobile/clients/counts-by-status');
    $this->_total = $counts_result[0]['totalCount'];
       
      $this->_limit   = $limit;
      $this->_page    = $page;
   
      if ( $this->_limit == 'all' ) {
        $results = $this->api->get('clients');
      } else {
        $results = $this->api->get('clients',[
            'limit' => $this->_limit,
            'offset' => ( $this->_page - 1 ) * $this->_limit,
        ]);
      }
   
      $result         = new stdClass();
      $result->page   = $this->_page;
      $result->limit  = $this->_limit;
      $result->total  = $this->_total;
      $result->data   = $results;
   
      return $result;
  }

  public function createLinks( $links, $list_class ) {
      if ( $this->_limit == 'all' ) {
          return '';
      }
   
      $last       = ceil( $this->_total / $this->_limit );
   
      $start      = ( ( $this->_page - $links ) > 0 ) ? $this->_page - $links : 1;
      $end        = ( ( $this->_page + $links ) < $last ) ? $this->_page + $links : $last;
   
      $html       = '<ul class="' . $list_class . '">';
   
      $class      = ( $this->_page == 1 ) ? "disabled" : "";
      $html       .= '<li class="page-item ' . $class . '"><a class="page-link" href="?admin=manage-clients&limit=' . $this->_limit . '&page=' . ( $this->_page - 1 ) . '">&laquo;</a></li>';
   
      if ( $start > 1 ) {
          $html   .= '<li><a class="page-link" href="?admin=manage-clients&limit=' . $this->_limit . '&page=1">1</a></li>';
          $html   .= '<li class="disabled"><span>...</span></li>';
      }
   
      for ( $i = $start ; $i <= $end; $i++ ) {
          $class  = ( $this->_page == $i ) ? "active" : "";
          $html   .= '<li class="page-item ' . $class . '"><a class="page-link" href="?admin=manage-clients&limit=' . $this->_limit . '&page=' . $i . '">' . $i . '</a></li>';
      }
   
      if ( $end < $last ) {
          $html   .= '<li class="disabled"><span>...</span></li>';
          $html   .= '<li><a class="page-link" href="?admin=manage-clients&limit=' . $this->_limit . '&page=' . $last . '">' . $last . '</a></li>';
      }
   
      $class      = ( $this->_page == $last ) ? "disabled" : "";
      $html       .= '<li class="page-item ' . $class . '"><a class="page-link" href="?admin=manage-clients&limit=' . $this->_limit . '&page=' . ( $this->_page + 1 ) . '">&raquo;</a></li>';
   
      $html       .= '</ul>';
   
      return $html;
  }
}